<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();

            // Snapshot of product data at time of order
            $table->string('name')->comment('Product name at time of order');
            $table->string('variant_name')->nullable()->comment('Variant name at time of order');
            $table->decimal('price', 10, 2)->comment('Unit price at time of order');
            $table->integer('quantity');

            $table->json('addons')->nullable()->comment('[{"id":1,"name":"Extra ser","price":2.00}]');
            $table->json('exclusions')->nullable()->comment('["Cebula","Oliwki"]');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
