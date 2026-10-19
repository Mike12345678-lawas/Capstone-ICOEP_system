<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CustodianUnit;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Services\CustodianNotificationService;
use App\Services\ResourceSolver;
use Carbon\Carbon;
use Database\Seeders\CustodianUnitSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustodianInventoryAndPickupTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected User $registrarStaff;
    protected User $libraryStaff;
    protected User $maintenanceStaff;
    protected User $comlabStaff;

    protected CustodianUnit $registrarUnit;
    protected CustodianUnit $libraryUnit;
    protected CustodianUnit $maintenanceUnit;
    protected CustodianUnit $comlabUnit;

    protected Equipment $form137;
    protected Equipment $projector;
    protected Equipment $hammer;
    protected Equipment $laptop;
    protected Equipment $unassignedItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            DepartmentSeeder::class,
            CustodianUnitSeeder::class,
        ]);

        $adminRole       = Role::where('name', 'Administrator')->first();
        $studentRole     = Role::where('name', 'Student')->first();
        $registrarRole   = Role::where('name', 'Registrar')->first();
        $librarianRole   = Role::where('name', 'Librarian')->first();
        $maintenanceRole = Role::where('name', 'Maintenance Staff')->first();
        $comlabRole      = Role::where('name', 'ComLab Staff')->first();

        $dept = Department::first();

        $this->registrarUnit   = CustodianUnit::where('code', 'registrar')->first();
        $this->libraryUnit     = CustodianUnit::where('code', 'library')->first();
        $this->maintenanceUnit = CustodianUnit::where('code', 'maintenance')->first();
        $this->comlabUnit      = CustodianUnit::where('code', 'comlab')->first();

        $this->admin = User::create([
            'first_name'    => 'System',
            'last_name'     => 'Admin',
            'email'         => 'admin@icoep.test',
            'password'      => bcrypt('password'),
            'role_id'       => $adminRole->id,
            'department_id' => $dept->id,
            'employee_id'   => '001',
            'status'        => 'active',
        ]);

        $this->student = User::create([
            'first_name'    => 'John',
            'last_name'     => 'Borrower',
            'email'         => 'student@icoep.test',
            'password'      => bcrypt('password'),
            'role_id'       => $studentRole->id,
            'department_id' => $dept->id,
            'employee_id'   => '24-00101',
            'status'        => 'active',
        ]);

        $this->registrarStaff = User::create([
            'first_name'        => 'Rachel',
            'last_name'         => 'Registrar',
            'email'             => 'registrar@icoep.test',
            'password'          => bcrypt('password'),
            'role_id'           => $registrarRole->id,
            'department_id'     => $dept->id,
            'employee_id'       => '004',
            'custodian_unit_id' => $this->registrarUnit->id,
            'status'            => 'active',
        ]);

        $this->libraryStaff = User::create([
            'first_name'        => 'Leo',
            'last_name'         => 'Librarian',
            'email'             => 'library@icoep.test',
            'password'          => bcrypt('password'),
            'role_id'           => $librarianRole->id,
            'department_id'     => $dept->id,
            'employee_id'       => '005',
            'custodian_unit_id' => $this->libraryUnit->id,
            'status'            => 'active',
        ]);

        $this->maintenanceStaff = User::create([
            'first_name'        => 'Manny',
            'last_name'         => 'Maintenance',
            'email'             => 'maintenance@icoep.test',
            'password'          => bcrypt('password'),
            'role_id'           => $maintenanceRole->id,
            'department_id'     => $dept->id,
            'employee_id'       => '006',
            'custodian_unit_id' => $this->maintenanceUnit->id,
            'status'            => 'active',
        ]);

        $this->comlabStaff = User::create([
            'first_name'        => 'Carl',
            'last_name'         => 'ComLab',
            'email'             => 'comlab@icoep.test',
            'password'          => bcrypt('password'),
            'role_id'           => $comlabRole->id,
            'department_id'     => $dept->id,
            'employee_id'       => '007',
            'custodian_unit_id' => $this->comlabUnit->id,
            'status'            => 'active',
        ]);

        // Equipment items for each unit
        $this->form137 = Equipment::create([
            'name'               => 'Official Form 137 Copy',
            'category'           => 'Documents',
            'description'        => 'Official academic transcript/form',
            'quantity'           => 10,
            'available_quantity' => 10,
            'status'             => 'available',
            'custodian_unit_id'  => $this->registrarUnit->id,
        ]);

        $this->projector = Equipment::create([
            'name'               => 'Epson Projector',
            'category'           => 'Electronics',
            'description'        => 'Multimedia projector',
            'quantity'           => 5,
            'available_quantity' => 5,
            'status'             => 'available',
            'custodian_unit_id'  => $this->libraryUnit->id,
        ]);

        $this->hammer = Equipment::create([
            'name'               => 'Claw Hammer Set',
            'category'           => 'Tools',
            'description'        => 'Carpentry tools',
            'quantity'           => 4,
            'available_quantity' => 4,
            'status'             => 'available',
            'custodian_unit_id'  => $this->maintenanceUnit->id,
        ]);

        $this->laptop = Equipment::create([
            'name'               => 'Dell Latitude Laptop',
            'category'           => 'Computers',
            'description'        => 'ComLab test laptop',
            'quantity'           => 8,
            'available_quantity' => 8,
            'status'             => 'available',
            'custodian_unit_id'  => $this->comlabUnit->id,
        ]);

        $this->unassignedItem = Equipment::create([
            'name'               => 'Legacy Generic Whiteboard',
            'category'           => 'Furniture',
            'description'        => 'Old unassigned board',
            'quantity'           => 2,
            'available_quantity' => 2,
            'status'             => 'available',
            'custodian_unit_id'  => null,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Test 1: Approval triggers pickup notification to the owning unit's active users only.
     */
    public function test_approval_triggers_pickup_notification_to_owning_unit_only(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');

        $request = EquipmentRequest::create([
            'equipment_id'         => $this->form137->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'College application',
            'date_needed'          => '2026-10-21',
            'expected_return_date' => '2026-10-22',
            'status'               => 'pending',
        ]);

        $this->actingAs($this->admin);

        $response = $this->patch("/equipment-requests/{$request->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('equipment_requests', [
            'id'     => $request->id,
            'status' => 'approved',
        ]);

        // Registrar staff MUST have received the pickup notification
        $regNotification = Notification::where('user_id', $this->registrarStaff->id)->first();
        $this->assertNotNull($regNotification, 'Registrar staff should receive pickup notification');
        $this->assertStringContainsString('Ready for Pickup', $regNotification->title);
        $this->assertStringContainsString('Official Form 137 Copy', $regNotification->message);
        $this->assertStringContainsString('John Borrower', $regNotification->message);
        $this->assertEquals('/dashboard?section=pickup-queue', $regNotification->link);

        // Other units (Library, Maintenance, ComLab) MUST NOT have received it
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->libraryStaff->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->maintenanceStaff->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->comlabStaff->id]);

        // Student (borrower) receives approval notification, but not the pickup queue link
        $studentNotification = Notification::where('user_id', $this->student->id)->first();
        $this->assertNotNull($studentNotification);
        $this->assertNotEquals('/dashboard?section=pickup-queue', $studentNotification->link);

        // Audit log MUST record CUSTODIAN_NOTIFIED
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'CUSTODIAN_NOTIFIED',
            'table_name' => 'equipment_requests',
            'record_id'  => $request->id,
        ]);
    }

    /**
     * Test 2: Approval of unassigned equipment logs UNASSIGNED_EQUIPMENT_APPROVED and sends no notification.
     */
    public function test_approving_unassigned_equipment_logs_audit_and_sends_no_notifications(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');

        $request = EquipmentRequest::create([
            'equipment_id'         => $this->unassignedItem->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'Study session',
            'date_needed'          => '2026-10-21',
            'expected_return_date' => '2026-10-22',
            'status'               => 'pending',
        ]);

        $this->actingAs($this->admin);

        $response = $this->patch("/equipment-requests/{$request->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertRedirect();

        // Audit log MUST record UNASSIGNED_EQUIPMENT_APPROVED
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'UNASSIGNED_EQUIPMENT_APPROVED',
            'table_name' => 'equipment_requests',
            'record_id'  => $request->id,
        ]);

        // No custodian notifications sent
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->registrarStaff->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->libraryStaff->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->maintenanceStaff->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->comlabStaff->id]);
    }

    /**
     * Test 3: Deduplication prevents duplicate notifications on repeated or multiple status changes.
     */
    public function test_deduplication_prevents_duplicate_custodian_notifications(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');

        $request = EquipmentRequest::create([
            'equipment_id'         => $this->laptop->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'Programming exam',
            'date_needed'          => '2026-10-21',
            'expected_return_date' => '2026-10-22',
            'status'               => 'pending',
        ]);

        $this->actingAs($this->admin);

        // First approval
        $this->patch("/equipment-requests/{$request->id}/status", ['status' => 'approved']);

        $countBefore = Notification::where('user_id', $this->comlabStaff->id)->count();
        $this->assertEquals(1, $countBefore);

        // Subsequent transition to borrowed (which also runs through notification hook)
        $this->patch("/equipment-requests/{$request->id}/status", ['status' => 'borrowed']);

        $countAfter = Notification::where('user_id', $this->comlabStaff->id)->count();
        $this->assertEquals(1, $countAfter, 'Duplicate notification must be prevented by deduplication check');
    }

    /**
     * Test 4: Approver who is a member of the owning unit is excluded from the notification recipients.
     */
    public function test_approver_is_excluded_if_in_owning_unit(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');

        // Create a second ComLab staff user
        $comlabRole = Role::where('name', 'ComLab Staff')->first();
        $comlabStaff2 = User::create([
            'first_name'        => 'Colin',
            'last_name'         => 'ComLab2',
            'email'             => 'comlab2@icoep.test',
            'password'          => bcrypt('password'),
            'role_id'           => $comlabRole->id,
            'department_id'     => Department::first()->id,
            'employee_id'       => '008',
            'custodian_unit_id' => $this->comlabUnit->id,
            'status'            => 'active',
        ]);

        $request = EquipmentRequest::create([
            'equipment_id'         => $this->laptop->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'Workshop',
            'date_needed'          => '2026-10-21',
            'expected_return_date' => '2026-10-22',
            'status'               => 'pending',
        ]);

        // ComLab staff 1 is the approver
        CustodianNotificationService::notifyPickupReady($request, $this->comlabStaff->id);

        // Staff 1 (approver) was excluded
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->comlabStaff->id]);

        // Staff 2 (colleague in same unit) was notified
        $this->assertDatabaseHas('notifications', ['user_id' => $comlabStaff2->id]);
    }

    /**
     * Test 5: Rejection or cancellation sends no custodian pickup notification.
     */
    public function test_rejected_or_cancelled_request_does_not_send_custodian_notification(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');

        $request = EquipmentRequest::create([
            'equipment_id'         => $this->hammer->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'Personal DIY project',
            'date_needed'          => '2026-10-21',
            'expected_return_date' => '2026-10-22',
            'status'               => 'pending',
        ]);

        $this->actingAs($this->admin);

        $response = $this->patch("/equipment-requests/{$request->id}/status", [
            'status'  => 'rejected',
            'remarks' => 'Unauthorized purpose',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('audit_logs', [
            'action'     => 'CUSTODIAN_NOTIFIED',
            'table_name' => 'equipment_requests',
            'record_id'  => $request->id,
        ]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->maintenanceStaff->id]);
    }

    /**
     * Test 6: Bulk approve notifies respective owning units in a mixed batch.
     */
    public function test_bulk_approve_notifies_respective_units_for_each_item(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');

        $req1 = EquipmentRequest::create([
            'equipment_id'         => $this->projector->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'Class presentation',
            'date_needed'          => '2026-10-21',
            'expected_return_date' => '2026-10-22',
            'status'               => 'pending',
        ]);

        $req2 = EquipmentRequest::create([
            'equipment_id'         => $this->laptop->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'Coding seminar',
            'date_needed'          => '2026-10-21',
            'expected_return_date' => '2026-10-22',
            'status'               => 'pending',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post('/equipment-requests/bulk-approve', [
            'ids' => [$req1->id, $req2->id],
        ]);

        $response->assertRedirect();

        // Library staff should be notified for Projector
        $libNotif = Notification::where('user_id', $this->libraryStaff->id)->first();
        $this->assertNotNull($libNotif);
        $this->assertStringContainsString('Epson Projector', $libNotif->message);

        // ComLab staff should be notified for Laptop
        $comNotif = Notification::where('user_id', $this->comlabStaff->id)->first();
        $this->assertNotNull($comNotif);
        $this->assertStringContainsString('Dell Latitude Laptop', $comNotif->message);

        // Maintenance and Registrar staff received nothing
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->maintenanceStaff->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->registrarStaff->id]);
    }

    /**
     * Test 7: Unit staff can view their own unit inventory on dashboard, but not other units.
     */
    public function test_unit_staff_can_only_view_own_unit_inventory(): void
    {
        $this->actingAs($this->registrarStaff);

        $response = $this->get('/dashboard?section=inventory');
        $response->assertOk();
        $response->assertSee('Official Form 137 Copy');
        $response->assertDontSee('Epson Projector');
        $response->assertDontSee('Claw Hammer Set');
        $response->assertDontSee('Dell Latitude Laptop');
    }

    /**
     * Test 8: Catalog allows browsing all units for both borrowers and unit staff.
     */
    public function test_catalog_allows_browsing_all_units(): void
    {
        $this->actingAs($this->student);

        $response = $this->get('/dashboard?section=equipment');
        $response->assertOk();
        $response->assertSee('Official Form 137 Copy');
        $response->assertSee('Epson Projector');
        $response->assertSee('Claw Hammer Set');
        $response->assertSee('Dell Latitude Laptop');
        $response->assertSee('Registrar');
        $response->assertSee('Library');
        $response->assertSee('Maintenance');
        $response->assertSee('ComLab');
    }

    /**
     * Test 9: Unit staff cannot edit or delete equipment belonging to another unit.
     */
    public function test_unit_staff_cannot_edit_or_delete_equipment_from_other_units(): void
    {
        $this->actingAs($this->registrarStaff);

        // Attempting to edit Library's projector
        $editResponse = $this->get("/equipment/{$this->projector->id}/edit");
        $editResponse->assertStatus(403);

        // Attempting to update Library's projector
        $updateResponse = $this->put("/equipment/{$this->projector->id}", [
            'name'               => 'Hijacked Projector',
            'category'           => 'Electronics',
            'quantity'           => 5,
            'available_quantity' => 5,
            'status'             => 'available',
        ]);
        $updateResponse->assertStatus(403);

        // Attempting to delete Library's projector
        $deleteResponse = $this->delete("/equipment/{$this->projector->id}");
        $deleteResponse->assertStatus(403);
    }

    /**
     * Test 10: Unit staff can edit their own equipment, but cannot change the custodian_unit_id.
     */
    public function test_unit_staff_can_edit_own_equipment_without_changing_custodian_unit(): void
    {
        $this->actingAs($this->registrarStaff);

        $response = $this->put("/equipment/{$this->form137->id}", [
            'name'               => 'Official Form 137 Copy (Updated)',
            'category'           => 'Documents',
            'quantity'           => 12,
            'available_quantity' => 12,
            'status'             => 'available',
            'custodian_unit_id'  => $this->comlabUnit->id, // attempt to reassign to ComLab
        ]);

        $response->assertRedirect('/dashboard?section=inventory');
        $this->form137->refresh();
        $this->assertEquals('Official Form 137 Copy (Updated)', $this->form137->name);
        $this->assertEquals($this->registrarUnit->id, $this->form137->custodian_unit_id, 'Unit staff must not be able to reassign unit');
    }

    /**
     * Test 11: Unit staff creating equipment automatically binds to their unit.
     */
    public function test_unit_staff_creating_equipment_binds_to_their_unit(): void
    {
        $this->actingAs($this->comlabStaff);

        $response = $this->post('/equipment', [
            'name'        => 'Logitech C920 Webcam',
            'category'    => 'Electronics',
            'description' => 'HD video webcam',
            'quantity'    => 6,
            'status'      => 'available',
        ]);

        $response->assertRedirect('/dashboard?section=inventory');
        $created = Equipment::where('name', 'Logitech C920 Webcam')->first();
        $this->assertNotNull($created);
        $this->assertEquals($this->comlabUnit->id, $created->custodian_unit_id);
    }

    /**
     * Test 12: Admin requires custodian_unit_id when creating or editing equipment.
     */
    public function test_admin_requires_custodian_unit_when_creating_equipment(): void
    {
        $this->actingAs($this->admin);

        // Missing custodian_unit_id
        $badResponse = $this->post('/equipment', [
            'name'        => 'Admin Camera',
            'category'    => 'Electronics',
            'description' => 'Test camera',
            'quantity'    => 3,
            'status'      => 'available',
        ]);
        $badResponse->assertSessionHasErrors('custodian_unit_id');

        // Valid custodian_unit_id
        $goodResponse = $this->post('/equipment', [
            'name'              => 'Admin Camera',
            'category'          => 'Electronics',
            'description'       => 'Test camera',
            'quantity'          => 3,
            'status'            => 'available',
            'custodian_unit_id' => $this->comlabUnit->id,
        ]);
        $goodResponse->assertRedirect('/dashboard?section=inventory');
        $this->assertDatabaseHas('equipment', [
            'name'              => 'Admin Camera',
            'custodian_unit_id' => $this->comlabUnit->id,
        ]);
    }

    /**
     * Test 13: Pickup queue on dashboard displays approved and borrowed items for unit staff.
     */
    public function test_pickup_queue_displays_approved_and_borrowed_items_for_unit(): void
    {
        Carbon::setTestNow('2026-10-20 09:00:00');

        // Approved Library request
        $libReq = EquipmentRequest::create([
            'equipment_id'         => $this->projector->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'Seminar',
            'date_needed'          => '2026-10-21',
            'expected_return_date' => '2026-10-22',
            'status'               => 'approved',
        ]);

        // Pending Library request (should NOT appear in pickup queue)
        $pendingLibReq = EquipmentRequest::create([
            'equipment_id'         => $this->projector->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'Other seminar',
            'date_needed'          => '2026-10-23',
            'expected_return_date' => '2026-10-24',
            'status'               => 'pending',
        ]);

        // Approved ComLab request (should NOT appear for Library staff)
        $comReq = EquipmentRequest::create([
            'equipment_id'         => $this->laptop->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'Hackathon',
            'date_needed'          => '2026-10-21',
            'expected_return_date' => '2026-10-22',
            'status'               => 'approved',
        ]);

        $this->actingAs($this->libraryStaff);

        $response = $this->get('/dashboard?section=pickup-queue');
        $response->assertOk();
        $response->assertSee('Pickup Queue');
        $response->assertSee('Seminar');
        $response->assertSee('Epson Projector');
        $response->assertDontSee('Other seminar');
        $response->assertDontSee('Hackathon');
    }

    /**
     * Test 14: Equipment status 'retired' can be assigned and stored.
     */
    public function test_equipment_status_retired_can_be_assigned(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put("/equipment/{$this->hammer->id}", [
            'name'               => $this->hammer->name,
            'category'           => $this->hammer->category,
            'quantity'           => $this->hammer->quantity,
            'available_quantity' => $this->hammer->available_quantity,
            'status'             => 'retired',
            'custodian_unit_id'  => $this->maintenanceUnit->id,
        ]);

        $response->assertRedirect('/dashboard?section=inventory');
        $this->hammer->refresh();
        $this->assertEquals('retired', $this->hammer->status);
    }

    /**
     * Test 15: ResourceSolver priority tier 2 applies to all four custodian roles.
     */
    public function test_resource_solver_priority_tier_2_for_custodian_roles(): void
    {
        $this->assertEquals(2, ResourceSolver::getUserPriority($this->registrarStaff));
        $this->assertEquals(2, ResourceSolver::getUserPriority($this->libraryStaff));
        $this->assertEquals(2, ResourceSolver::getUserPriority($this->maintenanceStaff));
        $this->assertEquals(2, ResourceSolver::getUserPriority($this->comlabStaff));
        $this->assertEquals(1, ResourceSolver::getUserPriority($this->admin));
        $this->assertEquals(3, ResourceSolver::getUserPriority($this->student));
    }
}
