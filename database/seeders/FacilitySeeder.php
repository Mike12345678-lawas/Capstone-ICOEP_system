<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            ['name' => 'Main Auditorium',          'location' => 'Main Building, Ground Floor',      'capacity' => 500, 'description' => 'Large venue for school-wide events, programs, and assemblies. Equipped with professional sound system and stage lighting.'],
            ['name' => 'Conference Room A',         'location' => 'Admin Building, 2nd Floor',        'capacity' => 30,  'description' => 'Meeting room with LCD projector, whiteboard, and conference table. Ideal for department meetings.'],
            ['name' => 'Conference Room B',         'location' => 'Admin Building, 3rd Floor',        'capacity' => 20,  'description' => 'Smaller meeting room for focused group discussions and committee meetings.'],
            ['name' => 'Computer Laboratory 1',     'location' => 'CCS Building, Room 201',           'capacity' => 40,  'description' => '40-station computer lab with high-speed internet. Available for special classes and seminars.'],
            ['name' => 'Computer Laboratory 2',     'location' => 'CCS Building, Room 202',           'capacity' => 40,  'description' => 'Secondary computer lab for overflow and practical exams.'],
            ['name' => 'Multi-Purpose Hall',        'location' => 'PE Building',                       'capacity' => 200, 'description' => 'Versatile indoor space for sports activities, cultural performances, and exhibitions.'],
            ['name' => 'Covered Court',             'location' => 'Campus Grounds',                    'capacity' => 300, 'description' => 'Outdoor covered court for sports events, physical education classes, and large gatherings.'],
            ['name' => 'AVR (Audio-Visual Room)',   'location' => 'Library Building, 3rd Floor',       'capacity' => 60,  'description' => 'Equipped with surround sound, projector, and comfortable seating for film viewings and presentations.'],
        ];

        foreach ($facilities as $facility) {
            Facility::create(array_merge($facility, ['status' => 'available']));
        }
    }
}
