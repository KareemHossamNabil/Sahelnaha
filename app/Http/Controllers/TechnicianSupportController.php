<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TechnicianSupportController extends Controller
{
    // Method to send support request
    public function sendSupportRequest(Request $request)
    {
        // Validate the incoming data
        $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'message' => 'required|string',
        ]);

        // Send the support request via email
        Mail::send('emails.support', ['data' => $request->all()], function ($message) {
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
                ->to('sahelnaha.co@gmail.com') // Support email address
                ->subject('دعم فني - رسالة جديدة');
        });

        return response()->json(['status' => 'success', 'message' => 'تم إرسال الرسالة بنجاح']);
    }
}
