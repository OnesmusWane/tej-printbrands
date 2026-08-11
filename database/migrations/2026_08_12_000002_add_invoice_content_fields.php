<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('service')->nullable()->after('client');
            $table->unsignedInteger('subtotal')->default(0)->after('amount');
            $table->unsignedInteger('tax')->default(0)->after('subtotal');
            $table->boolean('vat_included')->default(true)->after('tax');
            $table->text('terms')->nullable()->after('payment_method');
            $table->timestamp('sent_at')->nullable()->after('terms');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['service', 'subtotal', 'tax', 'vat_included', 'terms', 'sent_at']);
        });
    }
};
