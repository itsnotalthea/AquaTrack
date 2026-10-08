<?php

namespace App\Console\Commands;

use App\Mail\LowStockAlert;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendLowStockAlert extends Command
{
    protected $signature = 'inventory:low-stock';

    protected $description = 'Email admins about inventory at or below threshold';

    public function handle(): int
    {
        $items = InventoryItem::all()->filter(fn (InventoryItem $item) => $item->isLowStock())->values();

        if ($items->isEmpty()) {
            $this->info('No low stock items.');

            return self::SUCCESS;
        }

        $admins = User::where('role', User::ROLE_ADMIN)->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin accounts to notify.');

            return self::SUCCESS;
        }

        Mail::to($admins)->send(new LowStockAlert($items));

        $this->info("Sent low stock alert for {$items->count()} item(s) to {$admins->count()} admin(s).");

        return self::SUCCESS;
    }
}
