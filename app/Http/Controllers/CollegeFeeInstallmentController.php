<?php
namespace App\Http\Controllers;

use App\Models\College;
use App\Models\FeeDemand;
use App\Models\FeeDemandItem;
use App\Models\FeeInstallmentSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CollegeFeeInstallmentController extends Controller
{
    public function store(Request $request, College $college, FeeDemand $demand, FeeDemandItem $item): RedirectResponse
    {
        $this->authorizeManage($request, $college);
        abort_unless((int)$demand->college_id === (int)$college->id && (int)$item->fee_demand_id === (int)$demand->id, 404);
        $this->assertSchedulable($demand, $item);
        $rows=$this->validateAmountRows($request, $this->netPayable($item));
        $this->replaceSchedule($request, $demand, $item, $rows, 'INDIVIDUAL');
        return back()->with('success','Installment schedule saved. Gross Fee Demand remains unchanged.');
    }

    public function bulkPreview(Request $request, College $college): JsonResponse
    {
        $this->authorizeManage($request, $college);
        $data=$this->validateBulkScope($request);
        $items=$this->bulkItems($college, $data);
        return response()->json([
            'data'=>$items->map(fn(FeeDemandItem $item)=>$this->bulkItemPayload($item))->values(),
            'summary'=>[
                'scanned'=>$items->count(),
                'eligible'=>$items->filter(fn($i)=>$this->bulkEligibility($i)===null)->count(),
                'existing_schedule'=>$items->filter(fn($i)=>$i->installmentSchedules->isNotEmpty())->count(),
                'blocked_collection_started'=>$items->filter(fn($i)=>(float)$i->demand->paid_amount>0)->count(),
            ],
        ]);
    }

    public function bulkStore(Request $request, College $college): RedirectResponse
    {
        $this->authorizeManage($request, $college);
        $data=$this->validateBulkScope($request)+$request->validate([
            'fee_head_code'=>['required','string','max:100'],
            'selected_item_ids'=>['required','array','min:1','max:1000'],
            'selected_item_ids.*'=>['integer'],
            'installments'=>['required','array','min:2','max:24'],
            'installments.*.percentage'=>['required','numeric','gt:0','lte:100'],
            'installments.*.due_date'=>['required','date'],
        ]);
        $rows=collect($data['installments']);
        if(abs((float)$rows->sum(fn($r)=>(float)$r['percentage'])-100)>0.009)
            throw ValidationException::withMessages(['installments'=>'Bulk installment percentages must total exactly 100%.']);
        $dates=$rows->pluck('due_date')->values();
        for($i=1;$i<$dates->count();$i++) if($dates[$i]<$dates[$i-1]) throw ValidationException::withMessages(['installments'=>'Installment due dates must be in chronological order.']);

        $allowed=$this->bulkItems($college,$data)->where('fee_head_code',$data['fee_head_code'])->keyBy('id');
        $selected=collect($data['selected_item_ids'])->unique();
        $created=0;$skipped=0;$errors=[];
        foreach($selected as $id){
            $item=$allowed->get((int)$id);
            if(!$item){$skipped++;continue;}
            $reason=$this->bulkEligibility($item);
            if($reason){$skipped++;$errors[]=$item->demand->demand_no.': '.$reason;continue;}
            $net=$this->netPayable($item);
            $amountRows=[];$allocated=0.0;
            foreach($rows->values() as $i=>$row){
                $amount=$i===$rows->count()-1 ? round($net-$allocated,2) : round($net*((float)$row['percentage']/100),2);
                $allocated+=$amount;
                $amountRows[]=['amount'=>number_format($amount,2,'.',''),'due_date'=>$row['due_date']];
            }
            try{$this->replaceSchedule($request,$item->demand,$item,collect($amountRows),'BULK');$created++;}
            catch(\Throwable $e){$skipped++;$errors[]=$item->demand->demand_no.': '.$e->getMessage();}
        }
        return back()->with('toast',['type'=>$errors?'warning':'success','message'=>"Bulk installment schedule applied to {$created} student demand(s). {$skipped} skipped.".($errors?' '.implode(' | ',array_slice($errors,0,3)):'' )]);
    }

    private function validateBulkScope(Request $request): array
    {
        return $request->validate([
            'offering_id'=>['required','integer'],
            'purpose'=>['required',Rule::in(['ADMISSION_INITIAL','ACADEMIC','EXAMINATION','OTHER'])],
            'basis_group'=>['required',Rule::in(['MIXED','TERM','ACADEMIC_YEAR','ONE_TIME'])],
            'period_no'=>['required','integer','min:1','max:50'],
        ]);
    }

    private function bulkItems(College $college,array $data)
    {
        /*
         * ADR 152:
         * Do not match installment candidates only against the raw Fee Demand
         * context columns. Older demands can legitimately have NULL context
         * columns because those columns were introduced after Fee Demand itself.
         * The Installment Scope selector already normalizes those records, so
         * candidate loading must use the exact same normalization.
         */
        $purposeExpr = $this->normalizedPurposeExpression();
        $basisExpr = $this->normalizedBasisExpression();
        $periodExpr = 'COALESCE(d.billing_period_no, item.source_period_no, 1)';

        $itemIds = DB::table('fee_demand_items as item')
            ->join('fee_demands as d', 'd.id', '=', 'item.fee_demand_id')
            ->where('d.college_id', $college->id)
            ->where('d.college_program_offering_id', $data['offering_id'])
            ->where('d.status', '!=', 'CANCELLED')
            ->where('item.installment_allowed', true)
            ->whereRaw($purposeExpr.' = ?', [$data['purpose']])
            ->whereRaw($basisExpr.' = ?', [$data['basis_group']])
            ->whereRaw($periodExpr.' = ?', [(int) $data['period_no']])
            ->pluck('item.id');

        if ($itemIds->isEmpty()) {
            return collect();
        }

        return FeeDemandItem::query()->with([
            'demand.admission.application.academicPreference.discipline:id,name,code',
            'installmentSchedules'=>fn($q)=>$q->where('status','ACTIVE'),
        ])->whereIn('id',$itemIds)
          ->orderBy('fee_head_name')->orderBy('id')->get();
    }

    private function normalizedPurposeExpression(): string
    {
        return "CASE
            WHEN d.demand_context IS NOT NULL AND TRIM(d.demand_context) <> '' THEN UPPER(d.demand_context)
            WHEN UPPER(COALESCE(d.generation_mode,'')) IN ('ADMISSION_AUTO','MANUAL_RECOVERY') THEN 'ADMISSION_INITIAL'
            WHEN UPPER(COALESCE(item.purpose,'')) IN ('ADMISSION','ADMISSION_INITIAL') THEN 'ADMISSION_INITIAL'
            WHEN UPPER(COALESCE(item.purpose,'')) = 'ACADEMIC' THEN 'ACADEMIC'
            WHEN UPPER(COALESCE(item.purpose,'')) = 'EXAMINATION' THEN 'EXAMINATION'
            ELSE 'OTHER'
        END";
    }

    private function normalizedBasisExpression(): string
    {
        return "CASE
            WHEN (".$this->normalizedPurposeExpression().") = 'ADMISSION_INITIAL' THEN 'MIXED'
            WHEN d.billing_basis_group IS NOT NULL AND TRIM(d.billing_basis_group) <> '' THEN UPPER(d.billing_basis_group)
            WHEN UPPER(COALESCE(item.charge_basis,'')) IN ('PER_TERM','SPECIFIC_TERM') THEN 'TERM'
            WHEN UPPER(COALESCE(item.charge_basis,'')) IN ('PER_ACADEMIC_YEAR','SPECIFIC_ACADEMIC_YEAR') THEN 'ACADEMIC_YEAR'
            ELSE 'ONE_TIME'
        END";
    }

    private function bulkItemPayload(FeeDemandItem $item): array
    {
        $d=$item->demand;$a=$d->admission?->application;$disc=$a?->academicPreference?->discipline;
        return ['item_id'=>$item->id,'demand_id'=>$d->id,'demand_no'=>$d->demand_no,'admission_no'=>$d->admission?->admission_no,'application_no'=>$a?->application_no,'candidate_name'=>$a?->candidate_name,'discipline'=>$disc?->name??'General / No Discipline','fee_head_code'=>$item->fee_head_code,'fee_head_name'=>$item->fee_head_name,'gross_amount'=>$item->amount,'net_payable_amount'=>number_format($this->netPayable($item),2,'.',''),'has_existing_schedule'=>$item->installmentSchedules->isNotEmpty(),'eligible'=>$this->bulkEligibility($item)===null,'reason'=>$this->bulkEligibility($item)];
    }

    private function bulkEligibility(FeeDemandItem $item): ?string
    {
        if($item->demand->status==='CANCELLED') return 'Demand is cancelled.';
        if((float)$item->demand->paid_amount>0) return 'Collection has started; bulk replacement is blocked.';
        if($this->netPayable($item)<=0) return 'No net payable amount remains.';
        return null;
    }

    private function netPayable(FeeDemandItem $item): float
    {
        $benefit=(float)DB::table('fee_student_benefit_items as bi')->join('fee_student_benefits as b','b.id','=','bi.fee_student_benefit_id')->where('b.status','APPROVED')->where('bi.fee_demand_item_id',$item->id)->sum('bi.sanctioned_amount');
        return max(0,round((float)$item->amount-$benefit,2));
    }

    private function assertSchedulable(FeeDemand $demand, FeeDemandItem $item): void
    {
        if(!$item->installment_allowed) throw ValidationException::withMessages(['installments'=>'Installment is not allowed for this Fee Demand item.']);
        if($demand->status==='CANCELLED') throw ValidationException::withMessages(['installments'=>'A cancelled Fee Demand cannot receive an installment schedule.']);
        if((float)$demand->paid_amount>0) throw ValidationException::withMessages(['installments'=>'Installment schedule cannot be replaced after collection has started.']);
    }

    private function validateAmountRows(Request $request,float $net)
    {
        $data=$request->validate(['installments'=>['required','array','min:2','max:24'],'installments.*.amount'=>['required','numeric','gt:0','max:999999999.99'],'installments.*.due_date'=>['required','date']]);
        $rows=collect($data['installments']);$dates=$rows->pluck('due_date')->values();
        for($i=1;$i<$dates->count();$i++) if($dates[$i]<$dates[$i-1]) throw ValidationException::withMessages(['installments'=>'Installment due dates must be in chronological order.']);
        if(abs((float)$rows->sum(fn($r)=>(float)$r['amount'])-$net)>0.009) throw ValidationException::withMessages(['installments'=>'Installment total must equal the current net payable amount '.number_format($net,2,'.','').' for this Fee Head.']);
        return $rows;
    }

    private function replaceSchedule(Request $request,FeeDemand $demand,FeeDemandItem $item,$rows,string $mode): void
    {
        DB::transaction(function()use($request,$demand,$item,$rows,$mode){
            $activeSchedules=FeeInstallmentSchedule::where('fee_demand_item_id',$item->id)->where('status','ACTIVE')->get();
            $before=$activeSchedules->toArray();
            if (\Illuminate\Support\Facades\Schema::hasTable('fee_late_fine_charges') && $activeSchedules->isNotEmpty()) {
                DB::table('fee_late_fine_charges')->whereIn('fee_installment_schedule_id',$activeSchedules->pluck('id'))->where('status','ACTIVE')->update(['status'=>'REVERSED','superseded_at'=>now(),'updated_at'=>now()]);
            }
            FeeInstallmentSchedule::where('fee_demand_item_id',$item->id)->where('status','ACTIVE')->update(['status'=>'CANCELLED','cancelled_by'=>$request->user()->id,'cancelled_at'=>now(),'cancellation_reason'=>'Replaced before collection via '.$mode,'updated_at'=>now()]);
            $created=[];foreach($rows->values() as $i=>$row)$created[]=FeeInstallmentSchedule::create(['fee_demand_id'=>$demand->id,'fee_demand_item_id'=>$item->id,'installment_no'=>$i+1,'amount'=>$row['amount'],'due_date'=>$row['due_date'],'status'=>'ACTIVE','created_by'=>$request->user()->id])->toArray();
            DB::table('audit_logs')->insert(['actor_user_id'=>$request->user()->id,'event'=>$mode==='BULK'?'FEE_INSTALLMENT_BULK_SCHEDULE_SET':'FEE_INSTALLMENT_SCHEDULE_SET','resource_type'=>'fee_demand_item','resource_id'=>$item->id,'before'=>json_encode($before),'after'=>json_encode(['mode'=>$mode,'installments'=>$created]),'ip_address'=>$request->ip(),'created_at'=>now()]);
        });
    }

    private function authorizeManage(Request $request,College $college): void { abort_unless($request->user()->hasCollegePermission('college_fee_installment.manage',$college->id),403); }
}
