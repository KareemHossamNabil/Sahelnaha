<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter; //Rate Limiting
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;  //Rate Limiting
use Illuminate\Support\Facades\Validator;
use App\Models\Payment;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\RequestException;
use App\Services\PaymobService;
use App\Services\TechnicianWalletService;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;





class PaymentController extends Controller
{
    protected $paymobService;
    protected $walletService;

    public function __construct(PaymobService $paymobService, TechnicianWalletService $walletService)
    {
        $this->paymobService = $paymobService;
        $this->walletService = $walletService;
    }

    public function createPaymobPayment(Request $request, PaymobService $paymobService)
    {
        // Step 3: Log incoming request data
        Log::info('Incoming Payment Request:', ['request' => json_encode($request->all(), JSON_PRETTY_PRINT)]);
        
        // Step 0: Apply Rate Limiting 
        $this->applyRateLimit($request);
    
        // Step 1: Validate CSRF Token
        if (!$request->expectsJson()) {
            $this->validateCsrfToken($request);
        }

        // Log the incoming request payload
        Log::info('Incoming Payment Request:', $request->all());

        //Step 2: Validate Request Data
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:vodafone cash,card',
            'amount' => 'required|numeric|min:1',
            'wallet_number' => 'required_if:payment_method,vodafone_cash|string|max:20',
            'card_details' => 'nullable|array|required_if:payment_method,card',
            'card_details.card_number' => 'required_if:payment_method,card|string|max:19', 
            'card_details.card_holder_name' => 'required_if:payment_method,card|string|max:255', 
            'card_details.card_expiry_month' => 'required_if:payment_method,card|string|max:2', 
            'card_details.card_expiry_year' => 'required_if:payment_method,card|string|max:4', 
            'card_details.card_cvn' => 'required_if:payment_method,card|string|max:4', 
        ]);
        
        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'error' => 'Invalid input data',
                'details' => $validator->errors(),
            ], 400);
        }
    
        try {
            try {
                try {
                    $user = Auth::user() ?? User::firstOrCreate(
                        ['email' => 'test@example.com'],
                        [
                            'first_name' => 'Test',
                            'last_name' => 'User',
                            'password' => bcrypt('password'),
                            'phone' => '01000000000'
                        ]
                    );
                    
                    if (!$user) {
                        throw new \Exception('Could not retrieve or create user');
                    }
                    
                } catch (\Exception $e) {
                    Log::error('User processing failed', ['error' => $e->getMessage()]);
                    return response()->json([
                        'error' => 'User processing failed',
                        'details' => $e->getMessage()
                    ], 500);
                }
            
                $order = Order::create([
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'total_amount' => $request->amount,
                ]);
                
                // إذا كنت تحتاج items، أنشئها هنا
                OrderItem::create([
                    'order_id' => $order->id,
                    'name' => 'Service Payment',
                    'price' => $request->amount,
                    'quantity' => 1,
                ]);

                    
            } catch (\Exception $e) {
                    Log::error('Order creation failed', ['error' => $e->getMessage()]);
                    return response()->json([
                        'error' => 'Order processing failed',
                        'details' => $e->getMessage()
                    ], 500);
            }
            


            
            // Check if order is already paid
            if ($order->payment_status === 'paid') {
                return response()->json([
                    'error' => 'Order already paid',
                ], 400);
            }

            $paymentResponse = null; // ✅ Add this line at the start!

            if ($request->payment_method === 'vodafone cash') { // space not underscore
                $paymentResponse = $this->processVodafoneCashPayment($request, $paymobService, $order);
            } else {
                $paymentResponse = $this->processCardPayment($request, $paymobService, $order);
            }
            


            // Handle response properly
            if ($paymentResponse instanceof JsonResponse) {
                // If it's already a JsonResponse (error case), return it directly
                return $paymentResponse;
            }

            // Update order status if successful
            // if ($paymentResponse && $paymentResponse['success'] ?? false) {
            //     $this->handleSuccessfulPayment($order, $paymentResponse, $request);
            // }

            return response()->json($paymentResponse);
                
        } catch (RequestException $e) {
            // Handle other HTTP request errors (e.g., invalid response, server errors)
            $responseBody = $e->hasResponse() ? json_decode($e->getResponse()->getBody(), true) : null;
            $errorMessage = $responseBody['message'] ?? $e->getMessage();
    
            $this->logError('Paymob API request failed.', [
                'error_message' => $errorMessage,
                'response_body' => $responseBody,
            ]);
    
            return response()->json([
                'error' => 'Paymob API request failed.',
                'details' => $errorMessage,
            ], 500);
    
        }  catch (\Exception $e) {
            // Handle all other unexpected exceptions
            $this->logError('An unexpected error occurred during the payment process.', [
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred during the payment process.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



    private function processVodafoneCashPayment(Request $request, PaymobService $paymobService, Order $order)
    {
        // Step 3: Authenticate with
        Log::info('Authenticating with Paymob...');
        $authToken = $paymobService->authenticate();
        if (!$authToken) {
            return response()->json([
                'error' => 'Failed to authenticate with Paymob API.',
            ], 500);
        }


        // Step 4: Create Payment Order
        $orderPayload = [
            'auth_token' => $authToken,
            'amount_cents' => (int) ($request->amount * 100),
            'currency' => 'EGP',
            'items' => $this->getOrderItems($order),
        ];

        $orderId = $paymobService->createOrder($authToken,  $orderPayload);
        if (!$orderId) {
            Log::error('Failed to create payment order.');
            return response()->json(['error' => 'Failed to create payment order.'], 500);
        }


        // Generate payment key
        $integrationId = config('services.paymob.vodafone_cash_integration_id');
        $billingData = $this->getBillingData($order->user);
        Log::info('Generating Payment Key for Order ID: ' . $orderId);
        $paymentKey = $paymobService->generatePaymentKey(
            $authToken,
            $orderId,
            $billingData,
            $integrationId,
            (int) ($request->amount * 100)
        );
        
        if (!$paymentKey) {
            Log::error('Payment Key Generation Failed');
            return response()->json([
                'error' => 'Failed to generate payment key.',
            ], 500);
        }

        // Process wallet payment
        $paymentResponse = $paymobService->processWalletPayment($paymentKey, $request->wallet_number);

        // حفظ بيانات الدفع في قاعدة البيانات
        $payment = Payment::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'payment_method' => 'vodafone_cash',
            'amount' => $request->amount,
            'currency' => 'EGP',
            'payment_status' => $paymentResponse['success'] ? 'completed' : 'failed',
            'transaction_id' => $paymentResponse['data']['id'] ?? null,
            'paymob_order_id' => $orderId,
            'paymob_transaction_id' => $paymentResponse['data']['txn_response_code'] ?? null,
            'wallet_number' => $request->wallet_number,
            'payment_response' => $paymentResponse
        ]);   
        // تحديث حالة الطلب
        $order->update([
            'payment_status' => $payment->payment_status
        ]);

        try {
            DB::transaction(function () use ($order, $request, $paymentResponse, $orderId) {
                $payment = Payment::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'payment_method' => 'vodafone_cash',
                    'amount' => $request->amount,
                    'currency' => 'EGP',
                    'payment_status' => $paymentResponse['success'] ? 'completed' : 'failed',
                    'transaction_id' => $paymentResponse['data']['id'] ?? null,
                    'paymob_order_id' => $orderId,
                    'paymob_transaction_id' => $paymentResponse['data']['txn_response_code'] ?? null,
                    'wallet_number' => $request->wallet_number,
                    'payment_response' => $paymentResponse
                ]);

                $order->update([
                    'payment_status' => $payment->payment_status
                ]);

                Log::info('Vodafone Cash Payment processed', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'amount' => $payment->amount,
                    'status' => $payment->payment_status
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Vodafone Payment Transaction Failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }

        return $paymentResponse;
    }


    private function processCardPayment(Request $request, PaymobService $paymobService, Order $order)
    {
        // التحقق من وجود جميع بيانات البطاقة المطلوبة
        $requiredCardFields = [
            'card_number',
            'card_holder_name',
            'card_expiry_month',
            'card_expiry_year',
            'card_cvn'
        ];

        foreach ($requiredCardFields as $field) {
            if (empty($request->card_details[$field])) {
                return response()->json([
                    'error' => 'Invalid card details',
                    'message' => "The field {$field} is required"
                ], 400);
            }
        }
        // معالجة اسم حامل البطاقة
        $cardHolderName = trim($request->card_details['card_holder_name']);
        if (empty($cardHolderName)) {
            $cardHolderName = $order->user->full_name ?? 'Customer';
        }

        $request->merge([
            'card_details' => array_merge(
                $request->card_details,
                ['card_holder_name' => $cardHolderName]
            )
        ]);


        // Step 3: Authenticate with
        Log::info('Authenticating with Paymob...');
        $authToken = $paymobService->authenticate();
        if (!$authToken) {
            return response()->json([
                'error' => 'Failed to authenticate with Paymob API.',
            ], 500);
        }


        // Step 4: Create Payment Order
        $orderPayload = [
            'auth_token' => $authToken,
            'amount_cents' => (int) ($request->amount * 100),
            'currency' => 'EGP',
            'items' => $this->getOrderItems($order),
        ];

        $orderId = $paymobService->createOrder($authToken,  $orderPayload);
        if (!$orderId) {
            Log::error('Failed to create payment order.');
            return response()->json(['error' => 'Failed to create payment order.'], 500);
        }


        // Generate payment key
        $integrationId = config('services.paymob.card_payment_integration_id');
        $billingData = $this->paymobService->getBillingData($order->user);
        Log::info('Generating Payment Key for Order ID: ' . $orderId);
        $paymentKey = $paymobService->generatePaymentKey(
            $authToken,
            $orderId,
            $billingData,
            $integrationId,
            (int) ($request->amount * 100)
        );
        
        if (!$paymentKey) {
            Log::error('Payment Key Generation Failed');
            return response()->json([
                'error' => 'Failed to generate payment key.',
            ], 500);
        }
        // Process card payment
        $paymentResponse = $this->paymobService->processCardPayment(
            $authToken,
            $paymentKey,
            $request->card_details,
            $order
        );

        // حفظ بيانات الدفع في قاعدة البيانات
        $payment = Payment::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'payment_method' => 'card',
            'amount' => $request->amount,
            'currency' => 'EGP',
            'payment_status' => $paymentResponse['success'] ? 'completed' : 'failed',
            'transaction_id' => $paymentResponse['data']['id'] ?? null,
            'paymob_order_id' => $orderId,
            'paymob_transaction_id' => $paymentResponse['data']['txn_response_code'] ?? null,
            'card_details' => [
                'last_four' => substr($request->card_details['card_number'], -4),
                'holder_name' => $request->card_details['card_holder_name']
            ],
            'payment_response' => $paymentResponse
        ]);

        // تحديث حالة الطلب
        $order->update([
            'payment_status' => $payment->payment_status
        ]);

        try {
            DB::transaction(function () use ($order, $request, $paymentResponse, $orderId) {
                $payment = Payment::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'payment_method' => 'card',
                    'amount' => $request->amount,
                    'currency' => 'EGP',
                    'payment_status' => $paymentResponse['success'] ? 'completed' : 'failed',
                    'transaction_id' => $paymentResponse['data']['id'] ?? null,
                    'paymob_order_id' => $orderId,
                    'paymob_transaction_id' => $paymentResponse['data']['txn_response_code'] ?? null,
                    'card_details' => [
                        'last_four' => substr($request->card_details['card_number'], -4),
                        'holder_name' => $request->card_details['card_holder_name']
                    ],
                    'payment_response' => $paymentResponse
                ]);

                $order->update([
                    'payment_status' => $payment->payment_status
                ]);

                Log::info('Card Payment processed', [
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'amount' => $payment->amount,
                    'status' => $payment->payment_status
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Payment Transaction Failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'error' => $e->getMessage()
            ], 500);
        }

        return $paymentResponse;

    }
    /**
     * Get order items for payment payload
     */
    private function getOrderItems(Order $order): array
    {
        // إذا كان لديك items
        // if ($order->relationLoaded('items') && $order->items->isNotEmpty()) {
        //     return $order->items->map(function ($item) {
        //         return [
        //             'name' => $item->name,
        //             'amount_cents' => (int) ($item->price * 100),
        //             'quantity' => $item->quantity,
        //         ];
        //     })->toArray();
        // }
    
        // القيمة الافتراضية إذا لم يكن هناك items
        return [
            [
                'name' => 'Service Payment',
                'amount_cents' => (int) ($order->total_amount * 100),
                'quantity' => 1,
            ]
        ];
    }


    /**
     * Get billing data from user
     */
    public function getBillingData(User $user): array
    {
        return [
            'first_name' => $user->first_name ?? 'Test',
            'last_name' => $user->last_name ?? 'User',
            'email' => $user->email ?? 'test@example.com',
            'phone_number' => $user->phone ?? '01000000000',
            'street' => $user->address ?? 'Default Street',
            'building' => '1',
            'floor' => '1',
            'apartment' => '1',
            'city' => $user->city ?? 'Cairo',
            'country' => 'EGY',
            'postal_code' => $user->postal_code ?? '00000',
        ];
    }


    /**
     * Handle Paymob callback notifications.
     *
     * @param Request $request
     * @param PaymobService $paymobService
     * @return \Illuminate\Http\JsonResponse
     */
    public function handlePaymobCallback(Request $request)
    {
        $data = $request->all();
        
        if (!$this->paymobService->verifyCallbackSignature($data)) {
            Log::error('Invalid Paymob Callback', $data);
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $transaction = $data['obj'];
        $orderId = $transaction['order']['id'];
        $success = $transaction['success'];

        // تحديث الدفع المرتبط بهذا الطلب
        $payment = Payment::where('paymob_order_id', $orderId)->firstOrFail();

        $payment->update([
            'payment_status' => $success ? 'completed' : 'failed',
            'transaction_id' => $transaction['id'],
            'paymob_transaction_id' => $transaction['txn_response_code'] ?? null,
            'payment_response' => array_merge(
                $payment->payment_response ?? [],
                ['callback_data' => $data]
            )
        ]);

        // تحديث حالة الطلب
        $payment->order->update([
            'payment_status' => $payment->payment_status
        ]);

        return response()->json(['status' => 'handled']);
    }


    /**
     * @param Request $request
     * @throws TooManyRequestsHttpException If the rate limit is exceeded.
     */
    private function applyRateLimit(Request $request)
    {
        $key = "fawry_payment:{$request->ip()}"; // Use the client's IP address as the key
        $maxAttempts = 10; // Maximum number of attempts allowed
        $decayMinutes = 1; // Time window for rate limiting (in minutes)

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            throw new TooManyRequestsHttpException(null, "Too many requests. Retry after {$retryAfter} seconds.", null, $retryAfter);
        }

        // Record the attempt
        RateLimiter::hit($key, $decayMinutes * 60); // Decay the attempts after the specified time
    }

    /**
     * Validate CSRF token for web requests.
     *
     * @param Request $request
     * @return void
     */
    private function validateCsrfToken(Request $request)
    {
        $csrfToken = $request->header('X-CSRF-TOKEN');
        if (empty($csrfToken)) {
            return response()->json([
                'error' => 'Missing CSRF token',
                'csrf_token' => csrf_token(),
            ], 403);
        }

        if (!hash_equals(session()->token(), $csrfToken)) {
            return response()->json([
                'error' => 'Invalid CSRF token',
                'csrf_token' => csrf_token(),
            ], 403);
        }
    }

        /**
     * Log errors consistently.
     *
     * @param string $message The error message.
     * @param array $context Additional context for the log entry.
     */
    private function logError(string $message, array $context = [])
    {
        Log::error('Fawry Payment Error', array_merge(['error_message' => $message], $context));
    }
}
