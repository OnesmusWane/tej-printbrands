<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A ledger entry used to be either a 'sale' or an 'expense', each with its own
 * amount. It's now a single record that can carry an income amount, an
 * expense amount, or both — e.g. "sold 5 shirts for 2000, spent 300 on
 * printing them" is one line, not two.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_ledger_entries', function (Blueprint $table) {
            $table->unsignedInteger('income')->nullable()->after('description');
            $table->unsignedInteger('expense')->nullable()->after('income');
        });

        DB::table('daily_ledger_entries')->where('type', 'sale')->update(['income' => DB::raw('amount')]);
        DB::table('daily_ledger_entries')->where('type', 'expense')->update(['expense' => DB::raw('amount')]);

        Schema::table('daily_ledger_entries', function (Blueprint $table) {
            $table->dropColumn(['type', 'amount']);
        });
    }

    public function down(): void
    {
        Schema::table('daily_ledger_entries', function (Blueprint $table) {
            $table->string('type')->nullable()->after('entry_date');
            $table->unsignedInteger('amount')->nullable()->after('category');
        });

        DB::table('daily_ledger_entries')->whereNotNull('income')->update(['type' => 'sale', 'amount' => DB::raw('income')]);
        DB::table('daily_ledger_entries')->whereNotNull('expense')->whereNull('income')->update(['type' => 'expense', 'amount' => DB::raw('expense')]);

        Schema::table('daily_ledger_entries', function (Blueprint $table) {
            $table->dropColumn(['income', 'expense']);
        });
    }
};
