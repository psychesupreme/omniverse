<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\MpesaCheckoutRequest;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaPaymentController extends Controller
{
    /**
     * Resolve the base URL based on config environment.
     */
    private function getBaseUrl(): string
    {
        return config('services.mpesa.env') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Fetch the Daraja API OAuth access token.
     */
    private function getAccessToken(): string
    {
        $consumerKey = config('services.mpesa.consumer_key');
        $consumerSecret = config('services.mpesa.consumer_secret');

        if (empty($consumerKey) || empty($consumerSecret)) {
            throw new \Exception('M-Pesa API Consumer Key or Secret is missing in config.');
        }

        $credentials = base64_encode($consumerKey . ':' . $consumerSecret);
        $url = $this->getBaseUrl() . '/oauth/v1/generate?grant_type=client_credentials';

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])->get($url);

        if ($response->failed()) {
            Log::error('M-Pesa OAuth failed: ' . $response->body());
            throw new \Exception('Failed to fetch M-Pesa OAuth access token: ' . $response->status());
        }

        return $response->json()['access_token'] ?? throw new \Exception('Access token key not found in M-Pesa response.');
    }

    /**
     * Initiate an M-Pesa STK Push payment.
     */
    public function initiateStkPush(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'exists:tenants,id'],
            'plan_id'   => ['required', 'exists:subscription_plans,id'],
            'phone'     => ['required', 'regex:/^(2547|2541)\d{8}$/'],
        ]);

        try {
            $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
            $tenant = Tenant::findOrFail($validated['tenant_id']);

            $shortcode = config('services.mpesa.shortcode');
            $passkey = config('services.mpesa.passkey');
            $callbackUrl = config('services.mpesa.callback_url');
            $timestamp = now()->format('YmdHis');

            // Secure password hash generation for Daraja LNM API
            $password = base64_encode($shortcode . $passkey . $timestamp);

            $token = $this->getAccessToken();
            $url = $this->getBaseUrl() . '/mpesa/stkpush/v1/processrequest';

            // M-Pesa sandbox accepts whole integers for payment test suites
            $amount = (int) round((float) $plan->price_monthly);

            $payload = [
                'BusinessShortCode' => (int) $shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'TransactionType'   => 'CustomerPayBillOnline',
                'Amount'            => $amount,
                'PartyA'            => (int) $validated['phone'],
                'PartyB'            => (int) $shortcode,
                'PhoneNumber'       => (int) $validated['phone'],
                'CallBackURL'       => $callbackUrl,
                'AccountReference'  => 'Tenant_' . $tenant->id,
                'TransactionDesc'   => 'OmniRoute Sub: ' . $plan->name,
            ];

            $response = Http::withToken($token)->post($url, $payload);

            if ($response->failed()) {
                Log::error('M-Pesa STK Push failed: ' . $response->body());
                return response()->json([
                    'message' => 'M-Pesa STK Push initiation failed.',
                    'error'   => $response->json('errorMessage') ?? $response->body(),
                ], 500);
            }

            $responseData = $response->json();

            // Log CheckoutRequest details to track transactions in central db
            if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == '0') {
                MpesaCheckoutRequest::create([
                    'tenant_id'            => $tenant->id,
                    'subscription_plan_id' => $plan->id,
                    'checkout_request_id'  => $responseData['CheckoutRequestID'],
                    'merchant_request_id'  => $responseData['MerchantRequestID'],
                    'phone'                => $validated['phone'],
                    'amount'               => $amount,
                    'status'               => 'pending',
                ]);

                return response()->json([
                    'message'             => 'STK Push initiated successfully.',
                    'checkout_request_id' => $responseData['CheckoutRequestID'],
                    'customer_message'    => $responseData['CustomerMessage'] ?? 'Please check your phone for the PIN prompt.',
                ], 200);
            }

            return response()->json([
                'message' => 'Safaricom returned a non-zero response code.',
                'error'   => $responseData,
            ], 400);

        } catch (\Exception $e) {
            Log::error('M-Pesa payment exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred while initiating payment.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
