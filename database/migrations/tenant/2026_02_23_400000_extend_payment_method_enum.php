<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite: create_orders_table already defines the full enum list,
        // so no schema change is needed — just migrate legacy records.
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("UPDATE orders SET payment_method = 'przelewy24' WHERE payment_method = 'online'");
            return;
        }

        $prefix = DB::connection()->getTablePrefix();

        // Extend payment_method enum to include all payment gateways (MySQL)
        DB::statement("ALTER TABLE `{$prefix}orders` MODIFY COLUMN payment_method ENUM(
            'przelewy24','payu','tpay','stripe','cash','card_on_delivery','online'
        ) NOT NULL");

        // Extend payment_status enum to include awaiting_payment (MySQL)
        DB::statement("ALTER TABLE `{$prefix}orders` MODIFY COLUMN payment_status ENUM(
            'pending','awaiting_payment','paid','failed','refunded'
        ) NOT NULL DEFAULT 'pending'");

        // Migrate legacy 'online' records to 'przelewy24'
        DB::statement("UPDATE `{$prefix}orders` SET payment_method = 'przelewy24' WHERE payment_method = 'online'");
    }

    public function down(): void
    {
        $prefix = DB::getDriverName() === 'sqlite' ? '' : DB::connection()->getTablePrefix();

        DB::statement("UPDATE `{$prefix}orders` SET payment_method = 'online' WHERE payment_method IN ('przelewy24','payu','tpay','stripe')");
        DB::statement("UPDATE `{$prefix}orders` SET payment_status = 'pending' WHERE payment_status = 'awaiting_payment'");

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `{$prefix}orders` MODIFY COLUMN payment_method ENUM('online','cash','card_on_delivery') NOT NULL");
        DB::statement("ALTER TABLE `{$prefix}orders` MODIFY COLUMN payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending'");
    }
};
