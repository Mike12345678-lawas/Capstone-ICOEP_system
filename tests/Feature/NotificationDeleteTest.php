<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $alice;
    protected User $bob;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, DepartmentSeeder::class]);

        $role = Role::where('name', 'Student')->first();
        $dept = Department::first();

        $make = fn ($first, $email, $id) => User::create([
            'first_name' => $first, 'last_name' => 'Test', 'email' => $email,
            'password' => bcrypt('password'), 'role_id' => $role->id,
            'department_id' => $dept->id, 'employee_id' => $id, 'status' => 'active',
        ]);

        $this->alice = $make('Alice', 'alice@icoep.test', 'STU-101');
        $this->bob   = $make('Bob', 'bob2@icoep.test', 'STU-102');
    }

    public function test_user_can_delete_own_notification(): void
    {
        $n = Notification::send($this->alice->id, 'Hi', 'Message');

        $this->actingAs($this->alice)
            ->deleteJson("/api/notifications/{$n->id}")
            ->assertOk()
            ->assertJson(['success' => true, 'total_count' => 0]);

        $this->assertDatabaseMissing('notifications', ['id' => $n->id]);
    }

    public function test_user_cannot_delete_someone_elses_notification(): void
    {
        $n = Notification::send($this->bob->id, 'Private', 'For Bob');

        $this->actingAs($this->alice)
            ->deleteJson("/api/notifications/{$n->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('notifications', ['id' => $n->id]);
    }

    public function test_clear_all_only_deletes_current_users_notifications(): void
    {
        foreach (range(1, 3) as $i) {
            Notification::send($this->alice->id, "A{$i}", 'msg');
        }
        Notification::send($this->bob->id, 'B1', 'msg');

        $this->actingAs($this->alice)
            ->deleteJson('/api/notifications')
            ->assertOk()
            ->assertJson(['deleted' => 3, 'unread_count' => 0, 'total_count' => 0]);

        $this->assertEquals(0, Notification::where('user_id', $this->alice->id)->count());
        $this->assertEquals(1, Notification::where('user_id', $this->bob->id)->count());
    }

    public function test_clear_read_only_keeps_unread(): void
    {
        Notification::send($this->alice->id, 'Read', 'msg')->update(['is_read' => true]);
        Notification::send($this->alice->id, 'Unread', 'msg');

        $this->actingAs($this->alice)
            ->deleteJson('/api/notifications?only=read')
            ->assertOk()
            ->assertJson(['deleted' => 1, 'unread_count' => 1, 'total_count' => 1]);
    }

    public function test_guest_cannot_delete_notifications(): void
    {
        $n = Notification::send($this->alice->id, 'Hi', 'msg');

        $this->deleteJson("/api/notifications/{$n->id}")->assertUnauthorized();
        $this->assertDatabaseHas('notifications', ['id' => $n->id]);
    }

    public function test_prune_command_removes_old_read_and_caps_per_user(): void
    {
        $old = Notification::send($this->alice->id, 'Old read', 'msg');
        $old->forceFill(['is_read' => true, 'created_at' => now()->subDays(40)])->save();

        $oldUnread = Notification::send($this->alice->id, 'Old unread', 'msg');
        $oldUnread->forceFill(['created_at' => now()->subDays(40)])->save();

        foreach (range(1, 8) as $i) {
            Notification::send($this->bob->id, "B{$i}", 'msg');
        }

        $this->artisan('notifications:prune', ['--keep' => 5])->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['id' => $old->id]);
        $this->assertDatabaseHas('notifications', ['id' => $oldUnread->id]); // unread & < 90 days kept
        $this->assertEquals(5, Notification::where('user_id', $this->bob->id)->count());
        $this->assertDatabaseHas('notifications', ['user_id' => $this->bob->id, 'title' => 'B8']); // newest kept
    }
}
