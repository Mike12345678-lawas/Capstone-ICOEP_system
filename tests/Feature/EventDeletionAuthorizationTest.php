<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CustodianUnitSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDeletionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $faculty;
    protected User $staff1;
    protected User $staff2;
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
            'id_type'              => 'employee',
            'employee_id'          => 'ADM001',
            'first_name'           => 'Admin',
            'last_name'            => 'Officer',
            'email'                => 'admin@slmcb.edu.ph',
            'password'             => bcrypt('Password123'),
            'role_id'              => $adminRole->id,
            'department_id'        => 1,
            'status'               => 'active',
            'privacy_consent'      => true,
            'privacy_consent_date' => now(),
        ]);

        $this->faculty = User::create([
            'id_type'              => 'employee',
            'employee_id'          => 'FAC001',
            'first_name'           => 'Professor',
            'last_name'            => 'Smith',
            'email'                => 'faculty@slmcb.edu.ph',
            'password'             => bcrypt('Password123'),
            'role_id'              => $facultyRole->id,
            'department_id'        => 1,
            'status'               => 'active',
            'privacy_consent'      => true,
            'privacy_consent_date' => now(),
        ]);

        $this->staff1 = User::create([
            'id_type'              => 'employee',
            'employee_id'          => 'STF001',
            'first_name'           => 'StaffOne',
            'last_name'            => 'Member',
            'email'                => 'staff1@slmcb.edu.ph',
            'password'             => bcrypt('Password123'),
            'role_id'              => $staffRole->id,
            'department_id'        => 1,
            'status'               => 'active',
            'privacy_consent'      => true,
            'privacy_consent_date' => now(),
        ]);

        $this->staff2 = User::create([
            'id_type'              => 'employee',
            'employee_id'          => 'STF002',
            'first_name'           => 'StaffTwo',
            'last_name'            => 'Member',
            'email'                => 'staff2@slmcb.edu.ph',
            'password'             => bcrypt('Password123'),
            'role_id'              => $staffRole->id,
            'department_id'        => 1,
            'status'               => 'active',
            'privacy_consent'      => true,
            'privacy_consent_date' => now(),
        ]);

        $this->student = User::create([
            'id_type'              => 'student',
            'student_id'           => 'STU001',
            'first_name'           => 'Student',
            'last_name'            => 'Learner',
            'email'                => 'student@slmcb.edu.ph',
            'password'             => bcrypt('Password123'),
            'role_id'              => $studentRole->id,
            'department_id'        => 1,
            'status'               => 'active',
            'privacy_consent'      => true,
            'privacy_consent_date' => now(),
        ]);
    }

    protected function createEvent(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'title'            => 'Campus Tech Seminar',
            'objectives'       => 'Introduce students to modern web development frameworks.',
            'description'      => 'A seminar on software engineering practices.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(3)->format('Y-m-d'),
            'time_start'       => '09:00',
            'time_end'         => '12:00',
            'venue'            => 'Main Hall',
            'max_participants' => 50,
            'organizer_id'     => $this->faculty->id,
            'status'           => 'pending',
        ], $overrides));
    }

    /**
     * Admin deletes an event organized by someone else: success, event soft-deleted, audit log written.
     */
    public function test_admin_deletes_event_organized_by_someone_else(): void
    {
        $event = $this->createEvent([
            'organizer_id' => $this->faculty->id,
            'status'       => 'upcoming',
        ]);

        $response = $this->actingAs($this->admin)->delete("/events/{$event->id}");

        $response->assertRedirect('/dashboard?section=events');
        $response->assertSessionHas('success', 'Event deleted successfully.');

        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE_EVENT',
            'table_name' => 'events',
            'record_id'  => $event->id,
            'user_id'    => $this->admin->id,
        ]);
    }

    /**
     * Staff cannot delete an admin's event (403) and the event still exists.
     */
    public function test_staff_cannot_delete_admin_event(): void
    {
        $event = $this->createEvent([
            'organizer_id' => $this->admin->id,
            'status'       => 'upcoming',
        ]);

        $response = $this->actingAs($this->staff1)->delete("/events/{$event->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('events', [
            'id'         => $event->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Staff cannot delete another Staff member's pending event (403).
     */
    public function test_staff_cannot_delete_another_staff_pending_event(): void
    {
        $event = $this->createEvent([
            'organizer_id' => $this->staff2->id,
            'status'       => 'pending',
        ]);

        $response = $this->actingAs($this->staff1)->delete("/events/{$event->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('events', [
            'id'         => $event->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Faculty organizer can delete their own pending event.
     */
    public function test_faculty_organizer_can_delete_own_pending_event(): void
    {
        $event = $this->createEvent([
            'organizer_id' => $this->faculty->id,
            'status'       => 'pending',
        ]);

        $response = $this->actingAs($this->faculty)->delete("/events/{$event->id}");

        $response->assertRedirect('/dashboard?section=events');
        $response->assertSessionHas('success', 'Event deleted successfully.');

        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE_EVENT',
            'table_name' => 'events',
            'record_id'  => $event->id,
            'user_id'    => $this->faculty->id,
        ]);
    }

    /**
     * Faculty organizer can delete their own rejected event.
     */
    public function test_faculty_organizer_can_delete_own_rejected_event(): void
    {
        $event = $this->createEvent([
            'organizer_id' => $this->faculty->id,
            'status'       => 'rejected',
        ]);

        $response = $this->actingAs($this->faculty)->delete("/events/{$event->id}");

        $response->assertRedirect('/dashboard?section=events');
        $response->assertSessionHas('success', 'Event deleted successfully.');

        $this->assertSoftDeleted('events', ['id' => $event->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE_EVENT',
            'table_name' => 'events',
            'record_id'  => $event->id,
            'user_id'    => $this->faculty->id,
        ]);
    }

    /**
     * Faculty organizer cannot delete their own upcoming event (403).
     */
    public function test_faculty_organizer_cannot_delete_own_upcoming_event(): void
    {
        $event = $this->createEvent([
            'organizer_id' => $this->faculty->id,
            'status'       => 'upcoming',
        ]);

        $response = $this->actingAs($this->faculty)->delete("/events/{$event->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('events', [
            'id'         => $event->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Student cannot delete any event (403).
     */
    public function test_student_cannot_delete_any_event(): void
    {
        $event = $this->createEvent([
            'organizer_id' => $this->admin->id,
            'status'       => 'pending',
        ]);

        $response = $this->actingAs($this->student)->delete("/events/{$event->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('events', [
            'id'         => $event->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Staff can still open GET /events/{event}/manage and POST scan-qr (not 403),
     * to prove the scanner flow is unchanged.
     */
    public function test_staff_can_still_open_manage_and_post_scan_qr(): void
    {
        $event = $this->createEvent([
            'organizer_id' => $this->admin->id,
            'status'       => 'upcoming',
        ]);

        $manageResponse = $this->actingAs($this->staff1)->get("/events/{$event->id}/manage");
        $manageResponse->assertStatus(200);

        $qrCode = 'QR-STAFF-SCAN-TEST-001';
        EventRegistration::create([
            'event_id'      => $event->id,
            'user_id'       => $this->student->id,
            'qr_code'       => $qrCode,
            'registered_at' => now(),
            'has_attended'  => false,
        ]);

        $scanResponse = $this->actingAs($this->staff1)->postJson("/events/{$event->id}/scan-qr", [
            'qr_code' => $qrCode,
        ]);

        $scanResponse->assertStatus(200);
        $scanResponse->assertJson([
            'status' => 'success',
        ]);
    }
}
