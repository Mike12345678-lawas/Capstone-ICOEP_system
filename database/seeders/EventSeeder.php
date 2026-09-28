<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title'            => 'ICOEP System Launch Demo',
                'description'      => 'Official launch demonstration of the Integrated Campus Operations and Engagement Portal. All students, faculty, and staff are invited to witness the unveiling of our new campus management system.',
                'event_type'       => 'academic',
                'event_date'       => now()->addDays(14)->format('Y-m-d'),
                'time_start'       => '09:00',
                'time_end'         => '12:00',
                'venue'            => 'Main Auditorium',
                'max_participants' => 200,
                'organizer_id'     => 1,
                'status'           => 'upcoming',
            ],
            [
                'title'            => 'Intramurals 2024',
                'description'      => 'Annual intramural sports competition. Events include basketball, volleyball, badminton, and table tennis. Teams from all departments are welcome to join.',
                'event_type'       => 'sports',
                'event_date'       => now()->addDays(30)->format('Y-m-d'),
                'time_start'       => '07:00',
                'time_end'         => '17:00',
                'venue'            => 'Covered Court & Multi-Purpose Hall',
                'max_participants' => 0,
                'organizer_id'     => 2,
                'status'           => 'upcoming',
            ],
            [
                'title'            => 'Tech Talk: AI in Education',
                'description'      => 'A seminar exploring the impact and potential of Artificial Intelligence in modern education. Guest speaker from the industry will share insights and best practices.',
                'event_type'       => 'academic',
                'event_date'       => now()->addDays(7)->format('Y-m-d'),
                'time_start'       => '13:00',
                'time_end'         => '16:00',
                'venue'            => 'AVR (Audio-Visual Room)',
                'max_participants' => 60,
                'organizer_id'     => 3,
                'status'           => 'upcoming',
            ],
            [
                'title'            => 'Campus Mass and Recollection',
                'description'      => 'Monthly spiritual gathering for the campus community. All are welcome to attend the Holy Mass followed by a recollection and reflection activity.',
                'event_type'       => 'spiritual',
                'event_date'       => now()->addDays(5)->format('Y-m-d'),
                'time_start'       => '08:00',
                'time_end'         => '10:00',
                'venue'            => 'Chapel',
                'max_participants' => 0,
                'organizer_id'     => 1,
                'status'           => 'upcoming',
            ],
        ];

        foreach ($events as $event) {
            Event::create($event);
        }
    }
}
