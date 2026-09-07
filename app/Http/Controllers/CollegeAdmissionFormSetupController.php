<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\CollegeAdmissionCycle;
use App\Models\CollegeAdmissionFormField;
use App\Models\CollegeAdmissionFormPanel;
use App\Models\CollegeAdmissionFormMapping;
use App\Models\CollegeAdmissionFormStep;
use App\Models\CollegeAdmissionFormTemplate;
use App\Models\CollegeApplicationFeeRule;
use App\Models\CollegeApplicantRegistrationSetting;
use App\Models\Curriculum;
use App\Models\Degree;
use App\Models\DegreeLevel;
use App\Models\ProgramTemplate;
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

class CollegeAdmissionFormSetupController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->authorizeAny($request, $college, 'college_admission_form.view');
        $templates = CollegeAdmissionFormTemplate::query()
            ->with(['manager:id,name,email', 'parent.steps.fields.options', 'parent.steps.fields.conditions.sourceField:id,label,field_key,field_type', 'parent.steps.fields.scopes', 'parent.steps.fields.comparisonRule.sourceField:id,label,field_key,field_type', 'parent.steps.fields.copyRule.sourceField:id,label,field_key,field_type', 'parent.steps.fields.copyRule.triggerField:id,label,field_key,field_type', 'steps.panels', 'steps.fields.panel:id,title,code', 'steps.fields.options', 'steps.fields.conditions.sourceField:id,label,field_key,field_type', 'steps.fields.scopes', 'steps.fields.comparisonRule.sourceField:id,label,field_key,field_type', 'steps.fields.copyRule.sourceField:id,label,field_key,field_type', 'steps.fields.copyRule.triggerField:id,label,field_key,field_type', 'mappings' => fn ($q) => $q->where('status', 'ACTIVE')->where('college_id', $college->id)])
            ->where('university_id', $college->university_id)
            ->where(fn ($q) => $q->whereNull('college_id')->orWhere('college_id', $college->id))
            ->orderByRaw("FIELD(owner_scope_type, 'UNIVERSITY','COLLEGE')")
            ->orderBy('name')->get();

        $offerings = $college->programOfferings()->with(['programTemplate.degree.degreeLevel','academicSession:id,name,code'])->orderByDesc('id')->get();
        $cycles = CollegeAdmissionCycle::query()->with(['programOffering.programTemplate.degree.degreeLevel','academicSession:id,name,code'])->where('college_id', $college->id)->orderByDesc('id')->get();
        $users = User::query()
            ->where('status', 'ACTIVE')
            ->whereHas('roles', function ($q) use ($college) {
                $q->where('roles.status', 'ACTIVE')
                    ->where('user_roles.status', 'ACTIVE')
                    ->where('user_roles.scope_type', 'COLLEGE')
                    ->where('user_roles.scope_reference', "college:{$college->id}")
                    ->where(fn ($roleQuery) => $roleQuery->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from', '<=', now()))
                    ->where(fn ($roleQuery) => $roleQuery->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until', '>=', now()));
            })
            ->with(['roles' => function ($q) use ($college) {
                $q->where('roles.status', 'ACTIVE')
                    ->where('user_roles.status', 'ACTIVE')
                    ->where('user_roles.scope_type', 'COLLEGE')
                    ->where('user_roles.scope_reference', "college:{$college->id}")
                    ->where(fn ($roleQuery) => $roleQuery->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from', '<=', now()))
                    ->where(fn ($roleQuery) => $roleQuery->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until', '>=', now()));
            }])
            ->orderBy('name')
            ->get(['id','name','email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values()->all(),
            ])
            ->values();
        $degreeLevels = DegreeLevel::query()->where('university_id',$college->university_id)->where('status','ACTIVE')->orderBy('display_order')->get(['id','name','code']);
        $degrees = Degree::query()->where('university_id',$college->university_id)->where('status','ACTIVE')->with('degreeLevel:id,name,code')->orderBy('display_order')->get(['id','degree_level_id','name','code']);
        $programs = ProgramTemplate::query()->where('university_id',$college->university_id)->where('status','ACTIVE')->with('degree:id,name,code,degree_level_id')->orderBy('display_order')->get(['id','degree_id','name','code']);
        $curricula = Curriculum::query()->currentApproved()->where('university_id',$college->university_id)->orderBy('name')->get(['id','program_template_id','academic_session_id','name','code','version']);
        $feeRules = CollegeApplicationFeeRule::query()->where('university_id',$college->university_id)->where(fn($q)=>$q->whereNull('college_id')->orWhere('college_id',$college->id))->orderByDesc('id')->get();

        return Inertia::render('college-admission-form-setup/index', [
            'college' => $college->only(['id','name','code','status','university_id']),
            'templates' => $templates,
            'users' => $users,
            'degreeLevels' => $degreeLevels,
            'degrees' => $degrees,
            'programs' => $programs,
            'offerings' => $offerings,
            'cycles' => $cycles,
            'curricula' => $curricula,
            'feeRules' => $feeRules,
            'registrationSettings' => CollegeApplicantRegistrationSetting::forCollege($college->id),
            'can' => [
                'create' => $this->canAny($request, $college, 'college_admission_form.create'),
                'update' => $this->canAny($request, $college, 'college_admission_form.update'),
                'status' => $this->canAny($request, $college, 'college_admission_form.status'),
                'stepCreate' => $this->canAny($request, $college, 'college_admission_form.step_create'),
                'fieldCreate' => $this->canAny($request, $college, 'college_admission_form.field_create'),
                'delete' => $this->canAny($request,$college,'college_admission_form.delete'),
                'stepUpdate' => $this->canAny($request,$college,'college_admission_form.step_update'),
                'stepDelete' => $this->canAny($request,$college,'college_admission_form.step_delete'),
                'panelCreate' => $this->canAny($request,$college,'college_admission_form.panel_create'),
                'panelUpdate' => $this->canAny($request,$college,'college_admission_form.panel_update'),
                'panelDelete' => $this->canAny($request,$college,'college_admission_form.panel_delete'),
                'fieldUpdate' => $this->canAny($request,$college,'college_admission_form.field_update'),
                'fieldDelete' => $this->canAny($request,$college,'college_admission_form.field_delete'),
                'map' => $this->canAny($request, $college, 'college_admission_form.map'),
                'fee' => $this->canAny($request, $college, 'college_application_fee.manage'),
                'registrationSettings' => $this->canAny($request, $college, 'college_applicant_registration.settings'),
                'universityManage' => false,
            ],
        ]);
    }

    public function storeTemplate(Request $request, College $college): RedirectResponse
    {
        $this->authorizeAny($request, $college, 'college_admission_form.create');
        $data = $request->validate([
            'name'=>['required','string','max:160'], 'code'=>['required','string','max:60'],
            'admission_mode'=>['required', Rule::in(['REGULAR','DIRECT','BOTH'])],
            'manager_user_id'=>['nullable','integer','exists:users,id'],
            'parent_template_id'=>['required','integer','exists:college_admission_form_templates,id'],
            'description'=>['nullable','string','max:3000'],
        ]);

        $parent = CollegeAdmissionFormTemplate::query()
            ->whereKey($data['parent_template_id'])
            ->where('university_id', $college->university_id)
            ->whereNull('college_id')
            ->where('owner_scope_type', 'UNIVERSITY')
            ->where('status', 'ACTIVE')
            ->where('allow_college_override', true)
            ->first();

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_template_id'=>'Choose an ACTIVE University base template where Allow College override is enabled.',
            ]);
        }

        if (filled($data['manager_user_id'] ?? null) && ! $this->isEligibleCollegeManager((int) $data['manager_user_id'], $college)) {
            throw ValidationException::withMessages([
                'manager_user_id' => 'Assigned manager must be an ACTIVE user with an ACTIVE role in this College scope.',
            ]);
        }

        $code = Str::upper(trim($data['code']));
        if (CollegeAdmissionFormTemplate::query()->where('university_id',$college->university_id)->where('college_id',$college->id)->where('code',$code)->exists()) {
            throw ValidationException::withMessages(['code'=>'This template code already exists in this College.']);
        }

        $template = CollegeAdmissionFormTemplate::create([
            'university_id'=>$college->university_id, 'college_id'=>$college->id, 'parent_template_id'=>$parent->id,
            'manager_user_id'=>$data['manager_user_id']??null, 'name'=>trim($data['name']), 'code'=>$code,
            'owner_scope_type'=>'COLLEGE', 'governance_mode'=>'UNIVERSITY_BASE_COLLEGE_EXTENSION',
            'allow_college_override'=>false, 'admission_mode'=>$data['admission_mode'], 'status'=>'DRAFT',
            'description'=>$data['description']??null, 'created_by'=>$request->user()->id, 'updated_by'=>$request->user()->id,
        ]);
        $this->audit($request,$college,'COLLEGE_ADMISSION_FORM_TEMPLATE_CREATED','CollegeAdmissionFormTemplate',$template->id,$template->toArray());
        return back()->with('toast',['type'=>'success','message'=>'College extension created from the University base form.']);
    }

    public function statusTemplate(Request $request, College $college, CollegeAdmissionFormTemplate $template): RedirectResponse
    {
        $this->assertTemplateScope($template,$college); $this->authorizeAny($request,$college,'college_admission_form.status');
        if ($template->owner_scope_type==='UNIVERSITY' && ! $request->user()->hasPermission('college_admission_form.status')) abort(403);
        $status=$request->validate(['status'=>['required',Rule::in(['DRAFT','ACTIVE','RETIRED'])]])['status'];
        $ownUsable = $template->steps()->where('status','ACTIVE')->whereHas('fields',fn($q)=>$q->where('status','ACTIVE'))->exists();
        $parentUsable = $template->parent_template_id && CollegeAdmissionFormTemplate::query()->whereKey($template->parent_template_id)->where('status','ACTIVE')->where('allow_college_override', true)->whereHas('steps',fn($q)=>$q->where('status','ACTIVE')->whereHas('fields',fn($f)=>$f->where('status','ACTIVE')))->exists();
        if ($status==='ACTIVE' && ! $parentUsable) throw ValidationException::withMessages(['status'=>'This College extension requires an ACTIVE University base template with Allow College override enabled.']);
        if ($status==='RETIRED' && CollegeAdmissionFormTemplate::query()->where('parent_template_id',$template->id)->where('status','ACTIVE')->exists()) throw ValidationException::withMessages(['status'=>'Retire or remap ACTIVE College extension templates before retiring this University base template.']);
        $template->update(['status'=>$status,'updated_by'=>$request->user()->id]);
        $this->audit($request,$college,'COLLEGE_ADMISSION_FORM_TEMPLATE_STATUS_CHANGED','CollegeAdmissionFormTemplate',$template->id,$template->fresh()->toArray(),$template->owner_scope_type==='UNIVERSITY');
        return back()->with('toast',['type'=>'success','message'=>'Template status updated.']);
    }

    public function storeStep(Request $request, College $college, CollegeAdmissionFormTemplate $template): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.step_create');
        $data=$request->validate(['title'=>['required','string','max:140'],'code'=>['required','string','max:60'],'description'=>['nullable','string','max:2000'],'display_order'=>['nullable','integer','min:0','max:999']]);
        if ($template->steps()->where('code',Str::upper(trim($data['code'])))->exists()) throw ValidationException::withMessages(['code'=>'This step code already exists in the template.']);
        $step = $template->steps()->create(['title'=>trim($data['title']),'code'=>Str::upper(trim($data['code'])),'description'=>$data['description']??null,'display_order'=>$data['display_order']??($template->steps()->max('display_order')+10),'status'=>'ACTIVE']);
        $this->audit($request,$college,'COLLEGE_ADMISSION_FORM_STEP_CREATED','CollegeAdmissionFormStep',$step->id,$step->toArray(),$template->owner_scope_type==='UNIVERSITY');
        return back()->with('toast',['type'=>'success','message'=>'Form step added.']);
    }

    public function storeField(Request $request, College $college, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.field_create'); abort_unless($step->college_admission_form_template_id===$template->id,404);
        $fieldRuleService = app(CollegeAdmissionFieldRuleService::class);
        $data=$request->validate([
            ...$fieldRuleService->requestRules(),
            'college_admission_form_panel_id'=>['nullable','integer','exists:college_admission_form_panels,id'],
            'label'=>['required','string','max:180'],'field_key'=>['required','string','max:100','regex:/^[a-z][a-z0-9_]*$/'],
            'field_type'=>['required',Rule::in(['TEXT','NUMBER','DATE','EMAIL','PHONE','TEXTAREA','SELECT','RADIO','CHECKBOX','MULTISELECT','FILE','IMAGE','YES_NO'])],
            'placeholder'=>['nullable','string','max:220'],'help_text'=>['nullable','string','max:2000'],'is_required'=>['nullable','boolean'],'display_order'=>['nullable','integer','min:0','max:9999'],
            'options'=>['nullable','string','max:5000'],'max_kb'=>['nullable','integer','min:1','max:51200'],'extensions'=>['nullable','string','max:500'],
            'condition_source_field_id'=>['nullable','integer','exists:college_admission_form_fields,id'],
            'condition_operator'=>['nullable',Rule::in(['EQUALS','NOT_EQUALS','IN','NOT_IN','CONTAINS','IS_EMPTY','IS_NOT_EMPTY'])],
            'condition_values'=>['nullable','string','max:5000'],
            'degree_level_id'=>['nullable','integer','exists:degree_levels,id'],'degree_id'=>['nullable','integer','exists:degrees,id'],
            'program_template_id'=>['nullable','integer','exists:program_templates,id'],'college_program_offering_id'=>['nullable','integer','exists:college_program_offerings,id'],
            'curriculum_id'=>['nullable','integer','exists:curricula,id'],'college_admission_cycle_id'=>['nullable','integer','exists:college_admission_cycles,id'],
        ]);
        if ($step->fields()->where('field_key',$data['field_key'])->exists()) throw ValidationException::withMessages(['field_key'=>'This field key already exists in the step.']);
        if (in_array($data['field_type'],['SELECT','RADIO','CHECKBOX','MULTISELECT'],true) && blank($data['options']??null)) throw ValidationException::withMessages(['options'=>'Add at least one option for this field type.']);
        $this->assertAcademicScopeIds($college,$data);
        if (filled($data['college_admission_form_panel_id'] ?? null) && ! CollegeAdmissionFormPanel::query()->whereKey($data['college_admission_form_panel_id'])->where('college_admission_form_step_id',$step->id)->exists()) throw ValidationException::withMessages(['college_admission_form_panel_id'=>'Selected panel is outside this step.']);
        $sourceField = $this->conditionSourceForTemplate($template, $data['condition_source_field_id'] ?? null);
        if ($sourceField && in_array($sourceField->field_type,['FILE','IMAGE'],true)) throw ValidationException::withMessages(['condition_source_field_id'=>'File/Image fields cannot be used as a condition source.']);
        if ($sourceField && ! in_array($data['condition_operator']??'EQUALS',['IS_EMPTY','IS_NOT_EMPTY'],true) && blank($data['condition_values']??null)) throw ValidationException::withMessages(['condition_values'=>'Enter the value that should make this field appear.']);
        $field = DB::transaction(function() use($fieldRuleService,$step,$data,$sourceField){
            $field=$step->fields()->create([
                'college_admission_form_panel_id'=>$data['college_admission_form_panel_id']??null,
                'field_key'=>$data['field_key'],'label'=>trim($data['label']),'field_type'=>$data['field_type'],'placeholder'=>$data['placeholder']??null,'help_text'=>$data['help_text']??null,
                'is_required'=>(bool)($data['is_required']??false),'display_order'=>$data['display_order']??(($step->fields()->max('display_order')??0)+10),
                'validation_rules'=>$fieldRuleService->intrinsicRules($data, $data['field_type']),
                'status'=>'ACTIVE',
            ]);
            $options = app(CollegeAdmissionFormOptionService::class)->build($data['field_type'], $data['options'] ?? null);
            foreach ($options as $i => [$label, $value]) {
                $field->options()->create(['label'=>$label,'value'=>$value,'display_order'=>($i+1)*10,'is_active'=>true]);
            }
            $scope = collect(['degree_level_id','degree_id','program_template_id','college_program_offering_id','curriculum_id','college_admission_cycle_id'])->mapWithKeys(fn($key)=>[$key=>$data[$key]??null])->all();
            if (collect($scope)->filter(fn($v)=>filled($v))->isNotEmpty()) $field->scopes()->create([...$scope,'is_active'=>true]);
            if ($sourceField) $field->conditions()->create(['source_field_id'=>$sourceField->id,'operator'=>$data['condition_operator']??'EQUALS','compare_values'=>in_array($data['condition_operator']??'EQUALS',['IS_EMPTY','IS_NOT_EMPTY'],true)?[]:$this->canonicalConditionValues($sourceField, $this->splitValues($data['condition_values']??'')),'display_order'=>10,'is_active'=>true]);
            $fieldRuleService->sync($field, $data);
            return $field->load(['options','conditions.sourceField','scopes','comparisonRule.sourceField','copyRule.sourceField','copyRule.triggerField']);
        });
        $this->audit($request,$college,'COLLEGE_ADMISSION_FORM_FIELD_CREATED','CollegeAdmissionFormField',$field->id,$field->toArray(),$template->owner_scope_type==='UNIVERSITY');
        return back()->with('toast',['type'=>'success','message'=>'Dynamic form field added. It will render using the ERP theme components.']);
    }


    public function updateTemplate(Request $request, College $college, CollegeAdmissionFormTemplate $template): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.update');
        abort_if($template->owner_scope_type!=='COLLEGE',403,'University base templates are edited only at University scope.');
        $data=$request->validate(['name'=>['required','string','max:160'],'code'=>['required','string','max:60'],'admission_mode'=>['required',Rule::in(['REGULAR','DIRECT','BOTH'])],'manager_user_id'=>['nullable','integer','exists:users,id'],'description'=>['nullable','string','max:3000']]);
        if (filled($data['manager_user_id'] ?? null) && ! $this->isEligibleCollegeManager((int) $data['manager_user_id'], $college)) {
            throw ValidationException::withMessages([
                'manager_user_id' => 'Assigned manager must be an ACTIVE user with an ACTIVE role in this College scope.',
            ]);
        }
        $code=Str::upper(trim($data['code'])); if(CollegeAdmissionFormTemplate::query()->where('college_id',$college->id)->where('code',$code)->whereKeyNot($template->id)->exists()) throw ValidationException::withMessages(['code'=>'This College template code already exists.']);
        $template->update(['name'=>trim($data['name']),'code'=>$code,'admission_mode'=>$data['admission_mode'],'manager_user_id'=>$data['manager_user_id']??null,'description'=>$data['description']??null,'updated_by'=>$request->user()->id]);
        return back()->with('toast',['type'=>'success','message'=>'College Admission Form template updated.']);
    }

    public function destroyTemplate(Request $request, College $college, CollegeAdmissionFormTemplate $template): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.delete'); abort_if($template->owner_scope_type!=='COLLEGE',403);
        if($template->status!=='DRAFT') throw ValidationException::withMessages(['template'=>'Only DRAFT College templates can be deleted.']);
        if($template->mappings()->exists()||DB::table('college_admission_applications')->where('college_admission_form_template_id',$template->id)->exists()) throw ValidationException::withMessages(['template'=>'This template is already linked. Remove mappings/dependencies first.']);
        $template->delete(); return back()->with('toast',['type'=>'success','message'=>'Draft College template deleted.']);
    }

    public function updateStep(Request $request, College $college, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.step_update'); abort_unless($step->college_admission_form_template_id===$template->id,404); $this->assertDraftStructure($template);
        $data=$request->validate(['title'=>['required','string','max:140'],'code'=>['required','string','max:60'],'description'=>['nullable','string','max:2000'],'display_order'=>['nullable','integer','min:0','max:9999']]); $code=Str::upper(trim($data['code']));
        if($template->steps()->where('code',$code)->whereKeyNot($step->id)->exists()) throw ValidationException::withMessages(['code'=>'This step code already exists.']);
        $step->update(['title'=>trim($data['title']),'code'=>$code,'description'=>$data['description']??null,'display_order'=>$data['display_order']??$step->display_order]); return back()->with('toast',['type'=>'success','message'=>'Step updated.']);
    }

    public function destroyStep(Request $request, College $college, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.step_delete');
        abort_unless($step->college_admission_form_template_id===$template->id,404);
        $this->assertDraftStructure($template);
        $this->deleteDraftStepSafely($step);
        return back()->with('toast',['type'=>'success','message'=>'Step deleted.']);
    }

    public function storePanel(Request $request, College $college, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.panel_create'); abort_unless($step->college_admission_form_template_id===$template->id,404); $this->assertDraftStructure($template);
        $data=$request->validate(['title'=>['required','string','max:160'],'code'=>['required','string','max:80'],'description'=>['nullable','string','max:2000'],'display_order'=>['nullable','integer','min:0','max:9999']]); $code=Str::upper(trim($data['code'])); if($step->panels()->where('code',$code)->exists()) throw ValidationException::withMessages(['code'=>'This panel code already exists.']);
        $step->panels()->create(['title'=>trim($data['title']),'code'=>$code,'description'=>$data['description']??null,'display_order'=>$data['display_order']??(($step->panels()->max('display_order')??0)+10),'is_locked'=>false,'status'=>'ACTIVE']); return back()->with('toast',['type'=>'success','message'=>'Optional panel added.']);
    }

    public function updatePanel(Request $request, College $college, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step, CollegeAdmissionFormPanel $panel): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.panel_update'); abort_unless($step->college_admission_form_template_id===$template->id&&$panel->college_admission_form_step_id===$step->id,404); $this->assertDraftStructure($template);
        $data=$request->validate(['title'=>['required','string','max:160'],'code'=>['required','string','max:80'],'description'=>['nullable','string','max:2000'],'display_order'=>['nullable','integer','min:0','max:9999']]); $panel->update(['title'=>trim($data['title']),'code'=>Str::upper(trim($data['code'])),'description'=>$data['description']??null,'display_order'=>$data['display_order']??$panel->display_order]); return back()->with('toast',['type'=>'success','message'=>'Panel updated.']);
    }

    public function destroyPanel(Request $request, College $college, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step, CollegeAdmissionFormPanel $panel): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.panel_delete'); abort_unless($step->college_admission_form_template_id===$template->id&&$panel->college_admission_form_step_id===$step->id,404); $this->assertDraftStructure($template); $panel->fields()->update(['college_admission_form_panel_id'=>null]); $panel->delete(); return back()->with('toast',['type'=>'success','message'=>'Panel deleted; fields were kept directly in the step.']);
    }

    public function updateField(Request $request, College $college, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step, CollegeAdmissionFormField $field): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.field_update'); abort_unless($step->college_admission_form_template_id===$template->id&&$field->college_admission_form_step_id===$step->id,404); $this->assertDraftStructure($template);
        $fieldRuleService = app(CollegeAdmissionFieldRuleService::class); $data=$request->validate([
            ...$fieldRuleService->requestRules(),
            'college_admission_form_panel_id'=>['nullable','integer','exists:college_admission_form_panels,id'],
            'label'=>['required','string','max:180'],'placeholder'=>['nullable','string','max:220'],'help_text'=>['nullable','string','max:2000'],'is_required'=>['nullable','boolean'],'display_order'=>['nullable','integer','min:0','max:9999'],
            'condition_source_field_id'=>['nullable','integer','exists:college_admission_form_fields,id'],
            'condition_operator'=>['nullable',Rule::in(['EQUALS','NOT_EQUALS','IN','NOT_IN','CONTAINS','IS_EMPTY','IS_NOT_EMPTY'])],
            'condition_values'=>['nullable','string','max:5000'],
        ]);
        if(filled($data['college_admission_form_panel_id']??null)&&!$step->panels()->whereKey($data['college_admission_form_panel_id'])->exists()) throw ValidationException::withMessages(['college_admission_form_panel_id'=>'Selected panel is outside this step.']);
        $sourceField = $this->conditionSourceForTemplate($template, $data['condition_source_field_id'] ?? null);
        $this->assertConditionConfiguration($field, $sourceField, $data);
        $fieldRuleService->assertConfiguration($template, $step, $field->field_type, $data, $field);
        DB::transaction(function () use ($field, $fieldRuleService, $data, $sourceField) {
            $field->update(['college_admission_form_panel_id'=>$data['college_admission_form_panel_id']??null,'label'=>trim($data['label']),'placeholder'=>$data['placeholder']??null,'help_text'=>$data['help_text']??null,'is_required'=>(bool)($data['is_required']??false),'display_order'=>$data['display_order']??$field->display_order,'validation_rules'=>$fieldRuleService->intrinsicRules($data, $field->field_type, $field->validation_rules??[])]);
            $fieldRuleService->sync($field,$data);
            $this->syncCondition($field, $sourceField, $data);
        });
        return back()->with('toast',['type'=>'success','message'=>'Field and answer-based condition updated.']);
    }

    public function destroyField(Request $request, College $college, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormStep $step, CollegeAdmissionFormField $field): RedirectResponse
    {
        $this->assertEditable($request,$college,$template,'college_admission_form.field_delete'); abort_unless($step->college_admission_form_template_id===$template->id&&$field->college_admission_form_step_id===$step->id,404); $this->assertDraftStructure($template);
        if(DB::table('college_admission_application_field_values')->where('college_admission_form_field_id',$field->id)->exists()||DB::table('college_admission_form_field_conditions')->where('source_field_id',$field->id)->exists()||DB::table('college_admission_form_field_comparisons')->where('source_field_id',$field->id)->exists()||DB::table('college_admission_form_field_copy_rules')->where('source_field_id',$field->id)->orWhere('trigger_field_id',$field->id)->exists()) throw ValidationException::withMessages(['field'=>'This field has application/condition dependencies.']); $field->delete(); return back()->with('toast',['type'=>'success','message'=>'Field deleted.']);
    }

    public function mapTemplate(Request $request, College $college, CollegeAdmissionFormTemplate $template): RedirectResponse
    {
        $this->assertTemplateScope($template,$college);
        $this->authorizeAny($request,$college,'college_admission_form.map');

        if ($template->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['template'=>'Only an ACTIVE form template can be mapped for Application Entry.']);
        }

        $data = $request->validate([
            'college_program_offering_id'=>['required','integer','exists:college_program_offerings,id'],
            'college_admission_cycle_id'=>['required','integer','exists:college_admission_cycles,id'],
            'seat_selection_required'=>['nullable','boolean'],
            'reservation_category_field_id'=>['nullable','integer','exists:college_admission_form_fields,id'],
        ]);

        $offering = $college->programOfferings()
            ->with('programTemplate.degree')
            ->whereKey($data['college_program_offering_id'])
            ->first();
        if (! $offering || ! $offering->programTemplate || ! $offering->programTemplate->degree) {
            throw ValidationException::withMessages(['college_program_offering_id'=>'Choose a valid Program Offering of this College.']);
        }

        $cycle = CollegeAdmissionCycle::query()
            ->whereKey($data['college_admission_cycle_id'])
            ->where('college_id',$college->id)
            ->where('college_program_offering_id',$offering->id)
            ->first();
        if (! $cycle) {
            throw ValidationException::withMessages(['college_admission_cycle_id'=>'Choose an Admission Cycle linked to the selected Program Offering.']);
        }

        $autoReservationFieldId = null;
        if (! filled($data['reservation_category_field_id'] ?? null)) {
            $autoReservationFieldId = CollegeAdmissionFormField::query()
                ->where('system_purpose','CANDIDATE_RESERVATION_CATEGORY')
                ->whereHas('step', fn ($q) => $q->whereIn('college_admission_form_template_id', array_values(array_filter([$template->id, $template->parent_template_id]))))
                ->where('status','ACTIVE')
                ->value('id');
        }

        if (filled($data['reservation_category_field_id'] ?? null)) {
            $categoryField = CollegeAdmissionFormField::query()
                ->whereKey($data['reservation_category_field_id'])
                ->whereHas('step', fn ($q) => $q->whereIn('college_admission_form_template_id', array_values(array_filter([$template->id, $template->parent_template_id]))))
                ->where('status', 'ACTIVE')
                ->whereIn('field_type', ['SELECT','RADIO'])
                ->first();
            if (! $categoryField) {
                throw ValidationException::withMessages(['reservation_category_field_id'=>'Map an ACTIVE SELECT/RADIO field from this exact Admission Form.']);
            }
        }

        $program = $offering->programTemplate;
        $degree = $program->degree;
        $mappingScope = [
            'university_id'=>$college->university_id,
            'college_id'=>$college->id,
            'degree_level_id'=>$degree->degree_level_id,
            'degree_id'=>$degree->id,
            'program_template_id'=>$program->id,
            'college_program_offering_id'=>$offering->id,
            'college_admission_cycle_id'=>$cycle->id,
        ];

        // One operational form wins for the same College Offering + Admission Cycle.
        CollegeAdmissionFormMapping::query()
            ->where('university_id',$college->university_id)
            ->where('college_id',$college->id)
            ->where('college_program_offering_id',$offering->id)
            ->where('college_admission_cycle_id',$cycle->id)
            ->where('status','ACTIVE')
            ->update(['status'=>'INACTIVE']);

        $mapping = CollegeAdmissionFormMapping::create([
            'college_admission_form_template_id'=>$template->id,
            ...$mappingScope,
            'status'=>'ACTIVE',
            'seat_selection_required'=>(bool) ($data['seat_selection_required'] ?? false),
            'reservation_category_field_id'=>$data['reservation_category_field_id'] ?? $autoReservationFieldId,
        ]);

        $this->audit($request,$college,'COLLEGE_ADMISSION_FORM_MAPPING_CREATED','CollegeAdmissionFormMapping',$mapping->id,$mapping->toArray());
        return back()->with('toast',['type'=>'success','message'=>'Form mapped to this College Program Offering and Admission Cycle.']);
    }

    public function destroyMapping(Request $request, College $college, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormMapping $mapping): RedirectResponse
    {
        $this->assertTemplateScope($template,$college);
        $this->authorizeAny($request,$college,'college_admission_form.map');

        abort_unless(
            (int) $mapping->college_admission_form_template_id === (int) $template->id
            && (int) $mapping->university_id === (int) $college->university_id
            && (int) $mapping->college_id === (int) $college->id,
            404
        );

        if ($mapping->status !== 'ACTIVE') return back();
        $before = $mapping->toArray();
        $mapping->update(['status'=>'INACTIVE']);
        $this->audit($request,$college,'COLLEGE_ADMISSION_FORM_MAPPING_REMOVED','CollegeAdmissionFormMapping',$mapping->id,['before'=>$before,'after'=>$mapping->fresh()->toArray()]);
        return back()->with('toast',['type'=>'success','message'=>'Form mapping removed.']);
    }


    public function publicAccess(Request $request, College $college, CollegeAdmissionFormTemplate $template, CollegeAdmissionFormMapping $mapping): RedirectResponse
    {
        $this->assertTemplateScope($template, $college);
        $this->authorizeAny($request, $college, 'college_admission_form.map');

        abort_unless(
            (int) $mapping->college_admission_form_template_id === (int) $template->id
            && (int) $mapping->college_id === (int) $college->id
            && (int) $mapping->university_id === (int) $college->university_id,
            404
        );

        $data = $request->validate([
            'public_enabled' => ['required','boolean'],
            'public_open_mode' => ['nullable', Rule::in(['SAME_WINDOW','NEW_WINDOW'])],
        ]);
        $enable = (bool) $data['public_enabled'];
        $mapping->public_open_mode = $data['public_open_mode'] ?? $mapping->public_open_mode ?? 'SAME_WINDOW';

        if ($enable) {
            if ($mapping->status !== 'ACTIVE' || $template->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['public_enabled' => 'Only an ACTIVE mapping with an ACTIVE form template can be published.']);
            }
            if ($template->admission_mode === 'DIRECT') {
                throw ValidationException::withMessages(['public_enabled' => 'A Direct-only template cannot be published. Public candidate applications use the Regular Admission workflow.']);
            }
            $cycle = CollegeAdmissionCycle::query()
                ->whereKey($mapping->college_admission_cycle_id)
                ->where('college_id', $college->id)
                ->where('college_program_offering_id', $mapping->college_program_offering_id)
                ->first();
            if (! $cycle) {
                throw ValidationException::withMessages(['public_enabled' => 'The mapping must point to a valid Admission Cycle of this College Program Offering.']);
            }

            if (blank($mapping->public_slug)) {
                $offeringCode = DB::table('college_program_offerings as cpo')->join('program_templates as pt','pt.id','=','cpo.program_template_id')->where('cpo.id', $mapping->college_program_offering_id)->value('pt.code');
                $base = Str::slug(collect([$college->code, $offeringCode, $cycle->code])->filter()->implode('-'));
                do { $slug = trim($base.'-'.Str::lower(Str::random(8)), '-'); }
                while (CollegeAdmissionFormMapping::query()->where('public_slug', $slug)->exists());
                $mapping->public_slug = $slug;
            }
            $mapping->public_enabled = true;
            $mapping->public_enabled_at = now();
        } else {
            $mapping->public_enabled = false;
            $mapping->public_enabled_at = null;
        }
        $mapping->save();

        $this->audit($request, $college, $enable ? 'COLLEGE_ADMISSION_PUBLIC_FORM_ENABLED' : 'COLLEGE_ADMISSION_PUBLIC_FORM_DISABLED', 'CollegeAdmissionFormMapping', $mapping->id, $mapping->fresh()->toArray());
        return back()->with('toast', ['type'=>'success','message'=>$enable ? 'Public application link enabled. Admission Cycle dates control when submissions are accepted.' : 'Public application link disabled.']);
    }

    public function updateApplicantRegistrationSettings(Request $request, College $college): RedirectResponse
    {
        $this->authorizeAny($request, $college, 'college_applicant_registration.settings');
        $data = $request->validate([
            'registration_enabled'=>['required','boolean'],
            'email_verification_required'=>['required','boolean'],
            'captcha_required'=>['required','boolean'],
            'registration_number_format'=>['required','string','max:120','regex:/\{SEQ(?::\d{1,2})?\}/'],
            'application_help_phone'=>['nullable','string','max:40'],
            'application_help_email'=>['nullable','email','max:190'],
            'application_help_text'=>['nullable','string','max:2000'],
        ]);
        $settings = CollegeApplicantRegistrationSetting::forCollege($college->id);
        $before = $settings->toArray();
        $settings->update([...$data, 'updated_by'=>$request->user()->id]);
        $this->audit($request,$college,'COLLEGE_APPLICANT_REGISTRATION_SETTINGS_UPDATED','CollegeApplicantRegistrationSetting',$settings->id,['before'=>$before,'after'=>$settings->fresh()->toArray()]);
        return back()->with('toast',['type'=>'success','message'=>'Applicant registration settings updated.']);
    }

    public function storeFeeRule(Request $request, College $college): RedirectResponse
    {
        $this->authorizeAny($request,$college,'college_application_fee.manage');
        $data=$request->validate([
            'name'=>['required','string','max:160'],'scope_owner'=>['required',Rule::in(['UNIVERSITY','COLLEGE'])],
            'degree_level_id'=>['nullable','integer','exists:degree_levels,id'],'degree_id'=>['nullable','integer','exists:degrees,id'],'program_template_id'=>['nullable','integer','exists:program_templates,id'],
            'college_program_offering_id'=>['nullable','integer','exists:college_program_offerings,id'],'college_admission_cycle_id'=>['nullable','integer','exists:college_admission_cycles,id'],
            'fee_required'=>['required','boolean'],'amount'=>['required','numeric','min:0','max:999999999.99'],'currency'=>['required','string','size:3'],
        ]);
        if ($data['scope_owner']==='UNIVERSITY' && ! $request->user()->hasPermission('college_application_fee.manage')) abort(403);
        $this->assertAcademicScopeIds($college,$data);
        $feeScope = [
            'university_id'=>$college->university_id,'college_id'=>$data['scope_owner']==='COLLEGE'?$college->id:null,
            'degree_level_id'=>$data['degree_level_id']??null,'degree_id'=>$data['degree_id']??null,'program_template_id'=>$data['program_template_id']??null,
            'college_program_offering_id'=>$data['college_program_offering_id']??null,'college_admission_cycle_id'=>$data['college_admission_cycle_id']??null,
        ];
        $existingFee = CollegeApplicationFeeRule::query();
        foreach ($feeScope as $key=>$value) $existingFee->where($key,$value);
        $existingFee->update(['status'=>'INACTIVE','updated_by'=>$request->user()->id]);
        $feeRule = CollegeApplicationFeeRule::create([
            ...$feeScope,'name'=>$data['name'],'fee_required'=>(bool)$data['fee_required'],'amount'=>$data['fee_required']?$data['amount']:0,
            'currency'=>strtoupper($data['currency']),'status'=>'ACTIVE','created_by'=>$request->user()->id,'updated_by'=>$request->user()->id,
        ]);
        $this->audit($request,$college,'COLLEGE_APPLICATION_FEE_RULE_CREATED','CollegeApplicationFeeRule',$feeRule->id,$feeRule->toArray(),$data['scope_owner']==='UNIVERSITY');
        return back()->with('toast',['type'=>'success','message'=>'Application Fee rule added. The most specific active rule will be snapshotted when an application is created.']);
    }

    private function audit(Request $request, College $college, string $event, string $resourceType, int $resourceId, array $after, bool $universityScope = false): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id'=>$request->user()->id,'event'=>$event,'resource_type'=>$resourceType,'resource_id'=>$resourceId,
            'scope_type'=>$universityScope?'UNIVERSITY':'COLLEGE','scope_reference'=>$universityScope?'university':'college:'.$college->id,
            'before'=>null,'after'=>json_encode($after),'ip_address'=>$request->ip(),'created_at'=>now(),
        ]);
    }

    private function assertAcademicScopeIds(College $college, array $data): void
    {
        if (filled($data['degree_level_id'] ?? null) && ! DegreeLevel::query()->whereKey($data['degree_level_id'])->where('university_id',$college->university_id)->exists()) abort(422, 'Degree Level is outside this University.');
        if (filled($data['degree_id'] ?? null) && ! Degree::query()->whereKey($data['degree_id'])->where('university_id',$college->university_id)->exists()) abort(422, 'Degree is outside this University.');
        if (filled($data['program_template_id'] ?? null) && ! ProgramTemplate::query()->whereKey($data['program_template_id'])->where('university_id',$college->university_id)->exists()) abort(422, 'Program Template is outside this University.');
        $degree = filled($data['degree_id'] ?? null) ? Degree::query()->find($data['degree_id']) : null;
        $program = filled($data['program_template_id'] ?? null) ? ProgramTemplate::query()->find($data['program_template_id']) : null;
        if (filled($data['degree_level_id'] ?? null) && $degree && (int)$degree->degree_level_id !== (int)$data['degree_level_id']) throw ValidationException::withMessages(['degree_id'=>'Selected Degree does not belong to the selected Degree Level.']);
        if ($degree && $program && (int)$program->degree_id !== (int)$degree->id) throw ValidationException::withMessages(['program_template_id'=>'Selected Program does not belong to the selected Degree.']);
        $offering = filled($data['college_program_offering_id'] ?? null) ? $college->programOfferings()->whereKey($data['college_program_offering_id'])->first() : null;
        if (filled($data['college_program_offering_id'] ?? null) && ! $offering) abort(422, 'Program Offering is outside this College.');
        if ($program && $offering && (int)$offering->program_template_id !== (int)$program->id) throw ValidationException::withMessages(['college_program_offering_id'=>'Selected Program Offering does not belong to the selected Program.']);
        $curriculum = filled($data['curriculum_id'] ?? null) ? Curriculum::query()->currentApproved()->whereKey($data['curriculum_id'])->where('university_id',$college->university_id)->first() : null;
        if (filled($data['curriculum_id'] ?? null) && ! $curriculum) throw ValidationException::withMessages(['curriculum_id'=>'Select the current approved ACTIVE Curriculum. Superseded Curriculum versions cannot be used.']);
        if ($program && $curriculum && (int)$curriculum->program_template_id !== (int)$program->id) throw ValidationException::withMessages(['curriculum_id'=>'Selected Curriculum does not belong to the selected Program.']);
        if ($offering && $curriculum && (int)$offering->curriculum_id !== (int)$curriculum->id) throw ValidationException::withMessages(['curriculum_id'=>'Selected Curriculum is not the Curriculum linked to this Program Offering.']);
        $cycle = filled($data['college_admission_cycle_id'] ?? null) ? CollegeAdmissionCycle::query()->whereKey($data['college_admission_cycle_id'])->where('college_id',$college->id)->first() : null;
        if (filled($data['college_admission_cycle_id'] ?? null) && ! $cycle) abort(422, 'Admission Cycle is outside this College.');
        if ($offering && $cycle && (int)$cycle->college_program_offering_id !== (int)$offering->id) throw ValidationException::withMessages(['college_admission_cycle_id'=>'Selected Admission Cycle does not belong to the selected Program Offering.']);
    }


    private function conditionSourceForTemplate(CollegeAdmissionFormTemplate $template, mixed $fieldId): ?CollegeAdmissionFormField
    {
        if (! filled($fieldId)) return null;
        $allowedTemplateIds = collect([$template->id]);
        $parent = $template->parent;
        while ($parent) { $allowedTemplateIds->push($parent->id); $parent = $parent->parent; }
        $field = CollegeAdmissionFormField::query()->whereKey($fieldId)->whereHas('step', fn($q)=>$q->whereIn('college_admission_form_template_id',$allowedTemplateIds->all()))->first();
        if (! $field) throw ValidationException::withMessages(['condition_source_field_id'=>'Condition source must be a field from this template or its inherited University base.']);
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
            : $this->canonicalConditionValues($source, $this->splitValues($data['condition_values'] ?? ''));
        $field->conditions()->create([
            'source_field_id'=>$source->id,
            'operator'=>$operator,
            'compare_values'=>$values,
            'display_order'=>10,
            'is_active'=>true,
        ]);
    }

    private function splitValues(string $raw): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $raw) ?: []), fn($v)=>$v!==''));
    }

    private function canonicalConditionValues(CollegeAdmissionFormField $sourceField, array $values): array
    {
        if (! in_array($sourceField->field_type, ['SELECT','RADIO','CHECKBOX','MULTISELECT','YES_NO'], true)) return $values;
        $sourceField->loadMissing('options');
        return collect($values)->map(function ($value) use ($sourceField) {
            $needle = Str::slug(trim((string) $value), '_');
            $option = $sourceField->options->first(fn ($option) => Str::slug((string) $option->value, '_') === $needle || Str::slug((string) $option->label, '_') === $needle);
            return (string) ($option?->value ?? $needle);
        })->filter(fn ($value) => $value !== '')->values()->all();
    }

    private function assertEditable(Request $request, College $college, CollegeAdmissionFormTemplate $template, string $permission): void
    {
        $this->assertTemplateScope($template,$college); $this->authorizeAny($request,$college,$permission);
        if ($template->owner_scope_type==='UNIVERSITY') abort(403, 'University base templates are managed only at University scope.');
        if ($template->parent_template_id && ! $template->parent?->allow_college_override) abort(403, 'University has disabled College override for this base form.');
        if ($template->status==='RETIRED') throw ValidationException::withMessages(['template'=>'A retired template cannot be changed.']);
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
        if($template->status!=='DRAFT') throw ValidationException::withMessages(['template'=>'Structural edit/delete is allowed only while the College extension is DRAFT.']);
    }
    private function assertTemplateScope(CollegeAdmissionFormTemplate $template, College $college): void { abort_unless($template->university_id===$college->university_id && ($template->college_id===null || $template->college_id===$college->id),404); }
    private function canAny(Request $request, College $college, string $permission): bool
    {
        if ($request->user()->hasPermission($permission)) return true;
        return $request->user()->hasCollegePermission($permission, $college->id);
    }
    private function authorizeAny(Request $request, College $college, string $permission): void
    {
        abort_unless($this->canAny($request, $college, $permission), 403);
    }

    private function isEligibleCollegeManager(int $userId, College $college): bool
    {
        return User::query()
            ->whereKey($userId)
            ->where('status', 'ACTIVE')
            ->whereHas('roles', function ($q) use ($college) {
                $q->where('roles.status', 'ACTIVE')
                    ->where('user_roles.status', 'ACTIVE')
                    ->where('user_roles.scope_type', 'COLLEGE')
                    ->where('user_roles.scope_reference', "college:{$college->id}")
                    ->where(fn ($roleQuery) => $roleQuery->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from', '<=', now()))
                    ->where(fn ($roleQuery) => $roleQuery->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until', '>=', now()));
            })
            ->exists();
    }

}
