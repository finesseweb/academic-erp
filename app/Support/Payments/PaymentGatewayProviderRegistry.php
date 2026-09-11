<?php

namespace App\Support\Payments;

use App\Models\CollegePaymentGateway;

class PaymentGatewayProviderRegistry
{
    /**
     * Provider definitions are intentionally data-driven so adding a future
     * gateway normally requires an adapter + one definition, not a DB redesign.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        return [
            'RAZORPAY' => [
                'code' => 'RAZORPAY',
                'name' => 'Razorpay',
                'credential_labels' => [
                    'merchant_id' => 'Merchant / Account Reference (optional)',
                    'key_id' => 'Key ID',
                    'key_secret' => 'Key Secret',
                    'webhook_secret' => 'Webhook Secret (optional)',
                ],
                'show' => [
                    'merchant_id' => true,
                    'webhook_secret' => true,
                    'default_product_code' => false,
                    'enc_key' => false,
                    'dec_key' => false,
                ],
                'required_for_activation' => ['key_id', 'key_secret'],
                'routing_product_mode' => 'OPTIONAL',
                'routing_help' => 'Product Code is optional. Razorpay routes the payment through the selected Key ID / Key Secret profile.',
            ],
            'CASHFREE' => [
                'code' => 'CASHFREE',
                'name' => 'Cashfree Payments',
                'credential_labels' => [
                    'merchant_id' => 'Merchant / Account Reference (optional)',
                    'key_id' => 'App ID / Client ID',
                    'key_secret' => 'Secret Key / Client Secret',
                    'webhook_secret' => 'Webhook Secret',
                ],
                'show' => [
                    'merchant_id' => true,
                    // Cashfree webhook verification uses the same Secret Key.
                    'webhook_secret' => false,
                    'default_product_code' => false,
                    'enc_key' => false,
                    'dec_key' => false,
                ],
                'required_for_activation' => ['key_id', 'key_secret'],
                'routing_product_mode' => 'OPTIONAL',
                'routing_help' => 'Product Code is optional. Cashfree payment routing is driven by the selected App ID / Secret Key profile.',
            ],
            'PAYU' => [
                'code' => 'PAYU',
                'name' => 'PayU',
                'credential_labels' => [
                    'merchant_id' => 'Merchant / Account Reference (optional)',
                    'key_id' => 'Merchant Key',
                    'key_secret' => 'Salt',
                    'webhook_secret' => 'Webhook Secret',
                ],
                'show' => [
                    'merchant_id' => true,
                    // PayU callback/webhook authenticity is verified with reverse hash using Salt.
                    'webhook_secret' => false,
                    'default_product_code' => false,
                    'enc_key' => false,
                    'dec_key' => false,
                ],
                'required_for_activation' => ['key_id', 'key_secret'],
                'routing_product_mode' => 'OPTIONAL',
                'routing_help' => 'Product Code is optional. If blank, PayU productinfo will be generated from the Fee Head / ERP transaction context.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function definition(string $provider): array
    {
        return self::definitions()[$provider] ?? [];
    }

    /** @return array<int, array<string, mixed>> */
    public static function frontendDefinitions(): array
    {
        return array_values(self::definitions());
    }

    /** @return array<int, string> */
    public static function activationErrors(CollegePaymentGateway $gateway): array
    {
        $definition = self::definition($gateway->provider);
        $required = $definition['required_for_activation'] ?? [];
        $labels = $definition['credential_labels'] ?? [];
        $errors = [];

        foreach ($required as $field) {
            if (blank($gateway->{$field})) {
                $errors[] = ($labels[$field] ?? $field).' is required before activation.';
            }
        }

        return $errors;
    }

    public static function effectiveProductCode(CollegePaymentGateway $gateway, ?string $mappingCode): ?string
    {
        $mappingCode = trim((string) $mappingCode);
        if ($mappingCode !== '') {
            return $mappingCode;
        }

        $config = is_array($gateway->provider_config) ? $gateway->provider_config : [];
        $default = trim((string) ($config['default_product_code'] ?? ''));

        return $default !== '' ? $default : null;
    }

    public static function productCodeRequiredForRouting(CollegePaymentGateway $gateway): bool
    {
        return (self::definition($gateway->provider)['routing_product_mode'] ?? 'OPTIONAL') === 'PROFILE_OR_HEAD_REQUIRED';
    }
}
