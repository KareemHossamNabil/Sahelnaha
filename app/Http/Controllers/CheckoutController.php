<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Charge;

class CheckoutController extends Controller
{
    public function showCheckout()
    {
        $cart = session()->get('cart', []);
        $total = collect($cart)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        return view('checkout', compact('cart', 'total'));
    }

    public function processPayment(Request $request)
    {
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $cart = session()->get('cart', []);
            $total = collect($cart)->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            }) * 100; // Convert to cents

            $charge = Charge::create([
                'amount' => $total,
                'currency' => 'usd',
                'source' => $request->stripeToken,
                'description' => 'Shopping Cart Payment'
            ]);

            // Clear the cart after successful payment
            session()->forget('cart');

            return response()->json([
                'status' => 200,
                'msg' => 'Payment successful',
                'charge' => $charge
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'msg' => 'Payment failed: ' . $e->getMessage()
            ], 400);
        }
    }
}
