<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put(Setting::DELIVERY_FEE, 30);
        Setting::put(Setting::DISPENSER_RENTAL_DAYS, 30);
    }
}
