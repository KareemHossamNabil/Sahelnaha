<?php

namespace App\Services;
use App\Models\Payment;
use App\Models\User;
use App\Models\Order;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;



class PaymobService
{
    protected $client; // HTTP client for API requests
    protected $paymobBaseUrl; // Base URL for Paymob API
    protected $apiKey;

    /**
     * Constructor for initializing dependencies and configurations.
     */
    public function __construct()
    {
        // Initialize the Guzzle HTTP client with timeout settings
        $this->client = new Client([
            //'verify' => false, // Disable SSL verification
            'verify' => true, // Ensure HTTPS verification
            'timeout' => 30, // Total request timeout (in seconds)
            'connect_timeout' => 10, // Connection timeout (in seconds)
        ]);
    
        // Determine the Paymob API base URL based on the mode (sandbox or production)
        $this->paymobBaseUrl = config('services.paymob.base_url');
        $this->apiKey = config('services.paymob.api_key');
    }

    /**
     * Authenticate with the Paymob API and retrieve an authentication token.
     *
     * @return string|null The authentication token or null on failure.
     */
    public function authenticate(): ?string
    {
        Log::info('Using API Key: ' . $this->apiKey); // Log the API key
        try {
            Log::info('Attempting Paymob Authentication', ['api_key' => substr($this->apiKey, 0, 5) . '...' . substr($this->apiKey, -5)]);

            $response = $this->client->post($this->paymobBaseUrl . 'auth/tokens', [
                'json' => [
                    'api_key' => $this->apiKey,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ]            
        ]);
    
            $responseBody = json_decode($response->getBody(), true);
            Log::info('Paymob Authentication Response: ', $responseBody); // Log the response
    
            return $responseBody['token'] ?? null;

        } catch (RequestException $e) {
            // Handle Guzzle request exceptions (e.g., HTTP errors)
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            Log::error('Paymob Authentication Error', [
                'error_message' => $e->getMessage(),
                'response_body' => $responseBody,
            ]);
            return null;
        } catch (\Exception $e) {
            // Handle all other exceptions
            Log::error('Paymob Authentication Error', [
                'error_message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function createOrder(string $authToken, array $orderPayload):?string
    {
        try {
            Log::info('Creating Paymob Payment Order', ['auth_token' => substr($authToken, 0, 5) . '...' . substr($authToken, -5)]);
            
            // Ensure the items array is properly structured
            if (!isset($orderPayload['items']) || !is_array($orderPayload['items'])) {
                $orderPayload['items'] = []; // Default to an empty array if items are not provided
            }

            $response = $this->client->post($this->paymobBaseUrl . 'ecommerce/orders', [
            'json' => $orderPayload,
            ]);

            if ($response->getStatusCode() !== 201) {
                $responseBody = json_decode($response->getBody(), true);
                Log::error('Paymob Order Creation Failed', [
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $responseBody,
                ]);
                return null;
            }

            $responseBody = json_decode($response->getBody(), true);
            Log::info('Paymob Order Creation Response:', $responseBody);
            return $responseBody['id'] ?? null;
            
        } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            Log::error('Paymob Order Creation Error', [
                'error_message' => $e->getMessage(),
                'response_body' => $responseBody,
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Paymob Order Creation Error', [
                'error_message' => $e->getMessage(),
            ]);
            return null;
        }
    }


    public function generatePaymentKey(string $authToken, string $orderId, array $billingData, string $integrationId, int $amountCents): ?string    {
        try {
            Log::info('Generating Paymob Payment Key...');

            $payload = [
                'auth_token' => $authToken,
                //'amount_cents' => $billingData['amount_cents'] ?? 0,
                'amount_cents' => $amountCents,
                'expiration' => 3600,
                'order_id' => $orderId,
                'currency' => 'EGP',
                'integration_id' => $integrationId,
                'billing_data' => $billingData,       
            ];
            Log::info('Paymob Payment Key Request Payload', $payload);

            // Make the API request
            $response = $this->client->post($this->paymobBaseUrl . 'acceptance/payment_keys', [
                'json' => $payload,
            ]);

            $responseBody = json_decode($response->getBody(), true);

            Log::info('Full Paymob Payment Key Response:', ['response' => $responseBody]);
            // Check for a successful response
            if ($response->getStatusCode() !== 201 || empty($responseBody['token'])) {
                Log::error('Payment Key Generation Failed', [
                    'status_code' => $response->getStatusCode(),
                    'response_body' => $responseBody,
                ]);
                return null;
            }
            
            return $responseBody['token'] ?? null;

        } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;
            Log::error('Paymob Payment Key Generation Error', [
                'error_message' => $e->getMessage(),
                'response_body' => $responseBody,
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Paymob Payment Key Generation Error', [
                'error_message' => $e->getMessage(),
            ]);
            return null;
        }
    }


    public function storePayment(array $data): ?Payment
    {
        try {
        Log::info('🔁 storePayment - incoming data', $data);

                // Ensure required fields are present
            if (!isset($data['user_id']) || !isset($data['amount'])) {
                throw new \Exception('Missing required payment fields');
            }

            // Create the payment record
            $payment = Payment::create([
                'user_id' => $data['user_id'],
                'order_id' => $data['order_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? 'unknown',
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'EGP',
                'payment_status' => $data['status'] ?? 'pending',
                'transaction_id' => $data['transaction_id'] ?? null,
                'paymob_order_id' => $data['paymob_order_id'] ?? null,
                'paymob_transaction_id' => $data['paymob_transaction_id'] ?? null,
                'wallet_number' => $data['wallet_number'] ?? null,
                'card_details' => isset($data['card_details']) ? json_encode($data['card_details']) : null,
                'payment_response' => isset($data['response']) ? json_encode($data['response']) : null,
            ]);
            Log::info('✅ Payment stored successfully', ['payment_id' => $payment->id]);
            
            return $payment;

        } catch (\Exception $e) {
            Log::error('❌ Failed to store payment', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString() // Add stack trace for debugging
            ]);
            
            return null;
        }
    }

    
    
    public function processWalletPayment( string $paymentKey, string $walletNumber): ?array
    {
        try {
            // Construct the payload for Vodafone Cash payment
            $payload = [
                'source' => [
                    'identifier' => $walletNumber, // Wallet number (e.g., Vodafone Cash number)
                    'subtype' => 'WALLET',        // Subtype for wallet payments
                ],
                'payment_token' => $paymentKey,   // Payment key generated earlier
            ];


            $response = $this->client->post($this->paymobBaseUrl . 'acceptance/payments/pay', [
                'json' => $payload,
            ]);
    
            $responseBody = json_decode($response->getBody(), true);
            Log::info('Paymob Wallet Payment Response:', $responseBody);
    
            // ✅ مضاف: التحقق من وجود redirect حتى لو success = false
            $hasRedirect = isset($responseBody['data']['redirect_url']);

            // ✅ مضاف: تحديد الحالة
            if ($response->getStatusCode() === 200 && $hasRedirect) {
                Log::info('Received Vodafone Cash Payment Response from Paymob with redirect:', ['response' => $responseBody]);
                $status = 'redirect';
            } else {
                Log::error('Vodafone Cash Payment Failed:', ['response' => json_encode($responseBody, JSON_PRETTY_PRINT)]);
                $status = 'failed';
            }
        

            return [
                'success' => $status !== 'failed', // ✅ success = true لو فيه redirect
                'message' => $status === 'redirect'
                    ? 'Please complete the payment using the provided link.'
                    : 'Vodafone Cash payment failed: ' . ($responseBody['data']['message'] ?? 'Unknown error'),
                'data' => $responseBody,
            ];
        } catch (RequestException $e) {
            Log::error('Vodafone Cash Payment Exception:', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'An error occurred while processing the Vodafone Cash payment.',
                'error' => $e->getMessage()
            ];
    
        } catch (\Exception $e) {
            Log::error('Vodafone Cash Payment Error', [
                'error_message' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'An error occurred while processing the Vodafone Cash payment.',
                'error' => $e->getMessage(),
            ];
        }
    }   


    public function generateCardPaymentLink(string $authToken, string $orderId, array $billingData, int $amountCents): ?array
    {
        try {
            $integrationId = config('services.paymob.card_payment_integration_id');
            
            // Generate payment key
            $paymentKey = $this->generatePaymentKey(
                $authToken,
                $orderId,
                $billingData,
                $integrationId,
                $amountCents
            );
            
            if (!$paymentKey) {
                throw new \Exception('Failed to generate payment key');
            }

            // Build the payment URL
            $iframeId = config('services.paymob.card_iframe_id');
            $paymentUrl = config('services.paymob.iframe_base_url') . $iframeId . '?payment_token=' . $paymentKey;

            return [
                'payment_url' => $paymentUrl,
                'payment_token' => $paymentKey
            ];

        } catch (\Exception $e) {
            Log::error('Card Payment Link Error: ' . $e->getMessage());
            return null;
        }
    }
    
     public function processCardPayment(string $authToken, string $paymentKey, array $cardDetails, Order $order): ?array
     {
        $paymentData = [
            'source' => [
                'identifier' => $cardDetails['card_number'],
                'subtype' => 'CARD',
            ],
            'card' => [ // ✅ مضاف بشكل صحيح حسب مستندات Paymob
                'holder_name' => $cardDetails['card_holder_name'],
                'expiry_month' => $cardDetails['card_expiry_month'],
                'expiry_year' => $cardDetails['card_expiry_year'],
                'cvn' => $cardDetails['card_cvn'],
            ],
            'payment_token' => $paymentKey,
            'billing_data' => $this->getBillingData($order->user->first()) // ✅ إضافة هذا الحقل لحل المشكلة
        ];
         try {    
         Log::info('📤 Sending card payment payload to Paymob:', $paymentData); // ✅ للتأكد من الطلب
           
             // Make the API request to Paymob's card payment endpoint
             $response = $this->client->post($this->paymobBaseUrl . 'acceptance/payments/pay', [
                'headers' => [
                    //'Authorization' => 'Bearer ' . $authToken,
                    'Accept' => 'application/json',
                ],
                'json' => $paymentData,
            ]);
     
             $responseData = json_decode($response->getBody(), true);
            // ✅ Log Paymob Response
            Log::info('💳 Card Payment Response from Paymob:', [
                'status_code' => $response->getStatusCode(),
                'response' => $responseData
            ]);    
            
            // ✅ تحقق من نجاح العملية
            $success = $responseData['success'] ?? false;
                
            return [
                'success' => $success,
                'id' => $responseData['id'] ?? null,
                'txn_response_code' => $responseData['txn_response_code'] ?? null,
                'message' => $success ? 'Payment succeeded' : ($responseData['data']['message'] ?? 'Payment failed'),
                'error' => $success ? null : $responseData,
                'raw' => $responseData
            ];

         } catch (RequestException $e) {
            $responseBody = $e->hasResponse() ? json_decode($e->getResponse()->getBody(), true) : null;

            Log::error('❌ Card Payment RequestException:', [
                'error_message' => $e->getMessage(),
                'response_body' => $responseBody,
            ]);

            return [
                'success' => false,
                'message' => 'Exception during card payment.',
                'error' => $responseBody ?? $e->getMessage(),
            ];
         } catch (\Exception $e) {
             // Handle other exceptions
             Log::error('Card Payment Exception:', ['error_message' => $e->getMessage()]);
             return [
                 'success' => false,
                 'message' => 'An unexpected error occurred while processing the card payment.',
                 'error' => $e->getMessage(),
             ];
         }
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
    
    public function processWithdrawal($paymobUserId, $amount, $method, $walletNumber = null)
    {
        try {
            $authToken = $this->authenticate();
            if (!$authToken) {
                throw new \Exception('Paymob authentication failed');
            }
    
            $payload = [
                'auth_token' => $authToken,
                'user_id' => $paymobUserId,
                'amount' => $amount * 100, // Paymob uses cents
                'disbursement_method' => $method,
            ];
    
            // For Vodafone Cash, include wallet number
            if ($method === 'vodafone_cash' && $walletNumber) {
                $payload['wallet_number'] = $walletNumber;
            }
    
            $response = $this->client->post($this->paymobBaseUrl . 'disbursements', [
                'json' => $payload,
            ]);
    
            $responseBody = json_decode($response->getBody(), true);
    
            if ($response->getStatusCode() === 200 && isset($responseBody['id'])) {
                return [
                    'success' => true,
                    'transaction_id' => $responseBody['id'],
                ];
            } else {
                Log::error('Paymob withdrawal failed', ['response' => $responseBody]);
                return [
                    'success' => false,
                    'message' => $responseBody['message'] ?? 'Withdrawal failed',
                ];
            }
        } catch (RequestException $e) {
            $errorResponse = $e->hasResponse() ? json_decode($e->getResponse()->getBody(), true) : null;
            Log::error('Paymob withdrawal error', ['error' => $errorResponse ?? $e->getMessage()]);
            return [
                'success' => false,
                'message' => $errorResponse['message'] ?? $e->getMessage(),
            ];
        }
    }

    
    /**
     * Verify the authenticity of the callback request using HMAC signature.
     *
     * @param Request $request The incoming request.
     * @param array $data The incoming callback data.
     * @return bool True if the signature is valid, false otherwise.
     */
    public function verifyCallbackSignature(array $data): bool
    {
        // $signature = $request->header('X-Paymob-Signature');
        $signature = $data['hmac'] ?? '';
        if (empty($signature)) {
            return false;
        }

        $hmacSecret = config('services.paymob.hmac_secret');
        $payload = json_encode(  $data  /*$request->all()*/);
        $expectedSignature = hash_hmac('sha256', $payload, $hmacSecret);

        return hash_equals($signature, $expectedSignature);
    }

    /**
     * Handle successful payment events.
     *
     * @param array $data The callback data.
     */
    public function handlePaymentSuccess(array $data)
    {
        $orderID = $data['order']['id'] ?? null;

        if ($orderID) {
            Payment::where('order_id', $orderID)                     // Update the payment status in the database
                ->update([
                    'transaction_id' => $data['id'] ?? null,
                    'status' => 'success',
                ]);
        }

        Log::info("Paymob Payment Succeeded for Order ID: $orderID");
    }

    /**
     * Handle failed payment events.
     *
     * @param array $data The callback data.
     */
    public function handlePaymentFailure(array $data)
    {
        $orderID = $data['order']['id'] ?? null;

        if ($orderID) {
            Payment::where('order_id', $orderID)                      
                ->update([
                    'status' => 'failed',
                ]);
        }

        Log::warning("Paymob Payment Failed for Order ID: $orderID");
    }
}