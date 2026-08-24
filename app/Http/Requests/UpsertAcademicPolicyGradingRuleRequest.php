<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpsertAcademicPolicyGradingRuleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('academic_policy.update') ?? false; }
    public function rules(): array
    {
        return [
            'grading_basis'=>['required',Rule::in(['LETTER_GRADE','GRADE_POINT','PASS_FAIL'])],
            'maximum_grade_point'=>['nullable','numeric','min:0','max:100'],
            'calculate_sgpa'=>['required','boolean'],'calculate_cgpa'=>['required','boolean'],
            'sgpa_decimal_places'=>['required','integer','min:0','max:4'],'cgpa_decimal_places'=>['required','integer','min:0','max:4'],
            'rounding_rule'=>['required',Rule::in(['NONE','NEAREST','FLOOR','CEIL'])],
            'notes'=>['nullable','string','max:5000'],
            'bands'=>['required_unless:grading_basis,PASS_FAIL','array','max:50'],
            'bands.*.minimum_percent'=>['required_with:bands','numeric','min:0','max:100'],
            'bands.*.maximum_percent'=>['required_with:bands','numeric','min:0','max:100'],
            'bands.*.grade_code'=>['required_with:bands','string','max:30'],
            'bands.*.grade_label'=>['nullable','string','max:100'],
            'bands.*.grade_point'=>['nullable','numeric','min:0','max:100'],
            'bands.*.is_passing'=>['required_with:bands','boolean'],
        ];
    }
}
