<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingApprovedByPhase5
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }
}
