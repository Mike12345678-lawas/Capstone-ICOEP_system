<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $announcements = [
            [
                'title'     => 'Welcome to ICOEP!',
                'content'   => "The Integrated Campus Operations and Engagement Portal (ICOEP) is now live!\n\nThis platform centralizes campus facility reservations, equipment borrowing, event management, and announcements into a single, unified system.\n\nAll students, faculty, and staff are encouraged to explore the portal and familiarize themselves with its features.",
                'priority'  => 'important',
                'audience'  => 'all',
                'posted_by' => 1,
                'is_pinned' => true,
            ],
            [
                'title'     => 'Library Hours Extended for Finals Week',
                'content'   => "In preparation for final examinations, the library will be extending its operating hours:\n\n- Monday to Friday: 7:00 AM - 9:00 PM\n- Saturday: 8:00 AM - 5:00 PM\n\nStudents are reminded to bring their valid school IDs when using the library facilities. Study rooms can be reserved through the ICOEP system.",
                'priority'  => 'general',
                'audience'  => 'students',
                'posted_by' => 2,
                'is_pinned' => false,
            ],
            [
                'title'     => 'Urgent: System Maintenance Scheduled',
                'content'   => "Please be advised that the ICOEP system will undergo scheduled maintenance on the last Saturday of this month from 10:00 PM to 2:00 AM.\n\nDuring this period, the system will be temporarily unavailable. Please plan your reservations and submissions accordingly.\n\nWe apologize for any inconvenience.",
                'priority'  => 'urgent',
                'audience'  => 'all',
                'posted_by' => 1,
                'is_pinned' => false,
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::create($announcement);
        }
    }
}
