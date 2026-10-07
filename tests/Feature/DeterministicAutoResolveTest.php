<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Facility;
use App\Models\FacilityReservation;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeterministicAutoResolveTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $facultyRole;
    protected Role $staffRole;
    protected Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckPermission::class,
            \App\Http\Middleware\CheckActiveUser::class,
        ]);

        $this->adminRole   = Role::firstOrCreate(['name' => 'Administrator'], ['description' => 'Administrator']);
        $this->facultyRole = Role::firstOrCreate(['name' => 'Faculty'], ['description' => 'Faculty']);
        $this->staffRole   = Role::firstOrCreate(['name' => 'Staff'], ['description' => 'Staff']);
        $this->studentRole = Role::firstOrCreate(['name' => 'Student'], ['description' => 'Student']);
    }

    protected function createUser(string $roleName, array $attributes = []): User
    {
        $role = match ($roleName) {
            'Administrator' => $this->adminRole,
            'Faculty'       => $this->facultyRole,
            'Staff'         => $this->staffRole,
            default         => $this->studentRole,
        };

        return User::create(array_merge([
            'first_name' => fake()->firstName(),
            'last_name'  => fake()->lastName(),
            'email'      => fake()->unique()->safeEmail(),
            'password'   => bcrypt('password'),
            'role_id'    => $role->id,
            'status'     => 'active',
        ], $attributes));
    }

    /**
     * 1. Two students request the same slot → first approved, second rejected with alternative facilities.
     */
    public function test_two_students_request_same_slot_first_approved_second_rejected_with_alternatives(): void
    {
        $student1 = $this->createUser('Student');
        $student2 = $this->createUser('Student');

        $facilityA = Facility::create([
            'name'     => 'Lecture Hall A',
            'location' => 'Building 1',
            'capacity' => 50,
            'status'   => 'available',
        ]);

        $facilityB = Facility::create([
            'name'     => 'Lecture Hall B',
            'location' => 'Building 2',
            'capacity' => 80,
            'status'   => 'available',
        ]);

        $targetDate = now()->addDays(2)->toDateString();

        // Student 1 books first
        $this->actingAs($student1);
        $res1 = $this->post("/facilities/{$facilityA->id}/reserve", [
            'title'            => 'Student 1 Study Session',
            'reservation_date' => $targetDate,
            'time_start'       => '10:00',
            'time_end'         => '12:00',
        ]);
        $res1->assertRedirect('/dashboard?section=booked-facilities');
        $res1->assertSessionHas('success');

        $booking1 = FacilityReservation::where('user_id', $student1->id)->first();
        $this->assertNotNull($booking1);
        $this->assertEquals('approved', $booking1->status);
        $this->assertNull($booking1->approved_by);

        // Student 2 books overlapping slot on the same facility
        $this->actingAs($student2);
        $res2 = $this->post("/facilities/{$facilityA->id}/reserve", [
            'title'            => 'Student 2 Project Meeting',
            'reservation_date' => $targetDate,
            'time_start'       => '11:00',
            'time_end'         => '13:00',
        ]);

        $res2->assertSessionHas('error');
        $res2->assertSessionHas('suggestions');

        // Check suggestions contain alternate facility B
        $suggestions = session('suggestions');
        $this->assertNotEmpty($suggestions);
        $this->assertLessThanOrEqual(5, count($suggestions));

        $hasAltFacility = collect($suggestions)->contains(function ($s) use ($facilityB) {
            return ($s['type'] ?? '') === 'alternate_facility' && ($s['facility_id'] ?? null) === $facilityB->id;
        });
        $this->assertTrue($hasAltFacility, 'Expected Facility B to be suggested as alternate facility.');

        // Verify rejected reservation is saved in the database with audit log
        $booking2 = FacilityReservation::where('user_id', $student2->id)->first();
        $this->assertNotNull($booking2, 'Auto-rejected request must be saved in database.');
        $this->assertEquals('rejected', $booking2->status);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_APPROVED_RESERVATION',
            'table_name' => 'facility_reservations',
            'record_id'  => $booking1->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_REJECTED_RESERVATION',
            'table_name' => 'facility_reservations',
            'record_id'  => $booking2->id,
        ]);
    }

    /**
     * 2. A Faculty/Staff request overlaps an approved Student booking →
     *    Student bumped, notified with alternatives, faculty approved.
     */
    public function test_faculty_request_overlaps_approved_student_booking_bumps_student_and_approves_faculty(): void
    {
        $student = $this->createUser('Student');
        $faculty = $this->createUser('Faculty');

        $facility = Facility::create([
            'name'     => 'Conference Room 1',
            'location' => 'Admin Wing',
            'capacity' => 30,
            'status'   => 'available',
        ]);

        // Scheduled 3 days out (well past 24-hour preemption limit)
        $date = now()->addDays(3)->toDateString();

        // Student reserves 14:00 - 16:00
        $this->actingAs($student);
        $this->post("/facilities/{$facility->id}/reserve", [
            'title'            => 'Student Club Meeting',
            'reservation_date' => $date,
            'time_start'       => '14:00',
            'time_end'         => '16:00',
        ]);

        $studentBooking = FacilityReservation::where('user_id', $student->id)->first();
        $this->assertEquals('approved', $studentBooking->status);

        // Faculty reserves overlapping slot 15:00 - 17:00
        $this->actingAs($faculty);
        $res = $this->post("/facilities/{$facility->id}/reserve", [
            'title'            => 'Department Faculty Meeting',
            'reservation_date' => $date,
            'time_start'       => '15:00',
            'time_end'         => '17:00',
        ]);
        $res->assertSessionHas('success');

        // Check student booking is cancelled with reason
        $studentBooking->refresh();
        $this->assertEquals('cancelled', $studentBooking->status);
        $this->assertStringContainsString('preempted', strtolower($studentBooking->remarks));

        // Check faculty booking is approved
        $facultyBooking = FacilityReservation::where('user_id', $faculty->id)->first();
        $this->assertNotNull($facultyBooking);
        $this->assertEquals('approved', $facultyBooking->status);

        // Student should receive notification with warning
        $notification = Notification::where('user_id', $student->id)->orderByDesc('id')->first();
        $this->assertNotNull($notification);
        $this->assertEquals('warning', $notification->type);
        $this->assertStringContainsString('cancelled', strtolower($notification->message));

        // Audit trail checks
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'PREEMPTED_RESERVATION',
            'table_name' => 'facility_reservations',
            'record_id'  => $studentBooking->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_APPROVED_RESERVATION',
            'table_name' => 'facility_reservations',
            'record_id'  => $facultyBooking->id,
        ]);
    }

    /**
     * 3. A Student request overlaps an approved Faculty booking → rejected.
     */
    public function test_student_request_overlaps_approved_faculty_booking_is_rejected(): void
    {
        $faculty = $this->createUser('Faculty');
        $student = $this->createUser('Student');

        $facility = Facility::create([
            'name'     => 'Multimedia Room',
            'location' => 'Library 2F',
            'capacity' => 40,
            'status'   => 'available',
        ]);

        $date = now()->addDays(3)->toDateString();

        // Faculty reserves first
        $this->actingAs($faculty);
        $this->post("/facilities/{$facility->id}/reserve", [
            'title'            => 'Faculty Research Seminar',
            'reservation_date' => $date,
            'time_start'       => '13:00',
            'time_end'         => '15:00',
        ]);
        $facultyBooking = FacilityReservation::where('user_id', $faculty->id)->first();
        $this->assertEquals('approved', $facultyBooking->status);

        // Student tries to book overlapping slot
        $this->actingAs($student);
        $res = $this->post("/facilities/{$facility->id}/reserve", [
            'title'            => 'Student Rehearsal',
            'reservation_date' => $date,
            'time_start'       => '14:00',
            'time_end'         => '16:00',
        ]);
        $res->assertSessionHas('error');

        // Student request is saved as rejected
        $studentBooking = FacilityReservation::where('user_id', $student->id)->first();
        $this->assertNotNull($studentBooking);
        $this->assertEquals('rejected', $studentBooking->status);

        // Faculty booking remains approved
        $this->assertEquals('approved', $facultyBooking->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_REJECTED_RESERVATION',
            'table_name' => 'facility_reservations',
            'record_id'  => $studentBooking->id,
        ]);
    }

    /**
     * 4. Faculty vs Faculty on the same slot → first come, first served.
     */
    public function test_faculty_vs_faculty_same_slot_is_first_come_first_served(): void
    {
        $faculty1 = $this->createUser('Faculty');
        $faculty2 = $this->createUser('Faculty');

        $facility = Facility::create([
            'name'     => 'Lab 101',
            'location' => 'Science Bldg',
            'capacity' => 30,
            'status'   => 'available',
        ]);

        $date = now()->addDays(3)->toDateString();

        // Faculty 1 reserves
        $this->actingAs($faculty1);
        $this->post("/facilities/{$facility->id}/reserve", [
            'title'            => 'Faculty 1 Class',
            'reservation_date' => $date,
            'time_start'       => '09:00',
            'time_end'         => '11:00',
        ]);
        $booking1 = FacilityReservation::where('user_id', $faculty1->id)->first();
        $this->assertEquals('approved', $booking1->status);

        // Faculty 2 tries to reserve same slot (same tier cannot preempt)
        $this->actingAs($faculty2);
        $res = $this->post("/facilities/{$facility->id}/reserve", [
            'title'            => 'Faculty 2 Class',
            'reservation_date' => $date,
            'time_start'       => '09:00',
            'time_end'         => '11:00',
        ]);
        $res->assertSessionHas('error');

        $booking2 = FacilityReservation::where('user_id', $faculty2->id)->first();
        $this->assertNotNull($booking2);
        $this->assertEquals('rejected', $booking2->status);

        // Faculty 1 keeps slot
        $this->assertEquals('approved', $booking1->fresh()->status);
    }

    /**
     * 5. Preemption blocked when the booking starts within preempt_min_hours_before.
     */
    public function test_preemption_blocked_when_booking_starts_within_preempt_min_hours_before(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 08:00:00'));
        config(['icoep.auto_approve.facility.preempt_min_hours_before' => 24]);

        $student = $this->createUser('Student');
        $faculty = $this->createUser('Faculty');

        $facility = Facility::create([
            'name'     => 'Discussion Room',
            'location' => 'Library 1F',
            'capacity' => 20,
            'status'   => 'available',
        ]);

        // Student creates an approved reservation starting in 5 hours (within 24 hours of now)
        $startIn12 = Carbon::parse('2026-10-01 13:00:00');
        $studentBooking = FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $student->id,
            'title'            => 'Urgent Study Session',
            'reservation_date' => $startIn12->toDateString(),
            'time_start'       => $startIn12->format('H:i'),
            'time_end'         => $startIn12->copy()->addHours(2)->format('H:i'),
            'status'           => 'approved',
            'remarks'          => 'Pre-existing approved booking',
        ]);

        // Faculty tries to reserve overlapping slot
        $this->actingAs($faculty);
        $res = $this->post("/facilities/{$facility->id}/reserve", [
            'title'            => 'Faculty Rush Meeting',
            'reservation_date' => $startIn12->toDateString(),
            'time_start'       => $startIn12->format('H:i'),
            'time_end'         => $startIn12->copy()->addHours(2)->format('H:i'),
        ]);

        // Preemption should be blocked because booking starts in < 24 hours
        $res->assertSessionHas('error');

        $studentBooking->refresh();
        $this->assertEquals('approved', $studentBooking->status, 'Student booking must NOT be bumped within 24h window.');

        $facultyBooking = FacilityReservation::where('user_id', $faculty->id)->first();
        $this->assertNotNull($facultyBooking);
        $this->assertEquals('rejected', $facultyBooking->status);
    }

    /**
     * 6. Equipment: requests are NOT auto-approved (auto-approval is facility-only) and remain pending for admin.
     */
    public function test_equipment_requests_are_not_auto_approved_and_remain_pending(): void
    {
        $student = $this->createUser('Student');
        $faculty = $this->createUser('Faculty');
        $admin   = $this->createUser('Administrator');

        $equipment = Equipment::create([
            'name'               => 'HDMI Projector A',
            'category'           => 'Electronics',
            'quantity'           => 2,
            'available_quantity' => 2,
            'status'             => 'available',
        ]);

        $dateNeeded     = now()->addDays(3)->toDateString();
        $expectedReturn = now()->addDays(5)->toDateString();

        // Student requests 1 unit -> remains pending, stock NOT decremented
        config(['icoep.auto_approve.equipment.enabled' => false]);
        $this->actingAs($student);
        $resStudent = $this->post("/equipment/{$equipment->id}/borrow", [
            'quantity'             => 1,
            'purpose'              => 'Student Presentation',
            'date_needed'          => $dateNeeded,
            'expected_return_date' => $expectedReturn,
        ]);
        $resStudent->assertSessionHas('success');
        $studentReq = EquipmentRequest::where('user_id', $student->id)->first();
        $this->assertNotNull($studentReq);
        $this->assertEquals('pending', $studentReq->status);
        $this->assertEquals('Pending admin approval', $studentReq->remarks);
        $this->assertNull($studentReq->approved_by);
        // Stock must not be decremented upon request creation
        $this->assertEquals(2, $equipment->fresh()->available_quantity);

        // Student receives pending notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'title'   => '⏳ Equipment Request Pending',
        ]);

        // Faculty requests 1 unit -> also remains pending (no auto-approval for equipment regardless of role)
        $this->actingAs($faculty);
        $resFaculty = $this->post("/equipment/{$equipment->id}/borrow", [
            'quantity'             => 1,
            'purpose'              => 'Faculty Lecture',
            'date_needed'          => $dateNeeded,
            'expected_return_date' => $expectedReturn,
        ]);
        $resFaculty->assertSessionHas('success');
        $facultyReq = EquipmentRequest::where('user_id', $faculty->id)->first();
        $this->assertNotNull($facultyReq);
        $this->assertEquals('pending', $facultyReq->status);
        $this->assertEquals(2, $equipment->fresh()->available_quantity);

        // Neither request was auto-approved
        $this->assertDatabaseMissing('audit_logs', [
            'action'     => 'AUTO_APPROVED_BORROW',
            'table_name' => 'equipment_requests',
        ]);

        // Admin manually approves Student request
        $this->actingAs($admin);
        $this->patch("/equipment-requests/{$studentReq->id}/status", [
            'status' => 'approved',
        ]);
        $this->assertEquals('approved', $studentReq->fresh()->status);
        $this->assertEquals(1, $equipment->fresh()->available_quantity);
    }

    /**
     * 7. requests:auto-resolve --dry-run changes nothing.
     */
    public function test_requests_auto_resolve_dry_run_changes_nothing(): void
    {
        $student = $this->createUser('Student');

        $facility = Facility::create([
            'name'     => 'Auditorium',
            'location' => 'Main',
            'capacity' => 200,
            'status'   => 'available',
        ]);

        $equipment = Equipment::create([
            'name'               => 'Wireless Microphone',
            'category'           => 'Audio',
            'quantity'           => 4,
            'available_quantity' => 4,
            'status'             => 'available',
        ]);

        $date = now()->addDays(4)->toDateString();

        // Create a pending facility reservation
        $resPending = FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $student->id,
            'title'            => 'Pending Event',
            'reservation_date' => $date,
            'time_start'       => '09:00',
            'time_end'         => '11:00',
            'status'           => 'pending',
            'remarks'          => 'Initial pending state',
        ]);

        // Create a pending equipment request
        $eqPending = EquipmentRequest::create([
            'equipment_id'         => $equipment->id,
            'user_id'              => $student->id,
            'quantity'             => 2,
            'purpose'              => 'Pending Audio Request',
            'date_needed'          => $date,
            'expected_return_date' => $date,
            'status'               => 'pending',
            'remarks'              => 'Initial pending state',
        ]);

        $logsBefore  = AuditLog::count();
        $stockBefore = $equipment->available_quantity;

        // Run with --dry-run
        Artisan::call('requests:auto-resolve', ['--dry-run' => true]);

        // Verify state is completely unchanged
        $this->assertEquals('pending', $resPending->fresh()->status);
        $this->assertEquals('pending', $eqPending->fresh()->status);
        $this->assertEquals($stockBefore, $equipment->fresh()->available_quantity);
        $this->assertEquals($logsBefore, AuditLog::count());

        // Now run without --dry-run
        Artisan::call('requests:auto-resolve');

        // Verify facility was auto-approved, while equipment remained pending (auto-approval is facility-only)
        $this->assertEquals('approved', $resPending->fresh()->status);
        $this->assertEquals('pending', $eqPending->fresh()->status);
        $this->assertEquals($stockBefore, $equipment->fresh()->available_quantity);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_APPROVED_RESERVATION',
            'table_name' => 'facility_reservations',
            'record_id'  => $resPending->id,
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'action'     => 'AUTO_APPROVED_BORROW',
            'table_name' => 'equipment_requests',
            'record_id'  => $eqPending->id,
        ]);
    }

    /**
     * 8. Full-day facility booking (e.g. 08:05 to 17:00) is auto-approved immediately.
     */
    public function test_full_day_facility_reservation_is_auto_approved(): void
    {
        $student = $this->createUser('Student');
        $facility = Facility::create([
            'name'     => 'Audio-Visual Room',
            'location' => 'Main Wing 2F',
            'capacity' => 60,
            'status'   => 'available',
        ]);

        $targetDate = now()->addDays(5)->toDateString();

        $this->actingAs($student);
        $res = $this->post("/facilities/{$facility->id}/reserve", [
            'title'            => 'IT Department Annual Assembly',
            'reservation_date' => $targetDate,
            'time_start'       => '08:05',
            'time_end'         => '17:00',
        ]);

        $res->assertRedirect('/dashboard?section=booked-facilities');
        $res->assertSessionHas('success');

        $booking = FacilityReservation::where('user_id', $student->id)
            ->where('facility_id', $facility->id)
            ->first();

        $this->assertNotNull($booking);
        $this->assertEquals('approved', $booking->status);
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_APPROVED_RESERVATION',
            'table_name' => 'facility_reservations',
            'record_id'  => $booking->id,
        ]);
    }

    /**
     * 9. Admin auto-resolve endpoint processes pending facility reservations only.
     */
    public function test_admin_auto_resolve_endpoint_processes_pending_facilities_only(): void
    {
        $admin = $this->createUser('Administrator');
        $student = $this->createUser('Student');

        $facility = Facility::create([
            'name'     => 'Science Lab',
            'location' => 'Building C',
            'capacity' => 40,
            'status'   => 'available',
        ]);

        $equipment = Equipment::create([
            'name'               => 'Digital Projector',
            'category'           => 'Electronics',
            'quantity'           => 3,
            'available_quantity' => 3,
            'status'             => 'available',
        ]);

        $targetDate = now()->addDays(6)->toDateString();

        $pendingFacility = FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $student->id,
            'title'            => 'Pending Club Meeting',
            'reservation_date' => $targetDate,
            'time_start'       => '13:00',
            'time_end'         => '15:00',
            'status'           => 'pending',
            'remarks'          => 'Pending review',
        ]);

        $pendingEquipment = EquipmentRequest::create([
            'equipment_id'         => $equipment->id,
            'user_id'              => $student->id,
            'quantity'             => 1,
            'purpose'              => 'Pending Projector Request',
            'date_needed'          => $targetDate,
            'expected_return_date' => $targetDate,
            'status'               => 'pending',
            'remarks'              => 'Pending admin approval',
        ]);

        $this->actingAs($admin);
        $response = $this->postJson('/facility-reservations/auto-resolve');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'stats'   => [
                'approved' => 1,
                'rejected' => 0,
                'pending'  => 0,
            ],
        ]);

        // Facility is now approved
        $this->assertEquals('approved', $pendingFacility->fresh()->status);

        // Equipment remains pending (automation is facility only)
        $this->assertEquals('pending', $pendingEquipment->fresh()->status);
    }
}

