<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\College;
use App\Models\FeeDemandItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Authoritative enrollment Fee Clearance projection.
 *
 * Clearance is deliberately derived from the immutable demand-item clearance
 * snapshot plus the authoritative Student Fee Ledger. No mutable "cleared"
 * flag is stored, so a later refund/reversal/debit immediately re-opens the gate.
 */
class FeeClearanceService
{
    public function register(College $college, array $filters): LengthAwarePaginator
    {
        $sessionId = (int) ($filters['session_id'] ?? 0);
        $offeringId = (int) ($filters['offering_id'] ?? 0);
        $search = trim((string) ($filters['q'] ?? ''));
        $status = strtoupper(trim((string) ($filters['status'] ?? '')));
        $perPage = (int) ($filters['per_page'] ?? 25);
        if (! in_array($perPage, [25, 50, 100], true)) $perPage = 25;

        $query = Admission::query()
            ->with(['application:id,candidate_name,application_no,email,phone','intake.offering.programTemplate:id,name,code','intake.offering.academicSession:id,name,code'])
            ->where('college_id', $college->id)
            ->where('status', 'CONFIRMED')
            ->whereHas('feeDemands', fn ($q) => $q
                ->when($sessionId > 0, fn ($x) => $x->where('academic_session_id', $sessionId))
                ->when($offeringId > 0, fn ($x) => $x->where('college_program_offering_id', $offeringId)))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('admission_no', 'like', '%'.$search.'%')->orWhereHas('application', function ($a) use ($search) {
                        $a->where('candidate_name', 'like', '%'.$search.'%')->orWhere('application_no', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%');
                    });
                });
            })->orderByDesc('id');

        if (in_array($status, ['CLEARED','PENDING','NOT_REQUIRED'], true)) {
            $all = $query->get()->map(fn (Admission $a) => $this->forAdmission($college, $a, $sessionId, $offeringId))->where('status', $status)->values();
            $currentPage = max((int) request()->query('page', 1), 1);
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $all->forPage($currentPage, $perPage)->values(), $all->count(), $perPage, $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }
        $page = $query->paginate($perPage)->withQueryString();
        $page->setCollection($page->getCollection()->map(fn (Admission $a) => $this->forAdmission($college, $a, $sessionId, $offeringId)));
        return $page;
    }

    public function forAdmission(College $college, Admission $admission, int $sessionId = 0, int $offeringId = 0): array
    {
        abort_unless((int) $admission->college_id === (int) $college->id, 404);
        $ledger = app(FeeLedgerService::class)->ledger($college, $admission, $sessionId);

        $requiredItems = FeeDemandItem::query()->join('fee_demands as d', 'd.id', '=', 'fee_demand_items.fee_demand_id')
            ->where('d.college_id', $college->id)->where('d.admission_id', $admission->id)->where('d.status', '!=', 'CANCELLED')
            ->when($sessionId > 0, fn ($q) => $q->where('d.academic_session_id', $sessionId))
            ->when($offeringId > 0, fn ($q) => $q->where('d.college_program_offering_id', $offeringId))
            ->where('fee_demand_items.is_enrollment_clearance_required', true)
            ->get(['fee_demand_items.id','fee_demand_items.fee_demand_id','fee_demand_items.fee_head_name','fee_demand_items.fee_head_code','fee_demand_items.amount','d.demand_no','d.currency']);

        $requiredIds = $requiredItems->pluck('id')->map(fn ($id) => (int) $id)->all();
        $netByItem = array_fill_keys($requiredIds, 0.0);
        foreach ($ledger['entries'] as $entry) {
            $itemId = (int) $entry['fee_demand_item_id'];
            if (array_key_exists($itemId, $netByItem)) $netByItem[$itemId] = round($netByItem[$itemId] + (float) $entry['debit'] - (float) $entry['credit'], 2);
        }

        $items = $requiredItems->map(function ($item) use ($netByItem) {
            $outstanding = max(round((float) ($netByItem[(int) $item->id] ?? 0), 2), 0);
            return ['fee_demand_item_id'=>(int)$item->id,'fee_demand_id'=>(int)$item->fee_demand_id,'demand_no'=>$item->demand_no,'fee_head_name'=>$item->fee_head_name,'fee_head_code'=>$item->fee_head_code,'amount'=>number_format((float)$item->amount,2,'.',''),'outstanding'=>number_format($outstanding,2,'.',''),'cleared'=>$outstanding <= 0.009];
        })->values();

        $requiredAmount = round((float) $requiredItems->sum('amount'), 2);
        $outstanding = round((float) $items->sum(fn ($x) => (float) $x['outstanding']), 2);
        $status = $requiredItems->isEmpty() ? 'NOT_REQUIRED' : ($outstanding <= 0.009 ? 'CLEARED' : 'PENDING');
        $student = $ledger['student'];

        return array_merge($student, [
            'currency'=>(string)($requiredItems->first()?->currency ?: $ledger['summary']['currency']),
            'required_amount'=>number_format($requiredAmount,2,'.',''),
            'clearance_outstanding'=>number_format($outstanding,2,'.',''),
            'required_item_count'=>$requiredItems->count(),
            'cleared_item_count'=>$items->where('cleared', true)->count(),
            'status'=>$status,
            'is_cleared'=>in_array($status, ['CLEARED','NOT_REQUIRED'], true),
            'items'=>$items->all(),
        ]);
    }
}
