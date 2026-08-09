<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date')->index();
            $table->string('type')->index(); // sale|expense
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('amount');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_ledger_entries');
    }
};
