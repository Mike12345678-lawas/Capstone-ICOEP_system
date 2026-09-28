<x-mail::message>
# Upcoming Reservation Reminder

Hello **{{ $reservation->user->first_name }}**,

This is a friendly reminder that you have an approved reservation scheduled for tomorrow.

**Facility:** {{ $reservation->facility->name }}  
**Date:** {{ $reservation->reservation_date->format('M d, Y') }}  
**Time:** {{ \Carbon\Carbon::parse($reservation->start_time)->format('g:i A') }} to {{ \Carbon\Carbon::parse($reservation->end_time)->format('g:i A') }}  
**Purpose:** {{ $reservation->purpose }}  

If you no longer need this facility, please cancel it through the dashboard so others can use it.

<x-mail::button :url="url('/dashboard?section=facilities')">
View Your Reservations
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
