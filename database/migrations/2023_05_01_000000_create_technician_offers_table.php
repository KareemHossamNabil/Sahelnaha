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
        Schema::create('technician_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('technician_id');
            $table->unsignedBigInteger('service_request_id')->nullable();
            $table->unsignedBigInteger('order_service_id')->nullable();
            $table->text('description');
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2);
            $table->string('currency', 10)->default('EGP');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'rejected', 'cancelled'])->default('pending');
            $table->string('request_type')->default('service_request');
            $table->decimal('final_price', 10, 2)->nullable();
            $table->text('invoice_image')->nullable();
            $table->integer('rating')->nullable();
            $table->text('review')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('cascade');
            $table->foreign('service_request_id')->references('id')->on('service_requests')->onDelete('cascade');
            $table->foreign('order_service_id')->references('id')->on('order_services')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_offers');
    }
};
