<?php

namespace App\Http\Controllers;

use App\Models\CollegeAdmissionFormField;
use App\Models\CollegeAdmissionFormStep;
use App\Models\CollegeAdmissionFormTemplate;
use App\Models\CollegeApplicationFeeRule;
use App\Models\University;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
            ->with(['manager:id,name,email', 'steps.fields.options'])
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
            'can' => [
                'manage' => $request->user()->hasPermission('college_admission_form.create')
                    || $request->user()->hasPermission('college_admission_form.update')
                    || $request->user()->hasPermission('college_admission_form.status')
                    || $request->user()->hasPermission('college_admission_form.step_create')
                    || $request->user()->hasPermission('college_admission_form.field_create'),
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
            'governance_mode'=>['required', Rule::in(['UNIVERSITY_CONTROLLED','UNIVERSITY_BASE_COLLEGE_EXTENSION'])],
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
            'owner_scope_type'=>'UNIVERSITY', 'governance_mode'=>$data['governance_mode'], 'admission_mode'=>$data['admission_mode'],
            'status'=>'DRAFT', 'description'=>$data['description']??null, 'created_by'=>$request->user()->id, 'updated_by'=>$request->user()->id,
        ]);
        $this->audit($request, 'UNIVERSITY_ADMISSION_FORM_TEMPLATE_CREATED', 'CollegeAdmissionFormTemplate', $template->id, $template->toArray());
        return back()->with('toast',['type'=>'success','message'=>'University Admission Form template created as DRAFT.']);
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
        $data=$request->validate([
            'label'=>['required','string','max:180'],'field_key'=>['required','string','max:100','regex:/^[a-z][a-z0-9_]*$/'],
            'field_type'=>['required',Rule::in(['TEXT','NUMBER','DATE','EMAIL','PHONE','TEXTAREA','SELECT','RADIO','CHECKBOX','MULTISELECT','FILE','IMAGE','YES_NO'])],
            'placeholder'=>['nullable','string','max:220'],'help_text'=>['nullable','string','max:2000'],'is_required'=>['nullable','boolean'],
            'options'=>['nullable','string','max:5000'],'max_kb'=>['nullable','integer','min:1','max:51200'],'extensions'=>['nullable','string','max:500'],
        ]);
        if ($step->fields()->where('field_key',$data['field_key'])->exists()) throw ValidationException::withMessages(['field_key'=>'This field key already exists in the step.']);
        if (in_array($data['field_type'],['SELECT','RADIO','CHECKBOX','MULTISELECT'],true) && blank($data['options']??null)) throw ValidationException::withMessages(['options'=>'Add at least one option for this field type.']);
        $field=DB::transaction(function()use($step,$data){
            $field=$step->fields()->create([
                'field_key'=>$data['field_key'],'label'=>trim($data['label']),'field_type'=>$data['field_type'],'placeholder'=>$data['placeholder']??null,'help_text'=>$data['help_text']??null,
                'is_required'=>(bool)($data['is_required']??false),'is_locked'=>true,'display_order'=>($step->fields()->max('display_order')??0)+10,
                'validation_rules'=>array_filter(['max_kb'=>$data['max_kb']??null,'extensions'=>filled($data['extensions']??null)?array_values(array_filter(array_map(fn($v)=>strtolower(trim($v)),explode(',',$data['extensions'])))):null],fn($v)=>$v!==null),'status'=>'ACTIVE',
            ]);
            $options=[];
            if($data['field_type']==='YES_NO')$options=[['Yes','YES'],['No','NO']];
            elseif(filled($data['options']??null))foreach(preg_split('/\r\n|\r|\n|,/', $data['options']) as $raw){$label=trim($raw);if($label!=='')$options[]=[$label,Str::slug($label,'_')];}
            foreach($options as $i=>[$label,$value])$field->options()->create(['label'=>$label,'value'=>$value,'display_order'=>($i+1)*10,'is_active'=>true]);
            return $field->load('options');
        });
        $this->audit($request, 'UNIVERSITY_ADMISSION_FORM_FIELD_CREATED', 'CollegeAdmissionFormField', $field->id, $field->toArray());
        return back()->with('toast',['type'=>'success','message'=>'University base field added and locked for College inheritance.']);
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

    private function authorizeUniversity(Request $request, string $permission): void { abort_unless($request->user()?->hasPermission($permission), 403); }
    private function assertUniversityTemplate(CollegeAdmissionFormTemplate $template): void { $university=University::query()->firstOrFail(); abort_unless($template->university_id===$university->id && $template->college_id===null && $template->owner_scope_type==='UNIVERSITY',404); }
    private function audit(Request $request,string $event,string $resourceType,int $resourceId,array $after):void{DB::table('audit_logs')->insert(['actor_user_id'=>$request->user()->id,'event'=>$event,'resource_type'=>$resourceType,'resource_id'=>$resourceId,'scope_type'=>'UNIVERSITY','scope_reference'=>'university','before'=>null,'after'=>json_encode($after),'ip_address'=>$request->ip(),'created_at'=>now()]);}
}
