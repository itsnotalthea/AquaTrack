<?php

namespace App\Console\Commands;

use App\Mail\WeeklyReminder;
use App\Models\Reminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = 'Email active weekly refill reminders that are due';

    // a reminder goes out at most once every seven days
    private const CADENCE_DAYS = 7;

    public function handle(): int
    {
        $sent = 0;

        Reminder::with('customer')
            ->active()
            ->where(fn ($q) => $q->whereNull('last_sent_at')->orWhere('last_sent_at', '<=', now()->subDays(self::CADENCE_DAYS)))
            ->chunkById(100, function ($reminders) use (&$sent) {
                foreach ($reminders as $reminder) {
                    Mail::to($reminder->email)->send(new WeeklyReminder($reminder));
                    $reminder->update(['last_sent_at' => now()]);
                    $sent++;
                }
            });

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
