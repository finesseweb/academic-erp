<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CollegeAdmissionFormOptionService
{
    /**
     * Build machine-safe, field-local unique option values from administrator labels.
     *
     * Labels remain the authoritative display text. Values are deterministic identifiers
     * used by saved answers/conditions and must therefore be unique within one field.
     *
     * @return array<int, array{0:string,1:string}>
     */
    public function build(string $fieldType, ?string $rawOptions): array
    {
        if ($fieldType === 'YES_NO') {
            return [['Yes', 'YES'], ['No', 'NO']];
        }

        if (! filled($rawOptions)) {
            return [];
        }

        $labels = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n|,/', $rawOptions) ?: []),
            fn (string $value) => $value !== ''
        ));

        $seenLabels = [];
        $usedValues = [];
        $options = [];

        foreach ($labels as $label) {
            $labelKey = Str::lower($label);
            if (isset($seenLabels[$labelKey])) {
                throw ValidationException::withMessages([
                    'options' => "Duplicate option '{$label}' is not allowed.",
                ]);
            }
            $seenLabels[$labelKey] = true;

            $base = $this->valueBase($label);
            $value = $base;

            if (isset($usedValues[$value])) {
                // Different labels can legitimately slug to the same identifier (A+ / A-,
                // punctuation-only variants, etc.). Preserve both by adding a stable suffix
                // derived from the original label instead of allowing a database 500.
                $value = $base.'_'.substr(hash('sha256', $label), 0, 8);
                $suffix = 2;
                while (isset($usedValues[$value])) {
                    $value = $base.'_'.substr(hash('sha256', $label.'#'.$suffix), 0, 8);
                    $suffix++;
                }
            }

            $usedValues[$value] = true;
            $options[] = [$label, $value];
        }

        return $options;
    }

    private function valueBase(string $label): string
    {
        // Preserve common symbolic meaning before slugging. This keeps values readable
        // for option sets such as blood groups while the collision fallback remains generic.
        $prepared = str_replace(
            ['+', '&', '%', '@', '#'],
            [' plus ', ' and ', ' percent ', ' at ', ' number '],
            $label
        );

        // A trailing minus sign commonly has semantic meaning (for example A-, O-).
        // Internal hyphens remain ordinary word separators (e.g. Non-Creamy Layer).
        $prepared = preg_replace('/-(?=\s*$)/u', ' minus ', $prepared) ?? $prepared;

        $value = Str::slug($prepared, '_');

        return $value !== '' ? Str::limit($value, 150, '') : 'option_'.substr(hash('sha256', $label), 0, 12);
    }
}
