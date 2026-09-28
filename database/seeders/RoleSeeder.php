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
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
