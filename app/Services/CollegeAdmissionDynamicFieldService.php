<?php

namespace App\Services;

use App\Models\CollegeAdmissionApplication;
use App\Models\CollegeAdmissionCycle;
use App\Models\CollegeAdmissionFormField;
use App\Models\CollegeAdmissionFormTemplate;
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
        $effectiveValues = $this->effectiveValues($fields, $input, $application);
        $normalized = [];
        $errors = [];

        foreach ($fields as $field) {
            if (! $this->isApplicable($field, $cycle) || ! $this->conditionsPass($field, $effectiveValues)) {
                $normalized[$field->id] = null;
                continue;
            }

            $value = $input[(string) $field->id] ?? null;
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

            $allowed = $field->options->where('is_active', true)->pluck('value')->all();
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

            $normalized[$field->id] = is_scalar($value) ? trim((string) $value) : $value;
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

    private function allFields(CollegeAdmissionFormTemplate $template): Collection
    {
        $own = $template->steps->flatMap(fn ($step) => $step->fields);
        return $template->parent ? $this->allFields($template->parent)->concat($own) : $own;
    }

    private function templateRelations(): array
    {
        return [
            'parent.steps.fields.options','parent.steps.fields.conditions','parent.steps.fields.scopes',
            'steps.fields.options','steps.fields.conditions','steps.fields.scopes',
        ];
    }

    private function blankValue(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && count(array_filter($value, fn ($v) => $v !== null && $v !== '')) === 0);
    }
}
