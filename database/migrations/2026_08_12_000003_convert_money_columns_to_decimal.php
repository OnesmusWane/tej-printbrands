<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Line-item prices (e.g. Ksh 12.20 per unit) were being rejected because every
 * money column was an unsigned integer. Widened to DECIMAL(12,2) UNSIGNED so
 * cents survive through validation, totals math, and storage.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE quotations MODIFY subtotal DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE quotations MODIFY tax DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE quotations MODIFY total DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE quotation_items MODIFY unit_price DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE quotation_items MODIFY total DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE invoices MODIFY amount DECIMAL(12,2) UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE invoices MODIFY paid_amount DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE invoices MODIFY subtotal DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE invoices MODIFY tax DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE invoice_items MODIFY unit_price DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE invoice_items MODIFY total DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE payments MODIFY amount DECIMAL(12,2) UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE quotations MODIFY subtotal INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE quotations MODIFY tax INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE quotations MODIFY total INT UNSIGNED NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE quotation_items MODIFY unit_price INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE quotation_items MODIFY total INT UNSIGNED NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE invoices MODIFY amount INT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE invoices MODIFY paid_amount INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE invoices MODIFY subtotal INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE invoices MODIFY tax INT UNSIGNED NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE invoice_items MODIFY unit_price INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE invoice_items MODIFY total INT UNSIGNED NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE payments MODIFY amount INT UNSIGNED NOT NULL');
    }
};
