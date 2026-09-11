<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\CollegePaymentGateway;
use App\Models\OnlinePaymentTransaction;
use App\Services\Payments\CashfreeGatewayService;
use App\Services\Payments\PayUGatewayService;
use App\Services\Payments\RazorpayGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CollegeOnlinePaymentController extends Controller
{
    public function createRazorpayTestOrder(
        Request $request,
        College $college,
        CollegePaymentGateway $gateway,
        RazorpayGatewayService $razorpay,
    ): RedirectResponse {
        $this->auth($request, $college, 'college_payment_gateway.manage');
        $this->assertGatewayCollege($college, $gateway);

        if ($gateway->provider !== 'RAZORPAY') {
            throw ValidationException::withMessages(['gateway' => 'This test action is available only for Razorpay profiles.']);
        }

        $reference = 'RZPTEST-'.now()->format('YmdHis').'-'.$college->id.'-'.$gateway->id.'-'.Str::upper(Str::random(4));
        $transaction = $this->createTestTransaction($request, $college, $gateway, 'CREDENTIAL_TEST_ORDER', 1.00, $reference, [
            'amount_paise' => 100,
            'profile_name' => $gateway->display_name,
        ]);

        try {
            $order = $razorpay->createOrder($gateway->fresh(), 100, 'INR', $reference, [
                'academic_erp' => 'credential_test',
                'college_id' => (string) $college->id,
                'gateway_profile_id' => (string) $gateway->id,
            ]);
        } catch (ValidationException $e) {
            $this->markFailed($transaction, $e, $request);
            throw $e;
        }

        $transaction->update([
            'provider_order_id' => $order['id'],
            'provider_status' => $order['status'] ?? null,
            'status' => 'ORDER_CREATED',
            'response_context' => [
                'order_id' => $order['id'],
                'status' => $order['status'] ?? null,
                'amount' => $order['amount'] ?? null,
                'currency' => $order['currency'] ?? null,
                'receipt' => $order['receipt'] ?? null,
            ],
            'updated_by' => $request->user()->id,
        ]);

        $this->auditTestSuccess($request, $transaction, 'RAZORPAY_TEST_ORDER_CREATED');

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Razorpay TEST order created successfully · '.$transaction->provider_order_id.' · ₹1.00.',
        ]);
    }

    public function createCashfreeTestOrder(
        Request $request,
        College $college,
        CollegePaymentGateway $gateway,
        CashfreeGatewayService $cashfree,
    ): RedirectResponse {
        $this->auth($request, $college, 'college_payment_gateway.manage');
        $this->assertGatewayCollege($college, $gateway);

        if ($gateway->provider !== 'CASHFREE') {
            throw ValidationException::withMessages(['gateway' => 'This test action is available only for Cashfree profiles.']);
        }

        $reference = 'CFTEST-'.now()->format('YmdHis').'-'.$college->id.'-'.$gateway->id.'-'.Str::upper(Str::random(4));
        $orderId = 'CF'.now()->format('ymdHis').$college->id.$gateway->id.Str::upper(Str::random(5));
        $transaction = $this->createTestTransaction($request, $college, $gateway, 'CREDENTIAL_TEST_ORDER', 1.00, $reference, [
            'order_id' => $orderId,
            'profile_name' => $gateway->display_name,
        ]);

        try {
            $order = $cashfree->createTestOrder($gateway->fresh(), $orderId, 1.00, 'INR', [
                'academic_erp' => 'credential_test',
                'college_id' => (string) $college->id,
                'gateway_profile_id' => (string) $gateway->id,
            ]);
        } catch (ValidationException $e) {
            $this->markFailed($transaction, $e, $request);
            throw $e;
        }

        $transaction->update([
            'provider_order_id' => $order['order_id'],
            'provider_status' => $order['order_status'] ?? null,
            'status' => 'ORDER_CREATED',
            'response_context' => [
                'order_id' => $order['order_id'],
                'cf_order_id' => $order['cf_order_id'] ?? null,
                'order_status' => $order['order_status'] ?? null,
                'payment_session_id_present' => filled($order['payment_session_id'] ?? null),
                'order_amount' => $order['order_amount'] ?? null,
                'order_currency' => $order['order_currency'] ?? null,
            ],
            'updated_by' => $request->user()->id,
        ]);

        $this->auditTestSuccess($request, $transaction, 'CASHFREE_TEST_ORDER_CREATED');

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Cashfree TEST order created successfully · '.$transaction->provider_order_id.' · ₹1.00.',
        ]);
    }

    public function testPayUCredentials(
        Request $request,
        College $college,
        CollegePaymentGateway $gateway,
        PayUGatewayService $payu,
    ): RedirectResponse {
        $this->auth($request, $college, 'college_payment_gateway.manage');
        $this->assertGatewayCollege($college, $gateway);

        if ($gateway->provider !== 'PAYU') {
            throw ValidationException::withMessages(['gateway' => 'This test action is available only for PayU profiles.']);
        }

        $reference = 'PUTEST'.now()->format('ymdHis').$college->id.$gateway->id.Str::upper(Str::random(5));
        $transaction = $this->createTestTransaction($request, $college, $gateway, 'CREDENTIAL_TEST_API', 0.00, $reference, [
            'command' => 'verify_payment',
            'probe_transaction_id' => $reference,
            'profile_name' => $gateway->display_name,
        ]);

        try {
            $result = $payu->testCredentials($gateway->fresh(), $reference);
        } catch (ValidationException $e) {
            $this->markFailed($transaction, $e, $request);
            throw $e;
        }

        $detail = data_get($result, 'transaction_details.'.$reference, []);
        $transaction->update([
            'provider_status' => 'API_AUTHENTICATED',
            'status' => 'API_VERIFIED',
            'response_context' => [
                'api_status' => $result['status'] ?? null,
                'message' => $result['msg'] ?? null,
                'probe_result' => $detail,
            ],
            'updated_by' => $request->user()->id,
        ]);

        $this->auditTestSuccess($request, $transaction, 'PAYU_TEST_API_VERIFIED');

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'PayU TEST API authenticated successfully · '.$reference.'.',
        ]);
    }

    private function createTestTransaction(
        Request $request,
        College $college,
        CollegePaymentGateway $gateway,
        string $purpose,
        float $amount,
        string $reference,
        array $requestContext,
    ): OnlinePaymentTransaction {
        return OnlinePaymentTransaction::create([
            'university_id' => $college->university_id,
            'college_id' => $college->id,
            'college_payment_gateway_id' => $gateway->id,
            'provider' => $gateway->provider,
            'environment' => $gateway->environment,
            'purpose' => $purpose,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'INITIATED',
            'reference_no' => $reference,
            'request_context' => $requestContext,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
    }

    private function markFailed(OnlinePaymentTransaction $transaction, ValidationException $e, Request $request): void
    {
        $transaction->update([
            'status' => 'FAILED',
            'response_context' => ['message' => collect($e->errors())->flatten()->first()],
            'updated_by' => $request->user()->id,
        ]);
    }

    private function auditTestSuccess(Request $request, OnlinePaymentTransaction $transaction, string $event): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $request->user()->id,
            'event' => $event,
            'resource_type' => 'online_payment_transaction',
            'resource_id' => $transaction->id,
            'before' => null,
            'after' => json_encode([
                'gateway_profile_id' => $transaction->college_payment_gateway_id,
                'provider' => $transaction->provider,
                'environment' => $transaction->environment,
                'provider_order_id' => $transaction->provider_order_id,
                'reference_no' => $transaction->reference_no,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'status' => $transaction->status,
            ]),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    private function assertGatewayCollege(College $college, CollegePaymentGateway $gateway): void
    {
        abort_unless((int) $gateway->college_id === (int) $college->id, 404);
    }

    private function auth(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
