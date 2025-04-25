<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceRequestSchedulesTable extends Migration
{
    public function up()
    {
        Schema::create('service_request_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->date('date');
            $table->string('day');
            $table->string('time_range');
            $table->string('is_urgent')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('service_request_schedules');
    }
}
