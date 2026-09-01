<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeAdmissionCycle;
use App\Models\CollegeAdmissionFormMapping;
use App\Models\CollegeAdmissionFormTemplate;
use App\Models\CollegeApplicationFeeRule;
use App\Models\ProgramTemplate;

class CollegeAdmissionFormResolver
{
    public function __construct(private CollegeAdmissionDynamicFieldService $dynamicFieldService) {}

    public function resolveTemplate(College $college, CollegeAdmissionCycle $cycle, string $admissionMode = 'REGULAR'): ?CollegeAdmissionFormTemplate
    {
        $cycle->loadMissing('programOffering');
        $offering = $cycle->programOffering;
        if (! $offering) return null;

        // Resolve the Program Template from its authoritative id instead of trusting a
        // possibly partially-selected eager-loaded relation. Some Admission pages load
        // programTemplate with only id/name/code for display; in that state degree_id is
        // absent and degree-scoped Form Mappings would incorrectly look unresolved.
        $programTemplateId = $offering->program_template_id ?: $offering->programTemplate?->id;
        $program = $programTemplateId
            ? ProgramTemplate::query()->with('degree:id,degree_level_id')->find($programTemplateId)
            : null;
        if (! $program) return null;
        $degree = $program->degree;

        $mapping = CollegeAdmissionFormMapping::query()
            ->with(['template.parent.steps.panels','template.parent.steps.fields.options','template.parent.steps.fields.conditions','template.parent.steps.fields.scopes','template.parent.steps.fields.comparisonRule.sourceField','template.parent.steps.fields.copyRule.sourceField','template.parent.steps.fields.copyRule.triggerField','template.steps.panels','template.steps.fields.options','template.steps.fields.conditions','template.steps.fields.scopes','template.steps.fields.comparisonRule.sourceField','template.steps.fields.copyRule.sourceField','template.steps.fields.copyRule.triggerField'])
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

    /**
     * Return the application routes currently allowed by the mapped ACTIVE Admission Form.
     * If no dynamic form is mapped at all, the legacy/core-only application capture remains
     * available for both routes. Once a form is mapped, its admission_mode is authoritative.
     *
     * @return array<int, string>
     */
    public function allowedAdmissionModes(College $college, CollegeAdmissionCycle $cycle): array
    {
        $regular = $this->resolveTemplate($college, $cycle, 'REGULAR');
        $direct = $this->resolveTemplate($college, $cycle, 'DIRECT');

        if (! $regular && ! $direct) {
            return ['REGULAR', 'DIRECT'];
        }

        $modes = [];
        if ($regular) $modes[] = 'REGULAR';
        if ($direct) $modes[] = 'DIRECT';

        return $modes;
    }

    public function assertAdmissionModeAllowed(College $college, CollegeAdmissionCycle $cycle, string $admissionMode): void
    {
        $mode = strtoupper($admissionMode);
        $allowed = $this->allowedAdmissionModes($college, $cycle);
        if (in_array($mode, $allowed, true)) return;

        $label = $allowed === ['REGULAR']
            ? 'Regular / Selection Based only'
            : ($allowed === ['DIRECT'] ? 'Direct Admission only' : implode(' / ', $allowed));

        throw \Illuminate\Validation\ValidationException::withMessages([
            'admission_mode' => "The mapped Admission Form allows {$label}. The selected admission route is not permitted for this template.",
        ]);
    }

    public function resolveFee(College $college, CollegeAdmissionCycle $cycle): array
    {
        $cycle->loadMissing('programOffering');
        $offering = $cycle->programOffering;
        $programTemplateId = $offering?->program_template_id ?: $offering?->programTemplate?->id;
        $program = $programTemplateId
            ? ProgramTemplate::query()->with('degree:id,degree_level_id')->find($programTemplateId)
            : null;
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
        $template->loadMissing(['parent.steps.panels','parent.steps.fields.options','parent.steps.fields.conditions','parent.steps.fields.scopes','parent.steps.fields.comparisonRule.sourceField','parent.steps.fields.copyRule.sourceField','parent.steps.fields.copyRule.triggerField','steps.panels','steps.fields.options','steps.fields.conditions','steps.fields.scopes','steps.fields.comparisonRule.sourceField','steps.fields.copyRule.sourceField','steps.fields.copyRule.triggerField']);

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
                    'comparison_rule' => ($field->comparisonRule && $field->comparisonRule->is_active) ? [
                        'source_field_id' => $field->comparisonRule->source_field_id,
                        'source_field_label' => $field->comparisonRule->sourceField?->label,
                        'operator' => $field->comparisonRule->operator,
                    ] : null,
                    'copy_rule' => ($field->copyRule && $field->copyRule->is_active) ? [
                        'source_field_id' => $field->copyRule->source_field_id,
                        'source_field_label' => $field->copyRule->sourceField?->label,
                        'trigger_field_id' => $field->copyRule->trigger_field_id,
                        'trigger_field_label' => $field->copyRule->triggerField?->label,
                        'trigger_values' => array_values($field->copyRule->trigger_values ?? []),
                        'read_only' => (bool) $field->copyRule->read_only,
                    ] : null,
                    'options' => $field->options->where('is_active', true)->map(fn ($o) => ['value' => $o->value, 'label' => $o->label])->values(),
                ])->values(),
            ];
        })->filter(fn ($step) => count($step['fields']) > 0)->values();

        $parentPayload = $template->parent ? $this->templatePayload($template->parent, $cycle) : null;
        $steps = collect($parentPayload['steps'] ?? [])->concat($ownSteps)->values();

        // Academic applicability can remove a condition source from the effective form.
        // Never send a dangling child condition to either applicant runtime. Prune such
        // children iteratively because removing one field can invalidate another field
        // that depends on it.
        do {
            $before = $steps->sum(fn ($step) => collect($step['fields'] ?? [])->count());
            $available = $steps->flatMap(fn ($step) => collect($step['fields'] ?? []))->pluck('id')->map(fn ($id) => (int) $id)->flip();
            $steps = $steps->map(function ($step) use ($available) {
                $step['fields'] = collect($step['fields'] ?? [])->filter(function ($field) use ($available) {
                    return collect($field['conditions'] ?? [])->every(fn ($condition) => $available->has((int) $condition['source_field_id']));
                })->values();
                return $step;
            })->filter(fn ($step) => collect($step['fields'] ?? [])->isNotEmpty())->values();
            $after = $steps->sum(fn ($step) => collect($step['fields'] ?? [])->count());
        } while ($after < $before);

        // Copy/comparison rules are runtime behavior, so they must be present in the
        // effective payload. If academic applicability removed a source/trigger field,
        // keep the target field usable but disable only the dangling advanced rule.
        $available = $steps->flatMap(fn ($step) => collect($step['fields'] ?? []))->pluck('id')->map(fn ($id) => (int) $id)->flip();
        $steps = $steps->map(function ($step) use ($available) {
            $step['fields'] = collect($step['fields'] ?? [])->map(function ($field) use ($available) {
                $copy = $field['copy_rule'] ?? null;
                if ($copy && (! $available->has((int) $copy['source_field_id']) || ! $available->has((int) $copy['trigger_field_id']))) {
                    $field['copy_rule'] = null;
                }
                $comparison = $field['comparison_rule'] ?? null;
                if ($comparison && ! $available->has((int) $comparison['source_field_id'])) {
                    $field['comparison_rule'] = null;
                }
                return $field;
            })->values();
            return $step;
        })->values();

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
