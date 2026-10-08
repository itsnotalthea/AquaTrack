<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Collection $items) {}

    public function build(): self
    {
        return $this->subject('AquaTrack low stock alert')
            ->view('mail.low-stock-alert');
    }
}
