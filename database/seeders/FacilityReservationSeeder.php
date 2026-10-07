<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\FacilityReservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FacilityReservationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role_id', 1)->first() ?? User::first();
        $student = User::where('role_id', 4)->first() ?? $admin;
        $faculty = User::where('role_id', 3)->first() ?? $admin;

        $auditorium = Facility::where('name', 'Main Auditorium')->first() ?? Facility::first();
        $confRoom = Facility::where('name', 'Conference Room A')->first() ?? Facility::first();
        $court = Facility::where('name', 'Covered Court')->first() ?? Facility::first();

        // Only create if empty or missing demo reservations
        if (FacilityReservation::count() === 0) {
            FacilityReservation::create([
                'facility_id'      => $auditorium->id,
                'user_id'          => $student->id,
                'title'            => 'Student Council Leadership Summit',
                'description'      => 'Annual student leadership assembly and club officers meeting.',
                'reservation_date' => '2026-10-15',
                'time_start'       => '13:00:00',
                'time_end'         => '17:00:00',
                'status'           => 'pending',
                'remarks'          => 'Awaiting administrator review.',
                'approved_by'      => null,
            ]);

            FacilityReservation::create([
                'facility_id'      => $confRoom->id,
                'user_id'          => $faculty->id,
                'title'            => 'Faculty Academic Board Deliberation',
                'description'      => 'Quarterly department curriculum assessment and planning session.',
                'reservation_date' => '2026-10-12',
                'time_start'       => '09:00:00',
                'time_end'         => '12:00:00',
                'status'           => 'approved',
                'remarks'          => 'Approved by administration.',
                'approved_by'      => $admin->id,
            ]);

            FacilityReservation::create([
                'facility_id'      => $court->id,
                'user_id'          => $student->id,
                'title'            => 'Department Basketball Tryouts',
                'description'      => 'Preliminary roster selection for intramural team roster.',
                'reservation_date' => '2026-10-20',
                'time_start'       => '15:00:00',
                'time_end'         => '18:00:00',
                'status'           => 'pending',
                'remarks'          => 'Under evaluation.',
                'approved_by'      => null,
            ]);
        }
    }
}
