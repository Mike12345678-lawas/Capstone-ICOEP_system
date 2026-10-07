<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Resolve department IDs from the canonical academic programs
        $deptBSIT   = Department::where('code', 'BSIT')->first()?->id;
        $deptBSBAFM = Department::where('code', 'BSBA-FM')->first()?->id;

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
                'department_id' => $deptBSBAFM,
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
