<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTargetAudienceTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $facultyRole;
    protected Role $staffRole;
    protected Role $studentRole;
    protected Role $registrarRole;

    protected User $admin;
    protected User $facultyUser;
    protected User $staffUser;
    protected User $registrarUser;
    protected User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, DepartmentSeeder::class]);

        $this->adminRole = Role::where('name', 'Administrator')->first();
        $this->facultyRole = Role::where('name', 'Faculty')->first();
        $this->staffRole = Role::where('name', 'Staff')->first();
        $this->studentRole = Role::where('name', 'Student')->first();
        $this->registrarRole = Role::where('name', 'Registrar')->first();

        $dept = Department::first();

        $this->admin = User::factory()->create([
            'role_id'       => $this->adminRole->id,
            'department_id' => $dept?->id,
            'status'        => 'active',
            'employee_id'   => 'ADM-001',
            'first_name'    => 'System',
            'last_name'     => 'Admin',
            'email'         => 'admin@icoep.test',
        ]);

        $this->facultyUser = User::factory()->create([
            'role_id'       => $this->facultyRole->id,
            'department_id' => $dept?->id,
            'status'        => 'active',
            'employee_id'   => 'FAC-001',
            'first_name'    => 'Prof',
            'last_name'     => 'Santos',
            'email'         => 'faculty@icoep.test',
        ]);

        $this->staffUser = User::factory()->create([
            'role_id'       => $this->staffRole->id,
            'department_id' => $dept?->id,
            'status'        => 'active',
            'employee_id'   => 'STF-001',
            'first_name'    => 'Staff',
            'last_name'     => 'Dela Cruz',
            'email'         => 'staff@icoep.test',
        ]);

        $this->registrarUser = User::factory()->create([
            'role_id'       => $this->registrarRole->id,
            'department_id' => $dept?->id,
            'status'        => 'active',
            'employee_id'   => 'REG-001',
            'first_name'    => 'Reg',
            'last_name'     => 'Officer',
            'email'         => 'registrar@icoep.test',
        ]);

        $this->studentUser = User::factory()->create([
            'role_id'       => $this->studentRole->id,
            'department_id' => $dept?->id,
            'status'        => 'active',
            'employee_id'   => 'STU-001',
            'first_name'    => 'Student',
            'last_name'     => 'Reyes',
            'email'         => 'student@icoep.test',
        ]);
    }

    public function test_faculty_only_event_creation_notifies_only_faculty(): void
    {
        $response = $this->actingAs($this->admin)->post('/events', [
            'title'            => 'Faculty Research Assembly',
            'objectives'       => 'Review and approve upcoming curriculum syllabi and research proposals.',
            'event_type'       => 'academic',
            'target_audience'  => 'faculty',
            'event_date'       => now()->addDays(5)->format('Y-m-d'),
            'time_start'       => '09:00',
            'time_end'         => '12:00',
            'venue'            => 'Conference Hall A',
            'max_participants' => 50,
        ]);

        $event = Event::where('title', 'Faculty Research Assembly')->first();
        $this->assertNotNull($event);
        $this->assertSame('faculty', $event->target_audience);
        $response->assertRedirect('/events/' . $event->id);

        // Faculty user should receive notification
        $this->assertTrue(
            Notification::where('user_id', $this->facultyUser->id)
                ->where('title', 'like', '%Faculty%')
                ->where('link', '/events/' . $event->id)
                ->exists()
        );

        // Staff, Custodian (Registrar), and Student must NOT receive notification
        $this->assertFalse(Notification::where('user_id', $this->staffUser->id)->exists());
        $this->assertFalse(Notification::where('user_id', $this->registrarUser->id)->exists());
        $this->assertFalse(Notification::where('user_id', $this->studentUser->id)->exists());
        // Admin creator must NOT receive notification
        $this->assertFalse(Notification::where('user_id', $this->admin->id)->exists());
    }

    public function test_staff_only_event_creation_notifies_only_staff_and_custodians(): void
    {
        $this->actingAs($this->admin)->post('/events', [
            'title'            => 'Staff Logistics & Operations Alignment',
            'objectives'       => 'Standardize procurement and campus operations workflows.',
            'event_type'       => 'other',
            'target_audience'  => 'staff',
            'event_date'       => now()->addDays(6)->format('Y-m-d'),
            'time_start'       => '14:00',
            'time_end'         => '16:00',
            'venue'            => 'Admin Room 201',
            'max_participants' => 30,
        ]);

        $event = Event::where('title', 'Staff Logistics & Operations Alignment')->first();
        $this->assertNotNull($event);

        // Staff and Registrar (custodian staff) should receive notification
        $this->assertTrue(Notification::where('user_id', $this->staffUser->id)->where('link', '/events/' . $event->id)->exists());
        $this->assertTrue(Notification::where('user_id', $this->registrarUser->id)->where('link', '/events/' . $event->id)->exists());

        // Faculty and Student must NOT receive notification
        $this->assertFalse(Notification::where('user_id', $this->facultyUser->id)->exists());
        $this->assertFalse(Notification::where('user_id', $this->studentUser->id)->exists());
    }

    public function test_student_only_event_creation_notifies_only_students(): void
    {
        $this->actingAs($this->admin)->post('/events', [
            'title'            => 'Freshman Welcome Rally',
            'objectives'       => 'Orient freshmen students with student organizations and campus services.',
            'event_type'       => 'cultural',
            'target_audience'  => 'student',
            'event_date'       => now()->addDays(7)->format('Y-m-d'),
            'time_start'       => '10:00',
            'time_end'         => '12:00',
            'venue'            => 'Gymnasium',
            'max_participants' => 500,
        ]);

        $event = Event::where('title', 'Freshman Welcome Rally')->first();
        $this->assertNotNull($event);

        // Student receives notification
        $this->assertTrue(Notification::where('user_id', $this->studentUser->id)->where('link', '/events/' . $event->id)->exists());

        // Faculty, Staff, Registrar must NOT receive notification
        $this->assertFalse(Notification::where('user_id', $this->facultyUser->id)->exists());
        $this->assertFalse(Notification::where('user_id', $this->staffUser->id)->exists());
        $this->assertFalse(Notification::where('user_id', $this->registrarUser->id)->exists());
    }

    public function test_faculty_only_event_is_hidden_from_staff_and_students_on_dashboard(): void
    {
        $facultyEvent = Event::create([
            'title'            => 'Faculty Only Meeting',
            'objectives'       => 'Mandatory syllabus alignment for next academic semester.',
            'event_type'       => 'academic',
            'target_audience'  => 'faculty',
            'event_date'       => now()->addDays(3)->format('Y-m-d'),
            'time_start'       => '10:00',
            'time_end'         => '11:30',
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        $studentEvent = Event::create([
            'title'            => 'Student Only Workshop',
            'objectives'       => 'Study habits and leadership skills for student officers.',
            'event_type'       => 'cultural',
            'target_audience'  => 'student',
            'event_date'       => now()->addDays(4)->format('Y-m-d'),
            'time_start'       => '13:00',
            'time_end'         => '15:00',
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        $allEvent = Event::create([
            'title'            => 'Campus All Hands',
            'objectives'       => 'Campus-wide town hall meeting for all departments.',
            'event_type'       => 'other',
            'target_audience'  => 'all',
            'event_date'       => now()->addDays(5)->format('Y-m-d'),
            'time_start'       => '08:00',
            'time_end'         => '10:00',
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        // 1. Faculty checks dashboard
        $facultyResp = $this->actingAs($this->facultyUser)->get('/dashboard?section=events');
        $facultyResp->assertOk();
        $facultyResp->assertSee('Faculty Only Meeting');
        $facultyResp->assertSee('Campus All Hands');
        $facultyResp->assertDontSee('Student Only Workshop');

        // 2. Student checks dashboard
        $studentResp = $this->actingAs($this->studentUser)->get('/dashboard?section=events');
        $studentResp->assertOk();
        $studentResp->assertSee('Student Only Workshop');
        $studentResp->assertSee('Campus All Hands');
        $studentResp->assertDontSee('Faculty Only Meeting');

        // 3. Staff checks dashboard
        $staffResp = $this->actingAs($this->staffUser)->get('/dashboard?section=events');
        $staffResp->assertOk();
        $staffResp->assertSee('Campus All Hands');
        $staffResp->assertDontSee('Faculty Only Meeting');
        $staffResp->assertDontSee('Student Only Workshop');

        // 4. Admin checks dashboard
        $adminResp = $this->actingAs($this->admin)->get('/dashboard?section=events');
        $adminResp->assertOk();
        $adminResp->assertSee('Faculty Only Meeting');
        $adminResp->assertSee('Student Only Workshop');
        $adminResp->assertSee('Campus All Hands');
    }

    public function test_direct_access_to_different_audience_event_returns_403(): void
    {
        $facultyEvent = Event::create([
            'title'            => 'Dean and Faculty Council',
            'objectives'       => 'Exclusive faculty deliberation on academic honors.',
            'event_type'       => 'academic',
            'target_audience'  => 'faculty',
            'event_date'       => now()->addDays(3)->format('Y-m-d'),
            'time_start'       => '14:00',
            'time_end'         => '16:00',
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        // Faculty user can view
        $this->actingAs($this->facultyUser)
            ->get('/events/' . $facultyEvent->id)
            ->assertOk()
            ->assertSee('Faculty Only')
            ->assertSee('Dean and Faculty Council');

        // Student trying to directly access gets 403 Forbidden
        $this->actingAs($this->studentUser)
            ->get('/events/' . $facultyEvent->id)
            ->assertForbidden();

        // Staff trying to directly access gets 403 Forbidden
        $this->actingAs($this->staffUser)
            ->get('/events/' . $facultyEvent->id)
            ->assertForbidden();

        // Admin can view any event
        $this->actingAs($this->admin)
            ->get('/events/' . $facultyEvent->id)
            ->assertOk();
    }

    public function test_non_target_audience_cannot_register_for_restricted_event(): void
    {
        $facultyEvent = Event::create([
            'title'            => 'Faculty Workshop 2026',
            'objectives'       => 'Teaching pedagogy improvements and laboratory equipment safety.',
            'event_type'       => 'academic',
            'target_audience'  => 'faculty',
            'event_date'       => now()->addDays(5)->format('Y-m-d'),
            'time_start'       => '09:00',
            'time_end'         => '12:00',
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        // Student attempts to register
        $studentAttempt = $this->actingAs($this->studentUser)
            ->from('/dashboard?section=events')
            ->post('/events/' . $facultyEvent->id . '/register');

        $studentAttempt->assertSessionHas('error');
        $this->assertFalse(
            EventRegistration::where('event_id', $facultyEvent->id)
                ->where('user_id', $this->studentUser->id)
                ->exists()
        );

        // Faculty registers successfully
        $facultyAttempt = $this->actingAs($this->facultyUser)
            ->from('/events/' . $facultyEvent->id)
            ->post('/events/' . $facultyEvent->id . '/register');

        $facultyAttempt->assertSessionHas('success');
        $this->assertTrue(
            EventRegistration::where('event_id', $facultyEvent->id)
                ->where('user_id', $this->facultyUser->id)
                ->exists()
        );
    }

    public function test_calendar_api_filters_events_by_audience(): void
    {
        Event::create([
            'title'            => 'Faculty Seminar Calendar Test',
            'objectives'       => 'Curriculum and evaluation strategies for academic staff.',
            'event_type'       => 'academic',
            'target_audience'  => 'faculty',
            'event_date'       => now()->addDays(2)->format('Y-m-d'),
            'time_start'       => '09:00',
            'time_end'         => '11:00',
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        Event::create([
            'title'            => 'Student Festival Calendar Test',
            'objectives'       => 'Campus sports and cultural competition among student houses.',
            'event_type'       => 'sports',
            'target_audience'  => 'student',
            'event_date'       => now()->addDays(2)->format('Y-m-d'),
            'time_start'       => '13:00',
            'time_end'         => '16:00',
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        // Student calls calendar API
        $studentRes = $this->actingAs($this->studentUser)->getJson('/api/calendar/events');
        $studentRes->assertOk();
        $studentTitles = collect($studentRes->json())->pluck('title')->implode(' ');
        $this->assertStringContainsString('Student Festival Calendar Test', $studentTitles);
        $this->assertStringNotContainsString('Faculty Seminar Calendar Test', $studentTitles);

        // Faculty calls calendar API
        $facultyRes = $this->actingAs($this->facultyUser)->getJson('/api/calendar/events');
        $facultyRes->assertOk();
        $facultyTitles = collect($facultyRes->json())->pluck('title')->implode(' ');
        $this->assertStringContainsString('Faculty Seminar Calendar Test', $facultyTitles);
        $this->assertStringNotContainsString('Student Festival Calendar Test', $facultyTitles);
    }

    public function test_department_audience_event_creation_notifies_only_matching_department(): void
    {
        $bsitDept = Department::where('code', 'BSIT')->first();
        $bshmDept = Department::where('code', 'BSHM')->first();

        $bsitStudent = User::factory()->create([
            'role_id'       => $this->studentRole->id,
            'department_id' => $bsitDept->id,
            'status'        => 'active',
            'employee_id'   => 'IT-001',
            'email'         => 'it.student@icoep.test',
        ]);

        $bshmStudent = User::factory()->create([
            'role_id'       => $this->studentRole->id,
            'department_id' => $bshmDept->id,
            'status'        => 'active',
            'employee_id'   => 'HM-001',
            'email'         => 'hm.student@icoep.test',
        ]);

        $response = $this->actingAs($this->admin)->post('/events', [
            'title'            => 'BSIT Hackathon 2026',
            'objectives'       => 'Foster coding innovation and collaborative problem solving for IT students.',
            'event_type'       => 'academic',
            'target_audience'  => 'bsit',
            'event_date'       => now()->addDays(5)->format('Y-m-d'),
            'time_start'       => '08:00',
            'time_end'         => '17:00',
            'venue'            => 'Computer Laboratory 1',
            'max_participants' => 60,
        ]);

        $event = Event::where('title', 'BSIT Hackathon 2026')->first();
        $this->assertNotNull($event);
        $this->assertSame('bsit', $event->target_audience);
        $this->assertSame('BSIT Only', $event->target_audience_label);
        $response->assertRedirect('/events/' . $event->id);

        // BSIT student received notification
        $this->assertTrue(
            Notification::where('user_id', $bsitStudent->id)
                ->where('title', 'like', '%BSIT%')
                ->where('link', '/events/' . $event->id)
                ->exists()
        );

        // BSHM student must NOT receive notification
        $this->assertFalse(Notification::where('user_id', $bshmStudent->id)->exists());

        // Admin creator must NOT receive self notification
        $this->assertFalse(Notification::where('user_id', $this->admin->id)->exists());
    }

    public function test_department_audience_scoping_dashboard_and_direct_access(): void
    {
        $bsitDept = Department::where('code', 'BSIT')->first();
        $bshmDept = Department::where('code', 'BSHM')->first();

        $bsitStudent = User::factory()->create([
            'role_id'       => $this->studentRole->id,
            'department_id' => $bsitDept->id,
            'status'        => 'active',
            'employee_id'   => 'IT-002',
            'email'         => 'it2@icoep.test',
        ]);

        $bshmStudent = User::factory()->create([
            'role_id'       => $this->studentRole->id,
            'department_id' => $bshmDept->id,
            'status'        => 'active',
            'employee_id'   => 'HM-002',
            'email'         => 'hm2@icoep.test',
        ]);

        $bsitEvent = Event::create([
            'title'            => 'BSIT Tech Symposium',
            'objectives'       => 'Emerging trends in cloud architecture and machine learning.',
            'event_type'       => 'academic',
            'target_audience'  => 'bsit',
            'event_date'       => now()->addDays(4)->format('Y-m-d'),
            'time_start'       => '13:00',
            'time_end'         => '16:00',
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        $bshmEvent = Event::create([
            'title'            => 'BSHM Culinary Expo',
            'objectives'       => 'Masterclass in food presentation and banquet service operations.',
            'event_type'       => 'cultural',
            'target_audience'  => 'bshm',
            'event_date'       => now()->addDays(4)->format('Y-m-d'),
            'time_start'       => '10:00',
            'time_end'         => '14:00',
            'organizer_id'     => $this->admin->id,
            'status'           => 'upcoming',
        ]);

        // BSIT student dashboard view
        $itResp = $this->actingAs($bsitStudent)->get('/dashboard?section=events');
        $itResp->assertOk();
        $itResp->assertSee('BSIT Tech Symposium');
        $itResp->assertDontSee('BSHM Culinary Expo');

        // BSHM student dashboard view
        $hmResp = $this->actingAs($bshmStudent)->get('/dashboard?section=events');
        $hmResp->assertOk();
        $hmResp->assertSee('BSHM Culinary Expo');
        $hmResp->assertDontSee('BSIT Tech Symposium');

        // Direct show route: BSIT student can view BSIT event
        $this->actingAs($bsitStudent)->get('/events/' . $bsitEvent->id)
            ->assertOk()
            ->assertSee('BSIT Only');

        // Direct show route: BSHM student trying to view BSIT event gets 403 Forbidden
        $this->actingAs($bshmStudent)->get('/events/' . $bsitEvent->id)
            ->assertForbidden();

        // Registration: BSIT student can register for BSIT event
        $this->actingAs($bsitStudent)->post('/events/' . $bsitEvent->id . '/register')
            ->assertSessionHas('success');
        $this->assertTrue(EventRegistration::where('event_id', $bsitEvent->id)->where('user_id', $bsitStudent->id)->exists());

        // Registration: BSHM student blocked from registering for BSIT event
        $this->actingAs($bshmStudent)->post('/events/' . $bsitEvent->id . '/register')
            ->assertSessionHas('error');
        $this->assertFalse(EventRegistration::where('event_id', $bsitEvent->id)->where('user_id', $bshmStudent->id)->exists());
    }
}
