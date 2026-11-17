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
}
