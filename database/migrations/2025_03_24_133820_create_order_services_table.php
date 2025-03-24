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
            $table->foreignId('service_type_id')->constrained('service_types')->onDelete('restrict'); // ربط مع جدول service_types
            $table->text('description')->nullable(); // وصف الخدمة (اختياري)
            $table->string('image')->nullable(); // مسار الصورة (اختياري)
            $table->date('date'); // التاريخ
            $table->string('time_slot'); // الوقت
            $table->boolean('is_urgent')->default(false); // هل الطلب فوري؟
            $table->string('address'); // العنوان
            $table->foreignId('payment_method_id')->constrained('payment_methods')->onDelete('restrict'); // ربط مع جدول payment_methods
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending'); // حالة الطلب
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_services');
    }
}
