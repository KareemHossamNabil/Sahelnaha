<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run()
    {
        $paymentMethods = [
            ['key' => 'mastercard', 'name' => 'MasterCard'],
            ['key' => 'vodafone_cash', 'name' => 'فودافون كاش'],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::create($paymentMethod);
        }
    }
}
