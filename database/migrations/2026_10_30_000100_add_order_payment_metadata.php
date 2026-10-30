<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->string('payment_proof_path')->nullable()->after('payment_reference');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('method');
            $table->string('proof_path')->nullable()->after('reference_number');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'proof_path']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_reference', 'payment_proof_path']);
        });
    }
};
