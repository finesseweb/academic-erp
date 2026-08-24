<?php
namespace App\Http\Controllers;

use App\Http\Requests\UpsertAcademicPolicyGradingRuleRequest;
use App\Models\AcademicPolicy;
use App\Models\AcademicPolicyGradeBand;
use App\Models\AcademicPolicyGradingRule;
use App\Services\AcademicPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPolicyGradingRuleController extends Controller
{
    public function __construct(private readonly AcademicPolicyService $policyService) {}

    public function edit(Request $request, AcademicPolicy $academicPolicy): Response
    {
        abort_unless($request->user()->hasPermission('academic_policy.view'), 403);
        $academicPolicy->load(['academicSession:id,name,code','programTemplate:id,name,code','curriculum:id,name,code,version','gradingRule','gradeBands']);
        return Inertia::render('admin/academic-policies/grading', [
            'policy'=>$academicPolicy,'rule'=>$academicPolicy->gradingRule,'bands'=>$academicPolicy->gradeBands,
            'editable'=>$request->user()->hasPermission('academic_policy.update') && $academicPolicy->lifecycle_status==='DRAFT' &&
                ! in_array($academicPolicy->approval_status ?? 'NOT_SUBMITTED',['SUBMITTED','UNDER_APPROVAL','APPROVED'],true),
        ]);
    }

    public function update(UpsertAcademicPolicyGradingRuleRequest $request, AcademicPolicy $academicPolicy): RedirectResponse
    {
        $this->policyService->assertEditable($academicPolicy);
        $data=$request->validated(); $bands=$data['bands'] ?? []; unset($data['bands']);

        if ($data['grading_basis'] !== 'PASS_FAIL' && count($bands) === 0) {
            throw ValidationException::withMessages(['bands'=>'At least one Grade Band is required for Letter Grade / Grade Point grading.']);
        }
        if ($data['grading_basis'] === 'GRADE_POINT' && $data['maximum_grade_point'] === null) {
            throw ValidationException::withMessages(['maximum_grade_point'=>'Maximum Grade Point is required for Grade Point grading.']);
        }
        foreach ($bands as $i=>$band) {
            if ((float)$band['minimum_percent'] > (float)$band['maximum_percent']) {
                throw ValidationException::withMessages(["bands.$i.minimum_percent"=>'Minimum % cannot exceed Maximum %.']);
            }
            if ($data['grading_basis'] === 'GRADE_POINT' && $band['grade_point'] === null) {
                throw ValidationException::withMessages(["bands.$i.grade_point"=>'Grade Point is required for Grade Point grading.']);
            }
            if ($data['maximum_grade_point'] !== null && $band['grade_point'] !== null && (float)$band['grade_point'] > (float)$data['maximum_grade_point']) {
                throw ValidationException::withMessages(["bands.$i.grade_point"=>'Grade Point cannot exceed Maximum Grade Point.']);
            }
        }
        $sorted=$bands; usort($sorted,fn($a,$b)=>(float)$a['minimum_percent'] <=> (float)$b['minimum_percent']);
        for($i=1;$i<count($sorted);$i++) {
            if ((float)$sorted[$i]['minimum_percent'] <= (float)$sorted[$i-1]['maximum_percent']) {
                throw ValidationException::withMessages(['bands'=>'Grade percentage ranges cannot overlap.']);
            }
        }

        DB::transaction(function() use($academicPolicy,$data,$bands,$request) {
            $existing=AcademicPolicyGradingRule::where('academic_policy_id',$academicPolicy->id)->first();
            $before=['rule'=>$existing?->toArray(),'bands'=>$academicPolicy->gradeBands()->get()->toArray()];
            $rule=AcademicPolicyGradingRule::updateOrCreate(['academic_policy_id'=>$academicPolicy->id],[
                ...$data,'created_by'=>$existing?->created_by ?? $request->user()->id,'updated_by'=>$request->user()->id
            ]);
            $academicPolicy->gradeBands()->delete();
            foreach(array_values($bands) as $i=>$band) {
                AcademicPolicyGradeBand::create([...$band,'academic_policy_id'=>$academicPolicy->id,'display_order'=>$i+1]);
            }
            $this->policyService->clearValidationCheckpoint($academicPolicy);
            DB::table('audit_logs')->insert([
                'event'=>$existing?'ACADEMIC_POLICY_GRADING_UPDATED':'ACADEMIC_POLICY_GRADING_CREATED',
                'resource_type'=>'academic_policy_grading_rule','resource_id'=>$rule->id,'before'=>json_encode($before),
                'after'=>json_encode(['rule'=>$rule->fresh()->toArray(),'bands'=>$academicPolicy->gradeBands()->get()->toArray()]),
                'actor_user_id'=>$request->user()->id,'ip_address'=>$request->ip(),'created_at'=>now(),
            ]);
        });
        return back()->with('success','Grading Policy saved successfully.');
    }
}
