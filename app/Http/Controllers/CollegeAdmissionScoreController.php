<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertCollegeAdmissionScoreRequest;
use App\Models\College;
use App\Models\CollegeAdmissionApplicationChoice;
use App\Services\CollegeAdmissionScoreService;
use App\Services\CollegeReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollegeAdmissionScoreController extends Controller
{
    public function index(Request $request, College $college, CollegeReservationService $reservationService): Response
    {
        $this->authorizeCollege($request,$college,'college_admission_score.view');
        $search=trim((string)$request->query('search',''));

        $choices=CollegeAdmissionApplicationChoice::query()
            ->with([
                'application.admissionCycle.programOffering.programTemplate:id,name,code',
                'application.admissionCycle.programOffering.academicSession:id,name,code,is_current',
                'intake.allocations.discipline:id,name,code','intake.allocations.specialization:id,name,code',
                'selectionRule:id,name,code,version_no,selection_mode,merit_weight_percent,entrance_weight_percent,interview_weight_percent,minimum_merit_score,minimum_entrance_score,minimum_interview_score,minimum_final_score',
                'score',
            ])
            ->where('eligibility_status','ELIGIBLE')
            ->whereNotNull('college_admission_selection_rule_id')
            ->whereHas('application', fn($q)=>$q->where('college_id',$college->id)->where('status','SUBMITTED'))
            ->when($search!=='', fn($q)=>$q->whereHas('application', fn($a)=>$a->where(fn($w)=>$w->where('application_no','like',"%{$search}%")->orWhere('candidate_name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")->orWhere('phone','like',"%{$search}%"))))
            ->orderByDesc('id')->paginate(25)->withQueryString();

        $choices->getCollection()->each(function($choice) use($reservationService){
            $bucket=$reservationService->availableBuckets($choice->intake)->firstWhere('bucket_key',$choice->bucket_key);
            $choice->setAttribute('bucket_label',$bucket['label'] ?? $choice->bucket_key);
        });

        return Inertia::render('college-admission-scores/index',[
            'college'=>$college->only(['id','name','code','status']),
            'choices'=>$choices,'filters'=>['search'=>$search],
            'can'=>['manage'=>$request->user()->hasCollegePermission('college_admission_score.manage',$college->id)],
        ]);
    }

    public function upsert(UpsertCollegeAdmissionScoreRequest $request, College $college, CollegeAdmissionApplicationChoice $choice, CollegeAdmissionScoreService $service): RedirectResponse
    {
        $this->authorizeCollege($request,$college,'college_admission_score.manage');
        $service->upsert($college,$choice,$request->validated(),$request->user()->id,$request->ip());
        return back()->with('toast',['type'=>'success','message'=>'Admission scores normalized and saved against the locked Selection Rule version.']);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission,$college->id),403);
    }
}
