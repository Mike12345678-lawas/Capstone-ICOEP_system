<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentReturnTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $borrower;
    protected User $otherUser;
    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            DepartmentSeeder::class,
        ]);

        $adminRole = Role::where('name', 'Administrator')->first();
        $studentRole = Role::where('name', 'Student')->first();
        $dept = Department::first();

        $this->admin = User::create([
            'first_name'    => 'Admin',
            'last_name'     => 'Staff',
            'email'         => 'admin@icoep.test',
            'password'      => bcrypt('password'),
            'role_id'       => $adminRole->id,
            'department_id' => $dept->id,
            'employee_id'   => 'ADM-001',
            'status'        => 'active',
        ]);

        $this->borrower = User::create([
            'first_name'    => 'Jane',
            'last_name'     => 'Borrower',
            'email'         => 'jane@icoep.test',
            'password'      => bcrypt('password'),
            'role_id'       => $studentRole->id,
            'department_id' => $dept->id,
            'employee_id'   => 'STU-001',
            'status'        => 'active',
        ]);

        $this->otherUser = User::create([
            'first_name'    => 'Bob',
            'last_name'     => 'Other',
            'email'         => 'bob@icoep.test',
            'password'      => bcrypt('password'),
            'role_id'       => $studentRole->id,
            'department_id' => $dept->id,
            'employee_id'   => 'STU-002',
            'status'        => 'active',
        ]);

        $this->equipment = Equipment::create([
            'name'               => 'Sony 4K Camera',
            'category'           => 'Electronics',
            'description'        => 'High-definition digital camera',
            'quantity'           => 5,
            'available_quantity' => 3,
            'status'             => 'available',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 1. Borrower can send a return request only for their own borrowed item
     */
    public function test_borrower_can_send_return_request_only_for_their_own_borrowed_item(): void
    {
        Carbon::setTestNow('2026-10-01 10:00:00');

        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 2,
            'purpose'              => 'Media project',
            'date_needed'          => '2026-10-01',
            'expected_return_date' => '2026-10-03',
            'status'               => 'borrowed',
        ]);

        $this->actingAs($this->borrower);
        \Illuminate\Support\Facades\Storage::fake('public');
        $photo = \Illuminate\Http\UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg');
        $response = $this->post("/equipment-requests/{$borrowedReq->id}/return-request", [
            'return_photo' => $photo,
        ]);

        $response->assertSessionHas('success');
        $borrowedReq->refresh();
        $this->assertNotNull($borrowedReq->return_requested_at);
        $this->assertEquals('2026-10-01 10:00:00', $borrowedReq->return_requested_at->format('Y-m-d H:i:s'));
        $this->assertEquals('borrowed', $borrowedReq->status);

        // Audit log must exist
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'RETURN_REQUESTED',
            'table_name' => 'equipment_requests',
            'record_id'  => $borrowedReq->id,
            'user_id'    => $this->borrower->id,
        ]);

        // Cannot send return request if status is not 'borrowed' (e.g. pending)
        $pendingReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 1,
            'purpose'              => 'Lecture',
            'date_needed'          => '2026-10-05',
            'expected_return_date' => '2026-10-06',
            'status'               => 'pending',
        ]);

        $resPending = $this->post("/equipment-requests/{$pendingReq->id}/return-request");
        $resPending->assertSessionHas('error');
        $this->assertNull($pendingReq->fresh()->return_requested_at);
    }

    /**
     * 2. Another user cannot send or cancel return request
     */
    public function test_another_user_cannot_send_or_cancel_return_request(): void
    {
        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 1,
            'purpose'              => 'Class project',
            'date_needed'          => '2026-10-01',
            'expected_return_date' => '2026-10-03',
            'status'               => 'borrowed',
            'return_requested_at'  => now(),
        ]);

        // Another user tries to send return request
        $this->actingAs($this->otherUser);
        $resSend = $this->post("/equipment-requests/{$borrowedReq->id}/return-request");
        $resSend->assertStatus(403);

        // Another user tries to cancel return request
        $resCancel = $this->post("/equipment-requests/{$borrowedReq->id}/cancel-return-request");
        $resCancel->assertStatus(403);

        // Owner CAN cancel their return request
        $this->actingAs($this->borrower);
        $resOwnerCancel = $this->post("/equipment-requests/{$borrowedReq->id}/cancel-return-request");
        $resOwnerCancel->assertSessionHas('success');
        $this->assertNull($borrowedReq->fresh()->return_requested_at);
    }

    /**
     * 3. Return request does not change status or stock
     */
    public function test_return_request_does_not_change_status_or_stock(): void
    {
        Carbon::setTestNow('2026-10-01 12:00:00');

        $initialAvailable = $this->equipment->available_quantity;

        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 2,
            'purpose'              => 'Class recording',
            'date_needed'          => '2026-10-01',
            'expected_return_date' => '2026-10-02',
            'status'               => 'borrowed',
        ]);

        $this->actingAs($this->borrower);
        \Illuminate\Support\Facades\Storage::fake('public');
        $photo = \Illuminate\Http\UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg');
        $this->post("/equipment-requests/{$borrowedReq->id}/return-request", [
            'return_photo' => $photo,
        ]);

        $borrowedReq->refresh();
        $this->assertEquals('borrowed', $borrowedReq->status);
        $this->assertEquals($initialAvailable, $this->equipment->fresh()->available_quantity);
    }

    /**
     * 4. Admin confirms a full return: status returned, stock restored, confirmed_by and returned_at set
     */
    public function test_admin_confirms_full_return_status_returned_stock_restored_confirmed_by_and_returned_at_set(): void
    {
        Carbon::setTestNow('2026-10-02 14:00:00');

        $initialStock = $this->equipment->available_quantity; // 3

        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 2,
            'purpose'              => 'Field work',
            'date_needed'          => '2026-10-01',
            'expected_return_date' => '2026-10-03',
            'status'               => 'borrowed',
            'return_requested_at'  => now()->subHour(),
        ]);

        $this->actingAs($this->admin);
        $response = $this->post("/equipment-requests/{$borrowedReq->id}/confirm-return", [
            'condition'    => 'good',
            'return_type'  => 'full',
            'return_notes' => 'Tested all functions, all good.',
        ]);

        $response->assertRedirect('/equipment/returns');
        $response->assertSessionHas('success');

        $borrowedReq->refresh();
        $this->assertEquals('returned', $borrowedReq->status);
        $this->assertEquals(2, $borrowedReq->returned_quantity);
        $this->assertEquals($this->admin->id, $borrowedReq->confirmed_by);
        $this->assertNotNull($borrowedReq->returned_at);
        $this->assertEquals('2026-10-02 14:00:00', $borrowedReq->returned_at->format('Y-m-d H:i:s'));
        $this->assertNull($borrowedReq->return_requested_at);
        $this->assertEquals('good', $borrowedReq->return_condition);
        $this->assertEquals('Tested all functions, all good.', $borrowedReq->return_notes);

        // Stock restored by 2 units: 3 + 2 = 5
        $this->assertEquals($initialStock + 2, $this->equipment->fresh()->available_quantity);

        // Borrower notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->borrower->id,
            'title'   => 'Equipment Return Confirmed',
        ]);
    }

    /**
     * 5. Confirming twice does not restore stock twice
     */
    public function test_confirming_twice_does_not_restore_stock_twice(): void
    {
        Carbon::setTestNow('2026-10-02 14:00:00');

        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 2,
            'purpose'              => 'Project',
            'date_needed'          => '2026-10-01',
            'expected_return_date' => '2026-10-03',
            'status'               => 'borrowed',
        ]);

        $this->actingAs($this->admin);

        // First confirmation
        $this->post("/equipment-requests/{$borrowedReq->id}/confirm-return", [
            'condition'   => 'good',
            'return_type' => 'full',
        ]);

        $stockAfterFirst = $this->equipment->fresh()->available_quantity;

        // Second confirmation (simulating duplicate tap / resubmission)
        $resSecond = $this->post("/equipment-requests/{$borrowedReq->id}/confirm-return", [
            'condition'   => 'good',
            'return_type' => 'full',
        ]);

        $stockAfterSecond = $this->equipment->fresh()->available_quantity;
        $this->assertEquals($stockAfterFirst, $stockAfterSecond, 'Stock must not be double incremented.');
    }

    /**
     * 6. Partial return restores only the returned units and keeps status borrowed
     */
    public function test_partial_return_restores_only_the_returned_units_and_keeps_status_borrowed(): void
    {
        Carbon::setTestNow('2026-10-02 10:00:00');

        $initialStock = $this->equipment->available_quantity; // 3

        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 3,
            'purpose'              => 'Multi-camera shoot',
            'date_needed'          => '2026-10-01',
            'expected_return_date' => '2026-10-05',
            'status'               => 'borrowed',
        ]);

        $this->actingAs($this->admin);

        // Partial return of 1 unit
        $response = $this->post("/equipment-requests/{$borrowedReq->id}/confirm-return", [
            'condition'         => 'good',
            'return_type'       => 'partial',
            'quantity_returned' => 1,
            'return_notes'      => 'Returned 1 unit, keeping 2 until tomorrow',
        ]);

        $response->assertRedirect('/equipment/returns');

        $borrowedReq->refresh();
        $this->assertEquals('borrowed', $borrowedReq->status, 'Status must remain borrowed after partial return.');
        $this->assertEquals(1, $borrowedReq->returned_quantity);
        $this->assertNull($borrowedReq->returned_at);

        // Stock incremented by exactly 1 unit
        $this->assertEquals($initialStock + 1, $this->equipment->fresh()->available_quantity);

        // Borrower notified about partial return
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->borrower->id,
            'title'   => 'Partial Return Recorded',
        ]);
    }

    /**
     * 7. The last partial return sets status returned
     */
    public function test_the_last_partial_return_sets_status_returned(): void
    {
        Carbon::setTestNow('2026-10-02 10:00:00');

        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 3,
            'returned_quantity'    => 2, // 2 units already confirmed previously
            'purpose'              => 'Shoot',
            'date_needed'          => '2026-10-01',
            'expected_return_date' => '2026-10-05',
            'status'               => 'borrowed',
        ]);

        $this->actingAs($this->admin);

        // Return the final 1 unit
        $this->post("/equipment-requests/{$borrowedReq->id}/confirm-return", [
            'condition'         => 'good',
            'return_type'       => 'partial',
            'quantity_returned' => 1,
        ]);

        $borrowedReq->refresh();
        $this->assertEquals('returned', $borrowedReq->status);
        $this->assertEquals(3, $borrowedReq->returned_quantity);
        $this->assertNotNull($borrowedReq->returned_at);
        $this->assertEquals($this->admin->id, $borrowedReq->confirmed_by);
    }

    /**
     * 8. Damaged or missing condition does not make those units available again
     */
    public function test_damaged_or_missing_condition_does_not_make_those_units_available_again(): void
    {
        Carbon::setTestNow('2026-10-02 11:00:00');

        $initialStock = $this->equipment->available_quantity; // 3

        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 2,
            'purpose'              => 'Field shoot',
            'date_needed'          => '2026-10-01',
            'expected_return_date' => '2026-10-04',
            'status'               => 'borrowed',
        ]);

        $this->actingAs($this->admin);

        // Return as damaged
        $this->post("/equipment-requests/{$borrowedReq->id}/confirm-return", [
            'condition'    => 'damaged',
            'return_type'  => 'full',
            'return_notes' => 'Cracked lens element.',
        ]);

        $borrowedReq->refresh();
        $this->equipment->refresh();

        $this->assertEquals('returned', $borrowedReq->status);
        $this->assertEquals('damaged', $borrowedReq->return_condition);

        // Stock must NOT have been restored
        $this->assertEquals($initialStock, $this->equipment->available_quantity, 'Damaged units must not be restored to available quantity.');

        // Equipment status must be set to maintenance
        $this->assertEquals('maintenance', $this->equipment->status);

        // Admins notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'title'   => 'Equipment Damaged/Missing Alert',
        ]);
    }

    /**
     * 9. Late return sets the correct days_late
     */
    public function test_late_return_sets_the_correct_days_late(): void
    {
        // Expected return was 2026-10-01
        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 1,
            'purpose'              => 'Project',
            'date_needed'          => '2026-09-28',
            'expected_return_date' => '2026-10-01',
            'status'               => 'borrowed',
        ]);

        // Returned 4 days late on 2026-10-05
        Carbon::setTestNow('2026-10-05 15:30:00');

        $this->actingAs($this->admin);
        $this->post("/equipment-requests/{$borrowedReq->id}/confirm-return", [
            'condition'   => 'good',
            'return_type' => 'full',
        ]);

        $borrowedReq->refresh();
        $this->assertEquals(4, $borrowedReq->days_late, 'Days late must accurately equal 4.');

        // On-time return test
        $onTimeReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 1,
            'purpose'              => 'Presentation',
            'date_needed'          => '2026-10-05',
            'expected_return_date' => '2026-10-06',
            'status'               => 'borrowed',
        ]);

        $this->post("/equipment-requests/{$onTimeReq->id}/confirm-return", [
            'condition'   => 'good',
            'return_type' => 'full',
        ]);

        $this->assertEquals(0, $onTimeReq->fresh()->days_late, 'On-time return must have 0 days late.');
    }

    /**
     * 10. User overdue beyond the threshold cannot create a new request
     */
    public function test_user_overdue_beyond_threshold_cannot_create_new_request(): void
    {
        // Default threshold is 3 days
        config(['icoep.block_borrowing_after_days_overdue' => 3]);

        // Overdue borrowed request: expected return was 2026-10-01
        EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 1,
            'purpose'              => 'Old borrow',
            'date_needed'          => '2026-09-25',
            'expected_return_date' => '2026-10-01',
            'status'               => 'borrowed',
        ]);

        // At 2026-10-05 (4 days overdue, > 3)
        Carbon::setTestNow('2026-10-05 09:00:00');

        $this->actingAs($this->borrower);

        $response = $this->post("/equipment/{$this->equipment->id}/borrow", [
            'quantity'             => 1,
            'purpose'              => 'New project attempt',
            'date_needed'          => '2026-10-06',
            'expected_return_date' => '2026-10-07',
        ]);

        $response->assertSessionHas('error');
        $errorMessage = session('error');
        $this->assertStringContainsString('Sony 4K Camera', $errorMessage);
        $this->assertStringContainsString('4 day(s) overdue', $errorMessage);

        // Verify request was NOT created
        $this->assertEquals(1, EquipmentRequest::where('user_id', $this->borrower->id)->count());
    }

    /**
     * 11. Users without admin permission cannot confirm returns
     */
    public function test_users_without_admin_permission_cannot_confirm_returns(): void
    {
        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 1,
            'purpose'              => 'Project',
            'date_needed'          => '2026-10-01',
            'expected_return_date' => '2026-10-03',
            'status'               => 'borrowed',
        ]);

        // Borrower attempts to confirm return
        $this->actingAs($this->borrower);
        $resBorrower = $this->post("/equipment-requests/{$borrowedReq->id}/confirm-return", [
            'condition' => 'good',
        ]);
        $resBorrower->assertStatus(403);

        // Non-admin user attempts to view returns desk
        $resDesk = $this->get('/equipment/returns');
        $resDesk->assertStatus(403);

        // Admin CAN access returns desk and confirm return
        $this->actingAs($this->admin);
        $resAdminDesk = $this->get('/equipment/returns');
        $resAdminDesk->assertOk();
    }

    /**
     * 12. Sidebar navigation contains Booked Facilities and Borrowed Equipment links
     */
    public function test_sidebar_contains_booked_facilities_and_borrowed_equipment_links(): void
    {
        $this->actingAs($this->borrower);
        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Booked Facilities');
        $response->assertSee('Borrowed Equipment');
        $response->assertSee('section=booked-facilities');
        $response->assertSee('section=borrowed-equipment');
    }

    /**
     * 13. Borrower can view borrowed equipment section and see their items with return actions
     */
    public function test_borrower_can_view_borrowed_equipment_section_with_return_action(): void
    {
        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 2,
            'purpose'              => 'Documentary project',
            'date_needed'          => '2026-10-01',
            'expected_return_date' => '2026-10-05',
            'status'               => 'borrowed',
        ]);

        $this->actingAs($this->borrower);
        $response = $this->get('/dashboard?section=borrowed-equipment');

        $response->assertOk();
        $response->assertSee('Borrowed Equipment');
        $response->assertSee('Sony 4K Camera');
        $response->assertSee('Return Equipment');

        // Submit return request with return photo
        \Illuminate\Support\Facades\Storage::fake('public');
        $photo = \Illuminate\Http\UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg');
        $this->post("/equipment-requests/{$borrowedReq->id}/return-request", [
            'return_photo' => $photo,
        ]);
        $resUpdated = $this->get('/dashboard?section=borrowed-equipment');
        $resUpdated->assertSee('Waiting for admin check');
        $resUpdated->assertSee('Cancel Return');
    }

    /**
     * 14. User can view booked facilities section and see their reservations
     */
    public function test_user_can_view_booked_facilities_section(): void
    {
        $facility = \App\Models\Facility::create([
            'name'        => 'Main Auditorium',
            'location'    => 'Building A',
            'capacity'    => 200,
            'status'      => 'available',
        ]);

        \App\Models\FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $this->borrower->id,
            'title'            => 'Department Assembly',
            'purpose'          => 'Quarterly meeting',
            'reservation_date' => '2026-10-10',
            'time_start'       => '09:00:00',
            'time_end'         => '11:00:00',
            'status'           => 'approved',
        ]);

        $this->actingAs($this->borrower);
        $response = $this->get('/dashboard?section=booked-facilities');

        $response->assertOk();
        $response->assertSee('Booked Facilities');
        $response->assertSee('Main Auditorium');
        $response->assertSee('Department Assembly');
    }

    /**
     * 15. Privacy & Isolation: Users can ONLY see what they requested (not other users' bookings/borrows)
     */
    public function test_users_can_only_see_their_own_booked_facilities_and_borrowed_equipment(): void
    {
        $facility = \App\Models\Facility::create([
            'name'     => 'Conference Room B',
            'location' => 'Admin Wing',
            'capacity' => 20,
            'status'   => 'available',
        ]);

        // Borrower's booking & borrow
        \App\Models\FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $this->borrower->id,
            'title'            => 'Borrower Secret Meeting',
            'purpose'          => 'Private discussion',
            'reservation_date' => '2026-10-15',
            'time_start'       => '14:00:00',
            'time_end'         => '16:00:00',
            'status'           => 'approved',
        ]);

        EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 1,
            'purpose'              => 'Borrower Personal Thesis Shoot',
            'date_needed'          => '2026-10-15',
            'expected_return_date' => '2026-10-16',
            'status'               => 'borrowed',
        ]);

        // Other user's booking & borrow
        \App\Models\FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $this->otherUser->id,
            'title'            => 'Other User Private Event',
            'purpose'          => 'Secret event',
            'reservation_date' => '2026-10-16',
            'time_start'       => '10:00:00',
            'time_end'         => '12:00:00',
            'status'           => 'approved',
        ]);

        EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->otherUser->id,
            'quantity'             => 1,
            'purpose'              => 'Other User Confidential Recording',
            'date_needed'          => '2026-10-16',
            'expected_return_date' => '2026-10-17',
            'status'               => 'borrowed',
        ]);

        // Act as otherUser: must see their own and NEVER see borrower's requests
        $this->actingAs($this->otherUser);

        // 1. Booked Facilities
        $resFac = $this->get('/dashboard?section=booked-facilities');
        $resFac->assertOk();
        $resFac->assertSee('Other User Private Event');
        $resFac->assertDontSee('Borrower Secret Meeting');

        // Search test: even searching for borrower's title returns empty for otherUser
        $resSearchFac = $this->get('/dashboard?section=booked-facilities&search=Borrower+Secret');
        $resSearchFac->assertDontSee('Borrower Secret Meeting');

        // 2. Borrowed Equipment
        $resEq = $this->get('/dashboard?section=borrowed-equipment');
        $resEq->assertOk();
        $resEq->assertSee('Other User Confidential Recording');
        $resEq->assertDontSee('Borrower Personal Thesis Shoot');

        // Search test: even searching for borrower's purpose returns empty for otherUser
        $resSearchEq = $this->get('/dashboard?section=borrowed-equipment&search=Thesis+Shoot');
        $resSearchEq->assertDontSee('Borrower Personal Thesis Shoot');
    }
}
