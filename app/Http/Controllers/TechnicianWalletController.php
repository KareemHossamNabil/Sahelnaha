<?php

namespace App\Http\Controllers;

use App\Services\TechnicianWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TechnicianWalletController extends Controller
{
    protected $walletService;

    public function __construct(TechnicianWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function createWallet(Request $request)
    {
        Log::info('createWallet called', $request->all());

        $validator = Validator::make($request->all(), [
            'technician_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $wallet = $this->walletService->getWallet($request->technician_id);

        Log::info('createWallet result', ['wallet' => $wallet]);

        return response()->json([
            'success' => true,
            'wallet' => $wallet
        ]);
    }

    public function deposit(Request $request)
    {
        Log::info('deposit called', $request->all());

        $validator = Validator::make($request->all(), [
            'technician_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction = $this->walletService->deposit(
            $request->technician_id,
            $request->amount,
            $request->description,
            $request->only(['reference', 'metadata'])
        );

        $balance = $this->walletService->getBalance($request->technician_id);

        Log::info('deposit result', ['transaction' => $transaction, 'balance' => $balance]);

        

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
            'balance' => $balance
        ]);
    }

    public function requestWithdrawal(Request $request)
    {
        Log::info('requestWithdrawal called', $request->all());
    
        // التحقق من صحة المدخلات
        $validator = Validator::make($request->all(), [
            'technician_id' => 'required|integer|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:vodafone_cash,card',
            'payment_details' => 'required|array'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
    
        try {
            // استدعاء دالة processWithdrawal
            $result = $this->walletService->processWithdrawal(
                $request->technician_id,
                $request->amount,
                $request->method,
                $request->payment_details
            );
    
            Log::info('requestWithdrawal result', ['result' => $result]);
    
            // إرجاع الاستجابة
            return response()->json([
                'success' => true,
                'message' => 'Withdrawal request created. Use complete endpoint to finalize.',
                'transaction' => [
                    'id' => $result['payment']->id,
                    'amount' => $result['payment']->amount,
                    'type' => $result['payment']->type,
                    'status' => $result['payment']->status,
                    'reference' => $result['payment']->reference,  // الـ reference سيكون موجود هنا
                    'description' => $result['payment']->description,
                    'metadata' => $result['payment']->metadata,
                ],
                'payment_reference' => $result['payment_reference']  // إرجاع الـ reference المولد
            ]);
    
        } catch (\Exception $e) {
            Log::error('Error processing withdrawal', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function completeWithdrawal(Request $request)
    {
        Log::info('completeWithdrawal called', $request->all());

        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
            'success' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $payment = $this->walletService->completeWithdrawal($request->reference, $request->success);

            Log::info('completeWithdrawal result', ['payment' => $payment]);

            return response()->json([
                'success' => true,
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getBalance(Request $request)
    {
        Log::info('getBalance called', $request->all());

        $validator = Validator::make($request->all(), [
            'technician_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $balance = $this->walletService->getBalance($request->technician_id);

            return response()->json([
                'success' => true,
                'balance' => $balance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getTransactions(Request $request)
    {
        Log::info('getTransactions called', $request->all());

        $validator = Validator::make($request->all(), [
            'technician_id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transactions = $this->walletService->getTransactions($request->technician_id);

            return response()->json([
                'success' => true,
                'transactions' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
