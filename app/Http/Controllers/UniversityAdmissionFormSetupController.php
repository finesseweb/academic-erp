<?php

namespace App\Http\Controllers;

use App\Models\CollegeAdmissionFormField;
use App\Models\CollegeAdmissionFormPanel;
use App\Models\CollegeAdmissionFormStep;
use App\Models\CollegeAdmissionFormTemplate;
use App\Models\CollegeApplicationFeeRule;
use App\Models\Curriculum;
use App\Models\Degree;
use App\Models\DegreeLevel;
use App\Models\ProgramTemplate;
use App\Models\ReservationCategory;
use App\Models\University;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use App\Services\CollegeAdmissionFieldRuleService;
use App\Services\CollegeAdmissionFormOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UniversityAdmissionFormSetupController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeUniversity($request, 'college_admission_form.view');
        $university = University::query()->firstOrFail();

        $templates = CollegeAdmissionFormTemplate::query()
            ->with(['manager:id,name,email', 'steps.panels', 'steps.fields.panel:id,title,code', 'steps.fields.options', 'steps.fields.conditions.sourceField:id,label,field_key,field_type', 'steps.fields.scopes', 'steps.fields.comparisonRule.sourceField:id,label,field_key,field_type', 'steps.fields.copyRule.sourceField:id,label,field_key,field_type', 'steps.fields.copyRule.triggerField:id,label,field_key,field_type'])
            ->where('university_id', $university->id)
            ->whereNull('college_id')
            ->where('owner_scope_type', 'UNIVERSITY')
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->where('status', 'ACTIVE')
            ->whereNull('primary_college_id')
            ->orderBy('name')
            ->get(['id','name','email']);

        $feeRules = CollegeApplicationFeeRule::query()
            ->where('university_id', $university->id)
            ->whereNull('college_id')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('admin/admission-form-setup/index', [
            'university' => $university->only(['id','name','code']),
            'templates' => $templates,
            'users' => $users,
            'feeRules' => $feeRules,
            'degreeLevels' => DegreeLevel::query()->where('university_id',$university->id)->where('status','ACTIVE')->orderBy('display_order')->get(['id','name','code']),
            'degrees' => Degree::query()->where('university_id',$university->id)->where('status','ACTIVE')->orderBy('display_order')->get(['id','degree_level_id','name','code']),
            'programs' => ProgramTemplate::query()->where('university_id',$university->id)->where('status','ACTIVE')->orderBy('display_order')->get(['id','degree_id','name','code']),
            'curricula' => Curriculum::query()->currentApproved()->where('university_id',$university->id)->orderBy('name')->get(['id','program_template_id','academic_session_id','name','code','version']),
            'can' => [
                'create' => $request->user()->hasPermission('college_admission_form.create'),
                'update' => $request->user()->hasPermission('college_admission_form.update'),
                'status' => $request->user()->hasPermission('college_admission_form.status'),
                'stepCreate' => $request->user()->hasPermission('college_admission_form.step_create'),
                'fieldCreate' => $request->user()->hasPermission('college_admission_form.field_create'),
                'delete' => $request->user()->hasPermission('college_admission_form.delete'),
                'stepUpdate' => $request->user()->hasPermission('college_admission_form.step_update'),
                'stepDelete' => $request->user()->hasPermission('college_admission_form.step_delete'),
                'panelCreate' => $request->user()->hasPermission('college_admission_form.panel_create'),
                'panelUpdate' => $request->user()->hasPermission('college_admission_form.panel_update'),
                'panelDelete' => $request->user()->hasPermission('college_admission_form.panel_delete'),
                'fieldUpdate' => $request->user()->hasPermission('college_admission_form.field_update'),
                'fieldDelete' => $request->user()->hasPermission('college_admission_form.field_delete'),
                'fee' => $request->user()->hasPermission('college_application_fee.manage'),
            ],
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $this->authorizeUniversity($request, 'college_admission_form.create');
        $university = University::query()->firstOrFail();
        $data = $request->validate([
            'name'=>['required','string','max:160'], 'code'=>['required','string','max:60'],
            'allow_college_override'=>['nullable','boolean'],
            'admission_mode'=>['required', Rule::in(['REGULAR','DIRECT','BOTH'])],
            'manager_user_id'=>['nullable','integer','exists:users,id'], 'description'=>['nullable','string','max:3000'],
        ]);
        if (filled($data['manager_user_id'] ?? null) && ! User::query()->whereKey($data['manager_user_id'])->where('status','ACTIVE')->whereNull('primary_college_id')->exists()) {
            throw ValidationException::withMessages(['manager_user_id'=>'University template manager must be an active University-level user.']);
        }
        $code = Str::upper(trim($data['code']));
        if (CollegeAdmissionFormTemplate::query()->where('university_id',$university->id)->whereNull('college_id')->where('code',$code)->exists()) {
            throw ValidationException::withMessages(['code'=>'This University template code already exists.']);
        }
        $template = CollegeAdmissionFormTemplate::create([
            'university_id'=>$university->id, 'college_id'=>null, 'parent_template_id'=>null,
            'manager_user_id'=>$data['manager_user_id']??null, 'name'=>trim($data['name']), 'code'=>$code,
            'owner_scope_type'=>'UNIVERSITY',
            'governance_mode'=>(bool)($data['allow_college_override']??false) ? 'UNIVERSITY_BASE_COLLEGE_EXTENSION' : 'UNIVERSITY_CONTROLLED',
            'allow_college_override'=>(bool)($data['allow_college_override']??false), 'admission_mode'=>$data['admission_mode'],
            'status'=>'DRAFT', 'description'=>$data['description']??null, 'created_by'=>$request->user()->id, 'updated_by'=>$request->user()->id,
        ]);
        $this->audit($request, 'UNIVERSITY_ADMISSION_FORM_TEMPLATE_CREATED', 'CollegeAdmissionFormTemplate', $template->id, $template->toArray());
        return back()->with('toast',['type'=>'success','message'=>'University Admission Form template created as DRAFT.']);
    }

    public function overrideControl(Request $request, CollegeAdmissionFormTemplate $template): RedirectResponse
    {
        $this->authorizeUniversity($request, 'college_admission_form.update');
        $this->assertUniversityTemplate($template);
        $allowed = (bool) $request->validate(['allow_college_override'=>['required','boolean']])['allow_college_override'];

        if (! $allowed && CollegeAdmissionFormTemplate::query()
            ->where('parent_template_id', $template->id)
            ->where('status', 'ACTIVE')
            ->exists()) {
            throw ValidationException::withMessages([
                'allow_college_override' => 'Active College extensions still inherit this template. Retire/remap them before disabling College override.',
            ]);
        }

        $template->update([
            'allow_college_override'=>$allowed,
            'governance_mode'=>$allowed ? 'UNIVERSITY_BASE_COLLEGE_EXTENSION' : 'UNIVERSITY_CONTROLLED',
            'updated_by'=>$request->user()->id,
        ]);
        $this->audit($request, 'UNIVERSITY_ADMISSION_FORM_OVERRIDE_CONTROL_CHANGED', 'CollegeAdmissionFormTemplate', $template->id, $template->fresh()->toArray());

        return back()->with('toast',['type'=>'success','message'=>$allowed ? 'College override enabled for this University form.' : 'College override disabled for this University form.']);
    }

    public function statusTemplate(Request $request, CollegeAdmissionFormTemplate $template): RedirectResponse
    {
        $this->authorizeUniversity($request, 'college_admission_form.status');
        $this->assertUniversityTemplate($template);
        $status = $request->validate(['status'=>['required',Rule::in(['DRAFT','ACTIVE','RETIRED'])]])['status'];
        if ($status === 'ACTIVE' && ! $template->steps()->where('status','ACTIVE')->whereHas('fields',fn($q)=>$q->where('status','ACTIVE'))->exists()) {
            throw ValidationException::withMessages(['status'=>'Add at least one active step with one active field before activating the University template.']);
        }
        if ($status === 'RETIRED' && CollegeAdmissionFormTemplate::query()->where('parent_template_id',$template->id)->where('status','ACTIVE')->exists()) {
            throw ValidationException::withMessages(['status'=>'Active College extensions still inherit this base template. Retire/remap them first.']);
        }
        $template->update(['status'=>$status,'updated_by'=>$request->user()->id]);
        $this->audit($request, 'UNIVERSITY_ADMISSION_FORM_TEMPLATE_STATUS_CHANGED', 'CollegeAdmissionFormTemplate', $template->id, $template->fresh()->toArray());
        return back()->with('toast',['type'=>'success','message'=>'University template status updated.']);
    }

    public function storeStep(Request $request, CollegeAdmissionFormTemplate $template): RedirectResponse
    {
        $this->authorizeUniversity($request, 'college_admission_form.step_create');
        $this->assertUniversityTemplate($template);
        abort_if($template->status === 'RETIRED', 422, 'A retired template cannot be changed.');
        $data=$request->validate(['title'=>['required','string','max:140'],'code'=>['required','string','max:60'],'description'=>['nullable','string','max:2000']]);
        $code=Str::upper(trim($data['code']));
        if ($template->steps()->where('code',$code)->exists()) throw ValidationException::withMessages(['code'=>'This step code already exists.']);
        $step=$template->steps()->create(['title'=>trim($data['title']),'code'=>$code,'description'=>$data['description']??null,'display_order'=>($template->steps()->max('display_order')??0)+10,'is_locked'=>true,'status'=>'ACTIVE']);
        $this->audit($request, 'UNIVERSITY_ADMISSION_FORM_STEP_CREATED', 'CollegeAdmissionFormStep', $step->id, $step->toArray());
        return back()->with('toast',['type'=>'success','message'=>'University base step added and locked for College inheritance.']);
    }

    public function storeField(Request $request, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step): RedirectResponse
    {
        $this->authorizeUniversity($request, 'college_admission_form.field_create');
        $this->assertUniversityTemplate($template);
        abort_unless($step->college_admission_form_template_id === $template->id, 404);
        $fieldRuleService = app(CollegeAdmissionFieldRuleService::class);
        $data=$request->validate([
            ...$fieldRuleService->requestRules(),
            'college_admission_form_panel_id'=>['nullable','integer','exists:college_admission_form_panels,id'],
            'label'=>['required','string','max:180'],'field_key'=>['required','string','max:100','regex:/^[a-z][a-z0-9_]*$/'],
            'field_type'=>['required',Rule::in(['TEXT','NUMBER','DATE','EMAIL','PHONE','TEXTAREA','SELECT','RADIO','CHECKBOX','MULTISELECT','FILE','IMAGE','YES_NO'])],
            'system_purpose'=>['nullable',Rule::in(['CANDIDATE_RESERVATION_CATEGORY','CANDIDATE_PROFILE_PHOTO'])],
            'placeholder'=>['nullable','string','max:220'],'help_text'=>['nullable','string','max:2000'],'is_required'=>['nullable','boolean'],'display_order'=>['nullable','integer','min:0','max:9999'],
            'options'=>['nullable','string','max:5000'],'max_kb'=>['nullable','integer','min:1','max:51200'],'extensions'=>['nullable','string','max:500'],
            'condition_source_field_id'=>['nullable','integer','exists:college_admission_form_fields,id'],
            'condition_operator'=>['nullable',Rule::in(['EQUALS','NOT_EQUALS','IN','NOT_IN','CONTAINS','IS_EMPTY','IS_NOT_EMPTY'])],
            'condition_values'=>['nullable','string','max:5000'],
            'degree_level_ids'=>['nullable','array','max:50'],'degree_level_ids.*'=>['integer','distinct','exists:degree_levels,id'],
            'degree_ids'=>['nullable','array','max:50'],'degree_ids.*'=>['integer','distinct','exists:degrees,id'],
            'program_template_ids'=>['nullable','array','max:100'],'program_template_ids.*'=>['integer','distinct','exists:program_templates,id'],
            'curriculum_ids'=>['nullable','array','max:100'],'curriculum_ids.*'=>['integer','distinct','exists:curricula,id'],
        ]);
        if ($step->fields()->where('field_key',$data['field_key'])->exists()) throw ValidationException::withMessages(['field_key'=>'This field key already exists in the step.']);
        if (($data['system_purpose'] ?? null) === 'CANDIDATE_RESERVATION_CATEGORY') {
            if ($data['field_type'] !== 'SELECT') throw ValidationException::withMessages(['field_type'=>'Candidate Reservation Category must use Dropdown input.']);
        } elseif (($data['system_purpose'] ?? null) === 'CANDIDATE_PROFILE_PHOTO') {
            if ($data['field_type'] !== 'IMAGE') throw ValidationException::withMessages(['field_type'=>'Candidate Profile Photo must use Image input.']);
        } elseif (in_array($data['field_type'],['SELECT','RADIO','CHECKBOX','MULTISELECT'],true) && blank($data['options']??null)) {
            throw ValidationException::withMessages(['options'=>'Add at least one option for this field type.']);
        }
        if (filled($data['system_purpose'] ?? null) && CollegeAdmissionFormField::query()->where('system_purpose',$data['system_purpose'])->whereHas('step',fn($q)=>$q->where('college_admission_form_template_id',$template->id))->exists()) throw ValidationException::withMessages(['system_purpose'=>'This system field is already configured in this University template.']);
        $scopeSelections = $this->assertUniversityFieldScopeSelections($template, $data);
        if (filled($data['college_admission_form_panel_id'] ?? null) && ! CollegeAdmissionFormPanel::query()->whereKey($data['college_admission_form_panel_id'])->where('college_admission_form_step_id',$step->id)->exists()) throw ValidationException::withMessages(['college_admission_form_panel_id'=>'Selected panel is outside this step.']);
        $sourceField = $this->conditionSource($template, $data['condition_source_field_id'] ?? null);
        if ($sourceField && in_array($sourceField->field_type, ['FILE','IMAGE'], true)) throw ValidationException::withMessages(['condition_source_field_id'=>'File/Image fields cannot be used as a condition source.']);
        if ($sourceField && ! in_array($data['condition_operator'] ?? 'EQUALS', ['IS_EMPTY','IS_NOT_EMPTY'], true) && blank($data['condition_values'] ?? null)) throw ValidationException::withMessages(['condition_values'=>'Enter the value that should make this field appear.']);
        $fieldRuleService->assertConfiguration($template, $step, $data['field_type'], $data);
        $field=DB::transaction(function()use($fieldRuleService,$step,$template,$data,$sourceField,$scopeSelections){
            $field=$step->fields()->create([
                'college_admission_form_panel_id'=>$data['college_admission_form_panel_id']??null,
                'field_key'=>$data['field_key'],'label'=>trim($data['label']),'field_type'=>$data['field_type'],'system_purpose'=>$data['system_purpose']??null,'placeholder'=>$data['placeholder']??null,'help_text'=>$data['help_text']??null,
                'is_required'=>(bool)($data['is_required']??false),'is_locked'=>true,'display_order'=>$data['display_order']??(($step->fields()->max('display_order')??0)+10),
                'validation_rules'=>$fieldRuleService->intrinsicRules($data, $data['field_type']),'status'=>'ACTIVE',
            ]);
            $options = ($data['system_purpose'] ?? null) === 'CANDIDATE_RESERVATION_CATEGORY'
                ? collect([['General / Unreserved','GENERAL']])->concat(
                    ReservationCategory::query()
                        ->where('university_id',$template->university_id)
                        ->where('status','ACTIVE')
                        ->where('nature','VERTICAL')
                        ->whereRaw("LOWER(TRIM(code)) NOT IN ('general','gen','open','unreserved','ur')")
                        ->orderBy('display_order')
                        ->orderBy('name')
                        ->get()
                        ->map(fn($c)=>[$c->name,$c->code])
                )->values()->all()
                : app(CollegeAdmissionFormOptionService::class)->build($data['field_type'], $data['options'] ?? null);
            foreach ($options as $i => [$label, $value]) {
                $field->options()->create(['label'=>$label,'value'=>$value,'display_order'=>($i+1)*10,'is_active'=>true]);
            }
            $this->syncAcademicScopes($field, $scopeSelections);
            if ($sourceField) $field->conditions()->create(['source_field_id'=>$sourceField->id,'operator'=>$data['condition_operator']??'EQUALS','compare_values'=>in_array($data['condition_operator']??'EQUALS',['IS_EMPTY','IS_NOT_EMPTY'],true)?[]:$this->splitValues($data['condition_values']??''),'display_order'=>10,'is_active'=>true]);
            $fieldRuleService->sync($field, $data);
            return $field->load(['options','conditions.sourceField','scopes','comparisonRule.sourceField','copyRule.sourceField','copyRule.triggerField']);
        });
        $this->audit($request, 'UNIVERSITY_ADMISSION_FORM_FIELD_CREATED', 'CollegeAdmissionFormField', $field->id, $field->toArray());
        return back()->with('toast',['type'=>'success','message'=>'University base field added and locked for College inheritance.']);
    }


    public function updateTemplate(Request $request, CollegeAdmissionFormTemplate $template): RedirectResponse
    {
        $this->authorizeUniversity($request, 'college_admission_form.update');
        $this->assertUniversityTemplate($template);
        abort_if($template->status === 'RETIRED', 422, 'A retired template cannot be edited.');
        $data=$request->validate(['name'=>['required','string','max:160'],'code'=>['required','string','max:60'],'admission_mode'=>['required',Rule::in(['REGULAR','DIRECT','BOTH'])],'manager_user_id'=>['nullable','integer','exists:users,id'],'description'=>['nullable','string','max:3000']]);
        $code=Str::upper(trim($data['code']));
        if (CollegeAdmissionFormTemplate::query()->where('university_id',$template->university_id)->whereNull('college_id')->where('code',$code)->whereKeyNot($template->id)->exists()) throw ValidationException::withMessages(['code'=>'This University template code already exists.']);
        $before=$template->toArray();
        $template->update(['name'=>trim($data['name']),'code'=>$code,'admission_mode'=>$data['admission_mode'],'manager_user_id'=>$data['manager_user_id']??null,'description'=>$data['description']??null,'updated_by'=>$request->user()->id]);
        $this->audit($request,'UNIVERSITY_ADMISSION_FORM_TEMPLATE_UPDATED','CollegeAdmissionFormTemplate',$template->id,['before'=>$before,'after'=>$template->fresh()->toArray()]);
        return back()->with('toast',['type'=>'success','message'=>'University Admission Form template updated.']);
    }

    public function destroyTemplate(Request $request, CollegeAdmissionFormTemplate $template): RedirectResponse
    {
        $this->authorizeUniversity($request, 'college_admission_form.delete'); $this->assertUniversityTemplate($template);
        if ($template->status !== 'DRAFT') throw ValidationException::withMessages(['template'=>'Only DRAFT templates can be deleted. Retire an activated template instead.']);
        if ($template->mappings()->exists() || $template->children()->exists() || DB::table('college_admission_applications')->where('college_admission_form_template_id',$template->id)->exists()) throw ValidationException::withMessages(['template'=>'This template is already linked. Remove mappings/dependencies first; historical applications cannot be broken.']);
        $id=$template->id; $template->delete(); $this->audit($request,'UNIVERSITY_ADMISSION_FORM_TEMPLATE_DELETED','CollegeAdmissionFormTemplate',$id,['deleted'=>true]);
        return back()->with('toast',['type'=>'success','message'=>'Draft template deleted.']);
    }

    public function updateStep(Request $request, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step): RedirectResponse
    {
        $this->authorizeUniversity($request,'college_admission_form.step_update'); $this->assertUniversityTemplate($template); abort_unless($step->college_admission_form_template_id===$template->id,404); $this->assertDraftStructure($template);
        $data=$request->validate(['title'=>['required','string','max:140'],'code'=>['required','string','max:60'],'description'=>['nullable','string','max:2000'],'display_order'=>['nullable','integer','min:0','max:9999']]); $code=Str::upper(trim($data['code']));
        if($template->steps()->where('code',$code)->whereKeyNot($step->id)->exists()) throw ValidationException::withMessages(['code'=>'This step code already exists.']);
        $step->update(['title'=>trim($data['title']),'code'=>$code,'description'=>$data['description']??null,'display_order'=>$data['display_order']??$step->display_order]);
        return back()->with('toast',['type'=>'success','message'=>'Step updated.']);
    }

    public function destroyStep(Request $request, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step): RedirectResponse
    {
        $this->authorizeUniversity($request,'college_admission_form.step_delete');
        $this->assertUniversityTemplate($template);
        abort_unless($step->college_admission_form_template_id===$template->id,404);
        $this->assertDraftStructure($template);
        $this->deleteDraftStepSafely($step);
        return back()->with('toast',['type'=>'success','message'=>'Step deleted.']);
    }

    public function storePanel(Request $request, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step): RedirectResponse
    {
        $this->authorizeUniversity($request,'college_admission_form.panel_create'); $this->assertUniversityTemplate($template); abort_unless($step->college_admission_form_template_id===$template->id,404); $this->assertDraftStructure($template);
        $data=$request->validate(['title'=>['required','string','max:160'],'code'=>['required','string','max:80'],'description'=>['nullable','string','max:2000'],'display_order'=>['nullable','integer','min:0','max:9999']]); $code=Str::upper(trim($data['code']));
        if($step->panels()->where('code',$code)->exists()) throw ValidationException::withMessages(['code'=>'This panel code already exists in the step.']);
        $step->panels()->create(['title'=>trim($data['title']),'code'=>$code,'description'=>$data['description']??null,'display_order'=>$data['display_order']??(($step->panels()->max('display_order')??0)+10),'is_locked'=>true,'status'=>'ACTIVE']);
        return back()->with('toast',['type'=>'success','message'=>'Optional panel added. Fields may be placed inside it or directly in the step.']);
    }

    public function updatePanel(Request $request, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step, CollegeAdmissionFormPanel $panel): RedirectResponse
    {
        $this->authorizeUniversity($request,'college_admission_form.panel_update'); $this->assertUniversityTemplate($template); abort_unless($step->college_admission_form_template_id===$template->id && $panel->college_admission_form_step_id===$step->id,404); $this->assertDraftStructure($template);
        $data=$request->validate(['title'=>['required','string','max:160'],'code'=>['required','string','max:80'],'description'=>['nullable','string','max:2000'],'display_order'=>['nullable','integer','min:0','max:9999']]); $code=Str::upper(trim($data['code']));
        if($step->panels()->where('code',$code)->whereKeyNot($panel->id)->exists()) throw ValidationException::withMessages(['code'=>'This panel code already exists.']);
        $panel->update(['title'=>trim($data['title']),'code'=>$code,'description'=>$data['description']??null,'display_order'=>$data['display_order']??$panel->display_order]); return back()->with('toast',['type'=>'success','message'=>'Panel updated.']);
    }

    public function destroyPanel(Request $request, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step, CollegeAdmissionFormPanel $panel): RedirectResponse
    {
        $this->authorizeUniversity($request,'college_admission_form.panel_delete'); $this->assertUniversityTemplate($template); abort_unless($step->college_admission_form_template_id===$template->id && $panel->college_admission_form_step_id===$step->id,404); $this->assertDraftStructure($template);
        // Deleting a panel does not delete fields; they become direct step fields.
        $panel->fields()->update(['college_admission_form_panel_id'=>null]); $panel->delete(); return back()->with('toast',['type'=>'success','message'=>'Panel deleted. Its fields were kept directly under the step.']);
    }

    public function updateField(Request $request, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step, CollegeAdmissionFormField $field): RedirectResponse
    {
        $this->authorizeUniversity($request,'college_admission_form.field_update'); $this->assertUniversityTemplate($template); abort_unless($step->college_admission_form_template_id===$template->id && $field->college_admission_form_step_id===$step->id,404); $this->assertDraftStructure($template);
        $fieldRuleService = app(CollegeAdmissionFieldRuleService::class); $data=$request->validate([
            ...$fieldRuleService->requestRules(),
            'college_admission_form_panel_id'=>['nullable','integer','exists:college_admission_form_panels,id'],
            'label'=>['required','string','max:180'],'system_purpose'=>['nullable',Rule::in(['CANDIDATE_RESERVATION_CATEGORY','CANDIDATE_PROFILE_PHOTO'])],'placeholder'=>['nullable','string','max:220'],'help_text'=>['nullable','string','max:2000'],'is_required'=>['nullable','boolean'],'display_order'=>['nullable','integer','min:0','max:9999'],
            'condition_source_field_id'=>['nullable','integer','exists:college_admission_form_fields,id'],
            'condition_operator'=>['nullable',Rule::in(['EQUALS','NOT_EQUALS','IN','NOT_IN','CONTAINS','IS_EMPTY','IS_NOT_EMPTY'])],
            'condition_values'=>['nullable','string','max:5000'],
            'degree_level_ids'=>['nullable','array','max:50'],'degree_level_ids.*'=>['integer','distinct','exists:degree_levels,id'],
            'degree_ids'=>['nullable','array','max:50'],'degree_ids.*'=>['integer','distinct','exists:degrees,id'],
            'program_template_ids'=>['nullable','array','max:100'],'program_template_ids.*'=>['integer','distinct','exists:program_templates,id'],
            'curriculum_ids'=>['nullable','array','max:100'],'curriculum_ids.*'=>['integer','distinct','exists:curricula,id'],
        ]);
        if(filled($data['college_admission_form_panel_id']??null) && ! $step->panels()->whereKey($data['college_admission_form_panel_id'])->exists()) throw ValidationException::withMessages(['college_admission_form_panel_id'=>'Selected panel is outside this step.']);
        $purpose = $data['system_purpose'] ?? null;
        if ($purpose === 'CANDIDATE_RESERVATION_CATEGORY' && $field->field_type !== 'SELECT') throw ValidationException::withMessages(['system_purpose'=>'Candidate Reservation Category can only be assigned to a Dropdown field.']);
        if ($purpose === 'CANDIDATE_PROFILE_PHOTO' && $field->field_type !== 'IMAGE') throw ValidationException::withMessages(['system_purpose'=>'Candidate Profile Photo can only be assigned to an Image field.']);
        if (filled($purpose) && CollegeAdmissionFormField::query()->where('system_purpose',$purpose)->whereKeyNot($field->id)->whereHas('step',fn($q)=>$q->where('college_admission_form_template_id',$template->id))->exists()) throw ValidationException::withMessages(['system_purpose'=>'This system field is already configured in this University template.']);
        $scopeSelections = $this->assertUniversityFieldScopeSelections($template, $data);
        $scopeProvided = $request->hasAny(['degree_level_ids','degree_ids','program_template_ids','curriculum_ids']);
        $sourceField = $this->conditionSource($template, $data['condition_source_field_id'] ?? null);
        $this->assertConditionConfiguration($field, $sourceField, $data);
        $fieldRuleService->assertConfiguration($template, $step, $field->field_type, $data, $field);
        DB::transaction(function () use ($field, $fieldRuleService, $data, $sourceField, $scopeSelections, $scopeProvided) {
            $field->update(['college_admission_form_panel_id'=>$data['college_admission_form_panel_id']??null,'label'=>trim($data['label']),'system_purpose'=>$data['system_purpose']??null,'placeholder'=>$data['placeholder']??null,'help_text'=>$data['help_text']??null,'is_required'=>(bool)($data['is_required']??false),'display_order'=>$data['display_order']??$field->display_order,'validation_rules'=>$fieldRuleService->intrinsicRules($data, $field->field_type, $field->validation_rules??[])]);
            $fieldRuleService->sync($field,$data);
            if ($scopeProvided) $this->syncAcademicScopes($field, $scopeSelections);
            $this->syncCondition($field, $sourceField, $data);
        });
        return back()->with('toast',['type'=>'success','message'=>'Field and answer-based condition updated.']);
    }

    public function destroyField(Request $request, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step, CollegeAdmissionFormField $field): RedirectResponse
    {
        $this->authorizeUniversity($request,'college_admission_form.field_delete'); $this->assertUniversityTemplate($template); abort_unless($step->college_admission_form_template_id===$template->id && $field->college_admission_form_step_id===$step->id,404); $this->assertDraftStructure($template);
        if(DB::table('college_admission_application_field_values')->where('college_admission_form_field_id',$field->id)->exists() || DB::table('college_admission_form_field_conditions')->where('source_field_id',$field->id)->exists()||DB::table('college_admission_form_field_comparisons')->where('source_field_id',$field->id)->exists()||DB::table('college_admission_form_field_copy_rules')->where('source_field_id',$field->id)->orWhere('trigger_field_id',$field->id)->exists()) throw ValidationException::withMessages(['field'=>'This field is already used by an application or another field condition. Remove the dependency first.']);
        $field->delete(); return back()->with('toast',['type'=>'success','message'=>'Field deleted.']);
    }


    private function deleteDraftStepSafely(CollegeAdmissionFormStep $step): void
    {
        $fieldIds = $step->fields()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($fieldIds === []) {
            $step->delete();
            return;
        }

        if (DB::table('college_admission_application_field_values')->whereIn('college_admission_form_field_id', $fieldIds)->exists()) {
            throw ValidationException::withMessages(['step' => 'This step already contains submitted application values and cannot be deleted.']);
        }

        $usedByExternalCondition = DB::table('college_admission_form_field_conditions')
            ->whereIn('source_field_id', $fieldIds)
            ->whereNotIn('college_admission_form_field_id', $fieldIds)
            ->exists();
        $usedByExternalComparison = DB::table('college_admission_form_field_comparisons')
            ->whereIn('source_field_id', $fieldIds)
            ->whereNotIn('target_field_id', $fieldIds)
            ->exists();
        $usedByExternalCopy = DB::table('college_admission_form_field_copy_rules')
            ->whereNotIn('target_field_id', $fieldIds)
            ->where(function ($query) use ($fieldIds) {
                $query->whereIn('source_field_id', $fieldIds)->orWhereIn('trigger_field_id', $fieldIds);
            })
            ->exists();

        if ($usedByExternalCondition || $usedByExternalComparison || $usedByExternalCopy) {
            throw ValidationException::withMessages([
                'step' => 'A field in this step is used by another field condition, validation comparison, or copy rule. Remove that dependency first.',
            ]);
        }

        DB::transaction(function () use ($step, $fieldIds) {
            // Source-field FKs are RESTRICT. Remove rules owned by fields inside this step first;
            // external references were blocked above, so the remaining delete is safe.
            DB::table('college_admission_form_field_conditions')->whereIn('college_admission_form_field_id', $fieldIds)->delete();
            DB::table('college_admission_form_field_comparisons')->whereIn('target_field_id', $fieldIds)->delete();
            DB::table('college_admission_form_field_copy_rules')->whereIn('target_field_id', $fieldIds)->delete();
            $step->delete();
        });
    }

    private function assertDraftStructure(CollegeAdmissionFormTemplate $template): void
    {
        if($template->status !== 'DRAFT') throw ValidationException::withMessages(['template'=>'Structural edit/delete is allowed only while the template is DRAFT. Activate only after the form structure is final.']);
    }

    public function storeFeeRule(Request $request): RedirectResponse
    {
        $this->authorizeUniversity($request, 'college_application_fee.manage');
        $university=University::query()->firstOrFail();
        $data=$request->validate(['name'=>['required','string','max:160'],'fee_required'=>['required','boolean'],'amount'=>['required','numeric','min:0','max:999999999.99'],'currency'=>['required','string','size:3']]);
        CollegeApplicationFeeRule::query()->where('university_id',$university->id)->whereNull('college_id')->whereNull('degree_level_id')->whereNull('degree_id')->whereNull('program_template_id')->whereNull('college_program_offering_id')->whereNull('college_admission_cycle_id')->update(['status'=>'INACTIVE','updated_by'=>$request->user()->id]);
        $rule=CollegeApplicationFeeRule::create(['university_id'=>$university->id,'college_id'=>null,'degree_level_id'=>null,'degree_id'=>null,'program_template_id'=>null,'college_program_offering_id'=>null,'college_admission_cycle_id'=>null,'name'=>$data['name'],'fee_required'=>(bool)$data['fee_required'],'amount'=>$data['fee_required']?$data['amount']:0,'currency'=>strtoupper($data['currency']),'status'=>'ACTIVE','created_by'=>$request->user()->id,'updated_by'=>$request->user()->id]);
        $this->audit($request, 'UNIVERSITY_APPLICATION_FEE_RULE_CREATED', 'CollegeApplicationFeeRule', $rule->id, $rule->toArray());
        return back()->with('toast',['type'=>'success','message'=>'University default application fee updated.']);
    }


    private function conditionSource(CollegeAdmissionFormTemplate $template, mixed $fieldId): ?CollegeAdmissionFormField
    {
        if (! filled($fieldId)) return null;
        $field = CollegeAdmissionFormField::query()->whereKey($fieldId)->whereHas('step', fn($q)=>$q->where('college_admission_form_template_id',$template->id))->first();
        if (! $field) throw ValidationException::withMessages(['condition_source_field_id'=>'Condition source must be another field in this University template.']);
        return $field;
    }

    private function assertConditionConfiguration(CollegeAdmissionFormField $target, ?CollegeAdmissionFormField $source, array $data): void
    {
        if (! $source) return;
        if (in_array($source->field_type, ['FILE','IMAGE'], true)) throw ValidationException::withMessages(['condition_source_field_id'=>'File/Image fields cannot be used as a condition source.']);
        if ((int) $source->id === (int) $target->id) throw ValidationException::withMessages(['condition_source_field_id'=>'A field cannot depend on itself.']);
        $operator = $data['condition_operator'] ?? 'EQUALS';
        if (! in_array($operator, ['IS_EMPTY','IS_NOT_EMPTY'], true) && blank($data['condition_values'] ?? null)) throw ValidationException::withMessages(['condition_values'=>'Enter the value that should make this field appear.']);
        $this->assertNoConditionCycle($target, $source);
    }

    private function assertNoConditionCycle(CollegeAdmissionFormField $target, CollegeAdmissionFormField $source): void
    {
        $visited = [];
        $stack = [(int) $source->id];
        while ($stack) {
            $fieldId = array_pop($stack);
            if ($fieldId === (int) $target->id) throw ValidationException::withMessages(['condition_source_field_id'=>'This condition would create a circular field dependency. Choose another source field.']);
            if (isset($visited[$fieldId])) continue;
            $visited[$fieldId] = true;
            $parents = DB::table('college_admission_form_field_conditions')
                ->where('college_admission_form_field_id', $fieldId)
                ->where('is_active', true)
                ->pluck('source_field_id');
            foreach ($parents as $parentId) $stack[] = (int) $parentId;
        }
    }

    private function syncCondition(CollegeAdmissionFormField $field, ?CollegeAdmissionFormField $source, array $data): void
    {
        $field->conditions()->delete();
        if (! $source) return;
        $operator = $data['condition_operator'] ?? 'EQUALS';
        $values = in_array($operator, ['IS_EMPTY','IS_NOT_EMPTY'], true)
            ? []
            : $this->splitValues($data['condition_values'] ?? '');
        $field->conditions()->create([
            'source_field_id'=>$source->id,
            'operator'=>$operator,
            'compare_values'=>$values,
            'display_order'=>10,
            'is_active'=>true,
        ]);
    }

    private function assertUniversityFieldScopeSelections(CollegeAdmissionFormTemplate $template, array $data): array
    {
        $uid = $template->university_id;
        $selections = [
            'degree_level_id' => collect($data['degree_level_ids'] ?? [])->map(fn($v)=>(int)$v)->unique()->values()->all(),
            'degree_id' => collect($data['degree_ids'] ?? [])->map(fn($v)=>(int)$v)->unique()->values()->all(),
            'program_template_id' => collect($data['program_template_ids'] ?? [])->map(fn($v)=>(int)$v)->unique()->values()->all(),
            'curriculum_id' => collect($data['curriculum_ids'] ?? [])->map(fn($v)=>(int)$v)->unique()->values()->all(),
        ];

        $levels = DegreeLevel::query()->where('university_id',$uid)->whereIn('id',$selections['degree_level_id'])->get()->keyBy('id');
        $degrees = Degree::query()->where('university_id',$uid)->whereIn('id',$selections['degree_id'])->get()->keyBy('id');
        $programs = ProgramTemplate::query()->where('university_id',$uid)->whereIn('id',$selections['program_template_id'])->get()->keyBy('id');
        $curricula = Curriculum::query()->currentApproved()->where('university_id',$uid)->whereIn('id',$selections['curriculum_id'])->get()->keyBy('id');

        if ($levels->count() !== count($selections['degree_level_id'])) throw ValidationException::withMessages(['degree_level_ids'=>'One or more Degree Levels are outside this University.']);
        if ($degrees->count() !== count($selections['degree_id'])) throw ValidationException::withMessages(['degree_ids'=>'One or more Degrees are outside this University.']);
        if ($programs->count() !== count($selections['program_template_id'])) throw ValidationException::withMessages(['program_template_ids'=>'One or more Programs are outside this University.']);
        if ($curricula->count() !== count($selections['curriculum_id'])) throw ValidationException::withMessages(['curriculum_ids'=>'Use only current approved ACTIVE Curriculum versions.']);

        if ($selections['degree_level_id'] && $degrees->contains(fn($degree)=>!in_array((int)$degree->degree_level_id,$selections['degree_level_id'],true))) throw ValidationException::withMessages(['degree_ids'=>'Every selected Degree must belong to one of the selected Degree Levels.']);
        if ($selections['degree_id'] && $programs->contains(fn($program)=>!in_array((int)$program->degree_id,$selections['degree_id'],true))) throw ValidationException::withMessages(['program_template_ids'=>'Every selected Program must belong to one of the selected Degrees.']);
        if ($selections['program_template_id'] && $curricula->contains(fn($curriculum)=>!in_array((int)$curriculum->program_template_id,$selections['program_template_id'],true))) throw ValidationException::withMessages(['curriculum_ids'=>'Every selected Curriculum must belong to one of the selected Programs.']);

        $combinationCount = collect($selections)->reduce(fn($count,$values)=>$count * max(count($values),1),1);
        if ($combinationCount > 500) throw ValidationException::withMessages(['academic_applicability'=>'Too many Academic Applicability combinations. Narrow the selection to 500 combinations or fewer.']);
        return $selections;
    }

    private function syncAcademicScopes(CollegeAdmissionFormField $field, array $selections): void
    {
        $field->scopes()->delete();
        if (collect($selections)->every(fn($values)=>count($values)===0)) return;
        $rows = [[]];
        foreach ($selections as $key => $selectedValues) {
            $values = $selectedValues ?: [null];
            $next = [];
            foreach ($rows as $row) foreach ($values as $value) $next[] = [...$row, $key=>$value];
            $rows = $next;
        }
        foreach ($rows as $row) $field->scopes()->create([...$row,'is_active'=>true]);
    }

    private function splitValues(string $raw): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $raw) ?: []), fn($v)=>$v!==''));
    }

    private function authorizeUniversity(Request $request, string $permission): void { abort_unless($request->user()?->hasPermission($permission), 403); }
    private function assertUniversityTemplate(CollegeAdmissionFormTemplate $template): void { $university=University::query()->firstOrFail(); abort_unless($template->university_id===$university->id && $template->college_id===null && $template->owner_scope_type==='UNIVERSITY',404); }
    private function audit(Request $request,string $event,string $resourceType,int $resourceId,array $after):void{DB::table('audit_logs')->insert(['actor_user_id'=>$request->user()->id,'event'=>$event,'resource_type'=>$resourceType,'resource_id'=>$resourceId,'scope_type'=>'UNIVERSITY','scope_reference'=>'university','before'=>null,'after'=>json_encode($after),'ip_address'=>$request->ip(),'created_at'=>now()]);}
}
