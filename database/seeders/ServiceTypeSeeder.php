<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run()
    {
        $serviceTypes = [
            ['key' => 'replace_faucet_or_bathroom', 'name' => 'تغيير خلاط أو حمام'],
            ['key' => 'sink_water_leak', 'name' => 'تسريب مياه من الحوض'],
            ['key' => 'heater_malfunction', 'name' => 'أعطال سخان'],
            ['key' => 'replace_shower_and_fix_bathtub', 'name' => 'تغيير دش وتصليح بانيو'],
            ['key' => 'install_replace_fix_water_filter', 'name' => 'فك وتغيير وتصليح فلتر المياه'],
            ['key' => 'other', 'name' => 'أخرى'],
        ];

        foreach ($serviceTypes as $serviceType) {
            ServiceType::create($serviceType);
        }
    }
}
