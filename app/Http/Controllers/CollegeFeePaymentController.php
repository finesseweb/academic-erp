<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\College;
use App\Models\FeeDemand;
use App\Services\FeeDueGroupingService;
use App\Services\FeePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeFeePaymentController extends Controller
{
    public function index(Request $request, College $college, FeeDueGroupingService $grouping, FeePaymentService $payments): Response
    {
        $this->auth($request,$college,'college_fee_payment.view');

        $sessions=AcademicSession::query()->where('university_id',$college->university_id)->where('status','ACTIVE')
            ->orderByDesc('is_current')->orderByDesc('starts_on')->get(['id','name','code','is_current']);
        $current=(int)($sessions->firstWhere('is_current',true)?->id??$sessions->first()?->id??0);
        $sessionId=(int)$request->query('session_id',$current);
        $q=trim((string)$request->query('q',''));
        $perPage=(int)$request->query('per_page',25); if(!in_array($perPage,[25,50,100],true))$perPage=25;

        // ADR 173: paginate students/admissions, not individual demands. This prevents one student
        // from flooding the register and keeps all of that student's demands together on one page.
        $studentPage=FeeDemand::query()->select('admission_id')->where('college_id',$college->id)->where('status','!=','CANCELLED')
            ->when($sessionId>0,fn($x)=>$x->where('academic_session_id',$sessionId))
            ->when($q!=='',function($x)use($q){$x->where(function($y)use($q){$y->where('demand_no','like','%'.$q.'%')
                ->orWhereHas('admission',fn($a)=>$a->where('admission_no','like','%'.$q.'%')->orWhereHas('application',fn($ap)=>$ap->where('candidate_name','like','%'.$q.'%')->orWhere('application_no','like','%'.$q.'%')));});})
            ->groupBy('admission_id')->orderByDesc('admission_id')->paginate($perPage)->withQueryString();

        $admissionIds=$studentPage->getCollection()->pluck('admission_id')->map(fn($id)=>(int)$id)->all();
        $demands=FeeDemand::query()->with([
            'admission.application:id,candidate_name,application_no,date_of_birth',
            'items.installmentSchedules'=>fn($x)=>$x->where('status','ACTIVE'),
            'studentBenefits'=>fn($x)=>$x->where('status','APPROVED')->with('items'),
        ])->whereIn('admission_id',$admissionIds)->where('college_id',$college->id)->where('status','!=','CANCELLED')
            ->when($sessionId>0,fn($x)=>$x->where('academic_session_id',$sessionId))->orderBy('admission_id')->orderBy('billing_period_no')->orderBy('id')->get();

        $students=$demands->groupBy('admission_id')->map(function($studentDemands)use($grouping,$payments){
            $first=$studentDemands->first();
            $rows=$studentDemands->map(function(FeeDemand $d)use($grouping,$payments){
                $groups=$grouping->forDemand($d);$fine=$payments->outstandingFineBreakdown($d);
                $mandatory=(float)collect($groups)->sum(fn($g)=>(float)$g['mandatory_due']);
                $optional=(float)collect($groups)->sum(fn($g)=>(float)$g['optional_due']);
                $today=now()->toDateString();
                $mandatoryDueNow=(float)collect($groups)->where('due_date','<=',$today)->sum(fn($g)=>(float)$g['mandatory_due']);
                $optionalDueNow=(float)collect($groups)->where('due_date','<=',$today)->sum(fn($g)=>(float)$g['optional_due']);
                $futureGroups=collect($groups)->filter(fn($g)=>$g['due_date']>$today && (float)$g['combined_available']>0)->sortBy('due_date')->values();
                $nextDueDate=$futureGroups->first()['due_date']??null;
                $nextDueAmount=$nextDueDate ? (float)$futureGroups->where('due_date',$nextDueDate)->sum(fn($g)=>(float)$g['combined_available']) : 0.0;
                return [
                    'id'=>$d->id,'demand_no'=>$d->demand_no,'currency'=>$d->currency,'status'=>$d->status,
                    'billing_period_label'=>$d->billing_period_label,'total_amount'=>$d->total_amount,'paid_amount'=>$d->paid_amount,
                    'adjusted_amount'=>$d->adjusted_amount,'outstanding_amount'=>$d->outstanding_amount,
                    'due_groups'=>$groups,'mandatory_due_total'=>number_format($mandatory,2,'.',''),'optional_due_total'=>number_format($optional,2,'.',''),
                    'mandatory_due_now'=>number_format($mandatoryDueNow,2,'.',''),'optional_due_now'=>number_format($optionalDueNow,2,'.',''),
                    'late_fine_mandatory'=>number_format($fine['mandatory'],2,'.',''),'late_fine_optional'=>number_format($fine['optional'],2,'.',''),
                    'collectable_default'=>number_format($mandatoryDueNow+$fine['mandatory'],2,'.',''),
                    'collectable_all'=>number_format($mandatory+$optional+$fine['total'],2,'.',''),
                    'next_due_date'=>$nextDueDate,'next_due_amount'=>number_format($nextDueAmount,2,'.',''),
                ];
            })->values();
            $nextDates=$rows->pluck('next_due_date')->filter()->sort()->values();
            $studentNextDate=$nextDates->first();
            $studentNextAmount=$studentNextDate ? (float)$rows->where('next_due_date',$studentNextDate)->sum(fn($d)=>(float)$d['next_due_amount']) : 0.0;
            return [
                'admission_id'=>(int)$first->admission_id,'candidate_name'=>$first->admission?->application?->candidate_name,
                'application_no'=>$first->admission?->application?->application_no,'admission_no'=>$first->admission?->admission_no,
                'currency'=>$first->currency ?: 'INR','demand_count'=>$rows->count(),
                'principal_outstanding'=>number_format((float)$rows->sum(fn($d)=>(float)$d['outstanding_amount']),2,'.',''),
                'late_fine_outstanding'=>number_format((float)$rows->sum(fn($d)=>(float)$d['late_fine_mandatory']+(float)$d['late_fine_optional']),2,'.',''),
                'next_due_date'=>$studentNextDate,'next_due_amount'=>number_format($studentNextAmount,2,'.',''),
                'demands'=>$rows,
            ];
        })->values();
        $studentPage->setCollection($students);

        $paymentRows=$payments->paymentRows($college,['session_id'=>$sessionId,'q'=>$q])->paginate(25,'*','payment_page')->withQueryString();

        return Inertia::render('college-fee-payments/index',[
            'college'=>$college->only(['id','name','code']),
            'sessions'=>$sessions->map(fn($s)=>['id'=>$s->id,'name'=>$s->name,'code'=>$s->code,'is_current'=>(bool)$s->is_current])->values(),
            'students'=>$studentPage,'payments'=>$paymentRows,
            'filters'=>['session_id'=>$sessionId,'q'=>$q,'per_page'=>$perPage],
            'can'=>['collect'=>$request->user()->hasCollegePermission('college_fee_payment.collect',$college->id)],
        ]);
    }

    public function store(Request $request, College $college, FeePaymentService $service): RedirectResponse
    {
        $this->auth($request,$college,'college_fee_payment.collect');
        $data=$request->validate([
            'demand_id'=>['required','integer',Rule::exists('fee_demands','id')->where(fn($q)=>$q->where('college_id',$college->id)->where('status','!=','CANCELLED'))],
            'amount'=>['required','numeric','gt:0'],
            'payment_date'=>['required','date'],
            'payment_mode'=>['required',Rule::in(['CASH','CARD','UPI','BANK_TRANSFER','CHEQUE','OTHER'])],
            'reference_no'=>['nullable','string','max:120'],
            'include_optional'=>['sometimes','boolean'],'include_late_fine'=>['sometimes','boolean'],'include_future'=>['sometimes','boolean'],
            'notes'=>['nullable','string','max:1000'],
        ]);
        $payment=$service->collect($college,FeeDemand::findOrFail((int)$data['demand_id']),$data,$request->user()->id,$request->ip());
        return back()->with('toast',['type'=>'success','message'=>'Payment posted. Receipt '.$payment->receipt_no.' · '.number_format((float)$payment->amount,2).'.']);
    }

    private function auth(Request $request, College $college, string $permission): void
    { abort_unless($request->user()->hasCollegePermission($permission,$college->id),403); }
}
