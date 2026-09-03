<?php

namespace App\Services;

use App\Models\ApplicantProfile;
use App\Models\College;
use App\Models\CollegeApplicantRegistrationSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicantRegistrationNumberService
{
    /**
     * Legacy-safe helper for callers that truly need a number immediately.
     * Public applicant registration no longer uses this method; public numbers
     * are finalized on the applicant's first successful application submission.
     */
    public function ensure(ApplicantProfile $profile): string
    {
        if (filled($profile->registration_no)) {
            return (string) $profile->registration_no;
        }

        return DB::transaction(fn () => $this->allocateLocked($profile, false), 3);
    }

    /**
     * Finalize the applicant-level registration number at the first successful
     * public application submission. This deliberately replaces any legacy
     * pre-submission number so the saved value follows the College's current
     * Registration Number Format at the moment of final submission.
     *
     * Once the applicant already has another SUBMITTED application in the same
     * College, the registration number is treated as stable and is not reissued.
     */
    public function finalizeForSubmission(ApplicantProfile $profile, int $currentApplicationId): string
    {
        return DB::transaction(function () use ($profile, $currentApplicationId) {
            $profile = ApplicantProfile::query()->lockForUpdate()->findOrFail($profile->id);

            $hasEarlierSubmittedApplication = DB::table('college_admission_applications')
                ->where('college_id', $profile->college_id)
                ->where('applicant_user_id', $profile->user_id)
                ->where('status', 'SUBMITTED')
                ->where('id', '<>', $currentApplicationId)
                ->exists();

            if ($hasEarlierSubmittedApplication && filled($profile->registration_no)) {
                return (string) $profile->registration_no;
            }

            return $this->allocateLocked($profile, true);
        }, 3);
    }

    public function preview(string $format, College $college, int $sequence = 1): string
    {
        return $this->render($format, $college, max(1, $sequence));
    }

    private function allocateLocked(ApplicantProfile $profile, bool $replaceExisting): string
    {
        $profile = ApplicantProfile::query()->lockForUpdate()->findOrFail($profile->id);
        if (! $replaceExisting && filled($profile->registration_no)) {
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
