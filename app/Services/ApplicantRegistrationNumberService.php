<?php

namespace App\Services;

use App\Models\ApplicantProfile;
use App\Models\College;
use App\Models\CollegeApplicantRegistrationSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicantRegistrationNumberService
{
    public function ensure(ApplicantProfile $profile): string
    {
        if (filled($profile->registration_no)) {
            return (string) $profile->registration_no;
        }

        return DB::transaction(function () use ($profile) {
            $profile = ApplicantProfile::query()->lockForUpdate()->findOrFail($profile->id);
            if (filled($profile->registration_no)) {
                return (string) $profile->registration_no;
            }

            $settings = CollegeApplicantRegistrationSetting::query()
                ->where('college_id', $profile->college_id)
                ->lockForUpdate()
                ->first();

            if (! $settings) {
                $settings = CollegeApplicantRegistrationSetting::create([
                    'college_id' => $profile->college_id,
                    'registration_enabled' => true,
                    'email_verification_required' => false,
                    'captcha_required' => false,
                    'registration_number_format' => '{COLLEGE_CODE}/{YEAR}/{SEQ:6}',
                    'registration_sequence_next' => 1,
                ]);
                $settings->refresh();
            }

            $college = College::findOrFail($profile->college_id);
            $sequence = max(1, (int) ($settings->registration_sequence_next ?? 1));
            $format = trim((string) ($settings->registration_number_format ?: '{COLLEGE_CODE}/{YEAR}/{SEQ:6}'));

            $number = $this->render($format, $college, $sequence);
            if (ApplicantProfile::where('registration_no', $number)->whereKeyNot($profile->id)->exists()) {
                throw ValidationException::withMessages([
                    'registration_number_format' => 'Registration Number format produced a duplicate value. Include {SEQ:6}, {YEAR}, or another unique token.',
                ]);
            }

            $profile->forceFill(['registration_no' => $number])->save();
            $settings->forceFill(['registration_sequence_next' => $sequence + 1])->save();

            return $number;
        }, 3);
    }

    public function preview(string $format, College $college, int $sequence = 1): string
    {
        return $this->render($format, $college, max(1, $sequence));
    }

    private function render(string $format, College $college, int $sequence): string
    {
        $now = now();
        $value = strtr($format, [
            '{COLLEGE_CODE}' => strtoupper((string) $college->code),
            '{COLLEGE_ID}' => (string) $college->id,
            '{YEAR}' => $now->format('Y'),
            '{YY}' => $now->format('y'),
        ]);

        $value = preg_replace_callback('/\{SEQ(?::(\d{1,2}))?\}/', function ($m) use ($sequence) {
            $width = isset($m[1]) ? min(12, max(1, (int) $m[1])) : 6;
            return str_pad((string) $sequence, $width, '0', STR_PAD_LEFT);
        }, $value);

        $value = trim((string) $value);
        if ($value === '' || strlen($value) > 80) {
            throw ValidationException::withMessages([
                'registration_number_format' => 'Registration Number format must generate a value between 1 and 80 characters.',
            ]);
        }

        return $value;
    }
}
