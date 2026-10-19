<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'description' => 'Full system access and management capabilities'],
            ['name' => 'Staff', 'description' => 'Operational management of facilities and equipment'],
            ['name' => 'Faculty', 'description' => 'Teaching staff with reservation and event privileges'],
            ['name' => 'Student', 'description' => 'Basic campus service access'],
            ['name' => 'Registrar', 'description' => 'Registrar custodian managing official documents and records'],
            ['name' => 'Librarian', 'description' => 'Library custodian managing library resources and furniture'],
            ['name' => 'Maintenance Staff', 'description' => 'Maintenance custodian managing tools and repair equipment'],
            ['name' => 'ComLab Staff', 'description' => 'Computer laboratory custodian managing IT and media equipment'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
