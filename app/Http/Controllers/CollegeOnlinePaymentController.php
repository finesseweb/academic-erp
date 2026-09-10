<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\CollegePaymentGateway;
use App\Models\OnlinePaymentTransaction;
use App\Services\Payments\RazorpayGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        abort_unless((int) $gateway->college_id === (int) $college->id, 404);

        if ($gateway->provider !== 'RAZORPAY') {
            throw ValidationException::withMessages(['gateway' => 'This test action is available only for Razorpay profiles.']);
        }

        $transaction = DB::transaction(function () use ($request, $college, $gateway, $razorpay) {
            $locked = CollegePaymentGateway::query()->whereKey($gateway->id)->lockForUpdate()->firstOrFail();
            $reference = 'RZPTEST-'.now()->format('YmdHis').'-'.$college->id.'-'.$locked->id;

            $transaction = OnlinePaymentTransaction::create([
                'university_id' => $college->university_id,
                'college_id' => $college->id,
                'college_payment_gateway_id' => $locked->id,
                'provider' => 'RAZORPAY',
                'environment' => $locked->environment,
                'purpose' => 'CREDENTIAL_TEST_ORDER',
                'amount' => 1.00,
                'currency' => 'INR',
                'status' => 'INITIATED',
                'reference_no' => $reference,
                'request_context' => [
                    'amount_paise' => 100,
                    'profile_name' => $locked->display_name,
                ],
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            try {
                $order = $razorpay->createOrder($locked, 100, 'INR', $reference, [
                    'academic_erp' => 'credential_test',
                    'college_id' => (string) $college->id,
                    'gateway_profile_id' => (string) $locked->id,
                ]);
            } catch (ValidationException $e) {
                $transaction->update([
                    'status' => 'FAILED',
                    'response_context' => ['message' => collect($e->errors())->flatten()->first()],
                    'updated_by' => $request->user()->id,
                ]);
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

            DB::table('audit_logs')->insert([
                'actor_user_id' => $request->user()->id,
                'event' => 'RAZORPAY_TEST_ORDER_CREATED',
                'resource_type' => 'online_payment_transaction',
                'resource_id' => $transaction->id,
                'before' => null,
                'after' => json_encode([
                    'gateway_profile_id' => $locked->id,
                    'environment' => $locked->environment,
                    'provider_order_id' => $order['id'],
                    'amount' => '1.00',
                    'currency' => 'INR',
                ]),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return $transaction;
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Razorpay TEST order created successfully · '.$transaction->provider_order_id.' · ₹1.00.',
        ]);
    }

    private function auth(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
