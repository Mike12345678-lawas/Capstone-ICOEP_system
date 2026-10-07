<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationRemovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            DepartmentSeeder::class,
        ]);
    }

    public function test_get_register_returns_404_for_guests(): void
    {
        $response = $this->get('/register');

        $response->assertNotFound();
    }

    public function test_post_register_returns_404_for_guests(): void
    {
        $response = $this->post('/register', [
            'first_name'            => 'John',
            'last_name'             => 'Doe',
            'email'                 => 'john.doe@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertNotFound();
    }

    public function test_get_register_returns_404_for_authenticated_users(): void
    {
        $studentRole = Role::where('name', 'Student')->first();
        $user = User::create([
            'first_name'  => 'Test',
            'last_name'   => 'Student',
            'email'       => 'student@example.com',
            'password'    => bcrypt('password'),
            'role_id'     => $studentRole->id,
            'employee_id' => '24-00001',
            'status'      => 'active',
        ]);

        $response = $this->actingAs($user)->get('/register');

        $response->assertNotFound();
    }

    public function test_post_register_returns_404_for_authenticated_users(): void
    {
        $studentRole = Role::where('name', 'Student')->first();
        $user = User::create([
            'first_name'  => 'Test',
            'last_name'   => 'Student',
            'email'       => 'student@example.com',
            'password'    => bcrypt('password'),
            'role_id'     => $studentRole->id,
            'employee_id' => '24-00001',
            'status'      => 'active',
        ]);

        $response = $this->actingAs($user)->post('/register', [
            'first_name' => 'Someone',
            'last_name'  => 'Else',
        ]);

        $response->assertNotFound();
    }

    public function test_login_page_does_not_contain_register_wording_or_link(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('Register', false);
        $response->assertDontSee('/register');
        $response->assertSee('Need an account? Please contact the administrator.');
    }

    public function test_user_without_user_create_cannot_reach_users_create_or_post_users(): void
    {
        $studentRole = Role::where('name', 'Student')->first();
        $student = User::create([
            'first_name'  => 'Regular',
            'last_name'   => 'Student',
            'email'       => 'regular.student@example.com',
            'password'    => bcrypt('password'),
            'role_id'     => $studentRole->id,
            'employee_id' => '24-00002',
            'status'      => 'active',
        ]);

        // GET /users/create is protected by permission:user.create
        $getResponse = $this->actingAs($student)->get('/users/create');
        $getResponse->assertForbidden();

        // POST /users is protected by permission:user.create & StoreUserRequest authorization
        $postResponse = $this->actingAs($student)->post('/users', [
            'first_name'            => 'Hacker',
            'last_name'             => 'User',
            'email'                 => 'hacker@example.com',
            'employee_id'           => '24-99999',
            'role_id'               => $studentRole->id,
            'status'                => 'active',
            'password'              => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);
        $postResponse->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    public function test_administrator_can_reach_users_create_and_create_user(): void
    {
        $adminRole = Role::where('name', 'Administrator')->first();
        $studentRole = Role::where('name', 'Student')->first();
        $department = Department::first();

        $admin = User::create([
            'first_name'  => 'Admin',
            'last_name'   => 'Super',
            'email'       => 'admin.super@example.com',
            'password'    => bcrypt('password'),
            'role_id'     => $adminRole->id,
            'employee_id' => '001',
            'status'      => 'active',
        ]);

        // Administrator can view GET /users/create
        $getResponse = $this->actingAs($admin)->get('/users/create');
        $getResponse->assertOk();
        $getResponse->assertSee('Create User');

        // Administrator can create user via POST /users
        $postResponse = $this->actingAs($admin)->post('/users', [
            'first_name'            => 'Alice',
            'last_name'             => 'Wonderland',
            'email'                 => 'alice.created@example.com',
            'employee_id'           => '24-11223',
            'department_id'         => $department?->id,
            'role_id'               => $studentRole->id,
            'status'                => 'active',
            'password'              => 'secr1234',
            'password_confirmation' => 'secr1234',
        ]);

        $postResponse->assertRedirect('/users');
        $postResponse->assertSessionHas('success', 'User created successfully.');

        $this->assertDatabaseHas('users', [
            'email'      => 'alice.created@example.com',
            'first_name' => 'Alice',
            'last_name'  => 'Wonderland',
            'role_id'    => $studentRole->id,
            'status'     => 'active',
        ]);
    }

    public function test_user_edit_page_does_not_contain_locked_status_option(): void
    {
        $adminRole = Role::where('name', 'Administrator')->first();
        $studentRole = Role::where('name', 'Student')->first();

        $admin = User::create([
            'first_name'  => 'Admin',
            'last_name'   => 'Super',
            'email'       => 'admin.statuscheck@example.com',
            'password'    => bcrypt('password'),
            'role_id'     => $adminRole->id,
            'employee_id' => '002',
            'status'      => 'active',
        ]);

        $student = User::create([
            'first_name'  => 'Bob',
            'last_name'   => 'Student',
            'email'       => 'bob.editcheck@example.com',
            'password'    => bcrypt('password'),
            'role_id'     => $studentRole->id,
            'employee_id' => '24-22334',
            'status'      => 'active',
        ]);

        $response = $this->actingAs($admin)->get("/users/{$student->id}/edit");
        $response->assertOk();
        $response->assertSee('<option value="active"', false);
        $response->assertSee('<option value="inactive"', false);
        $response->assertDontSee('<option value="locked"', false);
        $response->assertDontSee('>Locked</option>', false);
    }

    public function test_user_update_rejects_locked_status_and_accepts_inactive(): void
    {
        $adminRole = Role::where('name', 'Administrator')->first();
        $studentRole = Role::where('name', 'Student')->first();

        $admin = User::create([
            'first_name'  => 'Admin',
            'last_name'   => 'Super',
            'email'       => 'admin.statuspost@example.com',
            'password'    => bcrypt('password'),
            'role_id'     => $adminRole->id,
            'employee_id' => '003',
            'status'      => 'active',
        ]);

        $student = User::create([
            'first_name'  => 'Charlie',
            'last_name'   => 'Student',
            'email'       => 'charlie.update@example.com',
            'password'    => bcrypt('password'),
            'role_id'     => $studentRole->id,
            'employee_id' => '24-33445',
            'status'      => 'active',
        ]);

        // Attempting to set status to 'locked' must fail validation
        $responseLocked = $this->actingAs($admin)->put("/users/{$student->id}", [
            'first_name'  => 'Charlie',
            'last_name'   => 'Student',
            'email'       => 'charlie.update@example.com',
            'employee_id' => '24-33445',
            'role_id'     => $studentRole->id,
            'status'      => 'locked',
        ]);
        $responseLocked->assertSessionHasErrors('status');
        $this->assertEquals('active', $student->fresh()->status);

        // Setting status to 'inactive' must succeed
        $responseInactive = $this->actingAs($admin)->put("/users/{$student->id}", [
            'first_name'  => 'Charlie',
            'last_name'   => 'Student',
            'email'       => 'charlie.update@example.com',
            'employee_id' => '24-33445',
            'role_id'     => $studentRole->id,
            'status'      => 'inactive',
        ]);
        $responseInactive->assertSessionHasNoErrors();
        $this->assertEquals('inactive', $student->fresh()->status);
    }
}
