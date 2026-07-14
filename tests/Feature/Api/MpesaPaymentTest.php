<?php

namespace Tests\Feature\Api;

use App\Models\MpesaCheckoutRequest;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MpesaPaymentTest extends TestCase
{
    protected string $token;
    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up tables to ensure fresh state without using database transactions
        MpesaCheckoutRequest::query()->delete();
        SubscriptionPlan::whereIn('slug', ['pro', 'basic'])->delete();
        User::query()->delete();

        // 1. Seed plan & link it to the automatically set up test tenant
        $this->plan = SubscriptionPlan::create([
            'name'          => 'Pro',
            'slug'          => 'pro',
            'price_monthly' => 149.00,
            'price_annual'  => 1490.00,
            'max_users'     => 50,
            'max_outlets'   => 5000,
            'features'      => [],
        ]);

        $this->tenant->update([
            'subscription_plan_id' => $this->plan->id,
            'status'               => 'pending',
        ]);

        // 2. Create user for authentication
        $user = User::factory()->create();
        $this->token = $user->createToken('test-token')->plainTextToken;

        // 3. Set standard M-Pesa environment settings
        config([
            'services.mpesa.consumer_key'    => 'test-key',
            'services.mpesa.consumer_secret' => 'test-secret',
            'services.mpesa.shortcode'       => '174379',
            'services.mpesa.passkey'         => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
            'services.mpesa.callback_url'    => 'http://localhost/api/billing/mpesa/callback',
            'services.mpesa.env'             => 'sandbox',
        ]);

        // End tenancy since billing STK push & callback endpoints reside on the central app routes
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }

    protected function tearDown(): void
    {
        MpesaCheckoutRequest::query()->delete();
        SubscriptionPlan::whereIn('slug', ['pro', 'basic'])->delete();
        User::query()->delete();

        parent::tearDown();
    }

    public function test_mpesa_stk_push_initiation_successful()
    {
        // Mock Safaricom API calls
        Http::fake([
            'oauth/v1/generate*' => Http::response([
                'access_token' => 'mocked-access-token',
                'expires_in'   => '3599'
            ], 200),
            'mpesa/stkpush/v1/processrequest*' => Http::response([
                'MerchantRequestID'   => '12345-67890-1',
                'CheckoutRequestID'   => 'ws_CO_14072026110000001',
                'ResponseCode'        => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CustomerMessage'     => 'Success. Request accepted for processing'
            ], 200),
        ]);

        $response = $this->withToken($this->token)->postJson('/api/billing/mpesa/stk', [
            'tenant_id' => $this->tenant->id,
            'plan_id'   => $this->plan->id,
            'phone'     => '254712345678',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message'             => 'STK Push initiated successfully.',
            'checkout_request_id' => 'ws_CO_14072026110000001',
        ]);

        // Assert pending transaction created in database
        $this->assertDatabaseHas('mpesa_checkout_requests', [
            'tenant_id'            => $this->tenant->id,
            'subscription_plan_id' => $this->plan->id,
            'checkout_request_id'  => 'ws_CO_14072026110000001',
            'status'               => 'pending',
            'amount'               => 149.00,
        ]);
    }

    public function test_mpesa_callback_updates_tenant_to_active()
    {
        // 1. Create a pending request record
        $checkout = MpesaCheckoutRequest::create([
            'tenant_id'            => $this->tenant->id,
            'subscription_plan_id' => $this->plan->id,
            'checkout_request_id'  => 'ws_CO_14072026110000001',
            'merchant_request_id'  => '12345-67890-1',
            'phone'                => '254712345678',
            'amount'               => 149.00,
            'status'               => 'pending',
        ]);

        // 2. Post callback payload simulating client transaction success
        $response = $this->postJson('/api/billing/mpesa/callback', [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => '12345-67890-1',
                    'CheckoutRequestID' => 'ws_CO_14072026110000001',
                    'ResultCode'        => 0,
                    'ResultDesc'        => 'The service request is processed successfully.',
                    'CallbackMetadata'  => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => 149.00],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'NLJ7RT61SV'],
                            ['Name' => 'TransactionDate', 'Value' => 20260714201015],
                            ['Name' => 'PhoneNumber', 'Value' => 254712345678]
                        ]
                    ]
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback received successfully',
        ]);

        // Assert transaction status updated to success
        $this->assertEquals('success', $checkout->fresh()->status);

        // Assert tenant status updated to active and billing advanced by 30 days
        $this->tenant = $this->tenant->fresh();
        $this->assertEquals('active', $this->tenant->status);
        $this->assertEquals($this->plan->id, $this->tenant->subscription_plan_id);
        $this->assertNotNull($this->tenant->subscription_ends_at);
        $this->assertTrue($this->tenant->subscription_ends_at->isAfter(now()->addDays(29)));
    }

    public function test_mpesa_callback_marks_transaction_as_failed()
    {
        $checkout = MpesaCheckoutRequest::create([
            'tenant_id'            => $this->tenant->id,
            'subscription_plan_id' => $this->plan->id,
            'checkout_request_id'  => 'ws_CO_14072026110000001',
            'merchant_request_id'  => '12345-67890-1',
            'phone'                => '254712345678',
            'amount'               => 149.00,
            'status'               => 'pending',
        ]);

        // Post callback payload simulating cancellation/failed STK push transaction
        $response = $this->postJson('/api/billing/mpesa/callback', [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => '12345-67890-1',
                    'CheckoutRequestID' => 'ws_CO_14072026110000001',
                    'ResultCode'        => 1032,
                    'ResultDesc'        => 'Request cancelled by user',
                ]
            ]
        ]);

        $response->assertStatus(200);
        $this->assertEquals('failed', $checkout->fresh()->status);
        // Tenant state remains pending (unchanged)
        $this->assertEquals('pending', $this->tenant->fresh()->status);
    }
}
