<?php

namespace App\Http\Controllers;

use App\Models\OnlinePaymentTransaction;
use App\Services\Payments\CashfreeGatewayService;
use App\Services\Payments\OnlineFeePaymentService;
use App\Services\Payments\PayUGatewayService;
use App\Services\Payments\RazorpayGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends Controller
{
    public function razorpay(Request $request, RazorpayGatewayService $razorpay, OnlineFeePaymentService $online): JsonResponse
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) return response()->json(['message' => 'Invalid JSON payload.'], 400);

        $orderId = (string) data_get($payload, 'payload.payment.entity.order_id', '');
        if ($orderId === '') return response()->json(['message' => 'Missing Razorpay order_id.'], 400);
        $transaction = OnlinePaymentTransaction::query()->with('gateway')->where('provider', 'RAZORPAY')->where('provider_order_id', $orderId)->first();
        if (! $transaction || ! $transaction->gateway) return response()->json(['message' => 'Transaction not found.'], 404);

        $signature = (string) $request->header('x-razorpay-signature', '');
        if (! $razorpay->verifyWebhookSignature($rawBody, $signature, (string) $transaction->gateway->webhook_secret)) {
            return response()->json(['message' => 'Invalid Razorpay webhook signature.'], 401);
        }

        $event = (string) ($payload['event'] ?? '');
        $payment = (array) data_get($payload, 'payload.payment.entity', []);
        $paymentId = (string) ($payment['id'] ?? '');
        $providerStatus = strtoupper((string) ($payment['status'] ?? ''));

        if ($transaction->purpose === 'FEE_PAYMENT' && $event === 'payment.captured') {
            if ((int) ($payment['amount'] ?? 0) !== (int) round((float) $transaction->amount * 100)) {
                return response()->json(['message' => 'Razorpay webhook amount mismatch.'], 409);
            }
            try {
                $online->postVerified($transaction, $paymentId, $providerStatus ?: 'CAPTURED', ['razorpay_webhook' => $payload]);
            } catch (ValidationException $e) {
                return response()->json(['message' => collect($e->errors())->flatten()->first()], 409);
            }
        } else {
            $this->reconcileOnly($transaction, $paymentId, $providerStatus, $event === 'payment.captured' ? 'PAYMENT_VERIFIED' : 'PAYMENT_PENDING', ['razorpay_webhook' => $payload]);
        }

        return response()->json(['ok' => true]);
    }

    public function cashfree(Request $request, CashfreeGatewayService $cashfree, OnlineFeePaymentService $online): JsonResponse
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) return response()->json(['message' => 'Invalid JSON payload.'], 400);

        $orderId = (string) data_get($payload, 'data.order.order_id', '');
        if ($orderId === '') return response()->json(['message' => 'Missing Cashfree order_id.'], 400);
        $transaction = OnlinePaymentTransaction::query()->with('gateway')->where('provider', 'CASHFREE')->where('provider_order_id', $orderId)->first();
        if (! $transaction || ! $transaction->gateway) return response()->json(['message' => 'Transaction not found.'], 404);

        if (! $cashfree->verifyWebhookSignature($rawBody, (string) $request->header('x-webhook-timestamp', ''), (string) $request->header('x-webhook-signature', ''), (string) $transaction->gateway->key_secret)) {
            return response()->json(['message' => 'Invalid Cashfree webhook signature.'], 401);
        }

        $paymentId = (string) data_get($payload, 'data.payment.cf_payment_id', '');
        $paymentStatus = strtoupper((string) data_get($payload, 'data.payment.payment_status', ''));
        $localStatus = match ($paymentStatus) { 'SUCCESS' => 'PAYMENT_VERIFIED', 'FAILED', 'USER_DROPPED', 'CANCELLED' => 'PAYMENT_FAILED', default => 'PAYMENT_PENDING' };

        if ($transaction->purpose === 'FEE_PAYMENT' && $paymentStatus === 'SUCCESS') {
            $amount = (float) data_get($payload, 'data.payment.payment_amount', 0);
            if (abs($amount - (float) $transaction->amount) > 0.009) return response()->json(['message' => 'Cashfree webhook amount mismatch.'], 409);
            try {
                $online->postVerified($transaction, $paymentId, 'SUCCESS', ['cashfree_webhook' => $payload]);
            } catch (ValidationException $e) {
                return response()->json(['message' => collect($e->errors())->flatten()->first()], 409);
            }
        } else {
            $this->reconcileOnly($transaction, $paymentId, $paymentStatus, $localStatus, ['cashfree_webhook' => $payload]);
        }

        return response()->json(['ok' => true]);
    }

    public function payu(Request $request, PayUGatewayService $payu, OnlineFeePaymentService $online): Response
    {
        $payload = $request->all();
        $transactionId = (string) ($payload['txnid'] ?? '');
        if ($transactionId === '') return response('Missing PayU txnid.', 400);
        $transaction = OnlinePaymentTransaction::query()->with('gateway')->where('provider', 'PAYU')->where(fn ($q) => $q->where('reference_no', $transactionId)->orWhere('provider_order_id', $transactionId))->first();
        if (! $transaction || ! $transaction->gateway) return response('Transaction not found.', 404);
        if ((string) ($payload['key'] ?? '') !== (string) $transaction->gateway->key_id) return response('PayU merchant key mismatch.', 401);
        if (! $payu->verifyCallbackHash($payload, (string) $transaction->gateway->key_secret)) return response('Invalid PayU callback hash.', 401);

        $providerStatus = strtolower((string) ($payload['status'] ?? ''));
        $localStatus = match ($providerStatus) { 'success' => 'PAYMENT_VERIFIED', 'failure', 'failed' => 'PAYMENT_FAILED', default => 'PAYMENT_PENDING' };

        if ($transaction->purpose === 'FEE_PAYMENT' && $providerStatus === 'success') {
            try {
                $verified = $payu->verifyPayment($transaction->gateway, $transactionId);
                $details = $verified['details'];
                if (strtolower((string) ($details['status'] ?? '')) !== 'success' || abs((float) ($details['amt'] ?? $details['amount'] ?? 0) - (float) $transaction->amount) > 0.009) {
                    return response('PayU server-side verification mismatch.', 409);
                }
                $providerPaymentId = (string) ($details['mihpayid'] ?? $payload['mihpayid'] ?? $transactionId);
                $online->postVerified($transaction, $providerPaymentId, 'SUCCESS', ['payu_webhook' => $payload, 'payu_verify' => $verified['response']]);
            } catch (ValidationException $e) {
                return response((string) collect($e->errors())->flatten()->first(), 409);
            }
        } else {
            $this->reconcileOnly($transaction, (string) ($payload['mihpayid'] ?? ''), strtoupper($providerStatus), $localStatus, ['payu_webhook' => $payload]);
        }

        return response('OK', 200);
    }

    private function reconcileOnly(OnlinePaymentTransaction $transaction, string $paymentId, string $providerStatus, string $localStatus, array $context): void
    {
        DB::transaction(function () use ($transaction, $paymentId, $providerStatus, $localStatus, $context) {
            $locked = OnlinePaymentTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'PAYMENT_POSTED') return;
            $locked->update([
                'provider_payment_id' => $paymentId !== '' ? $paymentId : $locked->provider_payment_id,
                'provider_status' => $providerStatus !== '' ? $providerStatus : $locked->provider_status,
                'status' => $localStatus,
                'response_context' => array_merge($locked->response_context ?? [], $context),
            ]);
        });
    }
}
