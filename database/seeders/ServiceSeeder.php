<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ServiceSeeder extends Seeder
{
    public function run()
    {
        $path = public_path('uploads/services/'); // مسار الصور

        $files = File::files($path); // جلب جميع الملفات داخل المجلد

        foreach ($files as $file) {
            $serviceName = pathinfo($file, PATHINFO_FILENAME); // اسم الخدمة من اسم الصورة
            $imageData = file_get_contents($file); // تحويل الصورة إلى بيانات ثنائية
            $imagePath = 'uploads/services/' . $file->getFilename(); // مسار الصورة داخل public

            DB::table('services')->insert([
                'service_name' => $serviceName,
                'description' =>  $serviceName,
                'image' => $imageData, // حفظ الصورة كـ BLOB
                'image_path' => $imagePath, // حفظ المسار المباشر
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
