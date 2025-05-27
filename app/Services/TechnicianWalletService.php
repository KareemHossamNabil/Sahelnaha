<?php
namespace App\Services;

use App\Models\TechnicianWallet;
use App\Models\TechPayment;

class TechnicianWalletService
{
    // إنشاء محفظة إذا لم تكن موجودة
    public function getWallet($technicianId)
    {
        $wallet = TechnicianWallet::firstOrCreate(['tech_id' => $technicianId], ['balance' => 0]);
        return $wallet;
    }

    // عملية إيداع
    public function deposit($technicianId, $amount, $description, $meta)
    {
        // الحصول على المحفظة الفنية بناءً على technicianId
        $wallet = $this->getWallet($technicianId);
        
        // إنشاء عملية الإيداع
        $payment = TechPayment::create([
            'tech_id' => $technicianId,  // تأكد من تمرير الـ tech_id هنا
            'technician_wallet_id' => $wallet->id,  // هذا هو الـ wallet المرتبط بالفني
            'amount' => $amount,
            'type' => 'deposit',  // نوع العملية: إيداع
            'description' => $description,
            'metadata' => json_encode($meta),  // البيانات الإضافية مثل المرجع أو البيانات المرفقة
        ]);
            // ✅ تحديث الرصيد بعد الإيداع
        $wallet->balance += $amount;
        $wallet->save();
        return $payment;
    }
    // عملية سحب
    public function processWithdrawal($technicianId, $amount, $method, $paymentDetails)
    {
        $wallet = TechnicianWallet::where('tech_id', $technicianId)->first();
        
        if (!$wallet) {
            throw new \Exception("Wallet not found");
        }
        
        if ($wallet->balance < $amount) {
            throw new \Exception("Insufficient balance");
        }
    
        // توليد الـ reference
        $reference = 'ref-' . uniqid();
    
        // تسجيل المعاملة في جدول tech_payments
        $payment = new TechPayment([
            'tech_id' => $technicianId,
            'technician_wallet_id' => $wallet->id,
            'amount' => $amount,
            'type' => 'withdrawal',
            'status' => 'pending',
            'description' => "Withdrawal via {$method}",
            'metadata' => json_encode($paymentDetails),
            'reference' => $reference // إضافة الـ reference
        ]);
        $payment->save();
    
        // خصم المبلغ من الرصيد
        $wallet->balance -= $amount;
        $wallet->save();
    
        // إرجاع المراجع
        return ['payment' => $payment, 'payment_reference' => $reference];  // تم إضافة الـ reference هنا
    }
    

    // إكمال عملية السحب
    public function completeWithdrawal($reference, $success)
    {
        $payment = TechPayment::where('reference', $reference)->first();
        
        if (!$payment) {
            throw new \Exception("Payment not found");
        }
        
        // تحديث حالة المعاملة
        $payment->status = $success ? 'completed' : 'failed';
        $payment->save();
        
        // إرجاع المعاملة المحدثة
        return $payment;
    }

    // الحصول على الرصيد
    public function getBalance($technicianId)
    {
        $wallet = TechnicianWallet::where('tech_id', $technicianId)->first();
        
        if (!$wallet) {
            throw new \Exception("Wallet not found");
        }
        
        return $wallet->balance;
    }

    // الحصول على المعاملات
    public function getTransactions($technicianId, $limit = 10)
    {
        return TechPayment::where('technician_wallet_id', $technicianId)
            ->limit($limit)
            ->get();
    }
}
