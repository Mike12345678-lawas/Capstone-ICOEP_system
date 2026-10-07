<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the canonical list of academic departments for SLMCB:
 * - BSIT: Bachelor of Science in Information Technology
 * - BSHM: Bachelor of Science in Hospitality Management
 * - BSBA-FM: Bachelor of Science in Business Administration major in Financial Management
 * - BSEd: Bachelor of Secondary Education
 * - BEEd: Bachelor of Elementary Education
 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            [
                'code' => 'BSIT',
                'name' => 'BSIT - Bachelor of Science in Information Technology',
            ],
            [
                'code' => 'BSHM',
                'name' => 'BSHM - Bachelor of Science in Hospitality Management',
            ],
            [
                'code' => 'BSBA-FM',
                'name' => 'BSBA-FM - Bachelor of Science in Business Administration major in Financial Management',
            ],
            [
                'code' => 'BSEd',
                'name' => 'BSEd - Bachelor of Secondary Education',
            ],
            [
                'code' => 'BEEd',
                'name' => 'BEEd - Bachelor of Elementary Education',
            ],
        ];

        // Ensure users assigned to obsolete departments are safely detached before deletion
        $allowedCodes = array_column($departments, 'code');
        $obsoleteDeptIds = Department::whereNotIn('code', $allowedCodes)->pluck('id');
        if ($obsoleteDeptIds->isNotEmpty()) {
            User::whereIn('department_id', $obsoleteDeptIds)->update(['department_id' => null]);
            Department::whereIn('id', $obsoleteDeptIds)->delete();
        }

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name']]
            );
        }
    }
}
