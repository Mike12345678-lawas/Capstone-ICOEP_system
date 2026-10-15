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

class UserDepartmentFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $studentRole;
    protected Role $facultyRole;
    protected User $admin;
    protected Department $deptA;
    protected Department $deptB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, DepartmentSeeder::class]);

        $this->adminRole   = Role::where('name', 'Administrator')->first();
        $this->studentRole = Role::where('name', 'Student')->first();
        $this->facultyRole = Role::where('name', 'Faculty')->first();

        $this->admin = User::factory()->create([
            'role_id'     => $this->adminRole->id,
            'status'      => 'active',
            'employee_id' => '001',
            'first_name'  => 'Admin',
            'last_name'   => 'User',
            'email'       => 'admin@example.com',
        ]);

        $this->deptA = Department::where('code', 'BSIT')->first();
        $this->deptB = Department::where('code', 'BSHM')->first();
    }

    public function test_filtering_by_department_only_shows_matching_users(): void
    {
        $userA = User::factory()->create([
            'first_name'    => 'Alice',
            'last_name'     => 'Wonder',
            'email'         => 'alice@example.com',
            'role_id'       => $this->studentRole->id,
            'department_id' => $this->deptA->id,
            'status'        => 'active',
            'employee_id'   => '24-00101',
        ]);

        $userB = User::factory()->create([
            'first_name'    => 'Bob',
            'last_name'     => 'Builder',
            'email'         => 'bob@example.com',
            'role_id'       => $this->studentRole->id,
            'department_id' => $this->deptB->id,
            'status'        => 'active',
            'employee_id'   => '24-00102',
        ]);

        $response = $this->actingAs($this->admin)->get('/dashboard?section=users&department=' . $this->deptA->id);

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertDontSee('Bob');

        $responseB = $this->actingAs($this->admin)->get('/dashboard?section=users&department=' . $this->deptB->id);
        $responseB->assertOk();
        $responseB->assertSee('Bob');
        $responseB->assertDontSee('Alice');
    }

    public function test_combining_department_with_role_narrows_results_correctly(): void
    {
        $studentInA = User::factory()->create([
            'first_name'    => 'StudentA',
            'last_name'     => 'One',
            'email'         => 'studenta@example.com',
            'role_id'       => $this->studentRole->id,
            'department_id' => $this->deptA->id,
            'status'        => 'active',
            'employee_id'   => '24-00103',
        ]);

        $facultyInA = User::factory()->create([
            'first_name'    => 'FacultyA',
            'last_name'     => 'Two',
            'email'         => 'facultya@example.com',
            'role_id'       => $this->facultyRole->id,
            'department_id' => $this->deptA->id,
            'status'        => 'active',
            'employee_id'   => '002',
        ]);

        $studentInB = User::factory()->create([
            'first_name'    => 'StudentB',
            'last_name'     => 'Three',
            'email'         => 'studentb@example.com',
            'role_id'       => $this->studentRole->id,
            'department_id' => $this->deptB->id,
            'status'        => 'active',
            'employee_id'   => '24-00104',
        ]);

        $response = $this->actingAs($this->admin)->get("/dashboard?section=users&department={$this->deptA->id}&role={$this->facultyRole->id}");

        $response->assertOk();
        $response->assertSee('FacultyA');
        $response->assertDontSee('StudentA');
        $response->assertDontSee('StudentB');
    }

    public function test_combining_department_with_search_narrows_results_correctly(): void
    {
        $userA1 = User::factory()->create([
            'first_name'    => 'UniqueSearchTerm',
            'last_name'     => 'MatchingDept',
            'email'         => 'term.deptA@example.com',
            'role_id'       => $this->studentRole->id,
            'department_id' => $this->deptA->id,
            'status'        => 'active',
            'employee_id'   => '24-00105',
        ]);

        $userA2 = User::factory()->create([
            'first_name'    => 'OtherPerson',
            'last_name'     => 'SameDept',
            'email'         => 'other.deptA@example.com',
            'role_id'       => $this->studentRole->id,
            'department_id' => $this->deptA->id,
            'status'        => 'active',
            'employee_id'   => '24-00106',
        ]);

        $userB = User::factory()->create([
            'first_name'    => 'UniqueSearchTerm',
            'last_name'     => 'OtherDept',
            'email'         => 'term.deptB@example.com',
            'role_id'       => $this->studentRole->id,
            'department_id' => $this->deptB->id,
            'status'        => 'active',
            'employee_id'   => '24-00107',
        ]);

        $response = $this->actingAs($this->admin)->get("/dashboard?section=users&department={$this->deptA->id}&search=UniqueSearchTerm");

        $response->assertOk();
        $response->assertSee('MatchingDept');
        $response->assertDontSee('SameDept');
        $response->assertDontSee('OtherDept');
    }

    public function test_user_without_user_view_permission_gets_403(): void
    {
        $student = User::factory()->create([
            'role_id'     => $this->studentRole->id,
            'status'      => 'active',
            'employee_id' => '24-00108',
        ]);

        $response = $this->actingAs($student)->get('/dashboard?section=users');
        $response->assertForbidden();

        $responseWithDept = $this->actingAs($student)->get('/dashboard?section=users&department=' . $this->deptA->id);
        $responseWithDept->assertForbidden();
    }
}
