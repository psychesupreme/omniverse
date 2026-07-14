<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\MpesaCheckoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    /**
     * Handle the asynchronous M-Pesa Callback response from Safaricom.
     */
    public function handleCallback(Request $request): JsonResponse
    {
        $callbackData = $request->input('Body.stkCallback');

        if (! $callbackData) {
            Log::warning('M-Pesa Callback payload structure invalid.', ['payload' => $request->all()]);
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Invalid Callback Payload'], 400);
        }

        $checkoutRequestId = $callbackData['CheckoutRequestID'] ?? null;
        $resultCode = $callbackData['ResultCode'] ?? null;
        $resultDesc = $callbackData['ResultDesc'] ?? 'No description provided';

        if (! $checkoutRequestId) {
            Log::warning('M-Pesa Callback missing CheckoutRequestID.');
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Missing CheckoutRequestID'], 400);
        }

        // Find the checkout request in the central database
        $checkoutRequest = MpesaCheckoutRequest::where('checkout_request_id', $checkoutRequestId)->first();

        if (! $checkoutRequest) {
            Log::warning("M-Pesa CheckoutRequestID {$checkoutRequestId} not found in central database.");
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'CheckoutRequestID not found'], 404);
        }

        if ($resultCode === 0) {
            // Transaction succeeded
            $checkoutRequest->update(['status' => 'success']);

            // Find associated Tenant and update subscription settings
            $tenant = $checkoutRequest->tenant;

            if ($tenant) {
                $tenant->update([
                    'status'               => 'active',
                    'subscription_plan_id' => $checkoutRequest->subscription_plan_id,
                    'subscription_ends_at' => now()->addDays(30),
                ]);

                Log::info("Tenant {$tenant->id} subscription successfully updated via M-Pesa. Plan ID: {$checkoutRequest->subscription_plan_id}");
            } else {
                Log::error("Associated Tenant not found for MpesaCheckoutRequest ID: {$checkoutRequest->id}");
            }
        } else {
            // Transaction failed
            $checkoutRequest->update(['status' => 'failed']);
            Log::warning("M-Pesa STK Push rejected by customer. Request ID: {$checkoutRequestId}. Code: {$resultCode}. Desc: {$resultDesc}");
        }

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback received successfully',
        ], 200);
    }
}
