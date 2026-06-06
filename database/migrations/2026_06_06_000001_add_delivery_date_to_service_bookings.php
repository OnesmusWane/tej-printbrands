<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete()->after('user_id');
            $table->date('delivery_date')->nullable()->after('preferred_date');
            $table->string('description')->nullable()->after('project_description');
        });
    }

    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\ServiceRequest::class);
            $table->dropColumn(['service_request_id', 'delivery_date', 'description']);
        });
    }
};
