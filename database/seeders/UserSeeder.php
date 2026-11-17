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
        // Resolve department IDs from canonical academic programs
        $deptBSIT   = Department::where('code', 'BSIT')->first()?->id;
        $deptBSHM   = Department::where('code', 'BSHM')->first()?->id;
        $deptBSBAFM = Department::where('code', 'BSBA-FM')->first()?->id;
        $deptBSED   = Department::where('code', 'BSEd')->first()?->id ?? Department::whereRaw('UPPER(code) = ?', ['BSED'])->first()?->id;
        $deptBEED   = Department::where('code', 'BEEd')->first()?->id ?? Department::whereRaw('UPPER(code) = ?', ['BEED'])->first()?->id;

        // Resolve roles
        $roleAdmin       = Role::where('name', 'Administrator')->first()?->id ?? 1;
        $roleStaff       = Role::where('name', 'Staff')->first()?->id ?? 2;
        $roleFaculty     = Role::where('name', 'Faculty')->first()?->id ?? 3;
        $roleStudent     = Role::where('name', 'Student')->first()?->id ?? 4;
        $roleRegistrar   = Role::where('name', 'Registrar')->first()?->id;
        $roleLibrarian   = Role::where('name', 'Librarian')->first()?->id;
        $roleMaintenance = Role::where('name', 'Maintenance Staff')->first()?->id;
        $roleComLab      = Role::where('name', 'ComLab Staff')->first()?->id;

        $unitRegistrar   = CustodianUnit::where('code', 'registrar')->first()?->id;
        $unitLibrary     = CustodianUnit::where('code', 'library')->first()?->id;
        $unitMaintenance = CustodianUnit::where('code', 'maintenance')->first()?->id;
        $unitComLab      = CustodianUnit::where('code', 'comlab')->first()?->id;

        $users = [
            // ─── 1. ADMINISTRATORS (2 accounts) ───────────────────────────
            [
                'id_type'              => 'employee',
                'employee_id'          => '001',
                'first_name'           => 'System',
                'last_name'            => 'Administrator',
                'email'                => 'admin@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleAdmin,
                'department_id'        => null,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '008',
                'first_name'           => 'Administrator',
                'last_name'            => 'Two',
                'email'                => 'admin2@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleAdmin,
                'department_id'        => null,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],

            // ─── 2. STAFF MEMBERS (5 test accounts) ─────────────────────────
            [
                'id_type'              => 'employee',
                'employee_id'          => '021',
                'first_name'           => 'Staff',
                'last_name'            => 'One',
                'email'                => 'staff1@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleStaff,
                'department_id'        => null,
                'custodian_unit_id'    => $unitRegistrar,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '022',
                'first_name'           => 'Staff',
                'last_name'            => 'Two',
                'email'                => 'staff2@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleStaff,
                'department_id'        => null,
                'custodian_unit_id'    => $unitLibrary,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '023',
                'first_name'           => 'Staff',
                'last_name'            => 'Three',
                'email'                => 'staff3@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleStaff,
                'department_id'        => null,
                'custodian_unit_id'    => $unitMaintenance,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '024',
                'first_name'           => 'Staff',
                'last_name'            => 'Four',
                'email'                => 'staff4@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleStaff,
                'department_id'        => null,
                'custodian_unit_id'    => $unitComLab,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '025',
                'first_name'           => 'Staff',
                'last_name'            => 'Five',
                'email'                => 'staff5@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleStaff,
                'department_id'        => null,
                'custodian_unit_id'    => $unitComLab,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],

            // ─── 3. UNIT CUSTODIANS ────────────────────────────────────────
            [
                'id_type'              => 'employee',
                'employee_id'          => '004',
                'first_name'           => 'Roberto',
                'last_name'            => 'Flores',
                'email'                => 'registrar@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleRegistrar,
                'department_id'        => null,
                'custodian_unit_id'    => $unitRegistrar,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '005',
                'first_name'           => 'Elena',
                'last_name'            => 'Cruz',
                'email'                => 'librarian@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleLibrarian,
                'department_id'        => null,
                'custodian_unit_id'    => $unitLibrary,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '006',
                'first_name'           => 'Ramon',
                'last_name'            => 'Bautista',
                'email'                => 'maintenance@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleMaintenance,
                'department_id'        => null,
                'custodian_unit_id'    => $unitMaintenance,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '007',
                'first_name'           => 'Dexter',
                'last_name'            => 'Tan',
                'email'                => 'comlab@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleComLab,
                'department_id'        => null,
                'custodian_unit_id'    => $unitComLab,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],

            // ─── 4. FACULTY MEMBERS (5 accounts — 1 per department + original) ──
            [
                'id_type'              => 'employee',
                'employee_id'          => '003',
                'first_name'           => 'Juan',
                'last_name'            => 'Dela Cruz',
                'email'                => 'juan.delacruz@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleFaculty,
                'department_id'        => $deptBSIT,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '031',
                'first_name'           => 'Faculty',
                'last_name'            => 'BSIT',
                'email'                => 'faculty.bsit@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleFaculty,
                'department_id'        => $deptBSIT,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '032',
                'first_name'           => 'Faculty',
                'last_name'            => 'BSHM',
                'email'                => 'faculty.bshm@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleFaculty,
                'department_id'        => $deptBSHM,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '033',
                'first_name'           => 'Faculty',
                'last_name'            => 'BSBA',
                'email'                => 'faculty.bsba@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleFaculty,
                'department_id'        => $deptBSBAFM,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '034',
                'first_name'           => 'Faculty',
                'last_name'            => 'BSEd',
                'email'                => 'faculty.bsed@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleFaculty,
                'department_id'        => $deptBSED,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '035',
                'first_name'           => 'Faculty',
                'last_name'            => 'BEEd',
                'email'                => 'faculty.beed@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleFaculty,
                'department_id'        => $deptBEED,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],

            // ─── 5. TECHNICAL EVALUATORS (3 accounts) ─────────────────────
            // Assigned: IT Instructors -> Faculty (BSIT), IT Personnel -> ComLab Staff
            [
                'id_type'              => 'employee',
                'employee_id'          => '041',
                'first_name'           => 'IT Instructor',
                'last_name'            => 'One',
                'email'                => 'it.instructor1@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleFaculty,
                'department_id'        => $deptBSIT,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '042',
                'first_name'           => 'IT Instructor',
                'last_name'            => 'Two',
                'email'                => 'it.instructor2@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleFaculty,
                'department_id'        => $deptBSIT,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'employee',
                'employee_id'          => '043',
                'first_name'           => 'IT',
                'last_name'            => 'Personnel',
                'email'                => 'it.personnel@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleComLab,
                'department_id'        => $deptBSIT,
                'custodian_unit_id'    => $unitComLab,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],

            // ─── 6. ORIGINAL STUDENTS ──────────────────────────────────────
            [
                'id_type'              => 'student',
                'employee_id'          => '24-00001',
                'first_name'           => 'Ana',
                'last_name'            => 'Reyes',
                'email'                => 'ana.reyes@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleStudent,
                'department_id'        => $deptBSIT,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'student',
                'employee_id'          => '24-00002',
                'first_name'           => 'Carlos',
                'last_name'            => 'Garcia',
                'email'                => 'carlos.garcia@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleStudent,
                'department_id'        => $deptBSBAFM,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'student',
                'employee_id'          => '24-00003',
                'first_name'           => 'Bea',
                'last_name'            => 'Mendoza',
                'email'                => 'bea.mendoza@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleStudent,
                'department_id'        => $deptBSHM,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'student',
                'employee_id'          => '24-00004',
                'first_name'           => 'David',
                'last_name'            => 'Ramos',
                'email'                => 'david.ramos@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleStudent,
                'department_id'        => $deptBSED,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
            [
                'id_type'              => 'student',
                'employee_id'          => '24-00005',
                'first_name'           => 'Ella',
                'last_name'            => 'Villanueva',
                'email'                => 'ella.villanueva@slmcb.edu.ph',
                'password'             => 'Admin01a',
                'role_id'              => $roleStudent,
                'department_id'        => $deptBEED,
                'custodian_unit_id'    => null,
                'status'               => 'active',
                'login_attempts'       => 0,
                'locked_until'         => null,
                'last_activity_at'     => now(),
                'privacy_consent'      => false,
                'privacy_consent_date' => null,
            ],
        ];

        // ─── 7. STUDENTS (25 test accounts — 5 per department) ─────────────
        $departmentsMap = [
            'bsit' => ['dept_id' => $deptBSIT,   'code' => '01', 'name' => 'BSIT'],
            'bshm' => ['dept_id' => $deptBSHM,   'code' => '02', 'name' => 'BSHM'],
            'bsba' => ['dept_id' => $deptBSBAFM, 'code' => '03', 'name' => 'BSBA'],
            'bsed' => ['dept_id' => $deptBSED,   'code' => '04', 'name' => 'BSEd'],
            'beed' => ['dept_id' => $deptBEED,   'code' => '05', 'name' => 'BEEd'],
        ];

        $numberWords = [1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five'];

        foreach ($departmentsMap as $key => $deptInfo) {
            for ($i = 1; $i <= 5; $i++) {
                $studentNum = sprintf('24-%s%03d', $deptInfo['code'], $i); // e.g. 24-01001 to 24-01005
                $users[] = [
                    'id_type'              => 'student',
                    'employee_id'          => $studentNum,
                    'first_name'           => 'Student',
                    'last_name'            => $deptInfo['name'] . ' ' . $numberWords[$i],
                    'email'                => "student.{$key}{$i}@slmcb.edu.ph",
                    'password'             => 'Admin01a',
                    'role_id'              => $roleStudent,
                    'department_id'        => $deptInfo['dept_id'],
                    'custodian_unit_id'    => null,
                    'status'               => 'active',
                    'login_attempts'       => 0,
                    'locked_until'         => null,
                    'last_activity_at'     => now(),
                    'privacy_consent'      => false,
                    'privacy_consent_date' => null,
                ];
            }
        }

        // Clean up legacy Maria Santos if present so Staff role count is strictly five
        User::where('email', 'maria.santos@slmcb.edu.ph')->orWhere('employee_id', '002')->forceDelete();

        foreach ($users as $userData) {
            $user = User::where('email', $userData['email'])
                ->orWhere('employee_id', $userData['employee_id'])
                ->first();

            if ($user) {
                $user->update($userData);
            } else {
                User::create($userData);
            }
        }
    }
}
