<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\FeeDemand;
use App\Models\OnlinePaymentTransaction;
use App\Services\Payments\CashfreeGatewayService;
use App\Services\Payments\OnlineFeePaymentService;
use App\Services\Payments\PayUGatewayService;
use App\Services\Payments\RazorpayGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CollegeOnlineFeePaymentController extends Controller
{
    public function initiate(Request $request, College $college, OnlineFeePaymentService $online): JsonResponse
    {
        $this->auth($request, $college, 'college_fee_payment.collect');
        $data = $request->validate([
            'demand_id' => ['required', 'integer', Rule::exists('fee_demands', 'id')->where(fn ($q) => $q->where('college_id', $college->id)->where('status', '!=', 'CANCELLED'))],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'include_optional' => ['sometimes', 'boolean'],
            'include_late_fine' => ['sometimes', 'boolean'],
            'include_future' => ['sometimes', 'boolean'],
        ]);

        $payload = $online->initiate($college, FeeDemand::findOrFail((int) $data['demand_id']), $data, $request->user()->id);
        return response()->json($payload);
    }

    public function verifyRazorpay(
        Request $request,
        College $college,
        RazorpayGatewayService $razorpay,
        OnlineFeePaymentService $online,
    ): JsonResponse {
        $this->auth($request, $college, 'college_fee_payment.collect');
        $data = $request->validate([
            'transaction_id' => ['required', 'integer'],
            'razorpay_payment_id' => ['required', 'string', 'max:120'],
            'razorpay_order_id' => ['required', 'string', 'max:120'],
            'razorpay_signature' => ['required', 'string', 'max:255'],
        ]);
        $transaction = $this->transaction($college, (int) $data['transaction_id'], 'RAZORPAY');
        $gateway = $transaction->gateway;

        if (! hash_equals((string) $transaction->provider_order_id, (string) $data['razorpay_order_id'])) {
            throw ValidationException::withMessages(['payment' => 'Razorpay Order ID does not match the server-created order.']);
        }
        if (! $razorpay->verifyCheckoutSignature((string) $transaction->provider_order_id, $data['razorpay_payment_id'], $data['razorpay_signature'], (string) $gateway->key_secret)) {
            throw ValidationException::withMessages(['payment' => 'Razorpay checkout signature verification failed.']);
        }

        $providerPayment = $razorpay->fetchPayment($gateway, $data['razorpay_payment_id']);
        $expectedPaise = (int) round((float) $transaction->amount * 100);
        if ((string) ($providerPayment['order_id'] ?? '') !== (string) $transaction->provider_order_id
            || (int) ($providerPayment['amount'] ?? 0) !== $expectedPaise
            || strtoupper((string) ($providerPayment['currency'] ?? '')) !== strtoupper($transaction->currency)) {
            throw ValidationException::withMessages(['payment' => 'Razorpay payment amount/order verification failed.']);
        }
        if (strtolower((string) ($providerPayment['status'] ?? '')) !== 'captured') {
            throw ValidationException::withMessages(['payment' => 'Razorpay payment is not captured yet. Current status: '.($providerPayment['status'] ?? 'unknown').'.']);
        }

        $payment = $online->postVerified($transaction, $data['razorpay_payment_id'], strtoupper((string) $providerPayment['status']), ['razorpay_payment' => $providerPayment]);
        return response()->json(['ok' => true, 'receipt_no' => $payment->receipt_no]);
    }

    public function verifyCashfree(
        Request $request,
        College $college,
        CashfreeGatewayService $cashfree,
        OnlineFeePaymentService $online,
    ): JsonResponse {
        $this->auth($request, $college, 'college_fee_payment.collect');
        $data = $request->validate(['transaction_id' => ['required', 'integer']]);
        $transaction = $this->transaction($college, (int) $data['transaction_id'], 'CASHFREE');
        $gateway = $transaction->gateway;

        $order = $cashfree->fetchOrder($gateway, (string) $transaction->provider_order_id);
        if (strtoupper((string) ($order['order_status'] ?? '')) !== 'PAID') {
            throw ValidationException::withMessages(['payment' => 'Cashfree order is not PAID yet. Current status: '.($order['order_status'] ?? 'unknown').'.']);
        }
        if (abs((float) ($order['order_amount'] ?? 0) - (float) $transaction->amount) > 0.009
            || strtoupper((string) ($order['order_currency'] ?? '')) !== strtoupper($transaction->currency)) {
            throw ValidationException::withMessages(['payment' => 'Cashfree payment amount/currency verification failed.']);
        }

        $payments = collect($cashfree->fetchOrderPayments($gateway, (string) $transaction->provider_order_id));
        $success = $payments->first(fn ($row) => strtoupper((string) data_get($row, 'payment_status', '')) === 'SUCCESS');
        if (! is_array($success)) {
            throw ValidationException::withMessages(['payment' => 'Cashfree has not returned a SUCCESS payment for this order yet.']);
        }
        $paymentId = (string) ($success['cf_payment_id'] ?? '');
        if ($paymentId === '') {
            throw ValidationException::withMessages(['payment' => 'Cashfree SUCCESS payment is missing cf_payment_id.']);
        }

        $payment = $online->postVerified($transaction, $paymentId, 'SUCCESS', ['cashfree_order' => $order, 'cashfree_payment' => $success]);
        return response()->json(['ok' => true, 'receipt_no' => $payment->receipt_no]);
    }

    public function payuReturn(Request $request, PayUGatewayService $payu, OnlineFeePaymentService $online): RedirectResponse
    {
        $payload = $request->all();
        $txnid = (string) ($payload['txnid'] ?? '');
        $transaction = OnlinePaymentTransaction::query()->with('gateway')->where('provider', 'PAYU')->where('purpose', 'FEE_PAYMENT')
            ->where(function ($q) use ($txnid) { $q->where('reference_no', $txnid)->orWhere('provider_order_id', $txnid); })->first();
        if (! $transaction || ! $transaction->gateway) {
            return redirect('/login')->with('toast', ['type' => 'error', 'message' => 'PayU payment transaction could not be found.']);
        }
        $target = route('college-fee-payments.index', ['college' => $transaction->college_id]);

        if ((string) ($payload['key'] ?? '') !== (string) $transaction->gateway->key_id
            || ! $payu->verifyCallbackHash($payload, (string) $transaction->gateway->key_secret)) {
            $transaction->update(['status' => 'PAYMENT_FAILED', 'provider_status' => 'INVALID_HASH', 'response_context' => array_merge($transaction->response_context ?? [], ['payu_return' => $payload])]);
            return redirect($target)->with('toast', ['type' => 'error', 'message' => 'PayU payment response signature verification failed.']);
        }

        if (strtolower((string) ($payload['status'] ?? '')) !== 'success') {
            $transaction->update(['status' => 'PAYMENT_FAILED', 'provider_status' => strtoupper((string) ($payload['status'] ?? 'FAILED')), 'response_context' => array_merge($transaction->response_context ?? [], ['payu_return' => $payload])]);
            return redirect($target)->with('toast', ['type' => 'error', 'message' => 'PayU payment was not successful.']);
        }

        try {
            $verified = $payu->verifyPayment($transaction->gateway, $txnid);
            $details = $verified['details'];
            if (strtolower((string) ($details['status'] ?? '')) !== 'success'
                || abs((float) ($details['amt'] ?? $details['amount'] ?? 0) - (float) $transaction->amount) > 0.009) {
                throw ValidationException::withMessages(['payment' => 'PayU server-side payment verification did not confirm the expected successful amount.']);
            }
            $providerPaymentId = (string) ($details['mihpayid'] ?? $payload['mihpayid'] ?? $txnid);
            $payment = $online->postVerified($transaction, $providerPaymentId, 'SUCCESS', ['payu_return' => $payload, 'payu_verify' => $verified['response']]);
            return redirect($target)->with('toast', ['type' => 'success', 'message' => 'Online payment verified and posted. Receipt '.$payment->receipt_no.'.']);
        } catch (ValidationException $e) {
            return redirect($target)->with('toast', ['type' => 'error', 'message' => collect($e->errors())->flatten()->first()]);
        }
    }

    private function transaction(College $college, int $id, string $provider): OnlinePaymentTransaction
    {
        $transaction = OnlinePaymentTransaction::query()->with('gateway')->whereKey($id)->where('college_id', $college->id)
            ->where('purpose', 'FEE_PAYMENT')->where('provider', $provider)->firstOrFail();
        abort_unless($transaction->gateway, 404);
        return $transaction;
    }

    private function auth(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
