<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Illuminate\Database\Seeder;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timeSlots = [
            [
                'name' => '9_11 صباحا',
                'name_en' => '9-11 AM',
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
            ],
            [
                'name' => '12_3 بعد الظهر',
                'name_en' => '12-3 PM',
                'start_time' => '12:00:00',
                'end_time' => '15:00:00',
            ],
            [
                'name' => '3_7 مساءا',
                'name_en' => '3-7 PM',
                'start_time' => '15:00:00',
                'end_time' => '19:00:00',
            ],
        ];

        foreach ($timeSlots as $slot) {
            TimeSlot::create($slot);
        }
    }
}
