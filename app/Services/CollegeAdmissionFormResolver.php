<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeAdmissionCycle;
use App\Models\CollegeAdmissionFormMapping;
use App\Models\CollegeAdmissionFormTemplate;
use App\Models\CollegeApplicationFeeRule;

class CollegeAdmissionFormResolver
{
    public function __construct(private CollegeAdmissionDynamicFieldService $dynamicFieldService) {}

    public function resolveTemplate(College $college, CollegeAdmissionCycle $cycle, string $admissionMode = 'REGULAR'): ?CollegeAdmissionFormTemplate
    {
        $cycle->loadMissing('programOffering.programTemplate.degree');
        $offering = $cycle->programOffering;
        if (! $offering || ! $offering->programTemplate) return null;
        $program = $offering->programTemplate;
        $degree = $program->degree;

        $mapping = CollegeAdmissionFormMapping::query()
            ->with(['template.parent.steps.panels','template.parent.steps.fields.options','template.parent.steps.fields.conditions','template.parent.steps.fields.scopes','template.steps.panels','template.steps.fields.options','template.steps.fields.conditions','template.steps.fields.scopes'])
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->whereHas('template', fn ($q) => $q->where('status', 'ACTIVE')->whereIn('admission_mode', [$admissionMode, 'BOTH']))
            ->where(fn ($q) => $q->whereNull('college_id')->orWhere('college_id', $college->id))
            ->where(fn ($q) => $q->whereNull('degree_level_id')->orWhere('degree_level_id', $degree?->degree_level_id))
            ->where(fn ($q) => $q->whereNull('degree_id')->orWhere('degree_id', $program->degree_id))
            ->where(fn ($q) => $q->whereNull('program_template_id')->orWhere('program_template_id', $program->id))
            ->where(fn ($q) => $q->whereNull('college_program_offering_id')->orWhere('college_program_offering_id', $offering->id))
            ->where(fn ($q) => $q->whereNull('college_admission_cycle_id')->orWhere('college_admission_cycle_id', $cycle->id))
            ->get()
            ->sortByDesc(fn ($m) => $this->specificity($m))
            ->first();

        return $mapping?->template;
    }

    public function resolveFee(College $college, CollegeAdmissionCycle $cycle): array
    {
        $cycle->loadMissing('programOffering.programTemplate.degree');
        $offering = $cycle->programOffering;
        $program = $offering?->programTemplate;
        $degree = $program?->degree;
        if (! $offering || ! $program) return ['rule' => null, 'required' => false, 'amount' => '0.00', 'currency' => 'INR'];

        $rule = CollegeApplicationFeeRule::query()
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->where(fn ($q) => $q->whereNull('college_id')->orWhere('college_id', $college->id))
            ->where(fn ($q) => $q->whereNull('degree_level_id')->orWhere('degree_level_id', $degree?->degree_level_id))
            ->where(fn ($q) => $q->whereNull('degree_id')->orWhere('degree_id', $program->degree_id))
            ->where(fn ($q) => $q->whereNull('program_template_id')->orWhere('program_template_id', $program->id))
            ->where(fn ($q) => $q->whereNull('college_program_offering_id')->orWhere('college_program_offering_id', $offering->id))
            ->where(fn ($q) => $q->whereNull('college_admission_cycle_id')->orWhere('college_admission_cycle_id', $cycle->id))
            ->get()
            ->sortByDesc(fn ($r) => $this->specificity($r))
            ->first();

        return [
            'rule' => $rule,
            'required' => (bool) ($rule?->fee_required ?? false),
            'amount' => (string) ($rule?->amount ?? '0.00'),
            'currency' => $rule?->currency ?? 'INR',
        ];
    }

    public function templatePayload(?CollegeAdmissionFormTemplate $template, ?CollegeAdmissionCycle $cycle = null): ?array
    {
        if (! $template) return null;
        $template->loadMissing(['parent.steps.panels','parent.steps.fields.options','parent.steps.fields.conditions','parent.steps.fields.scopes','steps.panels','steps.fields.options','steps.fields.conditions','steps.fields.scopes']);

        $ownSteps = $template->steps->where('status', 'ACTIVE')->map(function ($step) use ($template, $cycle) {
            $fields = $step->fields->where('status', 'ACTIVE');
            if ($cycle) $fields = $fields->filter(fn ($field) => $this->dynamicFieldService->isApplicable($field, $cycle));

            return [
                'id' => $step->id, 'title' => $step->title, 'code' => $step->code, 'description' => $step->description,
                'source' => $template->owner_scope_type,
                'panels' => $step->panels->where('status','ACTIVE')->map(fn($panel)=>['id'=>$panel->id,'title'=>$panel->title,'code'=>$panel->code,'description'=>$panel->description,'display_order'=>$panel->display_order])->values(),
                'fields' => $fields->map(fn ($field) => [
                    'id' => $field->id, 'field_key' => $field->field_key, 'label' => $field->label, 'field_type' => $field->field_type,
                    'placeholder' => $field->placeholder, 'help_text' => $field->help_text, 'is_required' => $field->is_required, 'college_admission_form_panel_id'=>$field->college_admission_form_panel_id,
                    'validation_rules' => $field->validation_rules, 'condition_match_mode' => $field->condition_match_mode ?? 'ALL',
                    'conditions' => $field->conditions->where('is_active', true)->map(fn ($condition) => [
                        'source_field_id' => $condition->source_field_id,
                        'operator' => $condition->operator,
                        'compare_values' => array_values($condition->compare_values ?? []),
                    ])->values(),
                    'applicability_scopes' => $field->scopes->where('is_active', true)->map(fn ($scope) => [
                        'degree_level_id'=>$scope->degree_level_id,'degree_id'=>$scope->degree_id,'program_template_id'=>$scope->program_template_id,
                        'college_program_offering_id'=>$scope->college_program_offering_id,'curriculum_id'=>$scope->curriculum_id,'college_admission_cycle_id'=>$scope->college_admission_cycle_id,
                    ])->values(),
                    'options' => $field->options->where('is_active', true)->map(fn ($o) => ['value' => $o->value, 'label' => $o->label])->values(),
                ])->values(),
            ];
        })->filter(fn ($step) => count($step['fields']) > 0)->values();

        $parentPayload = $template->parent ? $this->templatePayload($template->parent, $cycle) : null;
        $steps = collect($parentPayload['steps'] ?? [])->concat($ownSteps)->values();
        return [
            'id' => $template->id, 'name' => $template->name, 'code' => $template->code,
            'admission_mode' => $template->admission_mode, 'governance_mode' => $template->governance_mode,
            'parent_template_id' => $template->parent_template_id, 'steps' => $steps,
        ];
    }

    private function specificity(object $row): int
    {
        return collect(['college_id','degree_level_id','degree_id','program_template_id','college_program_offering_id','college_admission_cycle_id'])
            ->sum(fn ($key) => filled($row->{$key} ?? null) ? match ($key) {
                'college_admission_cycle_id' => 64,
                'college_program_offering_id' => 32,
                'program_template_id' => 16,
                'degree_id' => 8,
                'degree_level_id' => 4,
                'college_id' => 2,
                default => 0,
            } : 0);
    }
}
