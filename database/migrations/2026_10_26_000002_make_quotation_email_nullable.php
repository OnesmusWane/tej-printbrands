<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A quotation can now be created and downloaded as a PDF without ever being
 * emailed to the client, so email is no longer guaranteed to be present.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE quotations MODIFY email VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE quotations SET email = '' WHERE email IS NULL");
        DB::statement('ALTER TABLE quotations MODIFY email VARCHAR(255) NOT NULL');
    }
};
