<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('container_inventory', function (Blueprint $table) {
            $table->id();
            $table->enum('status', [
                'at_station_full', 'at_station_empty',
                'with_customer', 'out_for_delivery', 'lost_damaged',
            ])->unique();
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('container_inventory');
    }
};
