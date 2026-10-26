<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quantities like 45.89 (sold by weight/length, e.g. kg or metres) were being
 * rejected because quantity was an unsigned integer. Widened to DECIMAL(12,2)
 * UNSIGNED, same treatment as unit_price before it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE quotation_items MODIFY quantity DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE invoice_items MODIFY quantity DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE quotation_items MODIFY quantity INT UNSIGNED NOT NULL DEFAULT 1');
        DB::statement('ALTER TABLE invoice_items MODIFY quantity INT UNSIGNED NOT NULL DEFAULT 1');
    }
};
