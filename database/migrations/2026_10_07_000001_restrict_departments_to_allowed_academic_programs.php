<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $programs = [
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

        // 1. Ensure the 5 canonical departments exist or are updated
        $canonicalMap = [];
        foreach ($programs as $prog) {
            $dept = Department::where('code', $prog['code'])
                ->orWhere('name', $prog['name'])
                ->orWhere('name', $prog['code'])
                ->first();

            if ($dept) {
                $dept->update([
                    'code' => $prog['code'],
                    'name' => $prog['name'],
                ]);
            } else {
                $dept = Department::create([
                    'code' => $prog['code'],
                    'name' => $prog['name'],
                ]);
            }

            $canonicalMap[$prog['code']] = $dept->id;
        }

        $canonicalIds = array_values($canonicalMap);

        // 2. Safely remap users pointing to obsolete computer departments to BSIT
        $compDeptIds = Department::whereNotIn('id', $canonicalIds)
            ->where(function ($q) {
                $q->where('code', 'BSCS')
                  ->orWhere('code', 'CCS')
                  ->orWhere('name', 'like', '%Computer%')
                  ->orWhere('name', 'BSCS');
            })
            ->pluck('id');

        if ($compDeptIds->isNotEmpty() && isset($canonicalMap['BSIT'])) {
            User::whereIn('department_id', $compDeptIds)->update(['department_id' => $canonicalMap['BSIT']]);
        }

        // 3. Clear department_id for users in other non-academic departments (e.g. Administration, General Services)
        $otherObsoleteIds = Department::whereNotIn('id', $canonicalIds)->pluck('id');
        if ($otherObsoleteIds->isNotEmpty()) {
            User::whereIn('department_id', $otherObsoleteIds)->update(['department_id' => null]);
            // 4. Delete obsolete departments
            Department::whereIn('id', $otherObsoleteIds)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible if needed
    }
};
