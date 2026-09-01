<?php

namespace App\Services;

use App\Models\CollegeAdmissionFormField;
use App\Models\CollegeAdmissionFormStep;
use App\Models\CollegeAdmissionFormTemplate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CollegeAdmissionFieldRuleService
{
    public function requestRules(): array
    {
        return [
            'min_length'=>['nullable','integer','min:0','max:100000'], 'max_length'=>['nullable','integer','min:1','max:100000'], 'exact_length'=>['nullable','integer','min:1','max:100000'],
            'min_value'=>['nullable','numeric'], 'max_value'=>['nullable','numeric'], 'integer_only'=>['nullable','boolean'], 'decimal_places'=>['nullable','integer','min:0','max:8'],
            'min_age_years'=>['nullable','integer','min:0','max:150'], 'max_age_years'=>['nullable','integer','min:0','max:150'],
            'age_reference_mode'=>['nullable',Rule::in(['TODAY','CUSTOM'])], 'age_reference_date'=>['nullable','date_format:Y-m-d'],
            'compare_field_id'=>['nullable','integer','exists:college_admission_form_fields,id'], 'compare_operator'=>['nullable',Rule::in(['LT','LTE','GT','GTE','EQ','NEQ'])],
            'copy_source_field_id'=>['nullable','integer','exists:college_admission_form_fields,id'], 'copy_trigger_field_id'=>['nullable','integer','exists:college_admission_form_fields,id'],
            'copy_trigger_values'=>['nullable','string','max:5000'], 'copy_read_only'=>['nullable','boolean'],
        ];
    }

    public function assertConfiguration(CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step, string $fieldType, array $data, ?CollegeAdmissionFormField $target = null): void
    {
        if (filled($data['exact_length'] ?? null) && (filled($data['min_length'] ?? null) || filled($data['max_length'] ?? null))) throw ValidationException::withMessages(['exact_length'=>'Use Exact Length OR Min/Max Length, not both.']);
        if (filled($data['min_length'] ?? null) && filled($data['max_length'] ?? null) && (int)$data['min_length'] > (int)$data['max_length']) throw ValidationException::withMessages(['max_length'=>'Maximum length must be greater than or equal to minimum length.']);
        if (filled($data['min_value'] ?? null) && filled($data['max_value'] ?? null) && (float)$data['min_value'] > (float)$data['max_value']) throw ValidationException::withMessages(['max_value'=>'Maximum value must be greater than or equal to minimum value.']);
        if (filled($data['min_age_years'] ?? null) && filled($data['max_age_years'] ?? null) && (int)$data['min_age_years'] > (int)$data['max_age_years']) throw ValidationException::withMessages(['max_age_years'=>'Maximum age must be greater than or equal to minimum age.']);
        if ($fieldType === 'DATE' && (filled($data['min_age_years'] ?? null) || filled($data['max_age_years'] ?? null))) {
            $mode = $data['age_reference_mode'] ?? 'TODAY';
            if ($mode === 'CUSTOM' && blank($data['age_reference_date'] ?? null)) throw ValidationException::withMessages(['age_reference_date'=>'Choose the custom date on which age should be calculated.']);
        }

        $compare = $this->eligibleField($template, $step, $data['compare_field_id'] ?? null, $target);
        if ($compare) {
            if (! in_array($fieldType, ['NUMBER','DATE'], true) || ! in_array($compare->field_type, ['NUMBER','DATE'], true) || $fieldType !== $compare->field_type) throw ValidationException::withMessages(['compare_field_id'=>'Cross-field comparison requires matching Number-to-Number or Date-to-Date fields.']);
            if (blank($data['compare_operator'] ?? null)) throw ValidationException::withMessages(['compare_operator'=>'Choose how this field compares with the other field.']);
        }

        $copySource = $this->eligibleField($template, $step, $data['copy_source_field_id'] ?? null, $target);
        $copyTrigger = $this->eligibleField($template, $step, $data['copy_trigger_field_id'] ?? null, $target);
        if ($copySource || $copyTrigger) {
            if (! $copySource || ! $copyTrigger) throw ValidationException::withMessages(['copy_source_field_id'=>'Copy behavior needs both a Source Field and a Trigger Field.']);
            if (in_array($fieldType, ['FILE','IMAGE'], true) || in_array($copySource->field_type, ['FILE','IMAGE'], true)) throw ValidationException::withMessages(['copy_source_field_id'=>'File/Image values cannot be copied by this rule.']);
            if (! $this->copyCompatible($fieldType, $copySource->field_type)) throw ValidationException::withMessages(['copy_source_field_id'=>'Source and target field types are not compatible for copying.']);
            if (in_array($copyTrigger->field_type, ['FILE','IMAGE'], true)) throw ValidationException::withMessages(['copy_trigger_field_id'=>'File/Image fields cannot trigger a copy rule.']);
            if (blank($data['copy_trigger_values'] ?? null)) throw ValidationException::withMessages(['copy_trigger_values'=>'Enter the trigger value, for example YES.']);
        }
    }

    public function intrinsicRules(array $data, string $fieldType, array $existing = []): array
    {
        $rules = [];
        if (in_array($fieldType, ['TEXT','TEXTAREA','EMAIL','PHONE'], true)) {
            foreach (['min_length','max_length','exact_length'] as $key) if (filled($data[$key] ?? null)) $rules[$key]=(int)$data[$key];
        }
        if ($fieldType === 'DATE') {
            foreach (['min_age_years','max_age_years'] as $key) if (filled($data[$key] ?? null) || ($data[$key] ?? null) === 0 || ($data[$key] ?? null) === '0') $rules[$key]=(int)$data[$key];
            if (isset($rules['min_age_years']) || isset($rules['max_age_years'])) {
                $rules['age_reference_mode']=$data['age_reference_mode'] ?? ($existing['age_reference_mode'] ?? 'TODAY');
                if (($rules['age_reference_mode'] ?? 'TODAY') === 'CUSTOM' && filled($data['age_reference_date'] ?? null)) $rules['age_reference_date']=$data['age_reference_date'];
                elseif (($rules['age_reference_mode'] ?? 'TODAY') !== 'CUSTOM') unset($rules['age_reference_date']);
            }
        }
        if ($fieldType === 'NUMBER') {
            foreach (['min_value','max_value'] as $key) if (filled($data[$key] ?? null)) $rules[$key]=(float)$data[$key];
            if (array_key_exists('integer_only',$data)) $rules['integer_only']=(bool)$data['integer_only'];
            if (filled($data['decimal_places'] ?? null) || ($data['decimal_places'] ?? null) === 0 || ($data['decimal_places'] ?? null) === '0') $rules['decimal_places']=(int)$data['decimal_places'];
        }
        if (in_array($fieldType, ['FILE','IMAGE'], true)) {
            if (filled($data['max_kb'] ?? null)) $rules['max_kb']=(int)$data['max_kb']; elseif (isset($existing['max_kb'])) $rules['max_kb']=$existing['max_kb'];
            if (filled($data['extensions'] ?? null)) $rules['extensions']=array_values(array_filter(array_map(fn($v)=>strtolower(trim($v)),explode(',',$data['extensions'])))); elseif (isset($existing['extensions'])) $rules['extensions']=$existing['extensions'];
        }
        return $rules;
    }

    public function sync(CollegeAdmissionFormField $field, array $data): void
    {
        if (filled($data['compare_field_id'] ?? null)) $field->comparisonRule()->updateOrCreate([], ['source_field_id'=>(int)$data['compare_field_id'],'operator'=>$data['compare_operator'],'is_active'=>true]);
        else $field->comparisonRule()->delete();

        if (filled($data['copy_source_field_id'] ?? null) && filled($data['copy_trigger_field_id'] ?? null)) {
            $field->copyRule()->updateOrCreate([], [
                'source_field_id'=>(int)$data['copy_source_field_id'],'trigger_field_id'=>(int)$data['copy_trigger_field_id'],
                'trigger_values'=>$this->splitValues($data['copy_trigger_values'] ?? ''),'is_read_only_when_active'=>(bool)($data['copy_read_only'] ?? true),'is_active'=>true,
            ]);
        } else $field->copyRule()->delete();
    }

    private function eligibleField(CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step, mixed $id, ?CollegeAdmissionFormField $target): ?CollegeAdmissionFormField
    {
        if (blank($id)) return null;
        $candidate = $this->allFields($template)->firstWhere('id',(int)$id);
        if (! $candidate || ($target && $candidate->id === $target->id)) throw ValidationException::withMessages(['compare_field_id'=>'Selected source field is not available in this template.']);
        $candidateStep = $candidate->step;
        if ($candidateStep && $candidateStep->display_order > $step->display_order) throw ValidationException::withMessages(['compare_field_id'=>'Choose a field from this step or an earlier step.']);
        return $candidate;
    }

    private function allFields(CollegeAdmissionFormTemplate $template)
    {
        $template->loadMissing(['parent.steps.fields.step','steps.fields.step']);
        $own=$template->steps->flatMap(fn($s)=>$s->fields);
        return $template->parent ? $this->allFields($template->parent)->concat($own) : $own;
    }

    private function copyCompatible(string $target, string $source): bool
    {
        if ($target === $source) return true;
        $textual=['TEXT','TEXTAREA','EMAIL','PHONE'];
        $choice=['SELECT','RADIO','YES_NO'];
        return (in_array($target,$textual,true)&&in_array($source,$textual,true)) || (in_array($target,$choice,true)&&in_array($source,$choice,true));
    }

    private function splitValues(string $values): array { return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $values) ?: []), fn($v)=>$v!=='')); }
}
