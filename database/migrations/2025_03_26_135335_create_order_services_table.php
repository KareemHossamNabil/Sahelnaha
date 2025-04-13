<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderServicesTable extends Migration
{
    public function up()
    {
        Schema::create('order_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_type_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->date('date');
            $table->foreignId('time_slot_id')->constrained('time_slots')->onDelete('cascade'); // required ومش nullable
            $table->boolean('is_urgent')->default(false);
            $table->string('address');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('confirmed');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_services');
    }
}
