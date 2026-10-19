<?php

namespace Database\Seeders;

use App\Models\CustodianUnit;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve department IDs from the canonical academic programs
        $deptBSIT   = Department::where('code', 'BSIT')->first()?->id;
        $deptBSBAFM = Department::where('code', 'BSBA-FM')->first()?->id;

        // Resolve custodian roles and units
        $roleRegistrar   = Role::where('name', 'Registrar')->first()?->id;
        $roleLibrarian   = Role::where('name', 'Librarian')->first()?->id;
        $roleMaintenance = Role::where('name', 'Maintenance Staff')->first()?->id;
        $roleComLab      = Role::where('name', 'ComLab Staff')->first()?->id;

        $unitRegistrar   = CustodianUnit::where('code', 'registrar')->first()?->id;
        $unitLibrary     = CustodianUnit::where('code', 'library')->first()?->id;
        $unitMaintenance = CustodianUnit::where('code', 'maintenance')->first()?->id;
        $unitComLab      = CustodianUnit::where('code', 'comlab')->first()?->id;

        $users = [
            [
                'id_type'       => 'employee',
                'employee_id'   => '001',
                'first_name'    => 'System',
                'last_name'     => 'Administrator',
                'email'         => 'admin@slmcb.edu.ph',
                'password'      => 'Admin01a',
                'role_id'       => 1,
                'department_id' => null,
                'custodian_unit_id' => null,
                'status'        => 'active',
                'privacy_consent' => true,
                'privacy_consent_date' => now(),
            ],
            [
                'id_type'       => 'employee',
                'employee_id'   => '002',
                'first_name'    => 'Maria',
                'last_name'     => 'Santos',
                'email'         => 'maria.santos@slmcb.edu.ph',
                'password'      => 'Admin01a',
                'role_id'       => 2,
                'department_id' => null,
                'custodian_unit_id' => null,
                'status'        => 'active',
                'privacy_consent' => true,
                'privacy_consent_date' => now(),
            ],
            [
                'id_type'       => 'employee',
                'employee_id'   => '003',
                'first_name'    => 'Juan',
                'last_name'     => 'Dela Cruz',
                'email'         => 'juan.delacruz@slmcb.edu.ph',
                'password'      => 'Admin01a',
                'role_id'       => 3,
                'department_id' => $deptBSIT,
                'custodian_unit_id' => null,
                'status'        => 'active',
                'privacy_consent' => true,
                'privacy_consent_date' => now(),
            ],
            // Unit Custodian Accounts (3-digit employee IDs)
            [
                'id_type'       => 'employee',
                'employee_id'   => '004',
                'first_name'    => 'Roberto',
                'last_name'     => 'Flores',
                'email'         => 'registrar@slmcb.edu.ph',
                'password'      => 'Admin01a',
                'role_id'       => $roleRegistrar,
                'department_id' => null,
                'custodian_unit_id' => $unitRegistrar,
                'status'        => 'active',
                'privacy_consent' => true,
                'privacy_consent_date' => now(),
            ],
            [
                'id_type'       => 'employee',
                'employee_id'   => '005',
                'first_name'    => 'Elena',
                'last_name'     => 'Cruz',
                'email'         => 'librarian@slmcb.edu.ph',
                'password'      => 'Admin01a',
                'role_id'       => $roleLibrarian,
                'department_id' => null,
                'custodian_unit_id' => $unitLibrary,
                'status'        => 'active',
                'privacy_consent' => true,
                'privacy_consent_date' => now(),
            ],
            [
                'id_type'       => 'employee',
                'employee_id'   => '006',
                'first_name'    => 'Ramon',
                'last_name'     => 'Bautista',
                'email'         => 'maintenance@slmcb.edu.ph',
                'password'      => 'Admin01a',
                'role_id'       => $roleMaintenance,
                'department_id' => null,
                'custodian_unit_id' => $unitMaintenance,
                'status'        => 'active',
                'privacy_consent' => true,
                'privacy_consent_date' => now(),
            ],
            [
                'id_type'       => 'employee',
                'employee_id'   => '007',
                'first_name'    => 'Dexter',
                'last_name'     => 'Tan',
                'email'         => 'comlab@slmcb.edu.ph',
                'password'      => 'Admin01a',
                'role_id'       => $roleComLab,
                'department_id' => null,
                'custodian_unit_id' => $unitComLab,
                'status'        => 'active',
                'privacy_consent' => true,
                'privacy_consent_date' => now(),
            ],
            [
                'id_type'       => 'student',
                'employee_id'   => '24-00001',
                'first_name'    => 'Ana',
                'last_name'     => 'Reyes',
                'email'         => 'ana.reyes@slmcb.edu.ph',
                'password'      => 'Admin01a',
                'role_id'       => 4,
                'department_id' => $deptBSIT,
                'custodian_unit_id' => null,
                'status'        => 'active',
                'privacy_consent' => true,
                'privacy_consent_date' => now(),
            ],
            [
                'id_type'       => 'student',
                'employee_id'   => '24-00002',
                'first_name'    => 'Carlos',
                'last_name'     => 'Garcia',
                'email'         => 'carlos.garcia@slmcb.edu.ph',
                'password'      => 'Admin01a',
                'role_id'       => 4,
                'department_id' => $deptBSBAFM,
                'custodian_unit_id' => null,
                'status'        => 'active',
                'privacy_consent' => true,
                'privacy_consent_date' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(['email' => $userData['email']], $userData);
        }
    }
}
