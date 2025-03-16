<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('task_type', [
                'تغيير خلاط مطبخ او حمام',
                'تسريب مياه',
                'اعطال سخان',
                'تغيير دش وتصليح بانيو',
                'فك وتغيير فلتر المياه',
                'اخري'
            ]);
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->enum('time_slot', ['9-11 صباحا', '12-3 بعد الظهر', '3-7 مساء']);
            $table->string('address');
            $table->enum('payment_method', ['MasterCard', 'كاش']);
            $table->enum('status', ['قيد المراجعة', 'مؤكد', 'تم التنفيذ', 'ملغي'])->default('قيد المراجعة');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
