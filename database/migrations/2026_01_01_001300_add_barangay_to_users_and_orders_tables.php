<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('barangay', 100)->nullable()->after('subdivision');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('barangay', 100)->nullable()->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('barangay');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('barangay');
        });
    }
};
