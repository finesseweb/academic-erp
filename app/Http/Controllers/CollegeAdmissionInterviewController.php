<?php
namespace App\Http\Controllers;
use App\Http\Requests\UpsertCollegeAdmissionInterviewRequest;
use App\Models\{College,CollegeAdmissionApplicationChoice,User};
use App\Services\{CollegeAdmissionInterviewService,CollegeAdmissionScoreService,CollegeReservationService};
use Illuminate\Http\{RedirectResponse,Request};
use Inertia\{Inertia,Response};
class CollegeAdmissionInterviewController extends Controller {
 public function index(Request $request,College $college,CollegeReservationService $reservationService):Response{
  $this->authorizeCollege($request,$college,'college_admission_interview.view');$search=trim((string)$request->query('search',''));
  $choices=CollegeAdmissionApplicationChoice::query()->with(['application.admissionCycle.programOffering.programTemplate:id,name,code','intake.allocations.discipline:id,name,code','intake.allocations.specialization:id,name,code','selectionRule:id,name,code,version_no,interview_weight_percent,minimum_interview_score,minimum_final_score,merit_weight_percent,entrance_weight_percent','score','interview.evaluators'])->where('eligibility_status','ELIGIBLE')->whereHas('application',fn($q)=>$q->where('college_id',$college->id)->where('status','SUBMITTED'))->whereHas('selectionRule',fn($q)=>$q->where('interview_weight_percent','>',0))->whereHas('score')->when($search!=='',fn($q)=>$q->whereHas('application',fn($a)=>$a->where(fn($w)=>$w->where('application_no','like',"%{$search}%")->orWhere('candidate_name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")->orWhere('phone','like',"%{$search}%"))))->orderByDesc('id')->paginate(25)->withQueryString();
  $choices->getCollection()->each(function($choice)use($reservationService){$bucket=$reservationService->availableBuckets($choice->intake)->firstWhere('bucket_key',$choice->bucket_key);$choice->setAttribute('bucket_label',$bucket['label']??$choice->bucket_key);});
  $evaluators=User::query()->where('primary_college_id',$college->id)->where('status','ACTIVE')->orderBy('name')->get(['id','name','email']);
  return Inertia::render('college-admission-interviews/index',['college'=>$college->only(['id','name','code','status']),'choices'=>$choices,'evaluators'=>$evaluators,'filters'=>['search'=>$search],'can'=>['manage'=>$request->user()->hasCollegePermission('college_admission_interview.manage',$college->id)]]);
 }
 public function upsert(UpsertCollegeAdmissionInterviewRequest $request,College $college,CollegeAdmissionApplicationChoice $choice,CollegeAdmissionInterviewService $service,CollegeAdmissionScoreService $scoreService):RedirectResponse{$this->authorizeCollege($request,$college,'college_admission_interview.manage');$service->save($college,$choice,$request->validated(),$request->user()->id,$request->ip(),$scoreService);return back()->with('toast',['type'=>'success','message'=>'Interview schedule / evaluation saved and Admission score re-evaluated.']);}
 private function authorizeCollege(Request $request,College $college,string $permission):void{abort_unless($request->user()->hasCollegePermission($permission,$college->id),403);}
}
