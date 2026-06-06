<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // service_requests: add phone field (web booking form sends phone)
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
        });

        // quote_requests: add budget + link to service_booking
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->string('budget')->nullable()->after('phone');
            $table->foreignId('service_booking_id')
                  ->nullable()
                  ->constrained('service_bookings')
                  ->nullOnDelete()
                  ->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->dropForeign(['service_booking_id']);
            $table->dropColumn(['service_booking_id', 'budget']);
        });

        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
