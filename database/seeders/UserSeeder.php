<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve department IDs from the normalized departments table
        $deptAdmin = Department::where('name', 'Administration')->first()?->id;
        $deptGS    = Department::where('name', 'General Services')->first()?->id;
        $deptCCS   = Department::where('name', 'College of Computer Studies')->first()?->id;
        $deptBSIT  = Department::where('name', 'BSIT')->first()?->id;
        $deptBSCS  = Department::where('name', 'BSCS')->first()?->id;

        $users = [
            [
                'id_type'       => 'employee',
                'employee_id'   => '001',
                'first_name'    => 'System',
                'last_name'     => 'Administrator',
                'email'         => 'admin@slmcb.edu.ph',
                'password'      => 'Admin01a',
                'role_id'       => 1,
                'department_id' => $deptAdmin,
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
                'department_id' => $deptGS,
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
                'department_id' => $deptCCS,
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
                'department_id' => $deptBSCS,
                'status'        => 'active',
                'privacy_consent' => true,
                'privacy_consent_date' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }
    }
}
