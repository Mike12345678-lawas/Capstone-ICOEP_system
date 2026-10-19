<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Facility
            ['name' => 'facility.view',    'description' => 'View facilities list'],
            ['name' => 'facility.reserve', 'description' => 'Submit facility reservation requests'],
            ['name' => 'facility.manage',  'description' => 'Legacy — superseded by facility.approve'],
            ['name' => 'facility.approve', 'description' => 'Approve/reject facility reservations (Admin only)'],
            // Equipment
            ['name' => 'equipment.view',   'description' => 'View equipment list'],
            ['name' => 'equipment.borrow', 'description' => 'Submit equipment borrow requests'],
            ['name' => 'equipment.manage', 'description' => 'Manage equipment inventory (add/edit/delete)'],
            ['name' => 'equipment.approve','description' => 'Approve/reject equipment requests (Admin only)'],
            ['name' => 'inventory.manage_own', 'description' => 'Manage own unit equipment inventory'],
            // Events
            ['name' => 'event.view',       'description' => 'View events list'],
            ['name' => 'event.create',     'description' => 'Create new events'],
            ['name' => 'event.manage',     'description' => 'Manage all events and attendance'],
            // Announcements
            ['name' => 'announcement.view',   'description' => 'View announcements'],
            ['name' => 'announcement.create', 'description' => 'Create new announcements'],
            ['name' => 'announcement.manage', 'description' => 'Manage all announcements'],
            ['name' => 'announcement.delete', 'description' => 'Delete announcements'],
            // Users
            ['name' => 'user.view',   'description' => 'View user list'],
            ['name' => 'user.create', 'description' => 'Create new users'],
            ['name' => 'user.edit',   'description' => 'Edit user accounts'],
            ['name' => 'user.delete', 'description' => 'Delete user accounts'],
            // Reports
            ['name' => 'report.view', 'description' => 'View system reports'],
            // Dashboard
            ['name' => 'dashboard.admin_stats', 'description' => 'View admin-level dashboard statistics'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm['name']], $perm);
        }

        // Assign permissions to roles
        $unitPermissions = [
            'facility.view', 'facility.reserve',
            'equipment.view', 'equipment.borrow',
            'event.view', 'announcement.view',
            'inventory.manage_own',
        ];

        $rolePermissions = [
            'Administrator' => Permission::pluck('name')->toArray(), // All permissions
            'Staff' => [
                'facility.view', 'facility.reserve',
                'equipment.view', 'equipment.borrow', 'equipment.manage',
                'event.view', 'event.create', 'event.manage',
                'announcement.view', 'announcement.create', 'announcement.manage', 'announcement.delete',
                'dashboard.admin_stats',
            ],
            'Faculty' => [
                'facility.view', 'facility.reserve',
                'equipment.view', 'equipment.borrow',
                'event.view', 'event.create',
                'announcement.view',
            ],
            'Student' => [
                'facility.view', 'facility.reserve',
                'equipment.view', 'equipment.borrow',
                'event.view',
                'announcement.view',
            ],
            'Registrar'         => $unitPermissions,
            'Librarian'         => $unitPermissions,
            'Maintenance Staff' => $unitPermissions,
            'ComLab Staff'      => $unitPermissions,
        ];

        foreach ($rolePermissions as $roleName => $permNames) {
            $role = Role::where('name', $roleName)->first();
            $permIds = Permission::whereIn('name', $permNames)->pluck('id');
            $role->permissions()->sync($permIds);
        }
    }
}
