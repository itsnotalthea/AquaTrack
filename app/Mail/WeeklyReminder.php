<?php

namespace App\Mail;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WeeklyReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reminder $reminder) {}

    public function build(): self
    {
        // at most two sentences plus a link to the order page
        return $this->subject('Your weekly AquaTrack refill reminder')
            ->view('mail.weekly-reminder')
            ->with(['url' => route('customer.orders.create')]);
    }
}
