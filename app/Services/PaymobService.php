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


public function storePayment(array $paymentData)
{
    try {
        return Payment::create([
            'user_id' => $paymentData['user_id'] ?? null,
            'order_id' => $paymentData['order_id'] ?? null,
            'payment_method' => $paymentData['payment_method'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'] ?? 'EGP',
            'payment_status' => $paymentData['status'] ?? 'pending',
            'transaction_id' => $paymentData['transaction_id'] ?? null,
            'paymob_order_id' => $paymentData['paymob_order_id'] ?? null,
            'paymob_transaction_id' => $paymentData['paymob_transaction_id'] ?? null,
            'card_details' => $paymentData['card_details'] ?? null,
            'wallet_number' => $paymentData['wallet_number'] ?? null,
            'payment_response' => $paymentData['response'] ?? null
        ]);
    } catch (\Exception $e) {
        Log::error('Error storing payment in database', [
            'error' => $e->getMessage(),
            'payment_data' => $paymentData
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
    
            // Check for a successful response
            if ($response->getStatusCode() === 200 && isset($responseBody['success']) && $responseBody['success']) {
                Log::info('Received Vodafone Cash Payment Response from Paymob:', ['response' => $responseBody]);
                $status = 'success';
            } else {
                Log::error('Vodafone Cash Payment Failed:', ['response' => json_encode($responseBody, JSON_PRETTY_PRINT)]);
                $status = 'failed';
            }
    
            // Store payment in DB
            $this->storePayment([
                'payment_method' => 'vodafone_cash',
                'amount' => ($responseBody['amount_cents'] ?? 0) / 100,
                'transaction_id' => $responseBody['id'] ?? null,
                'status' => $status,
                'response' => $responseBody,
                'wallet_number' => $walletNumber, // Store wallet number for Vodafone Cash
            ]);
    
            return [
                'success' => $status === 'success',
                'message' => $status === 'success' ? 'Vodafone Cash payment processed successfully.' : 'Vodafone Cash payment failed: ' . ($responseBody['data']['message'] ?? 'Unknown error'),
                'data' => $responseBody,
            ];
        } catch (RequestException $e) {
            Log::error('Vodafone Cash Payment Exception:', ['error' => $e->getMessage()]);
            $this->storePayment([
                'payment_method' => 'vodafone_cash',
                'amount' => 0,
                'status' => 'failed',
                'response' => ['error' => $e->getMessage()],
            ]);
    
            return ['success' => false, 'message' => 'An error occurred while processing the payment.'];
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



     public function processCardPayment(string $authToken, string $paymentKey, array $cardDetails, Order $order): ?array
     {
        $paymentData = [
            'source' => [
                'identifier' => $cardDetails['card_number'],
                'subtype' => 'CARD',
                'holder_name' => $cardDetails['card_holder_name'],
                'expiry_month' => $cardDetails['card_expiry_month'],
                'expiry_year' => $cardDetails['card_expiry_year'],
                'cvn' => $cardDetails['card_cvn'],
            ],
            'billing_data' => $this->getBillingData($order->user),
            'payment_token' => $paymentKey
        ];
         try {    
             // Make the API request to Paymob's card payment endpoint
             $response = $this->client->post($this->paymobBaseUrl . 'acceptance/payments/pay', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $authToken,
                    'Accept' => 'application/json',
                ],
                'json' => $paymentData,
            ]);
     
             $responseData = json_decode($response->getBody(), true);
            // ✅ Log Paymob Response
            Log::info('Received Card Payment Response from Paymob:', [
                'status_code' => $response->getStatusCode(),
                'response' => json_encode($responseData, JSON_PRETTY_PRINT)
            ]);     
            
             // Check for a successful response
            if ($response->getStatusCode() !== 200) {
                Log::error('Paymob Card Payment Failed', [
                    'response' => $responseData,
                    'request' => $paymentData
                ]);
                
                return [
                    'success' => false,
                    'message' => $responseData['message'] ?? 'Card payment failed',
                    'error' => $responseData
                ];
            }
    
            // Store payment in DB
            $this->storePayment([
                'payment_method' => 'card',
                'amount' => ($responseBody['amount_cents'] ?? 0) / 100,
                'transaction_id' => $responseBody['id'] ?? null,
                'response' => $responseData,
                'card_number' => substr($cardDetails['card_number'], -4),
                'card_holder_name' => $cardDetails['card_holder_name'] ?? null,
            ]);
    
            return $responseData;

         } catch (RequestException $e) {
             //Handle Guzzle HTTP request errors
             $responseBody = $e->hasResponse() ? json_decode($e->getResponse()->getBody(), true) : null;
             Log::error('Card Payment RequestException:', [
                'error_message' => $e->getMessage(),
                'response_body' => json_encode($responseBody, JSON_PRETTY_PRINT),
            ]);
            $this->storePayment([
                'payment_method' => 'card',
                'amount' => $cardDetails['amount'] ?? 0,
                'status' => 'failed',
                'response' => ['error' => $e->getMessage()],
            ]);
            
             return [
                 'success' => false,
                 'message' => 'An error occurred while processing the card payment.',
                 'error' => $e->getMessage(),
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