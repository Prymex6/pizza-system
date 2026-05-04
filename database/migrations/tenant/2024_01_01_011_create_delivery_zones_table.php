<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->json('polygon')->comment('Array of [lat, lng] pairs defining the delivery area');
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('min_order_value', 10, 2)->nullable();
            $table->integer('estimated_time')->nullable()->comment('Delivery time in minutes');
            $table->string('color', 7)->default('#3B82F6')->comment('Hex color for map display');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_delivery_zones');
    }
};
