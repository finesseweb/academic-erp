<?php

namespace App\Services\Payments;

use App\Models\CollegePaymentGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CashfreeGatewayService
{
    private const TEST_BASE_URL = 'https://sandbox.cashfree.com/pg';
    private const API_VERSION = '2025-01-01';

    public function createTestOrder(
        CollegePaymentGateway $gateway,
        string $orderId,
        float $amount,
        string $currency = 'INR',
        array $tags = [],
    ): array {
        $this->assertTestGateway($gateway);

        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => 'Cashfree TEST order amount must be at least ₹1.00.']);
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withHeaders([
                    'x-client-id' => (string) $gateway->key_id,
                    'x-client-secret' => (string) $gateway->key_secret,
                    'x-api-version' => self::API_VERSION,
                ])
                ->connectTimeout(10)
                ->timeout(20)
                ->post(self::TEST_BASE_URL.'/orders', [
                    'order_id' => $orderId,
                    'order_amount' => $amount,
                    'order_currency' => strtoupper($currency),
                    'customer_details' => [
                        'customer_id' => 'academic_erp_credential_test',
                        'customer_name' => 'Academic ERP Test',
                        'customer_email' => 'qa@example.com',
                        'customer_phone' => '9999999999',
                    ],
                    'order_note' => 'Academic ERP credential connectivity test',
                    'order_tags' => $tags,
                ]);
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages([
                'gateway' => 'Could not connect to Cashfree Sandbox. Check internet/server connectivity and try again.',
            ]);
        }

        if (! $response->successful()) {
            $message = data_get($response->json(), 'message')
                ?: data_get($response->json(), 'type')
                ?: 'Cashfree rejected the TEST order request.';

            throw ValidationException::withMessages(['gateway' => $message]);
        }

        $payload = $response->json();
        if (blank($payload['order_id'] ?? null) || blank($payload['payment_session_id'] ?? null)) {
            throw ValidationException::withMessages([
                'gateway' => 'Cashfree returned an order response without the expected Order ID / Payment Session ID.',
            ]);
        }

        return $payload;
    }

    public function verifyWebhookSignature(string $rawBody, string $timestamp, string $signature, string $secretKey): bool
    {
        if ($rawBody === '' || $timestamp === '' || $signature === '' || $secretKey === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $timestamp.$rawBody, $secretKey, true));

        return hash_equals($expected, $signature);
    }

    private function assertTestGateway(CollegePaymentGateway $gateway): void
    {
        if ($gateway->provider !== 'CASHFREE') {
            throw ValidationException::withMessages(['gateway' => 'Selected credential profile is not Cashfree.']);
        }
        if ($gateway->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['gateway' => 'Activate the Cashfree credential profile before testing it.']);
        }
        if ($gateway->environment !== 'TEST') {
            throw ValidationException::withMessages(['gateway' => 'Cashfree credential testing is allowed only for TEST profiles.']);
        }
        if (blank($gateway->key_id) || blank($gateway->key_secret)) {
            throw ValidationException::withMessages(['gateway' => 'Cashfree App ID and Secret Key are required.']);
        }
    }
    public function createFeeOrder(
        CollegePaymentGateway $gateway,
        string $orderId,
        float $amount,
        string $currency,
        array $customer,
        ?string $notifyUrl = null,
        array $tags = [],
    ): array {
        $this->assertFeeCheckoutGateway($gateway);

        $payload = [
            'order_id' => $orderId,
            'order_amount' => round($amount, 2),
            'order_currency' => strtoupper($currency),
            'customer_details' => [
                'customer_id' => (string) ($customer['id'] ?? 'academic_erp_student'),
                'customer_name' => (string) ($customer['name'] ?? 'Student'),
                'customer_email' => (string) ($customer['email'] ?? 'student@example.com'),
                'customer_phone' => (string) ($customer['phone'] ?? '9999999999'),
            ],
            'order_note' => 'Academic ERP fee payment',
            'order_tags' => $tags,
        ];
        if ($notifyUrl) $payload['order_meta'] = ['notify_url' => $notifyUrl];

        try {
            $response = Http::asJson()->acceptJson()->withHeaders([
                'x-client-id' => (string) $gateway->key_id,
                'x-client-secret' => (string) $gateway->key_secret,
                'x-api-version' => self::API_VERSION,
            ])->connectTimeout(10)->timeout(20)->post(self::TEST_BASE_URL.'/orders', $payload);
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages(['gateway' => 'Could not connect to Cashfree Sandbox. Check internet/server connectivity and try again.']);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages(['gateway' => data_get($response->json(), 'message') ?: 'Cashfree rejected the fee order request.']);
        }
        $result = $response->json();
        if (blank($result['order_id'] ?? null) || blank($result['payment_session_id'] ?? null)) {
            throw ValidationException::withMessages(['gateway' => 'Cashfree returned an incomplete checkout response.']);
        }
        return $result;
    }

    public function fetchOrder(CollegePaymentGateway $gateway, string $orderId): array
    {
        $this->assertFeeCheckoutGateway($gateway);
        try {
            $response = Http::acceptJson()->withHeaders([
                'x-client-id' => (string) $gateway->key_id,
                'x-client-secret' => (string) $gateway->key_secret,
                'x-api-version' => self::API_VERSION,
            ])->connectTimeout(10)->timeout(20)->get(self::TEST_BASE_URL.'/orders/'.rawurlencode($orderId));
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages(['gateway' => 'Could not verify the Cashfree order. Try again.']);
        }
        if (! $response->successful()) throw ValidationException::withMessages(['gateway' => data_get($response->json(), 'message') ?: 'Cashfree order verification failed.']);
        return $response->json();
    }

    public function fetchOrderPayments(CollegePaymentGateway $gateway, string $orderId): array
    {
        $this->assertFeeCheckoutGateway($gateway);
        try {
            $response = Http::acceptJson()->withHeaders([
                'x-client-id' => (string) $gateway->key_id,
                'x-client-secret' => (string) $gateway->key_secret,
                'x-api-version' => self::API_VERSION,
            ])->connectTimeout(10)->timeout(20)->get(self::TEST_BASE_URL.'/orders/'.rawurlencode($orderId).'/payments');
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages(['gateway' => 'Could not verify the Cashfree payment. Try again.']);
        }
        if (! $response->successful()) throw ValidationException::withMessages(['gateway' => data_get($response->json(), 'message') ?: 'Cashfree payment verification failed.']);
        return is_array($response->json()) ? $response->json() : [];
    }

    private function assertFeeCheckoutGateway(CollegePaymentGateway $gateway): void
    {
        if ($gateway->provider !== 'CASHFREE') throw ValidationException::withMessages(['gateway' => 'Selected credential profile is not Cashfree.']);
        if ($gateway->status !== 'ACTIVE') throw ValidationException::withMessages(['gateway' => 'The Cashfree credential profile is not ACTIVE.']);
        if ($gateway->environment !== 'TEST') throw ValidationException::withMessages(['gateway' => 'Online fee checkout remains TEST-only until payment QA is completed.']);
        if (blank($gateway->key_id) || blank($gateway->key_secret)) throw ValidationException::withMessages(['gateway' => 'Cashfree App ID and Secret Key are required.']);
    }

}
