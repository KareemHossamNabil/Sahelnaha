<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Technician;

class TechnicianSeeder extends Seeder
{
    public function run()
    {
        Technician::create([
            'name' => 'أحمد محمد',
            'phone' => '0123456789',
            'email' => 'ahmed@example.com',
            'rating' => 4.5,
            'occupation' => 'سباك',
            'years_of_experience' => 5,
        ]);

        Technician::create([
            'name' => 'محمد علي',
            'phone' => '0987654321',
            'email' => 'mohamed@example.com',
            'rating' => 4.0,
            'occupation' => 'كهربائي',
            'years_of_experience' => 3,
        ]);
    }
}
