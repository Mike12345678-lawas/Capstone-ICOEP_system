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
        $res1->assertRedirect('/facilities/' . $facilityA->id);
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
        config(['icoep.auto_approve.facility.preempt_min_hours_before' => 24]);

        $student = $this->createUser('Student');
        $faculty = $this->createUser('Faculty');

        $facility = Facility::create([
            'name'     => 'Discussion Room',
            'location' => 'Library 1F',
            'capacity' => 20,
            'status'   => 'available',
        ]);

        // Student creates an approved reservation starting in 12 hours (within 24 hours of now)
        $startIn12 = now()->addHours(12);
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
     * 6. Equipment: stock restored correctly after preemption; borrowed items are never preempted.
     */
    public function test_equipment_stock_restored_after_preemption_and_borrowed_items_never_preempted(): void
    {
        $student1 = $this->createUser('Student');
        $student2 = $this->createUser('Student');
        $faculty  = $this->createUser('Faculty');

        $equipment = Equipment::create([
            'name'               => 'HDMI Projector A',
            'category'           => 'Electronics',
            'quantity'           => 2,
            'available_quantity' => 2,
            'status'             => 'available',
        ]);

        $dateNeeded     = now()->addDays(3)->toDateString();
        $expectedReturn = now()->addDays(5)->toDateString();

        // Student 1 requests 1 unit -> auto-approved
        $this->actingAs($student1);
        $this->post("/equipment/{$equipment->id}/borrow", [
            'quantity'             => 1,
            'purpose'              => 'Student 1 Presentation',
            'date_needed'          => $dateNeeded,
            'expected_return_date' => $expectedReturn,
        ]);
        $req1 = EquipmentRequest::where('user_id', $student1->id)->first();
        $this->assertEquals('approved', $req1->status);
        $this->assertEquals(1, $equipment->fresh()->available_quantity);

        // Student 1's equipment is already physically handed out (status = borrowed)
        $req1->update(['status' => 'borrowed']);

        // Student 2 requests remaining 1 unit -> auto-approved
        $this->actingAs($student2);
        $this->post("/equipment/{$equipment->id}/borrow", [
            'quantity'             => 1,
            'purpose'              => 'Student 2 Classwork',
            'date_needed'          => $dateNeeded,
            'expected_return_date' => $expectedReturn,
        ]);
        $req2 = EquipmentRequest::where('user_id', $student2->id)->first();
        $this->assertEquals('approved', $req2->status);
        $this->assertEquals(0, $equipment->fresh()->available_quantity);

        // Faculty requests 1 unit for the same dates
        $this->actingAs($faculty);
        $resFaculty = $this->post("/equipment/{$equipment->id}/borrow", [
            'quantity'             => 1,
            'purpose'              => 'Faculty Lecture',
            'date_needed'          => $dateNeeded,
            'expected_return_date' => $expectedReturn,
        ]);
        $resFaculty->assertSessionHas('success');

        // Verification:
        // Student 1 (borrowed) must NEVER be preempted
        $this->assertEquals('borrowed', $req1->fresh()->status, 'Borrowed equipment must never be preempted.');

        // Student 2 (approved) was preempted and cancelled
        $this->assertEquals('cancelled', $req2->fresh()->status);
        $this->assertStringContainsString('preempted', strtolower($req2->fresh()->remarks));

        // Faculty is approved
        $facultyReq = EquipmentRequest::where('user_id', $faculty->id)->first();
        $this->assertEquals('approved', $facultyReq->status);

        // Available quantity must be 0 (1 borrowed by student 1 + 1 approved for faculty)
        $this->assertEquals(0, $equipment->fresh()->available_quantity);

        // Student 2 received a notification with warning
        $notif = Notification::where('user_id', $student2->id)->orderByDesc('id')->first();
        $this->assertNotNull($notif);
        $this->assertEquals('warning', $notif->type);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'PREEMPTED_BORROW',
            'table_name' => 'equipment_requests',
            'record_id'  => $req2->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_APPROVED_BORROW',
            'table_name' => 'equipment_requests',
            'record_id'  => $facultyReq->id,
        ]);

        // If another Faculty now requests 1 unit, they should be auto-rejected since 0 stock is available and no preemptible items remain
        $faculty2 = $this->createUser('Faculty');
        $this->actingAs($faculty2);
        $resFac2 = $this->post("/equipment/{$equipment->id}/borrow", [
            'quantity'             => 1,
            'purpose'              => 'Faculty 2 Class',
            'date_needed'          => $dateNeeded,
            'expected_return_date' => $expectedReturn,
        ]);
        $resFac2->assertSessionHas('error');

        $fac2Req = EquipmentRequest::where('user_id', $faculty2->id)->first();
        $this->assertNotNull($fac2Req);
        $this->assertEquals('rejected', $fac2Req->status);
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

        // Verify items were processed and resolved
        $this->assertEquals('approved', $resPending->fresh()->status);
        $this->assertEquals('approved', $eqPending->fresh()->status);
        $this->assertEquals($stockBefore - 2, $equipment->fresh()->available_quantity);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_APPROVED_RESERVATION',
            'table_name' => 'facility_reservations',
            'record_id'  => $resPending->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_APPROVED_BORROW',
            'table_name' => 'equipment_requests',
            'record_id'  => $eqPending->id,
        ]);
    }
}
