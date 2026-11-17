<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CustodianUnit;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Event;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Services\CustodianNotificationService;
use Carbon\Carbon;
use Database\Seeders\CustodianUnitSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffEventAndCustodianTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected User $student;
    protected CustodianUnit $libraryUnit;
    protected CustodianUnit $registrarUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            DepartmentSeeder::class,
            CustodianUnitSeeder::class,
        ]);

        $adminRole = Role::where('name', 'Administrator')->first();
        $staffRole = Role::where('name', 'Staff')->first();
        $studentRole = Role::where('name', 'Student')->first();

        $this->libraryUnit = CustodianUnit::where('code', 'library')->first();
        $this->registrarUnit = CustodianUnit::where('code', 'registrar')->first();

        $this->admin = User::create([
            'id_type'               => 'employee',
            'employee_id'           => '001',
            'first_name'            => 'Admin',
            'last_name'             => 'User',
            'email'                 => 'admin@slmcb.edu.ph',
            'password'              => bcrypt('Admin01a'),
            'role_id'               => $adminRole->id,
            'department_id'         => 1,
            'custodian_unit_id'     => null,
            'status'                => 'active',
            'privacy_consent'       => true,
            'privacy_consent_date'  => now(),
        ]);

        $this->staff = User::create([
            'id_type'               => 'employee',
            'employee_id'           => '021',
            'first_name'            => 'Staff',
            'last_name'             => 'One',
            'email'                 => 'staff1@slmcb.edu.ph',
            'password'              => bcrypt('Admin01a'),
            'role_id'               => $staffRole->id,
            'department_id'         => 1,
            'custodian_unit_id'     => $this->libraryUnit->id,
            'status'                => 'active',
            'privacy_consent'       => true,
            'privacy_consent_date'  => now(),
        ]);

        $this->student = User::create([
            'id_type'               => 'student',
            'student_id'            => '2024-0001',
            'first_name'            => 'Student',
            'last_name'             => 'User',
            'email'                 => 'student@slmcb.edu.ph',
            'password'              => bcrypt('Admin01a'),
            'role_id'               => $studentRole->id,
            'department_id'         => 1,
            'custodian_unit_id'     => null,
            'status'                => 'active',
            'privacy_consent'       => true,
            'privacy_consent_date'  => now(),
        ]);
    }

    /**
     * Test UserSeeder creates strictly 5 Staff users, each with a custodian unit.
     */
    public function test_seeder_produces_exactly_five_staff_users_each_with_a_custodian_unit(): void
    {
        $this->seed(UserSeeder::class);

        $staffUsers = User::whereHas('role', fn ($q) => $q->where('name', 'Staff'))->get();

        $this->assertCount(5, $staffUsers, 'The database should contain exactly 5 users with the Staff role.');

        foreach ($staffUsers as $staff) {
            $this->assertNotNull(
                $staff->custodian_unit_id,
                "Staff member {$staff->email} must be assigned to a custodian unit."
            );
            $this->assertNotNull(
                $staff->custodianUnit,
                "Staff member {$staff->email} must have a valid custodian unit relation."
            );
        }
    }

    /**
     * Test Staff login shows "Request Event" button and NOT "Create Event",
     * and shows NO announcement creation buttons.
     */
    public function test_staff_dashboard_shows_request_event_and_no_create_announcement(): void
    {
        // 1. Events Section
        $responseEvents = $this->actingAs($this->staff)->get('/dashboard?section=events');
        $responseEvents->assertStatus(200);
        $responseEvents->assertSee('Request Event');
        $responseEvents->assertDontSee('Create Event');

        // 2. Announcements Section
        $responseAnnouncements = $this->actingAs($this->staff)->get('/dashboard?section=announcements');
        $responseAnnouncements->assertStatus(200);
        $responseAnnouncements->assertDontSee('Post Announcement');
        $responseAnnouncements->assertDontSee('Create Announcement');
        $responseAnnouncements->assertDontSee('/announcements/create');
    }

    /**
     * Test Staff creating an event saves with status 'pending' and notifies Admin.
     */
    public function test_staff_event_creation_saves_as_pending_and_notifies_admin(): void
    {
        $payload = [
            'title'            => 'Department Workshop 2026',
            'description'      => 'A workshop organized by department staff members.',
            'objectives'       => 'Improve administrative operational efficiency.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(7)->format('Y-m-d'),
            'time_start'       => '09:00',
            'time_end'         => '12:00',
            'venue'            => 'Conference Room B',
            'max_participants' => 30,
        ];

        $response = $this->actingAs($this->staff)->post('/events', $payload);

        $event = Event::where('title', 'Department Workshop 2026')->first();
        $this->assertNotNull($event);
        $response->assertRedirect("/events/{$event->id}");
        $response->assertSessionHas('success', 'Event request submitted successfully! It is pending approval from the administrator.');
        $this->assertEquals(Event::STATUS_PENDING, $event->status);
        $this->assertNull($event->approved_by);
        $this->assertEquals($this->staff->id, $event->organizer_id);

        // Verify Admin received notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'title'   => '📅 New Event Request',
        ]);
    }

    /**
     * Test Admin can approve a pending event request, setting status to 'upcoming'
     * and notifying the Staff organizer.
     */
    public function test_admin_approves_event_request_and_notifies_organizer(): void
    {
        $event = Event::create([
            'title'            => 'Campus Coding Challenge',
            'description'      => 'Coding competition for students.',
            'objectives'       => 'Evaluate algorithmic problem solving.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(10)->format('Y-m-d'),
            'time_start'       => '13:00',
            'time_end'         => '17:00',
            'venue'            => 'ComLab 1',
            'max_participants' => 50,
            'organizer_id'     => $this->staff->id,
            'status'           => Event::STATUS_PENDING,
            'approved_by'      => null,
        ]);

        $response = $this->actingAs($this->admin)->patch("/events/{$event->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Event request approved successfully! Targeted campus members have been notified.');

        $event->refresh();
        $this->assertEquals(Event::STATUS_UPCOMING, $event->status);
        $this->assertEquals($this->admin->id, $event->approved_by);

        // Verify Staff organizer received notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->staff->id,
            'type'    => 'success',
            'title'   => 'Event Request Approved',
        ]);
    }

    /**
     * Test Admin can reject a pending event request with remarks, notifying the Staff organizer.
     */
    public function test_admin_rejects_event_request_and_notifies_organizer(): void
    {
        $event = Event::create([
            'title'            => 'Weekend Hackathon',
            'description'      => 'Weekend competition.',
            'objectives'       => 'Fast prototyping.',
            'event_type'       => 'academic',
            'target_audience'  => 'all',
            'event_date'       => Carbon::now()->addDays(5)->format('Y-m-d'),
            'time_start'       => '08:00',
            'time_end'         => '18:00',
            'venue'            => 'Auditorium',
            'max_participants' => 100,
            'organizer_id'     => $this->staff->id,
            'status'           => Event::STATUS_PENDING,
            'approved_by'      => null,
        ]);

        $response = $this->actingAs($this->admin)->patch("/events/{$event->id}/reject", [
            'remarks' => 'Venue already booked for university maintenance on that day.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Event request has been rejected.');

        $event->refresh();
        $this->assertEquals(Event::STATUS_REJECTED, $event->status);
        $this->assertEquals('Venue already booked for university maintenance on that day.', $event->remarks);

        // Verify Staff organizer received rejection notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->staff->id,
            'type'    => 'error',
            'title'   => 'Event Request Rejected',
        ]);
    }

    /**
     * Test when an equipment borrow request is approved, the Staff member
     * assigned to that custodian unit receives the pickup ready notification.
     */
    public function test_approved_equipment_request_notifies_assigned_staff_custodian(): void
    {
        // Equipment owned by Library
        $equipment = Equipment::create([
            'name'                => 'Epson Overhead Projector',
            'category'            => 'Electronics',
            'total_quantity'      => 5,
            'available_quantity'  => 5,
            'condition'           => 'good',
            'status'              => 'available',
            'custodian_unit_id'   => $this->libraryUnit->id,
        ]);

        // Student creates request
        $equipmentRequest = EquipmentRequest::create([
            'equipment_id'         => $equipment->id,
            'user_id'              => $this->student->id,
            'quantity'             => 1,
            'date_needed'          => Carbon::now()->addDay()->format('Y-m-d'),
            'expected_return_date' => Carbon::now()->addDays(2)->format('Y-m-d'),
            'time_start'           => '09:00',
            'time_end'             => '11:00',
            'purpose'              => 'Thesis presentation',
            'status'               => 'pending',
        ]);

        // Admin approves request
        $response = $this->actingAs($this->admin)->patch("/equipment-requests/{$equipmentRequest->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertRedirect();

        // Verify that Staff 1 (assigned to Library) received the "Ready for Pickup" notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->staff->id,
            'type'    => 'info',
            'title'   => '📦 Ready for Pickup: Epson Overhead Projector',
        ]);
    }

    /**
     * Test Staff maintains permissions to manage equipment, inventory, and equipment approval.
     */
    public function test_staff_has_equipment_and_inventory_management_permissions(): void
    {
        $staffRole = $this->staff->role;
        $permissions = $staffRole->permissions->pluck('name')->toArray();

        $this->assertContains('equipment.manage', $permissions, 'Staff must have equipment.manage permission.');
        $this->assertContains('equipment.approve', $permissions, 'Staff must have equipment.approve permission.');
        $this->assertContains('inventory.manage_own', $permissions, 'Staff must have inventory.manage_own permission.');
        $this->assertNotContains('announcement.create', $permissions, 'Staff must NOT have announcement.create permission.');
    }
}
