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
            $table->string('order_number', 50)->unique();
            $table->enum('type', ['delivery', 'pickup', 'dine_in']);
            $table->enum('status', ['pending', 'confirmed', 'paid', 'preparing', 'ready', 'on_delivery', 'completed', 'cancelled'])
                  ->default('pending')
                  ->index();

            // Relations
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('delivery_zone_id')->nullable()->constrained('tenant_delivery_zones')->nullOnDelete();
            $table->foreignId('discount_code_id')->nullable()->constrained('discount_codes')->nullOnDelete();

            // Customer info snapshot (stored even if customer deletes account)
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 20);

            // Delivery details
            $table->string('delivery_address')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_postal_code', 10)->nullable();
            $table->json('delivery_coordinates')->nullable()->comment('{"lat": 50.06, "lng": 19.94}');

            // Financials
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Payment
            $table->enum('payment_method', ['przelewy24', 'payu', 'tpay', 'stripe', 'cash', 'card_on_delivery', 'online']);
            $table->enum('payment_status', ['pending', 'awaiting_payment', 'paid', 'failed', 'refunded'])->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->json('payment_data')->nullable()->comment('Raw gateway response data');

            // Misc
            $table->text('notes')->nullable();
            $table->timestamp('estimated_delivery_time')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
