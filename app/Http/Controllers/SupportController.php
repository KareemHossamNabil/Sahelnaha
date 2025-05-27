<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function sendSupportRequest(Request $request)
    {
        // التحقق من صحة البيانات
        $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        // إرسال الإيميل مع عرض HTML
        Mail::send('emails.support', ['data' => $request->all()], function ($message) {
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME')) // يضمن عدم التأثير على OTP
                ->to('sahelnaha.co@gmail.com')
                ->subject('دعم فني - رسالة جديدة');
        });

        return response()->json(['status' => 'success', 'message' => 'تم إرسال الرسالة بنجاح']);
    }
}
