<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->comment('Table number visible to staff');
            $table->integer('seats')->default(4)->comment('Number of seats at the table');
            $table->string('location')->nullable()->comment('e.g. Sala główna, Ogródek, VIP');
            $table->string('qr_code')->nullable()->comment('URL encoded in QR code for self-ordering');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
