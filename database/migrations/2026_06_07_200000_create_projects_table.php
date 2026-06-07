<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('client')->nullable();
            $table->string('status')->default('active'); // active|completed|on-hold
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('id');
        });
        Schema::table('service_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('project_id');
        });
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropColumn('project_id');
        });
        Schema::dropIfExists('projects');
    }
};
