<?php

namespace App\Services\Payments;

use App\Models\CollegePaymentGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RazorpayGatewayService
{
    private const BASE_URL = 'https://api.razorpay.com/v1';

    public function createOrder(CollegePaymentGateway $gateway, int $amountPaise, string $currency, string $receipt, array $notes = []): array
    {
        if ($gateway->provider !== 'RAZORPAY') {
            throw ValidationException::withMessages(['gateway' => 'Selected credential profile is not Razorpay.']);
        }
        if ($gateway->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['gateway' => 'Activate the Razorpay credential profile before creating an order.']);
        }
        if ($gateway->environment !== 'TEST') {
            throw ValidationException::withMessages(['gateway' => 'ADR 184 allows Razorpay TEST orders only. LIVE order creation is intentionally blocked until TEST payment verification passes.']);
        }
        if (blank($gateway->key_id) || blank($gateway->key_secret)) {
            throw ValidationException::withMessages(['gateway' => 'Razorpay Key ID and Key Secret are required.']);
        }
        if ($amountPaise < 100) {
            throw ValidationException::withMessages(['amount' => 'Test order amount must be at least ₹1.00.']);
        }

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withBasicAuth((string) $gateway->key_id, (string) $gateway->key_secret)
                ->timeout(15)
                ->post(self::BASE_URL.'/orders', [
                    'amount' => $amountPaise,
                    'currency' => strtoupper($currency),
                    'receipt' => $receipt,
                    'notes' => $notes,
                ]);
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages(['gateway' => 'Could not connect to Razorpay. Check internet/server connectivity and try again.']);
        }

        if (! $response->successful()) {
            $message = data_get($response->json(), 'error.description')
                ?: data_get($response->json(), 'error.reason')
                ?: 'Razorpay rejected the order request.';
            throw ValidationException::withMessages(['gateway' => $message]);
        }

        $payload = $response->json();
        if (blank($payload['id'] ?? null)) {
            throw ValidationException::withMessages(['gateway' => 'Razorpay returned an order response without an Order ID.']);
        }

        return $payload;
    }

    public function verifyCheckoutSignature(string $serverOrderId, string $paymentId, string $signature, string $keySecret): bool
    {
        $expected = hash_hmac('sha256', $serverOrderId.'|'.$paymentId, $keySecret);
        return hash_equals($expected, $signature);
    }
    public function createFeeOrder(CollegePaymentGateway $gateway, int $amountPaise, string $currency, string $receipt, array $notes = []): array
    {
        $this->assertFeeCheckoutGateway($gateway);

        try {
            $response = Http::asJson()->acceptJson()
                ->withBasicAuth((string) $gateway->key_id, (string) $gateway->key_secret)
                ->connectTimeout(10)->timeout(20)
                ->post(self::BASE_URL.'/orders', [
                    'amount' => $amountPaise,
                    'currency' => strtoupper($currency),
                    'receipt' => $receipt,
                    'notes' => $notes,
                ]);
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages(['gateway' => 'Could not connect to Razorpay. Check internet/server connectivity and try again.']);
        }

        if (! $response->successful()) {
            $message = data_get($response->json(), 'error.description') ?: data_get($response->json(), 'error.reason') ?: 'Razorpay rejected the order request.';
            throw ValidationException::withMessages(['gateway' => $message]);
        }

        $payload = $response->json();
        if (blank($payload['id'] ?? null)) {
            throw ValidationException::withMessages(['gateway' => 'Razorpay returned an order response without an Order ID.']);
        }
        return $payload;
    }

    public function fetchPayment(CollegePaymentGateway $gateway, string $paymentId): array
    {
        $this->assertFeeCheckoutGateway($gateway);
        try {
            $response = Http::acceptJson()
                ->withBasicAuth((string) $gateway->key_id, (string) $gateway->key_secret)
                ->connectTimeout(10)->timeout(20)
                ->get(self::BASE_URL.'/payments/'.rawurlencode($paymentId));
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages(['gateway' => 'Could not verify the Razorpay payment. Try again.']);
        }
        if (! $response->successful()) {
            throw ValidationException::withMessages(['gateway' => data_get($response->json(), 'error.description') ?: 'Razorpay payment verification failed.']);
        }
        return $response->json();
    }

    public function verifyWebhookSignature(string $rawBody, string $signature, string $webhookSecret): bool
    {
        if ($rawBody === '' || $signature === '' || $webhookSecret === '') return false;
        return hash_equals(hash_hmac('sha256', $rawBody, $webhookSecret), $signature);
    }

    private function assertFeeCheckoutGateway(CollegePaymentGateway $gateway): void
    {
        if ($gateway->provider !== 'RAZORPAY') throw ValidationException::withMessages(['gateway' => 'Selected credential profile is not Razorpay.']);
        if ($gateway->status !== 'ACTIVE') throw ValidationException::withMessages(['gateway' => 'The Razorpay credential profile is not ACTIVE.']);
        if ($gateway->environment !== 'TEST') throw ValidationException::withMessages(['gateway' => 'Online fee checkout remains TEST-only until payment QA is completed.']);
        if (blank($gateway->key_id) || blank($gateway->key_secret)) throw ValidationException::withMessages(['gateway' => 'Razorpay Key ID and Key Secret are required.']);
    }

}
