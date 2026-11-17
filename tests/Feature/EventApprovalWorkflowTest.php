<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CustodianUnit;
use App\Models\Department;
use App\Models\Event;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CustodianUnitSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $faculty;
    protected User $staff;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            DepartmentSeeder::class,
            CustodianUnitSeeder::class,
        ]);

        $adminRole   = Role::where('name', 'Administrator')->first();
        $facultyRole = Role::where('name', 'Faculty')->first();
        $staffRole   = Role::where('name', 'Staff')->first();
        $studentRole = Role::where('name', 'Student')->first();

        $this->admin = User::create([
            'id_type'               => 'employee',
            'employee_id'           => 'ADM001',
            'first_name'            => 'Admin',
            'last_name'             => 'Officer',
            'email'                 => 'admin@slmcb.edu.ph',
            'password'              => bcrypt('Password123'),
            'role_id'               => $adminRole->id,
            'department_id'         => 1,
            'status'                => 'active',
            'privacy_consent'       => true,
            'privacy_consent_date'  => now(),
        ]);

        $this->faculty = User::create([
            'id_type'               => 'employee',
            'employee_id'           => 'FAC001',
            'first_name'            => 'Professor',
            'last_name'             => 'Smith',
            'email'                 => 'faculty@slmcb.edu.ph',
            'password'              => bcrypt('Password123'),
            'role_id'               => $facultyRole->id,
            'department_id'         => 1,
            'status'                => 'active',
            'privacy_consent'       => true,
            'privacy_consent_date'  => now(),
        ]);

        $this->staff = User::create([
            'id_type'               => 'employee',
            'employee_id'           => 'STF001',
            'first_name'            => 'Staff',
            'last_name'             => 'Member',
            'email'                 => 'staff@slmcb.edu.ph',
            'password'              => bcrypt('Password123'),
            'role_id'               => $staffRole->id,
            'department_id'         => 1,
            'status'                => 'active',
            'privacy_consent'       => true,
            'privacy_consent_date'  => now(),
        ]);

        $this->student = User::create([
            'id_type'               => 'student',
            'student_id'            => 'STU001',
            'first_name'            => 'Student',
            'last_name'             => 'Learner',
            'email'                 => 'student@slmcb.edu.ph',
            'password'              => bcrypt('Password123'),
            'role_id'               => $studentRole->id,
            'department_id'         => 1,
            'status'                => 'active',
            'privacy_consent'       => true,
            'privacy_consent_date'  => now(),
        ]);
    }

    /**
     * Faculty requests an event; it is saved as pending and administrators are notified.
     */
    public function test_faculty_requests_event_and_admin_is_notified(): void
    {
        $payload = [
            'title'            => 'Annual Science Symposium',
            'description'      => 'A symposium discussing research and discoveries.',
            'objectives'       => 'Foster student research engagement and scientific awareness.',
            'event_type'       => 'academic',
            'target_audience'  => ['all'],
            'event_date'       => Carbon::now()->addDays(7)->format('Y-m-d'),
            'time_start'       => '09:00',
            'time_end'         => '12:00',
            'venue'            => 'Main Auditorium',
            'max_participants' => 120,
        ];

        $response = $this->actingAs($this->faculty)->post('/events', $payload);

        $event = Event::where('title', 'Annual Science Symposium')->first();
        $this->assertNotNull($event);
        $this->assertEquals(Event::STATUS_PENDING, $event->status);
        $this->assertNull($event->approved_by);
        $this->assertEquals($this->faculty->id, $event->organizer_id);

        $response->assertRedirect('/events/' . $event->id);
        $response->assertSessionHas('success');

        // Admin received notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'title'   => '📅 New Event Request',
        ]);
    }

    /**
     * Admin dashboard overview displays pending event requests with Quick Approve.
     */
    public function test_admin_dashboard_shows_pending_event_requests(): void
    {
        $event = Event::create([
            'title'            => 'Leadership Workshop',
            'description'      => 'Developing leadership skills.',
            'objectives'       => 'Enhance team management skills for student leaders.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(5)->format('Y-m-d'),
            'time_start'       => '10:00',
            'time_end'         => '13:00',
            'venue'            => 'Conference Room B',
            'max_participants' => 30,
            'organizer_id'     => $this->faculty->id,
            'status'           => Event::STATUS_PENDING,
            'approved_by'      => null,
        ]);

        // Overview
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Pending Event Requests');
        $response->assertSee('Leadership Workshop');
        $response->assertSee('Event Requests'); // Sidebar nav link

        // Events Section with pending filter
        $responseEvents = $this->actingAs($this->admin)->get('/dashboard?section=events&status=pending');
        $responseEvents->assertStatus(200);
        $responseEvents->assertSee('Leadership Workshop');
        $responseEvents->assertSee('Requested by:');
        $responseEvents->assertSee('Approve');
        $responseEvents->assertSee('Reject');
    }

    /**
     * Admin approves pending event; status changes to upcoming and organizer is notified.
     */
    public function test_admin_approves_pending_event(): void
    {
        $event = Event::create([
            'title'            => 'Hackathon 2026',
            'description'      => 'Campus coding sprint.',
            'objectives'       => 'Build working software solutions in 24 hours.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(14)->format('Y-m-d'),
            'time_start'       => '08:00',
            'time_end'         => '18:00',
            'venue'            => 'Computer Lab 3',
            'max_participants' => 50,
            'organizer_id'     => $this->faculty->id,
            'status'           => Event::STATUS_PENDING,
            'approved_by'      => null,
        ]);

        $response = $this->actingAs($this->admin)->patch("/events/{$event->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals(Event::STATUS_UPCOMING, $event->status);
        $this->assertEquals($this->admin->id, $event->approved_by);

        // Faculty organizer receives approval notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->faculty->id,
            'title'   => 'Event Request Approved',
        ]);
    }

    /**
     * Admin rejects pending event with remarks.
     */
    public function test_admin_rejects_pending_event_with_remarks(): void
    {
        $event = Event::create([
            'title'            => 'Overnight Film Screening',
            'description'      => 'Midnight movie screening.',
            'objectives'       => 'Cultural cinema appreciation and discussion.',
            'event_type'       => 'cultural',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(3)->format('Y-m-d'),
            'time_start'       => '20:00',
            'time_end'         => '23:00',
            'venue'            => 'Gymnasium',
            'max_participants' => 100,
            'organizer_id'     => $this->staff->id,
            'status'           => Event::STATUS_PENDING,
            'approved_by'      => null,
        ]);

        $response = $this->actingAs($this->admin)->patch("/events/{$event->id}/reject", [
            'remarks' => 'Facility cannot be reserved past 19:00 per campus safety policy.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $event->refresh();
        $this->assertEquals(Event::STATUS_REJECTED, $event->status);
        $this->assertEquals('Facility cannot be reserved past 19:00 per campus safety policy.', $event->remarks);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->staff->id,
            'title'   => 'Event Request Rejected',
        ]);
    }

    /**
     * Student without event.manage permission cannot approve or reject event requests.
     */
    public function test_student_cannot_approve_or_reject_event(): void
    {
        $event = Event::create([
            'title'            => 'Department Meetup',
            'description'      => 'Department social.',
            'objectives'       => 'Foster collaboration among academic departments.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(10)->format('Y-m-d'),
            'time_start'       => '14:00',
            'time_end'         => '16:00',
            'venue'            => 'Room 201',
            'max_participants' => 40,
            'organizer_id'     => $this->faculty->id,
            'status'           => Event::STATUS_PENDING,
            'approved_by'      => null,
        ]);

        $approveResponse = $this->actingAs($this->student)->patch("/events/{$event->id}/approve");
        $approveResponse->assertStatus(403);

        $rejectResponse = $this->actingAs($this->student)->patch("/events/{$event->id}/reject", [
            'remarks' => 'Unauthorized rejection',
        ]);
        $rejectResponse->assertStatus(403);

        $event->refresh();
        $this->assertEquals(Event::STATUS_PENDING, $event->status);
    }

    /**
     * Staff cannot approve, reject, or update status of events (Admin only).
     */
    public function test_staff_cannot_approve_reject_or_update_event_status(): void
    {
        $event = Event::create([
            'title'            => 'Staff Review Event',
            'description'      => 'Staff review.',
            'objectives'       => 'Test event permissions.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(12)->format('Y-m-d'),
            'time_start'       => '10:00',
            'time_end'         => '12:00',
            'venue'            => 'Auditorium',
            'max_participants' => 50,
            'organizer_id'     => $this->faculty->id,
            'status'           => Event::STATUS_PENDING,
            'approved_by'      => null,
        ]);

        // Staff tries to approve
        $this->actingAs($this->staff)->patch("/events/{$event->id}/approve")->assertStatus(403);

        // Staff tries to reject
        $this->actingAs($this->staff)->patch("/events/{$event->id}/reject", [
            'remarks' => 'Staff rejecting',
        ])->assertStatus(403);

        // Staff tries to updateStatus
        $this->actingAs($this->staff)->patch("/events/{$event->id}/status", [
            'status' => 'upcoming',
        ])->assertStatus(403);

        $this->assertEquals(Event::STATUS_PENDING, $event->fresh()->status);
    }

    /**
     * approve() and reject() only work when status is pending.
     */
    public function test_approve_and_reject_only_work_when_status_is_pending(): void
    {
        $event = Event::create([
            'title'            => 'Already Approved Event',
            'description'      => 'Desc.',
            'objectives'       => 'Objectives test.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(8)->format('Y-m-d'),
            'time_start'       => '10:00',
            'time_end'         => '12:00',
            'venue'            => 'Room 101',
            'max_participants' => 30,
            'organizer_id'     => $this->faculty->id,
            'status'           => Event::STATUS_UPCOMING,
            'approved_by'      => $this->admin->id,
        ]);

        // Trying to approve an upcoming event fails
        $approveRes = $this->actingAs($this->admin)->patch("/events/{$event->id}/approve");
        $approveRes->assertSessionHas('error');

        // Trying to reject an upcoming event fails
        $rejectRes = $this->actingAs($this->admin)->patch("/events/{$event->id}/reject", ['remarks' => 'Late reject']);
        $rejectRes->assertSessionHas('error');

        $this->assertEquals(Event::STATUS_UPCOMING, $event->fresh()->status);
    }

    /**
     * updateStatus() validation does not allow pending.
     */
    public function test_update_status_does_not_allow_pending(): void
    {
        $event = Event::create([
            'title'            => 'Status Test Event',
            'description'      => 'Desc.',
            'objectives'       => 'Objectives test.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(8)->format('Y-m-d'),
            'time_start'       => '10:00',
            'time_end'         => '12:00',
            'venue'            => 'Room 101',
            'max_participants' => 30,
            'organizer_id'     => $this->faculty->id,
            'status'           => Event::STATUS_UPCOMING,
            'approved_by'      => $this->admin->id,
        ]);

        $res = $this->actingAs($this->admin)->patch("/events/{$event->id}/status", [
            'status' => 'pending',
        ]);
        $res->assertSessionHasErrors('status');
    }

    /**
     * Unapproved events are hidden in isVisibleTo() from non-organizers even with all audience.
     */
    public function test_unapproved_events_are_hidden_from_non_organizers_even_with_all_audience(): void
    {
        $pendingEvent = Event::create([
            'title'            => 'Secret Unapproved Event',
            'description'      => 'Desc.',
            'objectives'       => 'Objectives test.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(8)->format('Y-m-d'),
            'time_start'       => '10:00',
            'time_end'         => '12:00',
            'venue'            => 'Room 101',
            'max_participants' => 30,
            'organizer_id'     => $this->faculty->id,
            'status'           => Event::STATUS_PENDING,
            'approved_by'      => null,
        ]);

        // Student and Staff cannot see it
        $this->assertFalse($pendingEvent->isVisibleTo($this->student));
        $this->assertFalse($pendingEvent->isVisibleTo($this->staff));

        // Organizer and Admin can see it
        $this->assertTrue($pendingEvent->isVisibleTo($this->faculty));
        $this->assertTrue($pendingEvent->isVisibleTo($this->admin));

        // Rejected event is also hidden
        $pendingEvent->update(['status' => Event::STATUS_REJECTED]);
        $this->assertFalse($pendingEvent->isVisibleTo($this->student));
        $this->assertTrue($pendingEvent->isVisibleTo($this->faculty));
        $this->assertTrue($pendingEvent->isVisibleTo($this->admin));
    }

    /**
     * Calendar masks reservation title and booker name for non-owners.
     */
    public function test_calendar_masks_personal_schedules_for_non_owners(): void
    {
        $facility = \App\Models\Facility::create([
            'name'     => 'Conference Hall',
            'location' => 'Building A',
            'capacity' => 100,
            'status'   => 'available',
        ]);

        $reservation = \App\Models\FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $this->faculty->id,
            'title'            => 'Private Strategy Meeting',
            'purpose'          => 'Confidential discussion',
            'reservation_date' => now()->addDays(3)->toDateString(),
            'time_start'       => '09:00:00',
            'time_end'         => '11:00:00',
            'status'           => 'approved',
        ]);

        // Student calls calendar: title and booker are masked
        $res = $this->actingAs($this->student)->getJson('/api/calendar/events');
        $res->assertOk();
        $items = collect($res->json());
        $calRes = $items->firstWhere('id', 'res_' . $reservation->id);
        $this->assertNotNull($calRes);
        $this->assertStringContainsString('Reserved', $calRes['title']);
        $this->assertStringNotContainsString('Private Strategy Meeting', $calRes['title']);
        $this->assertEquals('Reserved', $calRes['extendedProps']['booker']);

        // Owner (Faculty) calls calendar: full details visible
        $ownerRes = $this->actingAs($this->faculty)->getJson('/api/calendar/events');
        $ownerRes->assertOk();
        $ownerItems = collect($ownerRes->json());
        $ownerCalRes = $ownerItems->firstWhere('id', 'res_' . $reservation->id);
        $this->assertStringContainsString('Private Strategy Meeting', $ownerCalRes['title']);
        $this->assertStringContainsString($this->faculty->first_name, $ownerCalRes['extendedProps']['booker']);
    }

    /**
     * Staff has equipment.issue and equipment.return but cannot approve equipment.
     */
    public function test_staff_equipment_issue_and_return_flow(): void
    {
        $equipment = \App\Models\Equipment::create([
            'name'               => 'Epson Projector',
            'category'           => 'Electronics',
            'quantity'           => 5,
            'available_quantity' => 5,
            'status'             => 'available',
        ]);

        $eqReq = \App\Models\EquipmentRequest::create([
            'equipment_id'         => $equipment->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'purpose'              => 'Class Presentation',
            'date_needed'          => now()->addDays(2)->toDateString(),
            'expected_return_date' => now()->addDays(5)->toDateString(),
            'status'               => 'pending',
        ]);

        // Staff cannot approve equipment request (returns 403)
        $this->actingAs($this->staff)->patch("/equipment-requests/{$eqReq->id}/status", [
            'status' => 'approved',
        ])->assertStatus(403);

        // Admin approves it
        $this->actingAs($this->admin)->patch("/equipment-requests/{$eqReq->id}/status", [
            'status' => 'approved',
        ])->assertRedirect();
        $this->assertEquals('approved', $eqReq->fresh()->status);

        // Staff can issue approved equipment for pickup via pickup route
        $issueRes = $this->actingAs($this->staff)->post("/equipment-requests/{$eqReq->id}/pickup");
        $issueRes->assertRedirect();
        $this->assertEquals('borrowed', $eqReq->fresh()->status);

        // Staff can confirm return
        $returnRes = $this->actingAs($this->staff)->post("/equipment-requests/{$eqReq->id}/confirm-return", [
            'condition'   => 'good',
            'return_type' => 'full',
        ]);
        $returnRes->assertRedirect();
        $this->assertEquals('returned', $eqReq->fresh()->status);
    }
}
