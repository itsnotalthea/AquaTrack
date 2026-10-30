<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('fulfillment_type')->default('stocked_item')->after('unit')->index();
            $table->foreignId('inventory_item_id')->nullable()->after('fulfillment_type')
                ->constrained('inventory_items')->nullOnDelete();
        });

        // Existing catalog: gallon refill, piece new container, month dispenser rental.
        DB::table('products')->where('unit', 'gallon')->update(['fulfillment_type' => 'refill']);
        DB::table('products')->where('unit', 'piece')->update(['fulfillment_type' => 'new_container']);
        DB::table('products')->where('unit', 'month')->update(['fulfillment_type' => 'dispenser_rental']);

        $newContainer = DB::table('inventory_items')->where('name', 'new_container')->value('id');
        $dispenser = DB::table('inventory_items')->where('name', 'dispenser_available')->value('id');

        if ($newContainer) {
            DB::table('products')->where('fulfillment_type', 'new_container')->update(['inventory_item_id' => $newContainer]);
        }

        if ($dispenser) {
            DB::table('products')->where('fulfillment_type', 'dispenser_rental')->update(['inventory_item_id' => $dispenser]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_item_id');
            $table->dropColumn('fulfillment_type');
        });
    }
};
