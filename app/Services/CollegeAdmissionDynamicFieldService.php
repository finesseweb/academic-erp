<?php

namespace App\Services;

use App\Models\CollegeAdmissionApplication;
use App\Models\CollegeAdmissionCycle;
use App\Models\CollegeAdmissionFormField;
use App\Models\CollegeAdmissionFormTemplate;
use App\Models\ReservationCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CollegeAdmissionDynamicFieldService
{
    public function validateAndNormalize(CollegeAdmissionFormTemplate $template, CollegeAdmissionCycle $cycle, array $input, ?CollegeAdmissionApplication $application = null): array
    {
        $template->loadMissing($this->templateRelations());
        $cycle->loadMissing('programOffering.programTemplate.degree');

        $fields = $this->allFields($template)->where('status', 'ACTIVE')->values();
        $effectiveValues = $this->applyCopyRules($fields, $this->effectiveValues($fields, $input, $application));

        // Applicability is evaluated before answer conditions. A conditional field must
        // never become visible because a source field carries a stale/submitted value
        // while that source itself is outside the current academic context.
        $applicableFieldIds = $fields
            ->filter(fn ($field) => $this->isApplicable($field, $cycle))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $applicableLookup = array_fill_keys($applicableFieldIds, true);
        foreach ($fields as $field) {
            if (! isset($applicableLookup[(int) $field->id])) $effectiveValues[$field->id] = null;
        }

        $normalized = [];
        $errors = [];

        foreach ($fields as $field) {
            if (! isset($applicableLookup[(int) $field->id]) || ! $this->conditionsPass($field, $effectiveValues)) {
                $normalized[$field->id] = null;
                continue;
            }

            $value = $effectiveValues[$field->id] ?? null;
            $hasExisting = $application?->fieldValues()
                ->where('college_admission_form_field_id', $field->id)
                ->where(fn ($q) => $q->whereNotNull('value_text')->orWhereNotNull('value_json')->orWhereNotNull('file_path'))
                ->exists() ?? false;

            if ($field->is_required && $this->blankValue($value) && ! $hasExisting) {
                $errors["custom_fields.{$field->id}"] = "{$field->label} is required.";
                continue;
            }

            if ($this->blankValue($value)) {
                if (! $hasExisting) $normalized[$field->id] = null;
                continue;
            }

            $allowed = $field->system_purpose === 'CANDIDATE_RESERVATION_CATEGORY'
                ? collect(['GENERAL'])->concat(
                    ReservationCategory::query()
                        ->where('university_id',$template->university_id)
                        ->where('status','ACTIVE')
                        ->where('nature','VERTICAL')
                        ->whereRaw("LOWER(TRIM(code)) NOT IN ('general','gen','open','unreserved','ur')")
                        ->pluck('code')
                )->values()->all()
                : $field->options->where('is_active', true)->pluck('value')->all();
            if (in_array($field->field_type, ['SELECT','RADIO','YES_NO'], true) && $allowed && ! in_array((string) $value, $allowed, true)) {
                $errors["custom_fields.{$field->id}"] = "Invalid option selected for {$field->label}.";
                continue;
            }

            if (in_array($field->field_type, ['CHECKBOX','MULTISELECT'], true)) {
                $values = is_array($value) ? array_values($value) : [$value];
                if ($allowed && array_diff($values, $allowed)) $errors["custom_fields.{$field->id}"] = "Invalid option selected for {$field->label}.";
                $normalized[$field->id] = $values;
                continue;
            }

            if (in_array($field->field_type, ['FILE','IMAGE'], true)) {
                if (! $value instanceof UploadedFile) $errors["custom_fields.{$field->id}"] = "Upload a valid file for {$field->label}.";
                else {
                    $rules = $field->validation_rules ?? [];
                    $maxKb = (int) ($rules['max_kb'] ?? 5120);
                    if (($value->getSize() / 1024) > $maxKb) $errors["custom_fields.{$field->id}"] = "{$field->label} exceeds the maximum file size.";
                    $exts = array_map('strtolower', $rules['extensions'] ?? ($field->field_type === 'IMAGE' ? ['jpg','jpeg','png','webp'] : ['pdf','jpg','jpeg','png']));
                    if ($exts && ! in_array(strtolower($value->getClientOriginalExtension()), $exts, true)) $errors["custom_fields.{$field->id}"] = "{$field->label} file type is not allowed.";
                }
                $normalized[$field->id] = $value;
                continue;
            }

            $scalar = is_scalar($value) ? trim((string) $value) : $value;
            $ruleError = $this->validateIntrinsicValue($field, $scalar);
            if ($ruleError) {
                $errors["custom_fields.{$field->id}"] = $ruleError;
                continue;
            }
            $comparisonError = $this->validateComparison($field, $scalar, $effectiveValues);
            if ($comparisonError) {
                $errors["custom_fields.{$field->id}"] = $comparisonError;
                continue;
            }
            $normalized[$field->id] = $scalar;
        }

        if ($errors) throw ValidationException::withMessages($errors);
        return $normalized;
    }

    public function persist(CollegeAdmissionApplication $application, CollegeAdmissionFormTemplate $template, array $values): void
    {
        $template->loadMissing($this->templateRelations());
        $fieldIds = $this->allFields($template)->pluck('id')->all();
        $application->fieldValues()->whereNotIn('college_admission_form_field_id', $fieldIds ?: [0])->delete();

        foreach ($this->allFields($template) as $field) {
            if (! array_key_exists($field->id, $values)) continue;
            $value = $values[$field->id];

            if ($value === null) {
                $application->fieldValues()->where('college_admission_form_field_id', $field->id)->delete();
                continue;
            }

            $payload = ['value_text'=>null,'value_json'=>null,'file_path'=>null,'file_name'=>null,'file_mime'=>null,'file_size'=>null];
            if ($value instanceof UploadedFile) {
                $path = $value->store("admission-applications/{$application->college_id}/{$application->id}", 'local');
                $payload = [...$payload, 'file_path'=>$path, 'file_name'=>$value->getClientOriginalName(), 'file_mime'=>$value->getClientMimeType(), 'file_size'=>$value->getSize()];
            } elseif (is_array($value)) $payload['value_json'] = $value;
            else $payload['value_text'] = (string) $value;

            $application->fieldValues()->updateOrCreate(['college_admission_form_field_id'=>$field->id], $payload);
        }
    }

    public function isApplicable(CollegeAdmissionFormField $field, CollegeAdmissionCycle $cycle): bool
    {
        $field->loadMissing('scopes');
        $activeScopes = $field->scopes->where('is_active', true);
        if ($activeScopes->isEmpty()) return true;

        $cycle->loadMissing('programOffering.programTemplate.degree');
        $offering = $cycle->programOffering;
        $program = $offering?->programTemplate;
        $degree = $program?->degree;
        $context = [
            'degree_level_id' => $degree?->degree_level_id,
            'degree_id' => $program?->degree_id,
            'program_template_id' => $program?->id,
            'college_program_offering_id' => $offering?->id,
            'curriculum_id' => $offering?->curriculum_id,
            'college_admission_cycle_id' => $cycle->id,
        ];

        return $activeScopes->contains(function ($scope) use ($context) {
            foreach (array_keys($context) as $key) {
                if (filled($scope->{$key}) && (int) $scope->{$key} !== (int) $context[$key]) return false;
            }
            return true;
        });
    }

    public function conditionsPass(CollegeAdmissionFormField $field, array $values): bool
    {
        $field->loadMissing('conditions');
        $conditions = $field->conditions->where('is_active', true);
        if ($conditions->isEmpty()) return true;

        $results = $conditions->map(function ($condition) use ($values) {
            $actual = $values[$condition->source_field_id] ?? null;
            $expected = array_values($condition->compare_values ?? []);
            return $this->conditionMatches($actual, $condition->operator, $expected);
        });

        return ($field->condition_match_mode ?? 'ALL') === 'ANY' ? $results->contains(true) : ! $results->contains(false);
    }

    private function effectiveValues(Collection $fields, array $input, ?CollegeAdmissionApplication $application): array
    {
        $existing = $application?->fieldValues()->get()->keyBy('college_admission_form_field_id') ?? collect();
        $values = [];
        foreach ($fields as $field) {
            if (array_key_exists((string) $field->id, $input)) {
                $values[$field->id] = $input[(string) $field->id];
                continue;
            }
            $stored = $existing->get($field->id);
            $values[$field->id] = $stored?->value_json ?? $stored?->value_text ?? ($stored?->file_path ? $stored->file_name : null);
        }
        return $values;
    }

    private function conditionMatches(mixed $actual, string $operator, array $expected): bool
    {
        $blank = $this->blankValue($actual);
        if ($operator === 'IS_EMPTY') return $blank;
        if ($operator === 'IS_NOT_EMPTY') return ! $blank;

        $actualValues = is_array($actual) ? array_map('strval', $actual) : [(string) $actual];
        $expected = array_map('strval', $expected);
        $canonical = fn (string $value) => Str::slug(trim($value), '_');
        $actualCanonical = array_map($canonical, $actualValues);
        $expectedCanonical = array_map($canonical, $expected);
        $intersects = count(array_intersect($actualCanonical, $expectedCanonical)) > 0;

        return match ($operator) {
            'EQUALS', 'IN' => $intersects,
            'NOT_EQUALS', 'NOT_IN' => ! $intersects,
            'CONTAINS' => collect($actualValues)->contains(fn ($value) => collect($expected)->contains(fn ($needle) => $needle !== '' && str_contains(mb_strtolower($value), mb_strtolower($needle)))),
            default => false,
        };
    }

    private function applyCopyRules(Collection $fields, array $values): array
    {
        foreach ($fields as $field) {
            $field->loadMissing('copyRule');
            $rule = $field->copyRule;
            if (! $rule || ! $rule->is_active) continue;
            $trigger = $values[$rule->trigger_field_id] ?? null;
            if (! $this->triggerMatches($trigger, $rule->trigger_values ?? [])) continue;
            $values[$field->id] = $values[$rule->source_field_id] ?? null;
        }
        return $values;
    }

    private function triggerMatches(mixed $actual, array $expected): bool
    {
        $actualValues = is_array($actual) ? array_map('strval', $actual) : [(string) ($actual ?? '')];
        $canonical = fn (string $value) => Str::slug(trim($value), '_');
        $actualCanonical = array_map($canonical, $actualValues);
        $expectedCanonical = array_map($canonical, array_map('strval', $expected));
        return count(array_intersect($actualCanonical, $expectedCanonical)) > 0;
    }

    private function validateIntrinsicValue(CollegeAdmissionFormField $field, mixed $value): ?string
    {
        $rules = $field->validation_rules ?? [];
        $text = is_scalar($value) ? (string) $value : '';

        if (in_array($field->field_type, ['TEXT','TEXTAREA','EMAIL','PHONE'], true)) {
            $length = mb_strlen($text);
            if (isset($rules['exact_length']) && $length !== (int) $rules['exact_length']) return "{$field->label} must be exactly {$rules['exact_length']} characters.";
            if (isset($rules['min_length']) && $length < (int) $rules['min_length']) return "{$field->label} must be at least {$rules['min_length']} characters.";
            if (isset($rules['max_length']) && $length > (int) $rules['max_length']) return "{$field->label} may not be longer than {$rules['max_length']} characters.";
            if (in_array($field->field_type, ['TEXT','TEXTAREA'], true)) {
                $mode = $rules['text_input_mode'] ?? 'ANY';
                $pattern = match ($mode) {
                    'LETTERS_ONLY' => '/^[\p{L}\p{M}]+(?:[ \'-][\p{L}\p{M}]+)*$/u',
                    'DIGITS_ONLY' => '/^\d+$/u',
                    'ALPHANUMERIC' => '/^[\p{L}\p{M}\p{N}]+$/u',
                    default => null,
                };
                if ($text !== '' && $pattern && ! preg_match($pattern, $text)) {
                    $description = match ($mode) {
                        'LETTERS_ONLY' => 'letters only',
                        'DIGITS_ONLY' => 'digits only',
                        'ALPHANUMERIC' => 'letters and numbers only',
                        default => 'the configured format',
                    };
                    return "{$field->label} must contain {$description}.";
                }
            }
            if ($field->field_type === 'EMAIL' && ! filter_var($text, FILTER_VALIDATE_EMAIL)) return "Enter a valid email address for {$field->label}.";
        }

        if ($field->field_type === 'NUMBER') {
            if (! is_numeric($text)) return "{$field->label} must be a valid number.";
            $number = (float) $text;
            if (isset($rules['min_value']) && $number < (float) $rules['min_value']) return "{$field->label} must be at least {$rules['min_value']}.";
            if (isset($rules['max_value']) && $number > (float) $rules['max_value']) return "{$field->label} may not be greater than {$rules['max_value']}.";
            if (! empty($rules['integer_only']) && floor($number) != $number) return "{$field->label} must be a whole number.";
            if (isset($rules['decimal_places'])) {
                $parts = explode('.', $text, 2);
                $places = isset($parts[1]) ? strlen(rtrim($parts[1], '0')) : 0;
                if ($places > (int) $rules['decimal_places']) return "{$field->label} may have at most {$rules['decimal_places']} decimal places.";
            }
        }

        if ($field->field_type === 'DATE') {
            $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $text);
            if (! $parsed || $parsed->format('Y-m-d') !== $text) return "Enter a valid date for {$field->label}.";
            if (isset($rules['min_age_years']) || isset($rules['max_age_years'])) {
                $reference = ($rules['age_reference_mode'] ?? 'TODAY') === 'CUSTOM'
                    ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($rules['age_reference_date'] ?? ''))
                    : new \DateTimeImmutable('today');
                if (! $reference) return "Age reference date configured for {$field->label} is invalid.";
                if ($parsed > $reference) return "{$field->label} cannot be after the age reference date.";
                $age = $parsed->diff($reference)->y;
                if (isset($rules['min_age_years']) && $age < (int) $rules['min_age_years']) return "Age calculated from {$field->label} must be at least {$rules['min_age_years']} years.";
                if (isset($rules['max_age_years']) && $age > (int) $rules['max_age_years']) return "Age calculated from {$field->label} may not be more than {$rules['max_age_years']} years.";
            }
        }
        return null;
    }

    private function validateComparison(CollegeAdmissionFormField $field, mixed $value, array $values): ?string
    {
        $field->loadMissing('comparisonRule.sourceField');
        $rule = $field->comparisonRule;
        if (! $rule || ! $rule->is_active) return null;
        $other = $values[$rule->source_field_id] ?? null;
        if ($this->blankValue($other)) return null;

        if ($field->field_type === 'NUMBER') {
            if (! is_numeric($value) || ! is_numeric($other)) return "{$field->label} and {$rule->sourceField->label} must contain valid numbers.";
            $left = (float) $value; $right = (float) $other;
        } elseif ($field->field_type === 'DATE') {
            $leftDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $value);
            $rightDate = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $other);
            if (! $leftDate || ! $rightDate) return "{$field->label} and {$rule->sourceField->label} must contain valid dates.";
            $left = $leftDate->getTimestamp(); $right = $rightDate->getTimestamp();
        } else return null;

        $passes = match ($rule->operator) {
            'LT' => $left < $right, 'LTE' => $left <= $right, 'GT' => $left > $right,
            'GTE' => $left >= $right, 'EQ' => $left == $right, 'NEQ' => $left != $right,
            default => true,
        };
        if ($passes) return null;
        $symbol = ['LT'=>'<','LTE'=>'≤','GT'=>'>','GTE'=>'≥','EQ'=>'=','NEQ'=>'≠'][$rule->operator] ?? $rule->operator;
        return "{$field->label} must be {$symbol} {$rule->sourceField->label}.";
    }

    private function allFields(CollegeAdmissionFormTemplate $template): Collection
    {
        $own = $template->steps->flatMap(fn ($step) => $step->fields);
        return $template->parent ? $this->allFields($template->parent)->concat($own) : $own;
    }

    private function templateRelations(): array
    {
        return [
            'parent.steps.fields.options','parent.steps.fields.conditions','parent.steps.fields.scopes','parent.steps.fields.comparisonRule.sourceField','parent.steps.fields.copyRule.sourceField','parent.steps.fields.copyRule.triggerField',
            'steps.fields.options','steps.fields.conditions','steps.fields.scopes','steps.fields.comparisonRule.sourceField','steps.fields.copyRule.sourceField','steps.fields.copyRule.triggerField',
        ];
    }

    private function blankValue(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && count(array_filter($value, fn ($v) => $v !== null && $v !== '')) === 0);
    }
}
