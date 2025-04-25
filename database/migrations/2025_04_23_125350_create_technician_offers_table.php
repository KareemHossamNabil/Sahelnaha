<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTechnicianOffersTable extends Migration
{
    public function up()
    {
        Schema::create('technician_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('technician_id')->constrained('technicians')->onDelete('cascade');
            $table->text('description');
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2);
            $table->string('currency', 10)->default('EGP');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('technician_offers');
    }
}
