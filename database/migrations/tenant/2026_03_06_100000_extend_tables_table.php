<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->string('name')->nullable()->after('number')->comment('Custom display name, e.g. A1, VIP-1');
            $table->integer('min_guests')->default(1)->after('seats');
            $table->text('description')->nullable()->after('min_guests')->comment('Internal staff notes');
            $table->string('status', 20)->default('available')->after('is_active')
                ->comment('available|occupied|reserved|cleaning');
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['name', 'min_guests', 'description', 'status']);
        });
    }
};
