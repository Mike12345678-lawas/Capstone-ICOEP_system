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

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, DepartmentSeeder::class]);
    }

    public function test_departments_table_only_contains_the_five_canonical_programs(): void
    {
        $departments = Department::orderBy('id')->get();

        $this->assertCount(5, $departments);

        $expected = [
            'BSIT'    => 'BSIT - Bachelor of Science in Information Technology',
            'BSHM'    => 'BSHM - Bachelor of Science in Hospitality Management',
            'BSBA-FM' => 'BSBA-FM - Bachelor of Science in Business Administration major in Financial Management',
            'BSEd'    => 'BSEd - Bachelor of Secondary Education',
            'BEEd'    => 'BEEd - Bachelor of Elementary Education',
        ];

        foreach ($expected as $code => $name) {
            $dept = $departments->firstWhere('code', $code);
            $this->assertNotNull($dept, "Department with code {$code} should exist.");
            $this->assertEquals($name, $dept->name);
        }
    }

    public function test_user_create_page_displays_only_the_five_canonical_departments(): void
    {
        $adminRole = Role::where('name', 'Administrator')->first();
        $admin = User::factory()->create([
            'role_id'     => $adminRole->id,
            'status'      => 'active',
            'employee_id' => 'ADM-001',
        ]);

        $response = $this->actingAs($admin)->get('/users/create');
        $response->assertOk();

        // Must contain the 5 programs
        $response->assertSee('BSIT - Bachelor of Science in Information Technology');
        $response->assertSee('BSHM - Bachelor of Science in Hospitality Management');
        $response->assertSee('BSBA-FM - Bachelor of Science in Business Administration major in Financial Management');
        $response->assertSee('BSEd - Bachelor of Secondary Education');
        $response->assertSee('BEEd - Bachelor of Elementary Education');

        // Must NOT contain removed departments
        $response->assertDontSee('College of Arts and Sciences');
        $response->assertDontSee('College of Business');
        $response->assertDontSee('College of Computer Studies');
        $response->assertDontSee('College of Education');
        $response->assertDontSee('College of Engineering');
        $response->assertDontSee('College of Nursing');
        $response->assertDontSee('General Services');
        $response->assertDontSee('BSCS');
    }

    public function test_user_edit_page_displays_only_the_five_canonical_departments(): void
    {
        $adminRole = Role::where('name', 'Administrator')->first();
        $admin = User::factory()->create([
            'role_id'     => $adminRole->id,
            'status'      => 'active',
            'employee_id' => 'ADM-002',
        ]);

        $student = User::factory()->create([
            'role_id'     => Role::where('name', 'Student')->first()->id,
            'status'      => 'active',
            'employee_id' => 'STU-001',
        ]);

        $response = $this->actingAs($admin)->get("/users/{$student->id}/edit");
        $response->assertOk();

        $response->assertSee('BSIT - Bachelor of Science in Information Technology');
        $response->assertSee('BSHM - Bachelor of Science in Hospitality Management');
        $response->assertSee('BSBA-FM - Bachelor of Science in Business Administration major in Financial Management');
        $response->assertSee('BSEd - Bachelor of Secondary Education');
        $response->assertSee('BEEd - Bachelor of Elementary Education');

        $response->assertDontSee('College of Arts and Sciences');
        $response->assertDontSee('BSCS');
    }

    public function test_user_can_be_created_with_each_canonical_department(): void
    {
        $adminRole = Role::where('name', 'Administrator')->first();
        $studentRole = Role::where('name', 'Student')->first();
        $admin = User::factory()->create([
            'role_id'     => $adminRole->id,
            'status'      => 'active',
            'employee_id' => 'ADM-003',
        ]);

        $departments = Department::all();
        $index = 10;
        foreach ($departments as $dept) {
            $response = $this->actingAs($admin)->post('/users', [
                'first_name'            => 'Test',
                'last_name'             => 'User' . $index,
                'email'                 => "user{$index}@example.com",
                'employee_id'           => sprintf('24-%05d', $index),
                'department_id'         => $dept->id,
                'role_id'               => $studentRole->id,
                'status'                => 'active',
                'password'              => 'secr1234',
                'password_confirmation' => 'secr1234',
            ]);

            $response->assertRedirect('/users');
            $this->assertDatabaseHas('users', [
                'email'         => "user{$index}@example.com",
                'department_id' => $dept->id,
            ]);
            $index++;
        }
    }
}
