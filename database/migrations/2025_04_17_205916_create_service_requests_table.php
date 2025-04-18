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
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_type_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->date('scheduled_date');
            $table->boolean('is_urgent')->default(false);
            $table->foreignId('time_slot_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');

            // ✅ هنا العنوان بقى نص حر
            $table->text('address')->nullable();

            $table->json('images')->nullable();
            $table->enum('status', ['pending', 'accepted', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
