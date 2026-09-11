<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\CollegePaymentGateway;
use App\Models\FeeHead;
use App\Models\FeeHeadGatewayMapping;
use App\Support\Payments\PaymentGatewayProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegePaymentGatewayController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->auth($request, $college, 'college_payment_gateway.view');

        $gateways = CollegePaymentGateway::query()
            ->where('college_id', $college->id)
            ->with('mappings')
            ->orderBy('provider')
            ->get()
            ->map(fn (CollegePaymentGateway $gateway) => [
                'id' => $gateway->id,
                'provider' => $gateway->provider,
                'display_name' => $gateway->display_name,
                'environment' => $gateway->environment,
                'merchant_id' => $gateway->merchant_id,
                'key_id' => $gateway->key_id,
                'has_key_secret' => filled($gateway->key_secret),
                'has_webhook_secret' => filled($gateway->webhook_secret),
                'has_enc_key' => filled(data_get($gateway->provider_config, 'enc_key')),
                'has_dec_key' => filled(data_get($gateway->provider_config, 'dec_key')),
                'default_product_code' => data_get($gateway->provider_config, 'default_product_code'),
                'status' => $gateway->status,
                'mappings' => $gateway->mappings->map(fn (FeeHeadGatewayMapping $mapping) => [
                    'id' => $mapping->id,
                    'fee_head_id' => $mapping->fee_head_id,
                    'product_code' => $mapping->product_code,
                    'settlement_code' => $mapping->settlement_code,
                    'status' => $mapping->status,
                ])->values(),
            ]);

        $heads = FeeHead::query()
            ->where('university_id', $college->university_id)
            ->where(fn ($query) => $query->whereNull('college_id')->orWhere('college_id', $college->id))
            ->where('status', 'ACTIVE')
            ->with('category:id,name,code')
            ->orderBy('name')
            ->get(['id', 'fee_category_id', 'name', 'code', 'college_id']);

        return Inertia::render('college-payment-gateways/index', [
            'college' => $college->only(['id', 'name', 'code']),
            'providers' => PaymentGatewayProviderRegistry::frontendDefinitions(),
            'gateways' => $gateways,
            'feeHeads' => $heads,
            'can' => [
                'manage' => $request->user()->hasCollegePermission('college_payment_gateway.manage', $college->id),
            ],
        ]);
    }

    public function store(Request $request, College $college): RedirectResponse
    {
        $this->auth($request, $college, 'college_payment_gateway.manage');

        $data = $request->validate([
            'provider' => [
                'required',
                Rule::in(array_keys(PaymentGatewayProviderRegistry::definitions())),
            ],
            'display_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('college_payment_gateways', 'display_name')->where(fn ($query) => $query
                    ->where('college_id', $college->id)
                    ->where('provider', $request->string('provider')->upper()->toString())
                    ->where('environment', $request->string('environment')->upper()->toString())),
            ],
            'environment' => ['required', Rule::in(['TEST', 'LIVE'])],
            'merchant_id' => ['nullable', 'string', 'max:150'],
            'key_id' => ['nullable', 'string', 'max:255'],
            'key_secret' => ['nullable', 'string', 'max:2000'],
            'webhook_secret' => ['nullable', 'string', 'max:2000'],
            'default_product_code' => ['nullable', 'string', 'max:120'],
            'enc_key' => ['nullable', 'string', 'max:2000'],
            'dec_key' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['provider_config'] = $this->providerConfigFromData($data);
        unset($data['default_product_code'], $data['enc_key'], $data['dec_key']);

        $data += [
            'university_id' => $college->university_id,
            'college_id' => $college->id,
            'status' => 'INACTIVE',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ];

        CollegePaymentGateway::create($data);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Gateway configuration created as INACTIVE.',
        ]);
    }

    public function update(Request $request, College $college, CollegePaymentGateway $gateway): RedirectResponse
    {
        $this->auth($request, $college, 'college_payment_gateway.manage');
        $this->own($college, $gateway);
        if ($gateway->status === 'ACTIVE') {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'Deactivate the gateway before editing credentials.',
            ]);
        }

        $data = $request->validate([
            'display_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('college_payment_gateways', 'display_name')
                    ->where(fn ($query) => $query
                        ->where('college_id', $college->id)
                        ->where('provider', $gateway->provider)
                        ->where('environment', $request->string('environment')->upper()->toString()))
                    ->ignore($gateway->id),
            ],
            'environment' => ['required', Rule::in(['TEST', 'LIVE'])],
            'merchant_id' => ['nullable', 'string', 'max:150'],
            'key_id' => ['nullable', 'string', 'max:255'],
            'key_secret' => ['nullable', 'string', 'max:2000'],
            'webhook_secret' => ['nullable', 'string', 'max:2000'],
            'default_product_code' => ['nullable', 'string', 'max:120'],
            'enc_key' => ['nullable', 'string', 'max:2000'],
            'dec_key' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['provider_config'] = $this->providerConfigFromData($data, $gateway->provider_config ?? []);
        unset($data['default_product_code'], $data['enc_key'], $data['dec_key']);

        if (blank($data['key_secret'] ?? null)) {
            unset($data['key_secret']);
        }
        if (blank($data['webhook_secret'] ?? null)) {
            unset($data['webhook_secret']);
        }

        $gateway->update($data + ['updated_by' => $request->user()->id]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Gateway configuration updated.',
        ]);
    }

    public function status(Request $request, College $college, CollegePaymentGateway $gateway): RedirectResponse
    {
        $this->auth($request, $college, 'college_payment_gateway.manage');
        $this->own($college, $gateway);

        $data = $request->validate([
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        if ($data['status'] === 'ACTIVE') {
            $errors = PaymentGatewayProviderRegistry::activationErrors($gateway);
            if ($errors !== []) {
                return back()->with('toast', [
                    'type' => 'error',
                    'message' => implode(' ', $errors),
                ]);
            }
        }

        $gateway->update([
            'status' => $data['status'],
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Gateway status updated.',
        ]);
    }

    public function mapping(Request $request, College $college, CollegePaymentGateway $gateway): RedirectResponse
    {
        $this->auth($request, $college, 'college_payment_gateway.manage');
        $this->own($college, $gateway);

        $data = $request->validate($this->mappingRules($college));

        if ($data['status'] === 'ACTIVE'
            && PaymentGatewayProviderRegistry::productCodeRequiredForRouting($gateway)
            && blank(PaymentGatewayProviderRegistry::effectiveProductCode($gateway, $data['product_code'] ?? null))) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'This provider requires a Product ID. Set a default Product ID on the credential profile or enter a Product Code for this Fee Head.',
            ]);
        }

        if ($data['status'] === 'ACTIVE') {
            $this->deactivateCompetingProfileMappings(
                $college,
                $gateway,
                (int) $data['fee_head_id'],
                $request->user()->id,
            );
        }

        FeeHeadGatewayMapping::updateOrCreate(
            [
                'college_payment_gateway_id' => $gateway->id,
                'fee_head_id' => $data['fee_head_id'],
            ],
            array_merge($data, [
                'product_code' => blank($data['product_code'] ?? null) ? null : trim((string) $data['product_code']),
            ]) + [
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Fee Head mapping saved.',
        ]);
    }

    public function mappingsBulk(Request $request, College $college, CollegePaymentGateway $gateway): RedirectResponse
    {
        $this->auth($request, $college, 'college_payment_gateway.manage');
        $this->own($college, $gateway);

        $data = $request->validate([
            'mappings' => ['required', 'array', 'min:1'],
            'mappings.*.fee_head_id' => [
                'required',
                'integer',
                Rule::exists('fee_heads', 'id')->where(fn ($query) => $query
                    ->where('university_id', $college->university_id)
                    ->where(fn ($scope) => $scope->whereNull('college_id')->orWhere('college_id', $college->id))),
            ],
            'mappings.*.product_code' => ['nullable', 'string', 'max:120'],
            'mappings.*.settlement_code' => ['nullable', 'string', 'max:120'],
            'mappings.*.status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        $saved = 0;
        foreach ($data['mappings'] as $mapping) {
            $productCode = trim((string) ($mapping['product_code'] ?? ''));

            if ($mapping['status'] === 'ACTIVE'
                && PaymentGatewayProviderRegistry::productCodeRequiredForRouting($gateway)
                && blank(PaymentGatewayProviderRegistry::effectiveProductCode($gateway, $productCode))) {
                return back()->with('toast', [
                    'type' => 'error',
                    'message' => 'This provider requires a Product ID. Set a profile default or enter a Product Code for the assigned Fee Head.',
                ]);
            }

            // Blank rows are valid for credential-driven gateways. For inactive,
            // never-mapped blank rows there is nothing useful to persist.
            if ($productCode === '' && $mapping['status'] === 'INACTIVE') {
                continue;
            }

            if ($mapping['status'] === 'ACTIVE') {
                $this->deactivateCompetingProfileMappings(
                    $college,
                    $gateway,
                    (int) $mapping['fee_head_id'],
                    $request->user()->id,
                );
            }

            FeeHeadGatewayMapping::updateOrCreate(
                [
                    'college_payment_gateway_id' => $gateway->id,
                    'fee_head_id' => (int) $mapping['fee_head_id'],
                ],
                [
                    'product_code' => $productCode !== '' ? $productCode : null,
                    'settlement_code' => blank($mapping['settlement_code'] ?? null)
                        ? null
                        : trim((string) $mapping['settlement_code']),
                    'status' => $mapping['status'],
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]
            );
            $saved++;
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => $saved === 1 ? '1 Fee Head mapping saved.' : "{$saved} Fee Head mappings saved.",
        ]);
    }

    public function routingBulk(Request $request, College $college): RedirectResponse
    {
        $this->auth($request, $college, 'college_payment_gateway.manage');

        $data = $request->validate([
            'environment' => ['required', Rule::in(['TEST', 'LIVE'])],
            'mappings' => ['required', 'array', 'min:1'],
            'mappings.*.fee_head_id' => [
                'required',
                'integer',
                Rule::exists('fee_heads', 'id')->where(fn ($query) => $query
                    ->where('university_id', $college->university_id)
                    ->where(fn ($scope) => $scope->whereNull('college_id')->orWhere('college_id', $college->id))),
            ],
            'mappings.*.college_payment_gateway_id' => ['nullable', 'integer', 'exists:college_payment_gateways,id'],
            'mappings.*.product_code' => ['nullable', 'string', 'max:120'],
            'mappings.*.settlement_code' => ['nullable', 'string', 'max:120'],
        ]);

        $environment = $data['environment'];
        $profileIds = CollegePaymentGateway::query()
            ->where('college_id', $college->id)
            ->where('environment', $environment)
            ->pluck('id');

        foreach ($data['mappings'] as $mapping) {
            $selectedProfileId = filled($mapping['college_payment_gateway_id'] ?? null)
                ? (int) $mapping['college_payment_gateway_id']
                : null;

            if ($selectedProfileId === null) {
                continue;
            }

            $profileValid = CollegePaymentGateway::query()
                ->whereKey($selectedProfileId)
                ->where('college_id', $college->id)
                ->where('environment', $environment)
                ->exists();

            if (! $profileValid) {
                return back()->with('toast', [
                    'type' => 'error',
                    'message' => 'Selected credential profile is not valid for this College/environment.',
                ]);
            }

            $profile = CollegePaymentGateway::query()->find($selectedProfileId);
            if ($profile
                && PaymentGatewayProviderRegistry::productCodeRequiredForRouting($profile)
                && blank(PaymentGatewayProviderRegistry::effectiveProductCode($profile, $mapping['product_code'] ?? null))) {
                return back()->with('toast', [
                    'type' => 'error',
                    'message' => $profile->display_name.' requires a Product ID. Set a default Product ID on the credential profile or enter a Product Code for this Fee Head.',
                ]);
            }
        }

        DB::transaction(function () use ($data, $college, $profileIds, $environment, $request): void {
            foreach ($data['mappings'] as $mapping) {
                $feeHeadId = (int) $mapping['fee_head_id'];
                $selectedProfileId = filled($mapping['college_payment_gateway_id'] ?? null)
                    ? (int) $mapping['college_payment_gateway_id']
                    : null;

                // Each Fee Head independently chooses its route. A Fee Head may also
                // intentionally remain unassigned for this environment.
                FeeHeadGatewayMapping::query()
                    ->whereIn('college_payment_gateway_id', $profileIds)
                    ->where('fee_head_id', $feeHeadId)
                    ->where('status', 'ACTIVE')
                    ->update([
                        'status' => 'INACTIVE',
                        'updated_by' => $request->user()->id,
                        'updated_at' => now(),
                    ]);

                if ($selectedProfileId === null) {
                    continue;
                }

                $productCode = trim((string) ($mapping['product_code'] ?? ''));

                FeeHeadGatewayMapping::updateOrCreate(
                    [
                        'college_payment_gateway_id' => $selectedProfileId,
                        'fee_head_id' => $feeHeadId,
                    ],
                    [
                        'product_code' => $productCode !== '' ? $productCode : null,
                        'settlement_code' => blank($mapping['settlement_code'] ?? null)
                            ? null
                            : trim((string) $mapping['settlement_code']),
                        'status' => 'ACTIVE',
                        'created_by' => $request->user()->id,
                        'updated_by' => $request->user()->id,
                    ]
                );
            }
        });

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Fee Head routing saved.',
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    private function mappingRules(College $college): array
    {
        return [
            'fee_head_id' => [
                'required',
                'integer',
                Rule::exists('fee_heads', 'id')->where(fn ($query) => $query
                    ->where('university_id', $college->university_id)
                    ->where(fn ($scope) => $scope->whereNull('college_id')->orWhere('college_id', $college->id))),
            ],
            'product_code' => ['nullable', 'string', 'max:120'],
            'settlement_code' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ];
    }

    private function deactivateCompetingProfileMappings(
        College $college,
        CollegePaymentGateway $gateway,
        int $feeHeadId,
        int $userId,
    ): void {
        $otherProfileIds = CollegePaymentGateway::query()
            ->where('college_id', $college->id)
            ->where('provider', $gateway->provider)
            ->where('environment', $gateway->environment)
            ->whereKeyNot($gateway->id)
            ->pluck('id');

        if ($otherProfileIds->isEmpty()) {
            return;
        }

        FeeHeadGatewayMapping::query()
            ->whereIn('college_payment_gateway_id', $otherProfileIds)
            ->where('fee_head_id', $feeHeadId)
            ->where('status', 'ACTIVE')
            ->update([
                'status' => 'INACTIVE',
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $existing */
    private function providerConfigFromData(array $data, array $existing = []): array
    {
        $config = $existing;

        $defaultProduct = trim((string) ($data['default_product_code'] ?? ''));
        $config['default_product_code'] = $defaultProduct !== '' ? $defaultProduct : null;

        foreach (['enc_key', 'dec_key'] as $secretField) {
            $value = trim((string) ($data[$secretField] ?? ''));
            if ($value !== '') {
                $config[$secretField] = $value;
            } elseif (! array_key_exists($secretField, $config)) {
                $config[$secretField] = null;
            }
        }

        return $config;
    }

    private function auth(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }

    private function own(College $college, CollegePaymentGateway $gateway): void
    {
        abort_unless($gateway->college_id === $college->id, 404);
    }
}
