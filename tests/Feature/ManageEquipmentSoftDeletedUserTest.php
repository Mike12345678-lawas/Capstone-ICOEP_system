<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageEquipmentSoftDeletedUserTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $borrower;
    protected Equipment $equipment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class, DepartmentSeeder::class]);

        $adminRole = Role::where('name', 'Administrator')->first();
        $studentRole = Role::where('name', 'Student')->first();
        $dept = Department::first();

        $this->admin = User::create([
            'first_name'    => 'Admin',
            'last_name'     => 'Boss',
            'email'         => 'admin@icoep.test',
            'password'      => bcrypt('password'),
            'role_id'       => $adminRole->id,
            'department_id' => $dept->id,
            'employee_id'   => 'ADM-111',
            'status'        => 'active',
        ]);

        $this->borrower = User::create([
            'first_name'    => 'Soft',
            'last_name'     => 'DeletedBorrower',
            'email'         => 'softborrower@icoep.test',
            'password'      => bcrypt('password'),
            'role_id'       => $studentRole->id,
            'department_id' => $dept->id,
            'employee_id'   => 'STU-777',
            'status'        => 'active',
        ]);

        $this->equipment = Equipment::create([
            'name'               => 'Sony Projector 4K',
            'category'           => 'Electronics',
            'description'        => 'High resolution projector',
            'quantity'           => 5,
            'available_quantity' => 5,
            'status'             => 'available',
        ]);
    }

    public function test_manage_equipment_page_renders_cleanly_when_borrower_is_soft_deleted(): void
    {
        // Create an equipment request
        $request = EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $this->borrower->id,
            'quantity'             => 1,
            'purpose'              => 'Thesis presentation',
            'date_needed'          => now()->addDays(2),
            'expected_return_date' => now()->addDays(3),
            'status'               => 'pending',
        ]);

        // Soft delete the borrower
        $this->borrower->delete();
        $this->assertSoftDeleted('users', ['id' => $this->borrower->id]);

        // Access the manage equipment section
        $response = $this->actingAs($this->admin)->get('/dashboard?section=manage-equipment');

        $response->assertOk();
        $response->assertSee('Soft DeletedBorrower');
        $response->assertSee('Sony Projector 4K');
    }

    public function test_manage_equipment_page_handles_multiple_soft_deleted_borrowers(): void
    {
        // Second borrower
        $dept = Department::first();
        $studentRole = Role::where('name', 'Student')->first();
        $secondBorrower = User::create([
            'first_name'    => 'Another',
            'last_name'     => 'SoftDeletedUser',
            'email'         => 'another.soft@icoep.test',
            'password'      => bcrypt('password'),
            'role_id'       => $studentRole->id,
            'department_id' => $dept->id,
            'employee_id'   => 'STU-888',
            'status'        => 'active',
        ]);

        EquipmentRequest::create([
            'equipment_id'         => $this->equipment->id,
            'user_id'              => $secondBorrower->id,
            'quantity'             => 2,
            'purpose'              => 'Club activity',
            'date_needed'          => now()->addDays(1),
            'expected_return_date' => now()->addDays(4),
            'status'               => 'borrowed',
        ]);

        $secondBorrower->delete();
        $this->assertSoftDeleted('users', ['id' => $secondBorrower->id]);

        $response = $this->actingAs($this->admin)->get('/dashboard?section=manage-equipment');

        $response->assertOk();
        $response->assertSee('Another SoftDeletedUser');
        $response->assertSee('Deactivated');
    }
}
