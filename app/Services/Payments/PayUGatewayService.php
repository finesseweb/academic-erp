<?php

namespace App\Services\Payments;

use App\Models\CollegePaymentGateway;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PayUGatewayService
{
    private const TEST_VERIFY_URL = 'https://test.payu.in/merchant/postservice.php?form=2';

    public function testCredentials(CollegePaymentGateway $gateway, string $transactionId): array
    {
        $this->assertTestGateway($gateway);

        $command = 'verify_payment';
        $hash = hash('sha512', $gateway->key_id.'|'.$command.'|'.$transactionId.'|'.$gateway->key_secret);

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(20)
                ->post(self::TEST_VERIFY_URL, [
                    'key' => (string) $gateway->key_id,
                    'command' => $command,
                    'var1' => $transactionId,
                    'hash' => $hash,
                ]);
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages([
                'gateway' => 'Could not connect to PayU TEST API. Check internet/server connectivity and try again.',
            ]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'gateway' => 'PayU TEST API returned HTTP '.$response->status().'.',
            ]);
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw ValidationException::withMessages(['gateway' => 'PayU TEST API returned an unreadable response.']);
        }

        $details = data_get($payload, 'transaction_details.'.$transactionId);
        if (! is_array($details)) {
            $message = trim((string) ($payload['msg'] ?? 'PayU rejected the credential verification request.'));
            throw ValidationException::withMessages(['gateway' => $message !== '' ? $message : 'PayU rejected the credential verification request.']);
        }

        return $payload;
    }

    public function verifyCallbackHash(array $payload, string $salt): bool
    {
        $receivedHash = (string) ($payload['hash'] ?? '');
        if ($receivedHash === '' || $salt === '') {
            return false;
        }

        $parts = [
            $salt,
            (string) ($payload['status'] ?? ''),
        ];

        if (array_key_exists('splitInfo', $payload) && (string) $payload['splitInfo'] !== '') {
            $parts[] = (string) $payload['splitInfo'];
        }

        // PayU reverse hash keeps five empty fields before udf5.
        array_push($parts, '', '', '', '', '');
        array_push(
            $parts,
            (string) ($payload['udf5'] ?? ''),
            (string) ($payload['udf4'] ?? ''),
            (string) ($payload['udf3'] ?? ''),
            (string) ($payload['udf2'] ?? ''),
            (string) ($payload['udf1'] ?? ''),
            (string) ($payload['email'] ?? ''),
            (string) ($payload['firstname'] ?? ''),
            (string) ($payload['productinfo'] ?? ''),
            (string) ($payload['amount'] ?? ''),
            (string) ($payload['txnid'] ?? ''),
            (string) ($payload['key'] ?? ''),
        );

        $hashString = implode('|', $parts);
        $additionalCharges = $payload['additionalCharges'] ?? $payload['additional_charges'] ?? null;
        if ($additionalCharges !== null && (string) $additionalCharges !== '') {
            $hashString = (string) $additionalCharges.'|'.$hashString;
        }

        $expected = hash('sha512', $hashString);

        return hash_equals(strtolower($expected), strtolower($receivedHash));
    }

    private function assertTestGateway(CollegePaymentGateway $gateway): void
    {
        if ($gateway->provider !== 'PAYU') {
            throw ValidationException::withMessages(['gateway' => 'Selected credential profile is not PayU.']);
        }
        if ($gateway->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['gateway' => 'Activate the PayU credential profile before testing it.']);
        }
        if ($gateway->environment !== 'TEST') {
            throw ValidationException::withMessages(['gateway' => 'PayU credential testing is allowed only for TEST profiles.']);
        }
        if (blank($gateway->key_id) || blank($gateway->key_secret)) {
            throw ValidationException::withMessages(['gateway' => 'PayU Merchant Key and Salt are required.']);
        }
    }
    public function hostedCheckoutPayload(CollegePaymentGateway $gateway, array $data): array
    {
        $this->assertFeeCheckoutGateway($gateway);
        $fields = [
            'key' => (string) $gateway->key_id,
            'txnid' => (string) $data['txnid'],
            'amount' => number_format((float) $data['amount'], 2, '.', ''),
            'productinfo' => (string) $data['productinfo'],
            'firstname' => (string) $data['firstname'],
            'email' => (string) $data['email'],
            'phone' => (string) $data['phone'],
            'surl' => (string) $data['surl'],
            'furl' => (string) $data['furl'],
            'udf1' => (string) ($data['udf1'] ?? ''),
            'udf2' => (string) ($data['udf2'] ?? ''),
            'udf3' => (string) ($data['udf3'] ?? ''),
            'udf4' => (string) ($data['udf4'] ?? ''),
            'udf5' => (string) ($data['udf5'] ?? ''),
        ];
        $sequence = implode('|', [
            $fields['key'], $fields['txnid'], $fields['amount'], $fields['productinfo'],
            $fields['firstname'], $fields['email'], $fields['udf1'], $fields['udf2'], $fields['udf3'], $fields['udf4'], $fields['udf5'],
            '', '', '', '', '', (string) $gateway->key_secret,
        ]);
        $fields['hash'] = hash('sha512', $sequence);
        return ['action' => 'https://test.payu.in/_payment', 'fields' => $fields];
    }

    public function verifyPayment(CollegePaymentGateway $gateway, string $transactionId): array
    {
        $this->assertFeeCheckoutGateway($gateway);
        $command = 'verify_payment';
        $hash = hash('sha512', $gateway->key_id.'|'.$command.'|'.$transactionId.'|'.$gateway->key_secret);
        try {
            $response = Http::asForm()->acceptJson()->connectTimeout(10)->timeout(20)->post(self::TEST_VERIFY_URL, [
                'key' => (string) $gateway->key_id, 'command' => $command, 'var1' => $transactionId, 'hash' => $hash,
            ]);
        } catch (ConnectionException $e) {
            throw ValidationException::withMessages(['gateway' => 'Could not verify the PayU payment. Try again.']);
        }
        if (! $response->successful()) throw ValidationException::withMessages(['gateway' => 'PayU payment verification returned HTTP '.$response->status().'.']);
        $payload = $response->json();
        $details = data_get($payload, 'transaction_details.'.$transactionId);
        if (! is_array($details)) throw ValidationException::withMessages(['gateway' => trim((string) ($payload['msg'] ?? 'PayU payment verification failed.'))]);
        return ['response' => $payload, 'details' => $details];
    }

    private function assertFeeCheckoutGateway(CollegePaymentGateway $gateway): void
    {
        if ($gateway->provider !== 'PAYU') throw ValidationException::withMessages(['gateway' => 'Selected credential profile is not PayU.']);
        if ($gateway->status !== 'ACTIVE') throw ValidationException::withMessages(['gateway' => 'The PayU credential profile is not ACTIVE.']);
        if ($gateway->environment !== 'TEST') throw ValidationException::withMessages(['gateway' => 'Online fee checkout remains TEST-only until payment QA is completed.']);
        if (blank($gateway->key_id) || blank($gateway->key_secret)) throw ValidationException::withMessages(['gateway' => 'PayU Merchant Key and Salt are required.']);
    }

}
