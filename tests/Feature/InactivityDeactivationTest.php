<?php

namespace Tests\Feature;

use App\Console\Commands\DeactivateInactiveUsers;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Facility;
use App\Models\FacilityReservation;
use App\Models\Role;
use App\Models\User;
use App\Services\InactivityService;
use Carbon\Carbon;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InactivityDeactivationTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $studentRole;
    protected Role $facultyRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Fix reference time for deterministic testing
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00'));

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->adminRole   = Role::where('name', 'Administrator')->first();
        $this->studentRole = Role::where('name', 'Student')->first();
        $this->facultyRole = Role::where('name', 'Faculty')->first();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function createStudent(array $attributes = []): User
    {
        $createdAt = $attributes['created_at'] ?? now();
        unset($attributes['created_at']);

        $user = User::create(array_merge([
            'employee_id' => '24-' . str_pad((string) rand(100, 99999), 5, '0', STR_PAD_LEFT),
            'first_name'  => 'John',
            'last_name'   => 'Doe',
            'email'       => 'student_' . uniqid() . '@example.com',
            'password'    => Hash::make('secret123'),
            'role_id'     => $this->studentRole->id,
            'status'      => 'active',
        ], $attributes));

        if ($createdAt) {
            $user->timestamps = false;
            $user->created_at = $createdAt;
            $user->save();
            $user->timestamps = true;
        }

        return $user;
    }

    protected function createAdmin(array $attributes = []): User
    {
        $createdAt = $attributes['created_at'] ?? now();
        unset($attributes['created_at']);

        $user = User::create(array_merge([
            'employee_id' => str_pad((string) rand(10, 999), 3, '0', STR_PAD_LEFT),
            'first_name'  => 'Admin',
            'last_name'   => 'User',
            'email'       => 'admin_' . uniqid() . '@example.com',
            'password'    => Hash::make('secret123'),
            'role_id'     => $this->adminRole->id,
            'status'      => 'active',
        ], $attributes));

        if ($createdAt) {
            $user->timestamps = false;
            $user->created_at = $createdAt;
            $user->save();
            $user->timestamps = true;
        }

        return $user;
    }

    /**
     * 1. Active user with last access 15 days ago is auto-deactivated with reason 'inactivity' and audit entry.
     */
    public function test_active_user_last_access_15_days_ago_is_deactivated_with_audit_log(): void
    {
        $user = $this->createStudent([
            'created_at'       => now()->subDays(30),
            'last_login'       => now()->subDays(20),
            'last_activity_at' => now()->subDays(15),
        ]);

        Artisan::call('users:deactivate-inactive');

        $user->refresh();
        $this->assertEquals('inactive', $user->status);
        $this->assertEquals('inactivity', $user->deactivation_reason);
        $this->assertNotNull($user->deactivated_at);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_DEACTIVATED',
            'table_name' => 'users',
            'record_id'  => $user->id,
        ]);
    }

    /**
     * 2. Active user with last access 13 days ago is untouched.
     */
    public function test_active_user_last_access_13_days_ago_is_untouched(): void
    {
        $user = $this->createStudent([
            'created_at'       => now()->subDays(30),
            'last_login'       => now()->subDays(20),
            'last_activity_at' => now()->subDays(13),
        ]);

        Artisan::call('users:deactivate-inactive');

        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertNull($user->deactivation_reason);
        $this->assertNull($user->deactivated_at);
    }

    /**
     * 3. Never-logged-in user created 20 days ago is deactivated; created 3 days ago is untouched.
     */
    public function test_never_logged_in_user_reference_time_uses_created_at(): void
    {
        // Created 20 days ago, never logged in (last_login and last_activity_at are null)
        $oldUser = $this->createStudent([
            'last_login'       => null,
            'last_activity_at' => null,
            'created_at'       => now()->subDays(20),
        ]);

        // Created 3 days ago, never logged in
        $newUser = $this->createStudent([
            'last_login'       => null,
            'last_activity_at' => null,
            'created_at'       => now()->subDays(3),
        ]);

        Artisan::call('users:deactivate-inactive');

        $oldUser->refresh();
        $newUser->refresh();

        $this->assertEquals('inactive', $oldUser->status);
        $this->assertEquals('inactivity', $oldUser->deactivation_reason);

        $this->assertEquals('active', $newUser->status);
        $this->assertNull($newUser->deactivation_reason);
    }

    /**
     * 4. Administrator accounts and the last active admin are never deactivated.
     */
    public function test_administrators_and_last_active_admin_are_never_deactivated(): void
    {
        // Sole admin in system, inactive for 30 days
        $soleAdmin = $this->createAdmin([
            'created_at'       => now()->subDays(40),
            'last_login'       => now()->subDays(35),
            'last_activity_at' => now()->subDays(30),
        ]);

        Artisan::call('users:deactivate-inactive');

        $soleAdmin->refresh();
        $this->assertEquals('active', $soleAdmin->status);
        $this->assertNull($soleAdmin->deactivation_reason);

        // Add a second admin, also inactive for 25 days
        $secondAdmin = $this->createAdmin([
            'created_at'       => now()->subDays(40),
            'last_login'       => now()->subDays(30),
            'last_activity_at' => now()->subDays(25),
        ]);

        Artisan::call('users:deactivate-inactive');

        $soleAdmin->refresh();
        $secondAdmin->refresh();

        $this->assertEquals('active', $soleAdmin->status);
        $this->assertEquals('active', $secondAdmin->status);
    }

    /**
     * 5. User with borrowed equipment or upcoming approved booking is protected and skipped.
     */
    public function test_user_with_borrowed_equipment_or_upcoming_booking_is_skipped(): void
    {
        $userWithBorrowed = $this->createStudent([
            'created_at'       => now()->subDays(30),
            'last_activity_at' => now()->subDays(20),
        ]);

        $equipment = Equipment::create([
            'name'               => 'Laptop',
            'category'           => 'Electronics',
            'quantity'           => 5,
            'available_quantity' => 4,
            'status'             => 'available',
        ]);

        EquipmentRequest::create([
            'equipment_id'         => $equipment->id,
            'user_id'              => $userWithBorrowed->id,
            'quantity'             => 1,
            'purpose'              => 'Project',
            'date_needed'          => now()->subDays(5)->toDateString(),
            'expected_return_date' => now()->addDays(2)->toDateString(),
            'status'               => 'borrowed',
        ]);

        $userWithReservation = $this->createStudent([
            'created_at'       => now()->subDays(30),
            'last_activity_at' => now()->subDays(20),
        ]);

        $facility = Facility::create([
            'name'        => 'Conference Room A',
            'location'    => 'Main Bldg',
            'capacity'    => 20,
            'status'      => 'available',
        ]);

        FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => $userWithReservation->id,
            'title'            => 'Department Sync',
            'reservation_date' => now()->addDays(3)->toDateString(),
            'time_start'       => '10:00:00',
            'time_end'         => '12:00:00',
            'status'           => 'approved',
        ]);

        Artisan::call('users:deactivate-inactive');

        $userWithBorrowed->refresh();
        $userWithReservation->refresh();

        $this->assertEquals('active', $userWithBorrowed->status);
        $this->assertNull($userWithBorrowed->deactivation_reason);

        $this->assertEquals('active', $userWithReservation->status);
        $this->assertNull($userWithReservation->deactivation_reason);
    }

    /**
     * 6. Inactive and soft-deleted users are left untouched.
     */
    public function test_inactive_and_soft_deleted_users_are_untouched(): void
    {
        $inactiveUser = $this->createStudent([
            'status'           => 'inactive',
            'created_at'       => now()->subDays(30),
            'last_activity_at' => now()->subDays(25),
        ]);

        $deletedUser = $this->createStudent([
            'status'           => 'active',
            'created_at'       => now()->subDays(30),
            'last_activity_at' => now()->subDays(25),
        ]);
        $deletedUser->delete(); // Soft delete

        Artisan::call('users:deactivate-inactive');

        $inactiveUser->refresh();
        $this->assertEquals('inactive', $inactiveUser->status);
        $this->assertNull($inactiveUser->deactivation_reason);

        $this->assertNotNull(User::withTrashed()->find($deletedUser->id)->deleted_at);
        $this->assertNull(User::withTrashed()->find($deletedUser->id)->deactivation_reason);
    }

    /**
     * 7. Lazy check at login blocks a dormant user even without running the command.
     */
    public function test_lazy_check_at_login_deactivates_and_blocks_dormant_user(): void
    {
        $dormantUser = $this->createStudent([
            'employee_id'      => '24-11111',
            'password'         => Hash::make('secret123'),
            'created_at'       => now()->subDays(30),
            'last_login'       => now()->subDays(20),
            'last_activity_at' => now()->subDays(16),
            'status'           => 'active',
        ]);

        // Attempt login without running the artisan command
        $response = $this->post('/login', [
            'employee_id' => '24-11111',
            'password'    => 'secret123',
        ]);

        $response->assertSessionHas('error', 'Your account was deactivated after 14 days without activity. Please contact the administrator to reactivate it.');
        $this->assertGuest();

        $dormantUser->refresh();
        $this->assertEquals('inactive', $dormantUser->status);
        $this->assertEquals('inactivity', $dormantUser->deactivation_reason);

        // Verify audit log entry was written
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'AUTO_DEACTIVATED',
            'table_name' => 'users',
            'record_id'  => $dormantUser->id,
        ]);
    }

    /**
     * 8. Admin reactivation resets the activity timer, and the next command run does not deactivate them.
     */
    public function test_admin_reactivation_resets_timer_and_prevents_immediate_re_deactivation(): void
    {
        $admin = $this->createAdmin();

        $dormantUser = $this->createStudent([
            'created_at'          => now()->subDays(30),
            'last_login'          => now()->subDays(25),
            'last_activity_at'    => now()->subDays(20),
            'status'              => 'inactive',
            'deactivated_at'      => now()->subDays(5),
            'deactivation_reason' => 'inactivity',
        ]);

        // Admin updates status from inactive -> active
        $response = $this->actingAs($admin)
            ->put('/users/' . $dormantUser->id, [
                'first_name'  => $dormantUser->first_name,
                'last_name'   => $dormantUser->last_name,
                'email'       => $dormantUser->email,
                'employee_id' => $dormantUser->employee_id,
                'role_id'     => $dormantUser->role_id,
                'status'      => 'active',
            ]);

        $response->assertRedirect('/users/' . $dormantUser->id . '/edit');

        $dormantUser->refresh();
        $this->assertEquals('active', $dormantUser->status);
        $this->assertNull($dormantUser->deactivation_reason);
        $this->assertNull($dormantUser->deactivated_at);
        $this->assertEquals(now()->toDateTimeString(), $dormantUser->last_activity_at->toDateTimeString());

        // Verify REACTIVATED_USER audit log
        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'REACTIVATED_USER',
            'table_name' => 'users',
            'record_id'  => $dormantUser->id,
        ]);

        // Run nightly scheduled command
        Artisan::call('users:deactivate-inactive');

        $dormantUser->refresh();
        $this->assertEquals('active', $dormantUser->status);
        $this->assertNull($dormantUser->deactivation_reason);
    }

    /**
     * 9. --dry-run changes nothing; running the command twice gives idempotent results.
     */
    public function test_dry_run_changes_nothing_and_command_is_idempotent(): void
    {
        $user = $this->createStudent([
            'created_at'       => now()->subDays(30),
            'last_activity_at' => now()->subDays(18),
        ]);

        // Dry run
        Artisan::call('users:deactivate-inactive', ['--dry-run' => true]);

        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertNull($user->deactivation_reason);

        // First real run
        Artisan::call('users:deactivate-inactive');

        $user->refresh();
        $this->assertEquals('inactive', $user->status);
        $this->assertEquals('inactivity', $user->deactivation_reason);
        $deactivatedAt = $user->deactivated_at;

        // Second run: idempotent, should not fail or create duplicate audit entries
        Artisan::call('users:deactivate-inactive');

        $user->refresh();
        $this->assertEquals('inactive', $user->status);
        $this->assertEquals($deactivatedAt->toDateTimeString(), $user->deactivated_at->toDateTimeString());

        $this->assertEquals(1, AuditLog::where('action', 'AUTO_DEACTIVATED')->where('record_id', $user->id)->count());
    }

    /**
     * 10. Throttle writes last_activity_at at most once per 10 minutes.
     */
    public function test_activity_throttle_updates_last_activity_at_most_once_per_10_minutes(): void
    {
        $initialTime = now()->subMinutes(15);
        $user = $this->createStudent([
            'last_activity_at' => $initialTime,
        ]);

        // Authenticated request via CheckActiveUser middleware
        $this->actingAs($user)->get('/dashboard');

        $user->refresh();
        $firstUpdate = $user->last_activity_at;
        $this->assertTrue($firstUpdate->gt($initialTime));

        // Advance time by 5 minutes (< 10 min throttle)
        Carbon::setTestNow(now()->addMinutes(5));

        $this->actingAs($user)->get('/dashboard');

        $user->refresh();
        // Timestamp must NOT have changed
        $this->assertEquals($firstUpdate->toDateTimeString(), $user->last_activity_at->toDateTimeString());

        // Advance time by another 6 minutes (total 11 min > 10 min throttle)
        Carbon::setTestNow(now()->addMinutes(6));

        $this->actingAs($user)->get('/dashboard');

        $user->refresh();
        // Timestamp must have updated now
        $this->assertTrue($user->last_activity_at->gt($firstUpdate));
    }

    /**
     * 11. Self-reactivation when config flag inactivity.allow_self_reactivation is true.
     */
    public function test_self_reactivation_allows_dormant_user_to_login_with_correct_password(): void
    {
        config(['icoep.inactivity.allow_self_reactivation' => true]);

        $dormantUser = $this->createStudent([
            'employee_id'      => '24-22222',
            'password'         => Hash::make('secret123'),
            'created_at'       => now()->subDays(30),
            'last_activity_at' => now()->subDays(18),
            'status'           => 'active',
        ]);

        $response = $this->post('/login', [
            'employee_id' => '24-22222',
            'password'    => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($dormantUser);

        $dormantUser->refresh();
        $this->assertEquals('active', $dormantUser->status);
        $this->assertNull($dormantUser->deactivation_reason);
    }
}
