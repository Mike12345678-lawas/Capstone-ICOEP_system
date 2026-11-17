<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Facility;
use App\Models\FacilityReservation;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemRefinementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $adminRole = Role::where('name', 'Administrator')->first();
        $studentRole = Role::where('name', 'Student')->first();
        $this->department = Department::first();

        $this->admin = User::create([
            'id_type'       => 'employee',
            'employee_id'   => 'ADM-999',
            'first_name'    => 'Admin',
            'last_name'     => 'System',
            'email'         => 'admin.system@slmcb.edu.ph',
            'password'      => bcrypt('password'),
            'role_id'       => $adminRole->id,
            'department_id' => $this->department->id,
            'status'        => 'active',
            'privacy_consent' => true,
        ]);

        $this->user = User::create([
            'id_type'       => 'student',
            'employee_id'   => 'STU-999',
            'first_name'    => 'Student',
            'last_name'     => 'User',
            'email'         => 'student.user@slmcb.edu.ph',
            'password'      => bcrypt('password'),
            'role_id'       => $studentRole->id,
            'department_id' => $this->department->id,
            'status'        => 'active',
            'privacy_consent' => true,
        ]);
    }

    /**
     * 1. FacilityController::complete()
     * Only allow completing an approved booking that has started,
     * and do NOT overwrite time_end with the current time.
     */
    public function test_facility_complete_only_allows_approved_started_booking_and_preserves_time_end(): void
    {
        $facility = Facility::create([
            'name'     => 'Conference Hall',
            'capacity' => 100,
            'status'   => 'available',
        ]);

        // Scenario A: Pending reservation cannot be completed
        $pendingRes = FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $this->user->id,
            'title'            => 'Pending Booking',
            'reservation_date' => now()->toDateString(),
            'time_start'       => '08:00',
            'time_end'         => '12:00',
            'status'           => 'pending',
        ]);

        $this->actingAs($this->admin);
        $response = $this->post("/facility-reservations/{$pendingRes->id}/complete");
        $response->assertSessionHas('error', 'Only approved reservations can be completed.');
        $this->assertEquals('pending', $pendingRes->fresh()->status);

        // Scenario B: Approved reservation in the future (not started yet) cannot be completed
        Carbon::setTestNow('2026-11-20 07:00:00');
        $futureRes = FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $this->user->id,
            'title'            => 'Future Booking',
            'reservation_date' => '2026-11-20',
            'time_start'       => '10:00',
            'time_end'         => '12:00',
            'status'           => 'approved',
        ]);

        $responseFuture = $this->post("/facility-reservations/{$futureRes->id}/complete");
        $responseFuture->assertSessionHas('error', 'Cannot complete a reservation that has not started yet.');
        $this->assertEquals('approved', $futureRes->fresh()->status);

        // Scenario C: Approved reservation that has started CAN be completed, time_end is preserved
        Carbon::setTestNow('2026-11-20 10:30:00'); // 30 mins into the booking
        $originalTimeEnd = '12:00';

        $responseStarted = $this->post("/facility-reservations/{$futureRes->id}/complete");
        $responseStarted->assertRedirect('/dashboard?section=booked-facilities');
        $responseStarted->assertSessionHas('success');

        $futureRes->refresh();
        $this->assertEquals('completed', $futureRes->status);
        $this->assertEquals('12:00:00', Carbon::parse($futureRes->time_end)->format('H:i:s'), 'time_end must NOT be overwritten with current time.');
        Carbon::setTestNow();
    }

    /**
     * 2. Bulk approve: re-checks status is pending after lockForUpdate
     */
    public function test_bulk_approve_equipment_and_facility_validates_pending_status_under_lock(): void
    {
        $this->actingAs($this->admin);

        $equipment = Equipment::create([
            'name'               => 'Audio Mixer',
            'category'           => 'Sound',
            'quantity'           => 5,
            'available_quantity' => 5,
            'status'             => 'available',
        ]);

        $eqReq = EquipmentRequest::create([
            'equipment_id'         => $equipment->id,
            'user_id'              => $this->user->id,
            'quantity'             => 2,
            'purpose'              => 'Concert',
            'date_needed'          => now()->addDays(2)->toDateString(),
            'expected_return_date' => now()->addDays(5)->toDateString(),
            'status'               => 'cancelled', // not pending!
        ]);

        $res = $this->post('/equipment-requests/bulk-approve', [
            'ids' => [$eqReq->id],
        ]);
        $res->assertSessionHas('error');
        $this->assertEquals(5, $equipment->fresh()->available_quantity, 'Stock must not be decremented for non-pending request.');

        $facility = Facility::create(['name' => 'Room 101', 'capacity' => 40, 'status' => 'available']);
        $facRes = FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $this->user->id,
            'title'            => 'Cancelled Room Booking',
            'reservation_date' => now()->addDays(2)->toDateString(),
            'time_start'       => '09:00',
            'time_end'         => '11:00',
            'status'           => 'cancelled',
        ]);

        $resFac = $this->post('/facilities/reservations/bulk-approve', [
            'ids' => [$facRes->id],
        ]);
        $resFac->assertSessionHas('error');
    }

    /**
     * 3. Damaged returns: reduce quantity by damaged count instead of setting whole item to maintenance
     */
    public function test_damaged_return_reduces_total_quantity_instead_of_setting_maintenance(): void
    {
        $this->actingAs($this->admin);

        $equipment = Equipment::create([
            'name'               => 'Microphone Set',
            'category'           => 'Audio',
            'quantity'           => 10,
            'available_quantity' => 8, // 2 currently borrowed
            'status'             => 'available',
        ]);

        $borrowedReq = EquipmentRequest::create([
            'equipment_id'         => $equipment->id,
            'user_id'              => $this->user->id,
            'quantity'             => 2,
            'purpose'              => 'Seminar',
            'date_needed'          => now()->subDays(2)->toDateString(),
            'expected_return_date' => now()->toDateString(),
            'status'               => 'borrowed',
        ]);

        $response = $this->post("/equipment-requests/{$borrowedReq->id}/confirm-return", [
            'condition'    => 'damaged',
            'return_type'  => 'full',
            'return_notes' => 'Damaged by water spill',
        ]);

        $response->assertRedirect('/equipment/returns');
        $equipment->refresh();
        $borrowedReq->refresh();

        $this->assertEquals('returned', $borrowedReq->status);
        $this->assertEquals('damaged', $borrowedReq->return_condition);
        // Total quantity reduced from 10 to 8
        $this->assertEquals(8, $equipment->quantity);
        // Available quantity remains 8 (damaged units are not put back into available stock)
        $this->assertEquals(8, $equipment->available_quantity);
        // Equipment item itself is NOT set to maintenance
        $this->assertEquals('available', $equipment->status);
    }

    /**
     * 4. Event registration: normal error message when full instead of exception
     */
    public function test_event_registration_returns_error_message_when_full(): void
    {
        $event = Event::create([
            'title'            => 'Full Capacity Seminar',
            'objectives'       => 'Learn about campus systems and workflows',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => now()->addDays(5)->toDateString(),
            'time_start'       => '09:00',
            'time_end'         => '11:00',
            'venue'            => 'Auditorium',
            'max_participants' => 1,
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        // First user registers successfully
        $this->actingAs($this->admin);
        $res1 = $this->post("/events/{$event->id}/register");
        $res1->assertSessionHas('success');

        // Second user tries to register on full event
        $this->actingAs($this->user);
        $res2 = $this->post("/events/{$event->id}/register");
        $res2->assertSessionHas('error', 'This event has reached maximum capacity.');

        // Re-register by first user gives duplicate warning, no crash
        $this->actingAs($this->admin);
        $res3 = $this->post("/events/{$event->id}/register");
        $res3->assertSessionHas('warning', 'You are already registered for this event.');
    }

    /**
     * 5. Dashboard upcoming events: includes events with event_date >= today()
     */
    public function test_dashboard_upcoming_events_includes_events_happening_today(): void
    {
        $todayEvent = Event::create([
            'title'            => 'Event Happening Today',
            'objectives'       => 'Important campus gathering today',
            'event_type'       => 'cultural',
            'target_audience'  => 'all',
            'event_date'       => today()->toDateString(),
            'time_start'       => '14:00',
            'time_end'         => '16:00',
            'venue'            => 'Main Lobby',
            'max_participants' => 100,
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Event Happening Today');
    }

    /**
     * 6. Equipment stock:
     * - Block requests where quantity exceeds total stock
     * - Update form recomputes available_quantity based on active borrows
     */
    public function test_equipment_stock_limits_and_recomputation(): void
    {
        $equipment = Equipment::create([
            'name'               => 'Projector HD',
            'category'           => 'Electronics',
            'quantity'           => 3,
            'available_quantity' => 3,
            'status'             => 'available',
        ]);

        // Attempt to borrow 5 units when total quantity is 3
        $this->actingAs($this->user);
        $response = $this->post("/equipment/{$equipment->id}/borrow", [
            'quantity'             => 5,
            'purpose'              => 'Project presentation',
            'date_needed'          => now()->addDays(1)->toDateString(),
            'expected_return_date' => now()->addDays(2)->toDateString(),
        ]);
        $response->assertSessionHas('error');
        $this->assertStringContainsString('exceeds total', session('error'));

        $unit = \App\Models\CustodianUnit::create(['name' => 'AV Unit', 'code' => 'av_unit']);
        $equipment->update(['custodian_unit_id' => $unit->id]);

        // Admin updates equipment total quantity from 3 to 10
        $this->actingAs($this->admin);
        $updateRes = $this->put("/equipment/{$equipment->id}", [
            'name'              => 'Projector HD',
            'category'          => 'Electronics',
            'quantity'          => 10,
            'custodian_unit_id' => $unit->id,
            'status'            => 'available',
        ]);
        $updateRes->assertRedirect('/dashboard?section=inventory');

        $equipment->refresh();
        $this->assertEquals(10, $equipment->quantity);
        $this->assertEquals(10, $equipment->available_quantity, 'available_quantity should be automatically recomputed.');
    }

    /**
     * 7. Return desk search: REQ- prefix search with preg_replace
     */
    public function test_return_desk_search_strips_req_prefix_correctly(): void
    {
        $equipment = Equipment::create([
            'name'               => 'Lab Kit',
            'category'           => 'Science',
            'quantity'           => 5,
            'available_quantity' => 4,
            'status'             => 'available',
        ]);

        $req = EquipmentRequest::create([
            'equipment_id'         => $equipment->id,
            'user_id'              => $this->user->id,
            'quantity'             => 1,
            'purpose'              => 'Chemistry Lab',
            'date_needed'          => now()->toDateString(),
            'expected_return_date' => now()->addDays(1)->toDateString(),
            'status'               => 'borrowed',
        ]);

        $this->actingAs($this->admin);

        // Search with "REQ-{id}"
        $response = $this->get("/equipment/returns?search=REQ-{$req->id}");
        $response->assertOk();
        $response->assertSee('Lab Kit');

        // Search with lowercase "req-{id}"
        $responseLower = $this->get("/equipment/returns?search=req-{$req->id}");
        $responseLower->assertOk();
        $responseLower->assertSee('Lab Kit');
    }

    /**
     * 8. Deletes: Block deletion when history exists for facilities and equipment
     */
    public function test_deleting_facility_and_equipment_blocked_when_history_exists(): void
    {
        $this->actingAs($this->admin);

        $facility = Facility::create(['name' => 'Historic Room', 'capacity' => 50, 'status' => 'available']);
        FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $this->user->id,
            'title'            => 'Old Booking',
            'reservation_date' => now()->subDays(10)->toDateString(),
            'time_start'       => '08:00',
            'time_end'         => '10:00',
            'status'           => 'completed',
        ]);

        // Attempt deleting facility that has historical reservations
        $delFac = $this->delete("/facilities/{$facility->id}");
        $delFac->assertSessionHas('error');
        $this->assertDatabaseHas('facilities', ['id' => $facility->id]);

        $equipment = Equipment::create([
            'name'               => 'Historic Tablet',
            'category'           => 'Electronics',
            'quantity'           => 2,
            'available_quantity' => 2,
            'status'             => 'available',
        ]);
        EquipmentRequest::create([
            'equipment_id'         => $equipment->id,
            'user_id'              => $this->user->id,
            'quantity'             => 1,
            'purpose'              => 'Old research',
            'date_needed'          => now()->subDays(10)->toDateString(),
            'expected_return_date' => now()->subDays(8)->toDateString(),
            'actual_return_date'   => now()->subDays(8)->toDateString(),
            'status'               => 'returned',
        ]);

        // Attempt deleting equipment that has historical requests
        $delEq = $this->delete("/equipment/{$equipment->id}");
        $delEq->assertSessionHas('error');
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id]);
    }

    /**
     * 9. EmailNotificationService uses config/services.php
     */
    public function test_email_relay_config_services_resolution(): void
    {
        $this->assertEquals(env('GMAIL_RELAY_URL'), config('services.gmail_relay.url'));
    }
}
