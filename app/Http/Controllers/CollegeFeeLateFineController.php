<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\FeeHead;
use App\Models\FeeLateFineCharge;
use App\Models\FeeLateFineRule;
use App\Services\FeeLateFineService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeFeeLateFineController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->auth($request,$college,'college_fee_late_fine.view');
        $rules = FeeLateFineRule::with(['offering.programTemplate:id,name,code','academicSession:id,name,code','feeHead:id,name,code'])
            ->where('college_id',$college->id)->orderByDesc('id')->get();
        $offerings = CollegeProgramOffering::with(['programTemplate:id,name,code','academicSession:id,name,code,is_current'])
            ->where('college_id',$college->id)->where('status','ACTIVE')->orderBy('program_template_id')->get();
        $heads = FeeHead::where('university_id',$college->university_id)->where('status','ACTIVE')
            ->where(function($q)use($college){$q->whereNull('college_id')->orWhere('college_id',$college->id);})->orderBy('name')->get(['id','name','code']);
        $sessions = AcademicSession::where('university_id',$college->university_id)->where('status','ACTIVE')
            ->orderByDesc('is_current')->orderByDesc('starts_on')->get(['id','name','code','is_current']);
        $currentSessionId=(int)($sessions->firstWhere('is_current',true)?->id ?? $sessions->first()?->id ?? 0);
        $sessionId=(int)$request->query('session_id',$currentSessionId);
        $offeringId=(int)$request->query('offering_id',0);
        $q = trim((string)$request->query('q',''));
        $perPage = (int)$request->query('per_page',25); if(!in_array($perPage,[25,50,100],true))$perPage=25;
        $charges = FeeLateFineCharge::query()->with([
                'rule:id,name,code,calculation_type,frequency,value,grace_days',
                'demand:id,demand_no,admission_id,currency',
                'demand.admission.application:id,application_no,candidate_name',
                'demandItem:id,fee_head_name,fee_head_code',
                'installment:id,installment_no,due_date,amount,paid_amount',
            ])
            ->where('college_id',$college->id)->where('status','ACTIVE')
            ->when($sessionId>0,fn($query)=>$query->whereHas('demand',fn($d)=>$d->where('academic_session_id',$sessionId)))
            ->when($offeringId>0,fn($query)=>$query->whereHas('demand',fn($d)=>$d->where('college_program_offering_id',$offeringId)))
            ->when($q!=='',fn($query)=>$query->where(function($x)use($q){$x->whereHas('demand',fn($d)=>$d->where('demand_no','like','%'.$q.'%')->orWhereHas('admission.application',fn($a)=>$a->where('application_no','like','%'.$q.'%')->orWhere('candidate_name','like','%'.$q.'%')))->orWhereHas('rule',fn($r)=>$r->where('name','like','%'.$q.'%')->orWhere('code','like','%'.$q.'%'));}))
            ->orderByDesc('calculated_as_of')->orderByDesc('id')->paginate($perPage)->withQueryString();
        return Inertia::render('college-fee-late-fines/index',[
            'college'=>$college->only(['id','name','code']), 'rules'=>$rules, 'offerings'=>$offerings, 'feeHeads'=>$heads,'sessions'=>$sessions,
            'charges'=>$charges,'filters'=>['q'=>$q,'per_page'=>$perPage,'session_id'=>$sessionId,'offering_id'=>$offeringId],
            'can'=>[
                'manage'=>$request->user()->hasCollegePermission('college_fee_late_fine.manage',$college->id),
                'calculate'=>$request->user()->hasCollegePermission('college_fee_late_fine.calculate',$college->id),
            ],
        ]);
    }

    public function store(Request $request, College $college, FeeLateFineService $service): RedirectResponse
    {
        $this->auth($request,$college,'college_fee_late_fine.manage');
        $service->save(null,$college,$this->validated($request),$request->user()->id);
        return back()->with('toast',['type'=>'success','message'=>'Late Fine Rule created as INACTIVE. Review and activate it when ready.']);
    }

    public function update(Request $request, College $college, FeeLateFineRule $rule, FeeLateFineService $service): RedirectResponse
    {
        $this->auth($request,$college,'college_fee_late_fine.manage');
        $service->save($rule,$college,$this->validated($request,$rule->id),$request->user()->id);
        return back()->with('toast',['type'=>'success','message'=>'Late Fine Rule updated.']);
    }

    public function status(Request $request, College $college, FeeLateFineRule $rule, FeeLateFineService $service): RedirectResponse
    {
        $this->auth($request,$college,'college_fee_late_fine.manage');
        $status=$request->validate(['status'=>['required',Rule::in(['ACTIVE','INACTIVE'])]])['status'];
        $service->setStatus($rule,$college,$status,$request->user()->id);
        return back()->with('toast',['type'=>'success','message'=>'Late Fine Rule status updated.']);
    }

    public function recalculate(Request $request, College $college, FeeLateFineService $service): RedirectResponse
    {
        $this->auth($request,$college,'college_fee_late_fine.calculate');
        $data=$request->validate(['as_of'=>['nullable','date']]);
        $asOf=isset($data['as_of'])&&$data['as_of'] ? Carbon::parse($data['as_of']) : now();
        $result=$service->recalculateCollege($college,$asOf,$request->user()->id);
        return back()->with('toast',['type'=>'success','message'=>"Late fine calculation completed: {$result['created']} posted/recalculated, {$result['reversed']} reversed, {$result['unchanged']} unchanged."]);
    }

    private function validated(Request $request, ?int $ignoreId=null): array
    {
        $data = $request->validate([
            'name'=>['required','string','max:150'],
            'code'=>['required','string','max:60',Rule::unique('fee_late_fine_rules','code')->where(fn($q)=>$q->where('college_id',$request->route('college')->id))->ignore($ignoreId)],
            'college_program_offering_id'=>['required','integer'], 'fee_head_id'=>['required','integer'],
            'calculation_type'=>['required',Rule::in(['FIXED','PERCENTAGE'])],
            'frequency'=>['required',Rule::in(['ONE_TIME','PER_DAY','PER_WEEK'])],
            'value'=>['required','numeric','gt:0','max:999999999.9999'],
            'grace_days'=>['required','integer','min:0','max:365'],
            'maximum_fine_amount'=>['nullable','numeric','gt:0','max:999999999.99'],
            'notes'=>['nullable','string','max:2000'],
        ]);
        if ($data['calculation_type']==='PERCENTAGE' && (float)$data['value']>100) {
            throw \Illuminate\Validation\ValidationException::withMessages(['value'=>'Percentage Late Fine cannot exceed 100% per calculation unit.']);
        }
        return $data;
    }
    private function auth(Request $request, College $college, string $permission): void { abort_unless($request->user()->hasCollegePermission($permission,$college->id),403); }
}
