<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTimeSlotsTable extends Migration
{
    public function up()
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المعاد (مثل "من 9 لـ 11 صباحًا")
            $table->timestamps();
        });

        // إضافة المواعيد الثلاثة كبيانات أولية
        DB::table('time_slots')->insert([
            ['name' => 'من 9 لـ 11 صباحًا'],
            ['name' => 'من 12 لـ 3 بعد الظهر'],
            ['name' => 'من 3 لـ 7 مساءً'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('time_slots');
    }
}
