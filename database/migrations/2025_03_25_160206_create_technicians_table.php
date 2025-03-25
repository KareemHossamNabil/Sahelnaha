<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTechniciansTable extends Migration
{
    public function up()
    {
        Schema::create('technicians', function (Blueprint $table) {
            $table->id(); // معرف الفني
            $table->string('name'); // اسم الفني
            $table->string('phone')->unique(); // رقم الهاتف (فريد)
            $table->string('email')->unique(); // البريد الإلكتروني (فريد)
            $table->decimal('rating', 3, 1)->default(0.0); // التقييم (مثل 4.5 من 5)
            $table->string('occupation'); // المهنة (مثل سباك، كهربائي)
            $table->integer('years_of_experience')->unsigned(); // سنوات الخبرة
            $table->timestamps(); // تاريخ الإنشاء والتحديث
        });
    }

    public function down()
    {
        Schema::dropIfExists('technicians');
    }
}
