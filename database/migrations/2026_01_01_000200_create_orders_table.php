<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('order_type', ['delivery', 'pickup']);
            $table->enum('status', [
                'pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered',
                'ready_for_pickup', 'picked_up', 'completed', 'cancelled',
            ])->default('pending')->index();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid')->index();
            $table->enum('payment_method', ['cash', 'gcash', 'maya'])->nullable();
            $table->text('delivery_address')->nullable();
            $table->date('preferred_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('container_swap')->default(false);
            $table->boolean('weekly_reminder')->default(false);
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['order_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
