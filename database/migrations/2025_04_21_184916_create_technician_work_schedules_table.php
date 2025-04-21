<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('technician_work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_request_id')->constrained()->onDelete('cascade');
            $table->timestamp('scheduled_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_work_schedules');
    }
};
