<?php
namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\Curriculum;
use App\Models\FeeHead;
use App\Models\FeeScholarshipScheme;
use App\Models\ProgramTemplate;
use App\Models\ReservationCategory;
use App\Models\University;
use App\Services\FeeScholarshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FeeScholarshipController extends Controller
{
    public function universityIndex(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('fee_scholarship.view'),403);
        $u=University::firstOrFail();
        return $this->page($request,$u,null);
    }
    public function collegeIndex(Request $request, College $college): Response
    {
        abort_unless($request->user()->hasCollegePermission('college_fee_scholarship.view',$college->id),403);
        return $this->page($request,$college->university,$college);
    }
    public function storeUniversity(Request $r, FeeScholarshipService $s): RedirectResponse { $this->u($r,'fee_scholarship.create'); $s->save(null,University::firstOrFail(),null,$r->validate($this->rules(false)),$r->user()->id); return back()->with('toast',['type'=>'success','message'=>'Scholarship / benefit scheme created as INACTIVE.']); }
    public function updateUniversity(Request $r, FeeScholarshipScheme $scheme, FeeScholarshipService $s): RedirectResponse { $this->u($r,'fee_scholarship.update'); $u=University::firstOrFail(); $s->assertOwner($scheme,$u,null); $s->save($scheme,$u,null,$r->validate($this->rules(false,$scheme->id)),$r->user()->id); return back()->with('toast',['type'=>'success','message'=>'Scholarship / benefit scheme updated.']); }
    public function statusUniversity(Request $r, FeeScholarshipScheme $scheme, FeeScholarshipService $s): RedirectResponse { $status=$r->validate(['status'=>['required',Rule::in(['ACTIVE','INACTIVE'])]])['status']; $this->u($r,$status==='ACTIVE'?'fee_scholarship.enable':'fee_scholarship.disable'); $s->status($scheme,University::firstOrFail(),null,$status,$r->user()->id); return back()->with('toast',['type'=>'success','message'=>'Scheme status updated.']); }
    public function storeCollege(Request $r, College $college, FeeScholarshipService $s): RedirectResponse { $this->c($r,$college,'college_fee_scholarship.create'); $s->save(null,$college->university,$college,$r->validate($this->rules(true)),$r->user()->id); return back()->with('toast',['type'=>'success','message'=>'College scholarship / benefit scheme created as INACTIVE.']); }
    public function updateCollege(Request $r, College $college, FeeScholarshipScheme $scheme, FeeScholarshipService $s): RedirectResponse { $this->c($r,$college,'college_fee_scholarship.update'); $s->assertOwner($scheme,$college->university,$college); $s->save($scheme,$college->university,$college,$r->validate($this->rules(true,$scheme->id)),$r->user()->id); return back()->with('toast',['type'=>'success','message'=>'College scholarship / benefit scheme updated.']); }
    public function statusCollege(Request $r, College $college, FeeScholarshipScheme $scheme, FeeScholarshipService $s): RedirectResponse { $status=$r->validate(['status'=>['required',Rule::in(['ACTIVE','INACTIVE'])]])['status']; $this->c($r,$college,$status==='ACTIVE'?'college_fee_scholarship.enable':'college_fee_scholarship.disable'); $s->status($scheme,$college->university,$college,$status,$r->user()->id); return back()->with('toast',['type'=>'success','message'=>'College scheme status updated.']); }

    private function page(Request $r, University $u, ?College $college): Response
    {
        $own=FeeScholarshipScheme::with(['academicSession:id,name,code','programTemplate:id,name,code','offering.programTemplate:id,name,code','feeHeads:id,name,code,college_id','reservationCategories:id,name,code'])
            ->where('university_id',$u->id)->when($college,fn($q)=>$q->where('college_id',$college->id),fn($q)=>$q->whereNull('college_id'))->orderByDesc('id')->get();
        $inherited=$college ? FeeScholarshipScheme::with(['academicSession:id,name,code','programTemplate:id,name,code','feeHeads:id,name,code','reservationCategories:id,name,code'])->where('university_id',$u->id)->whereNull('college_id')->where('status','ACTIVE')->orderBy('name')->get() : collect();
        $heads=FeeHead::where('university_id',$u->id)->where('status','ACTIVE')->where(function($q) use($college){$q->whereNull('college_id');if($college)$q->orWhere('college_id',$college->id);})->orderBy('name')->get(['id','name','code','college_id']);
        if ($college) {
            $offerings = CollegeProgramOffering::with([
                    'programTemplate:id,name,code',
                    'academicSession:id,name,code,is_current,status',
                ])
                ->where('college_id',$college->id)
                ->where('status','ACTIVE')
                ->whereHas('curriculum', fn($q) => $q->currentApproved())
                ->get(['id','program_template_id','curriculum_id','academic_session_id']);

            $eligibleSessionIds = $offerings->pluck('academic_session_id')->filter()->unique()->values();
            $sessions = AcademicSession::where('university_id',$u->id)
                ->whereIn('id',$eligibleSessionIds)
                ->whereIn('status',['PLANNED','ACTIVE'])
                ->orderByDesc('is_current')
                ->orderByDesc('starts_on')
                ->get(['id','name','code','status','is_current']);
            $programs = collect();
        } else {
            $eligibleSessionIds = Curriculum::query()
                ->currentApproved()
                ->where('university_id',$u->id)
                ->whereNotNull('academic_session_id')
                ->select('academic_session_id');

            $sessions = AcademicSession::where('university_id',$u->id)
                ->whereIn('id',$eligibleSessionIds)
                ->whereIn('status',['PLANNED','ACTIVE'])
                ->orderByDesc('is_current')
                ->orderByDesc('starts_on')
                ->get(['id','name','code','status','is_current']);
            $offerings = collect();
            $eligibleProgramIds = Curriculum::query()
                ->currentApproved()
                ->where('university_id',$u->id)
                ->whereNotNull('program_template_id')
                ->select('program_template_id');
            $programs = ProgramTemplate::where('university_id',$u->id)
                ->where('status','ACTIVE')
                ->whereIn('id',$eligibleProgramIds)
                ->orderBy('name')
                ->get(['id','name','code']);
        }
        $categories=ReservationCategory::where('university_id',$u->id)->where('status','ACTIVE')->orderBy('display_order')->orderBy('name')->get(['id','name','code','nature']);
        $prefix=$college?'college_fee_scholarship':'fee_scholarship';
        $can=fn($action)=>$college?$r->user()->hasCollegePermission($prefix.'.'.$action,$college->id):$r->user()->hasPermission($prefix.'.'.$action);
        return Inertia::render('fee-scholarships/index',['scope'=>$college?'COLLEGE':'UNIVERSITY','university'=>$u->only(['id','name','code']),'college'=>$college?->only(['id','name','code']),'schemes'=>$own,'inheritedSchemes'=>$inherited,'feeHeads'=>$heads,'sessions'=>$sessions,'programs'=>$programs,'offerings'=>$offerings,'reservationCategories'=>$categories,'can'=>['create'=>$can('create'),'update'=>$can('update'),'enable'=>$can('enable'),'disable'=>$can('disable')]]);
    }
    private function rules(bool $college, ?int $ignore=null): array
    {
        return ['name'=>['required','string','max:150'],'code'=>['required','string','max:60'],'academic_session_id'=>['required','integer'],'program_template_id'=>[$college?'nullable':'nullable','integer'],'college_program_offering_id'=>[$college?'required':'nullable','integer'],'benefit_type'=>['required',Rule::in(['SCHOLARSHIP','CONCESSION','WAIVER'])],'calculation_type'=>['required',Rule::in(['FIXED','PERCENTAGE'])],'benefit_value'=>['required','numeric','gt:0','max:999999999.99'],'maximum_benefit_amount'=>['nullable','numeric','gt:0','max:999999999.99'],'eligibility_mode'=>['required',Rule::in(['OPEN','RESERVATION_CATEGORY'])],'approval_mode'=>['required',Rule::in(['AUTOMATIC','MANUAL'])],'fee_head_ids'=>['required','array','min:1'],'fee_head_ids.*'=>['integer'],'reservation_category_ids'=>['nullable','array'],'reservation_category_ids.*'=>['integer'],'description'=>['nullable','string','max:2000']];
    }
    private function u(Request $r,string $p):void { abort_unless($r->user()->hasPermission($p),403); }
    private function c(Request $r,College $c,string $p):void { abort_unless($r->user()->hasCollegePermission($p,$c->id),403); }
}
