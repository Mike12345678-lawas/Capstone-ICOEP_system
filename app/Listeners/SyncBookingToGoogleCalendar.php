<?php

namespace App\Listeners;

use App\Events\BookingApprovedByPhase5;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class SyncBookingToGoogleCalendar implements ShouldQueue
{
    use InteractsWithQueue;

    protected Google_Client $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(config('services.google.service_account_credentials'));
        $this->client->addScope(Google_Service_Calendar::CALENDAR_EVENTS);
        
        $impersonateEmail = config('services.google.calendar_impersonate_email');
        if ($impersonateEmail) {
            $this->client->setSubject($impersonateEmail);
        }
    }

    public function handle(BookingApprovedByPhase5 $event): void
    {
        $booking = $event->booking;

        try {
            $service = new Google_Service_Calendar($this->client);
            $calendarId = config('services.google.calendar_id');

            $googleEvent = new Google_Service_Calendar_Event([
                'summary' => "Booking: {$booking->title}",
                'location' => $booking->facility->name ?? 'TBD',
                'description' => "Approved by Phase 5 Solver.\nDetails: {$booking->description}",
                'start' => [
                    'dateTime' => $booking->start_time->toIso8601String(),
                    'timeZone' => config('app.timezone'),
                ],
                'end' => [
                    'dateTime' => $booking->end_time->toIso8601String(),
                    'timeZone' => config('app.timezone'),
                ],
                'attendees' => [
                    ['email' => $booking->user->email ?? 'unknown@example.com'],
                ],
            ]);

            $createdEvent = $service->events->insert($calendarId, $googleEvent);
            
            // Optionally update booking with GCal event ID
            // $booking->update(['google_calendar_event_id' => $createdEvent->getId()]);
            
            Log::info("Successfully synced booking {$booking->id} to Google Calendar.");
        } catch (\Exception $e) {
            Log::error("Failed to sync booking {$booking->id} to Google Calendar: " . $e->getMessage());
            $this->release(60); // Retry after 60 seconds
        }
    }
}
