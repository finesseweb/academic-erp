<?php

namespace App\Services\Payments;

use App\Models\College;
use App\Models\FeeDemand;
use App\Models\FeeHeadGatewayMapping;
use App\Models\OnlinePaymentTransaction;
use App\Services\FeePaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OnlineFeePaymentService
{
    public function __construct(
        private FeePaymentService $feePayments,
        private RazorpayGatewayService $razorpay,
        private CashfreeGatewayService $cashfree,
        private PayUGatewayService $payu,
    ) {}

    public function initiate(College $college, FeeDemand $demand, array $data, int $actorId): array
    {
        abort_unless((int) $demand->college_id === (int) $college->id, 404);
        if ($demand->status === 'CANCELLED') {
            throw ValidationException::withMessages(['demand_id' => 'Cancelled Fee Demand cannot receive payment.']);
        }

        $demand->loadMissing('admission.application');
        $preview = $this->feePayments->previewSelection($demand, $data);
        $allocations = collect($preview['allocations']);
        $feeHeadIds = $allocations->pluck('fee_head_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($feeHeadIds->isEmpty()) {
            throw ValidationException::withMessages(['gateway' => 'The selected payable rows do not resolve to Fee Heads.']);
        }

        $routes = FeeHeadGatewayMapping::query()
            ->with('gateway')
            ->whereIn('fee_head_id', $feeHeadIds)
            ->where('status', 'ACTIVE')
            ->whereHas('gateway', fn ($q) => $q->where('college_id', $college->id)->where('environment', 'TEST')->where('status', 'ACTIVE'))
            ->get()
            ->keyBy('fee_head_id');

        $missing = $feeHeadIds->reject(fn ($id) => $routes->has($id));
        if ($missing->isNotEmpty()) {
            $names = $demand->items->whereIn('fee_head_id', $missing)->pluck('fee_head_name')->filter()->unique()->implode(', ');
            throw ValidationException::withMessages(['gateway' => 'Online payment routing is not configured for: '.($names ?: $missing->implode(', ')).'.']);
        }

        $gatewayIds = $feeHeadIds->map(fn ($id) => (int) $routes->get($id)->college_payment_gateway_id)->unique()->values();
        if ($gatewayIds->count() !== 1) {
            throw ValidationException::withMessages([
                'gateway' => 'The selected amount spans Fee Heads routed to different gateway credential profiles. Collect those routed groups separately for online payment.',
            ]);
        }

        $gateway = $routes->first()->gateway;
        if (! $gateway || ! in_array($gateway->provider, ['RAZORPAY', 'CASHFREE', 'PAYU'], true)) {
            throw ValidationException::withMessages(['gateway' => 'The selected Fee Head route does not use a supported online payment provider.']);
        }

        $reference = 'ONLTEST-'.now()->format('YmdHis').'-'.$college->id.'-'.$demand->id.'-'.Str::upper(Str::random(6));
        $routeSnapshot = $feeHeadIds->map(function ($id) use ($routes, $demand) {
            $route = $routes->get($id);
            $item = $demand->items->firstWhere('fee_head_id', $id);
            return [
                'fee_head_id' => (int) $id,
                'fee_head_name' => $item?->fee_head_name,
                'fee_head_code' => $item?->fee_head_code,
                'product_code' => $route?->product_code,
                'settlement_code' => $route?->settlement_code,
            ];
        })->values()->all();

        $transaction = OnlinePaymentTransaction::create([
            'university_id' => $college->university_id,
            'college_id' => $college->id,
            'college_payment_gateway_id' => $gateway->id,
            'fee_demand_id' => $demand->id,
            'admission_id' => $demand->admission_id,
            'provider' => $gateway->provider,
            'environment' => 'TEST',
            'purpose' => 'FEE_PAYMENT',
            'amount' => $preview['amount'],
            'currency' => $demand->currency ?: 'INR',
            'status' => 'INITIATED',
            'reference_no' => $reference,
            'request_context' => [
                'payment' => [
                    'amount' => number_format((float) $preview['amount'], 2, '.', ''),
                    'payment_date' => $data['payment_date'],
                    'include_optional' => (bool) ($data['include_optional'] ?? false),
                    'include_late_fine' => (bool) ($data['include_late_fine'] ?? true),
                    'include_future' => (bool) ($data['include_future'] ?? false),
                ],
                'allocation_preview' => $allocations->map(fn ($row) => [
                    'fee_head_id' => (int) $row['fee_head_id'],
                    'fee_demand_item_id' => (int) $row['fee_demand_item_id'],
                    'fee_installment_schedule_id' => $row['fee_installment_schedule_id'] ? (int) $row['fee_installment_schedule_id'] : null,
                    'fee_late_fine_charge_id' => $row['fee_late_fine_charge_id'] ? (int) $row['fee_late_fine_charge_id'] : null,
                    'source_type' => $row['source_type'],
                    'allocated_amount' => number_format((float) $row['allocated_amount'], 2, '.', ''),
                ])->values()->all(),
                'routing' => $routeSnapshot,
            ],
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        try {
            $checkout = $this->createProviderCheckout($transaction, $gateway, $demand, $routeSnapshot);
        } catch (ValidationException $e) {
            $transaction->update([
                'status' => 'FAILED',
                'response_context' => ['message' => collect($e->errors())->flatten()->first()],
                'updated_by' => $actorId,
            ]);
            throw $e;
        }

        $transaction->update([
            'provider_order_id' => $checkout['provider_order_id'],
            'provider_status' => $checkout['provider_status'] ?? 'CREATED',
            'status' => 'CHECKOUT_READY',
            'response_context' => $checkout['audit'] ?? null,
            'updated_by' => $actorId,
        ]);

        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => 'ONLINE_FEE_CHECKOUT_INITIATED',
            'resource_type' => 'online_payment_transaction',
            'resource_id' => $transaction->id,
            'before' => null,
            'after' => json_encode([
                'provider' => $gateway->provider,
                'environment' => 'TEST',
                'fee_demand_id' => $demand->id,
                'amount' => number_format((float) $transaction->amount, 2, '.', ''),
                'provider_order_id' => $checkout['provider_order_id'],
            ]),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return [
            'transaction_id' => $transaction->id,
            'provider' => $gateway->provider,
            'environment' => 'TEST',
            'amount' => number_format((float) $transaction->amount, 2, '.', ''),
            'currency' => $transaction->currency,
            'checkout' => $checkout['client'],
        ];
    }

    public function postVerified(OnlinePaymentTransaction $transaction, string $providerPaymentId, string $providerStatus, array $verification): \App\Models\FeePayment
    {
        if ($transaction->purpose !== 'FEE_PAYMENT') {
            throw ValidationException::withMessages(['payment' => 'This online transaction is not a fee-payment transaction.']);
        }

        try {
            return DB::transaction(function () use ($transaction, $providerPaymentId, $providerStatus, $verification) {
                $locked = OnlinePaymentTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
                if ($locked->fee_payment_id) {
                    return \App\Models\FeePayment::findOrFail($locked->fee_payment_id);
                }

                $context = $locked->request_context ?? [];
                $paymentData = $context['payment'] ?? [];
                $demand = FeeDemand::query()->whereKey($locked->fee_demand_id)->lockForUpdate()->firstOrFail();
                $actorId = (int) ($locked->created_by ?: 0);
                if ($actorId <= 0) {
                    throw ValidationException::withMessages(['payment' => 'Online payment has no initiating user for receipt posting.']);
                }

                $college = College::findOrFail($demand->college_id);
                $payment = $this->feePayments->collect($college, $demand, [
                    'amount' => (float) $locked->amount,
                    'payment_date' => $paymentData['payment_date'] ?? now()->toDateString(),
                    'payment_mode' => 'ONLINE_'.$locked->provider,
                    'reference_no' => $providerPaymentId,
                    'include_optional' => (bool) ($paymentData['include_optional'] ?? false),
                    'include_late_fine' => (bool) ($paymentData['include_late_fine'] ?? true),
                    'include_future' => (bool) ($paymentData['include_future'] ?? false),
                    'notes' => 'Verified '.$locked->provider.' online payment · '.$locked->reference_no,
                ], $actorId, request()->ip());

                $locked->update([
                    'provider_payment_id' => $providerPaymentId,
                    'provider_status' => $providerStatus,
                    'status' => 'PAYMENT_POSTED',
                    'fee_payment_id' => $payment->id,
                    'verified_at' => now(),
                    'posted_at' => now(),
                    'response_context' => array_merge($locked->response_context ?? [], ['verification' => $verification]),
                    'updated_by' => $actorId,
                ]);

                DB::table('audit_logs')->insert([
                    'actor_user_id' => $actorId,
                    'event' => 'ONLINE_FEE_PAYMENT_POSTED',
                    'resource_type' => 'online_payment_transaction',
                    'resource_id' => $locked->id,
                    'before' => null,
                    'after' => json_encode(['fee_payment_id' => $payment->id, 'receipt_no' => $payment->receipt_no, 'provider_payment_id' => $providerPaymentId]),
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                ]);

                return $payment;
            });
        } catch (ValidationException $e) {
            OnlinePaymentTransaction::query()->whereKey($transaction->id)->whereNull('fee_payment_id')->update([
                'status' => 'PAYMENT_VERIFIED_UNPOSTED',
                'provider_payment_id' => $providerPaymentId,
                'provider_status' => $providerStatus,
                'verified_at' => now(),
                'response_context' => array_merge($transaction->response_context ?? [], [
                    'verification' => $verification,
                    'posting_error' => collect($e->errors())->flatten()->first(),
                ]),
                'updated_at' => now(),
            ]);
            throw $e;
        }
    }

    private function createProviderCheckout(OnlinePaymentTransaction $transaction, $gateway, FeeDemand $demand, array $routes): array
    {
        $application = $demand->admission?->application;
        $customer = [
            'id' => 'admission_'.$demand->admission_id,
            'name' => $application?->candidate_name ?: 'Student',
            'email' => $application?->email ?: 'student@example.com',
            'phone' => preg_replace('/\D+/', '', (string) ($application?->phone ?: '9999999999')) ?: '9999999999',
        ];
        $productInfo = collect($routes)->pluck('product_code')->filter()->unique()->implode(', ');
        if ($productInfo === '') $productInfo = 'Academic Fee '.$demand->demand_no;

        if ($gateway->provider === 'RAZORPAY') {
            $order = $this->razorpay->createFeeOrder($gateway, (int) round((float) $transaction->amount * 100), $transaction->currency, $transaction->reference_no, [
                'online_transaction_id' => (string) $transaction->id,
                'fee_demand_id' => (string) $demand->id,
            ]);
            return [
                'provider_order_id' => $order['id'], 'provider_status' => $order['status'] ?? 'created',
                'client' => [
                    'key_id' => (string) $gateway->key_id,
                    'order_id' => $order['id'],
                    'amount' => (int) $order['amount'],
                    'currency' => $order['currency'] ?? $transaction->currency,
                    'name' => $gateway->college?->name ?? 'Academic ERP',
                    'description' => $productInfo,
                    'prefill' => ['name' => $customer['name'], 'email' => $customer['email'], 'contact' => $customer['phone']],
                ],
                'audit' => ['order_id' => $order['id'], 'status' => $order['status'] ?? null, 'receipt' => $order['receipt'] ?? null],
            ];
        }

        if ($gateway->provider === 'CASHFREE') {
            $orderId = 'AERP-'.$transaction->id.'-'.now()->format('YmdHis');
            $order = $this->cashfree->createFeeOrder($gateway, $orderId, (float) $transaction->amount, $transaction->currency, $customer, route('payments.webhooks.cashfree'), [
                'online_transaction_id' => (string) $transaction->id,
                'fee_demand_id' => (string) $demand->id,
            ]);
            return [
                'provider_order_id' => $order['order_id'], 'provider_status' => $order['order_status'] ?? 'ACTIVE',
                'client' => ['payment_session_id' => $order['payment_session_id']],
                'audit' => ['order_id' => $order['order_id'], 'cf_order_id' => $order['cf_order_id'] ?? null, 'order_status' => $order['order_status'] ?? null],
            ];
        }

        $checkout = $this->payu->hostedCheckoutPayload($gateway, [
            'txnid' => $transaction->reference_no,
            'amount' => $transaction->amount,
            'productinfo' => $productInfo,
            'firstname' => $customer['name'],
            'email' => $customer['email'],
            'phone' => $customer['phone'],
            'surl' => route('payments.payu.return'),
            'furl' => route('payments.payu.return'),
            'udf1' => (string) $transaction->id,
            'udf2' => (string) $demand->id,
        ]);
        return [
            'provider_order_id' => $transaction->reference_no, 'provider_status' => 'CHECKOUT_READY',
            'client' => $checkout,
            'audit' => ['txnid' => $transaction->reference_no, 'productinfo' => $productInfo],
        ];
    }
}
