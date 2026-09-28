<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * Seeds the canonical list of departments for SLMCB.
 * Run after the normalization migration.
 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Administration',              'code' => 'ADMIN'],
            ['name' => 'General Services',            'code' => 'GS'],
            ['name' => 'College of Computer Studies', 'code' => 'CCS'],
            ['name' => 'BSIT',                        'code' => 'BSIT'],
            ['name' => 'BSCS',                        'code' => 'BSCS'],
            ['name' => 'College of Business',         'code' => 'CB'],
            ['name' => 'College of Education',        'code' => 'CHED'],
            ['name' => 'College of Arts and Sciences','code' => 'CAS'],
            ['name' => 'College of Nursing',          'code' => 'CN'],
            ['name' => 'College of Engineering',      'code' => 'CE'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['name' => $dept['name']],
                ['code' => $dept['code']]
            );
        }
    }
}
