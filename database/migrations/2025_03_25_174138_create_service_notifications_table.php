<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServicesNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('services_notifications', function (Blueprint $table) {
            $table->id(); // معرف الإشعار
            $table->unsignedBigInteger('technician_id'); // ربط مباشر بالفني
            $table->unsignedBigInteger('order_id')->nullable(); // ربط بالطلب (اختياري)
            $table->text('data'); // تفاصيل الإشعار بصيغة JSON
            $table->timestamp('read_at')->nullable(); // تاريخ القراءة
            $table->timestamps(); // created_at و updated_at

            // إضافة مفاتيح خارجية
            $table->foreign('technician_id')->references('id')->on('technicians')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('order_services')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('services_notifications');
    }
}
