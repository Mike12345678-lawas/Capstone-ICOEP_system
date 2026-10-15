<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManageAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $studentRole;
    protected User $admin;
    protected User $student1;
    protected User $student2;
    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, DepartmentSeeder::class]);

        $this->adminRole = Role::where('name', 'Administrator')->first();
        $this->studentRole = Role::where('name', 'Student')->first();

        $this->admin = User::factory()->create([
            'role_id'     => $this->adminRole->id,
            'status'      => 'active',
            'employee_id' => 'ADMIN-001',
            'first_name'  => 'System',
            'last_name'   => 'Admin',
            'email'       => 'admin@icoep.test',
        ]);

        $dept = Department::first();

        $this->student1 = User::factory()->create([
            'role_id'       => $this->studentRole->id,
            'department_id' => $dept?->id,
            'status'        => 'active',
            'employee_id'   => 'STU-001',
            'first_name'    => 'Ana',
            'last_name'     => 'Reyes',
            'email'         => 'ana.reyes@slmcb.edu.ph',
        ]);

        $this->student2 = User::factory()->create([
            'role_id'       => $this->studentRole->id,
            'department_id' => $dept?->id,
            'status'        => 'active',
            'employee_id'   => 'STU-002',
            'first_name'    => 'Carlos',
            'last_name'     => 'Garcia',
            'email'         => 'carlos.garcia@slmcb.edu.ph',
        ]);

        $this->event = Event::create([
            'title'            => 'Campus Mass and Recollection',
            'description'      => 'Annual spiritual recollection.',
            'objectives'       => 'Spiritual renewal.',
            'event_type'       => 'spiritual',
            'event_date'       => now()->addDays(5)->toDateString(),
            'time_start'       => '09:00:00',
            'time_end'         => '12:00:00',
            'venue'            => 'Main Chapel',
            'max_participants' => 100,
            'status'           => 'upcoming',
            'organizer_id'     => $this->admin->id,
        ]);

        EventRegistration::create([
            'event_id'     => $this->event->id,
            'user_id'      => $this->student1->id,
            'has_attended' => false,
            'registered_at'=> now()->subDay(),
        ]);

        EventRegistration::create([
            'event_id'     => $this->event->id,
            'user_id'      => $this->student2->id,
            'has_attended' => false,
            'registered_at'=> now()->subHours(12),
        ]);
    }

    public function test_event_manage_page_renders_responsive_elements_and_participants(): void
    {
        $response = $this->actingAs($this->admin)->get('/events/' . $this->event->id . '/manage');

        $response->assertStatus(200);
        $response->assertSee('Manage: Campus Mass and Recollection');
        $response->assertSee('Registered Participants (2)');
        $response->assertSee('Ana Reyes');
        $response->assertSee('Carlos Garcia');
        $response->assertSee('participant-table');
        $response->assertSee('table-scroll-wrapper');
        $response->assertSee('view-mode-toggle');
        $response->assertSee('participant-search');
        $response->assertSee('Save Attendance');
    }

    public function test_attendance_can_be_marked_for_selected_participants(): void
    {
        $response = $this->actingAs($this->admin)->post('/events/' . $this->event->id . '/attendance', [
            'user_ids' => [$this->student1->id],
        ]);

        $response->assertRedirect();
        $this->assertTrue(
            EventRegistration::where('event_id', $this->event->id)
                ->where('user_id', $this->student1->id)
                ->value('has_attended')
        );
        $this->assertFalse(
            EventRegistration::where('event_id', $this->event->id)
                ->where('user_id', $this->student2->id)
                ->value('has_attended')
        );
    }

    public function test_attendance_can_be_saved_with_all_absent_without_validation_error(): void
    {
        // First mark student1 as attended
        EventRegistration::where('event_id', $this->event->id)
            ->where('user_id', $this->student1->id)
            ->update(['has_attended' => true]);

        // Submit form with no attendees selected (simulating unchecking all)
        $response = $this->actingAs($this->admin)->post('/events/' . $this->event->id . '/attendance', []);

        $response->assertRedirect();
        $this->assertFalse(
            EventRegistration::where('event_id', $this->event->id)
                ->where('user_id', $this->student1->id)
                ->value('has_attended')
        );
        $this->assertFalse(
            EventRegistration::where('event_id', $this->event->id)
                ->where('user_id', $this->student2->id)
                ->value('has_attended')
        );
    }

    public function test_event_status_can_be_updated(): void
    {
        $response = $this->actingAs($this->admin)->patch('/events/' . $this->event->id . '/status', [
            'status' => 'ongoing',
        ]);

        $response->assertRedirect();
        $this->assertEquals('ongoing', $this->event->fresh()->status);
    }
}
