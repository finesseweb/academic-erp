import { Head, router } from '@inertiajs/react';
import { CheckCircle2, CircleX, RefreshCw, ShieldCheck, TicketCheck } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type Rule = {
    id:number; name:string; code:string; version_no:number; status:string;
    bucket_key:string; bucket_label:string; basis_capacity:number;
    program_name:string|null; program_code:string|null; degree_level_name:string|null;
    degree_name:string|null; session_name:string|null;
    reservation_plan_id:number|null; reservation_plan_status:string|null;
};
type PhysicalOption = { category_id:number; code:string; name:string; capacity:number; used:number; remaining:number };
type HorizontalOption = { category_id:number; code:string; name:string; target:number; fulfilled:number; remaining_target:number; actual_candidates:number };
type Capacity = {
    basis_capacity:number; total_used:number;
    open:{capacity:number;used:number;remaining:number};
    vertical:PhysicalOption[]; horizontal:HorizontalOption[];
    has_reservation_plan:boolean; reservation_plan_id:number|null; reservation_plan_status:string|null;
};
type Allocation = {
    id:number; status:'ALLOCATED'|'CANCELLED'; physical_seat_type:'OPEN'|'RESERVED';
    physical_category_code:string|null; physical_category_name:string|null; allocation_round:number;
    decision_note:string|null; allocated_at:string|null; cancellation_reason:string|null;
    admission_status:'CONFIRMED'|'REVOKED'|null;
    horizontal_categories:{id:number;code:string;name:string;fulfills_target:boolean}[];
};
type Row = {
    merit_entry_id:number; rank:number; final_weighted_score:number; application_id:number; choice_id:number;
    preference_no:number; application_no:string; candidate_name:string;
    document_verification:{id:number;status:'PENDING'|'VERIFIED'|'DEFICIENT';finalized_at:string|null}|null;
    discipline_name:string|null; discipline_code:string|null; specialization_name:string|null; specialization_code:string|null;
    allocation:Allocation|null;
};
type Screen = { summary:{roster_count:number;allocated_count:number;cancelled_count:number;remaining_physical_seats:number}; capacity:Capacity; rows:Row[] }|null;
type Props = { college:{id:number;name:string;code:string;status:string}; rules:Rule[]; selectedRuleId:number|null; screen:Screen; can:{allocate:boolean;cancel:boolean} };

function AllocationDialog({collegeId,row,capacity}:{collegeId:number;row:Row;capacity:Capacity}) {
    const [open,setOpen]=useState(false);
    const [physical,setPhysical]=useState(row.allocation?.physical_seat_type==='RESERVED' ? String(capacity.vertical.find(v=>v.code===row.allocation?.physical_category_code)?.category_id??'OPEN') : 'OPEN');
    const [horizontal,setHorizontal]=useState<number[]>(row.allocation?.horizontal_categories.map(x=>x.id)??[]);
    const [targets,setTargets]=useState<number[]>(row.allocation?.horizontal_categories.filter(x=>x.fulfills_target).map(x=>x.id)??[]);
    const [round,setRound]=useState(String(row.allocation?.allocation_round??1));
    const [note,setNote]=useState(row.allocation?.decision_note??'');
    const [processing,setProcessing]=useState(false);

    const toggleHorizontal=(id:number,checked:boolean)=>{
        setHorizontal(current=>checked?[...new Set([...current,id])]:current.filter(x=>x!==id));
        if(!checked) setTargets(current=>current.filter(x=>x!==id));
    };
    const toggleTarget=(id:number,checked:boolean)=>setTargets(current=>checked?[...new Set([...current,id])]:current.filter(x=>x!==id));
    const submit=()=>{
        setProcessing(true);
        router.post(`/college/${collegeId}/admission-seat-allocations/merit/${row.merit_entry_id}`,{
            physical_reservation_category_id:physical==='OPEN'?null:Number(physical),
            horizontal_category_ids:horizontal,
            horizontal_target_category_ids:targets,
            allocation_round:Number(round||1),
            decision_note:note||null,
        },{preserveScroll:true,onSuccess:()=>setOpen(false),onFinish:()=>setProcessing(false)});
    };

    return <Dialog open={open} onOpenChange={setOpen}>
        <DialogTrigger asChild><Button size="sm" variant={row.allocation?.status==='CANCELLED'?'outline':'default'}>{row.allocation?.status==='CANCELLED'?<RefreshCw/>:<TicketCheck/>}{row.allocation?.status==='CANCELLED'?'Re-allocate':'Allocate Seat'}</Button></DialogTrigger>
        <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader><DialogTitle>{row.allocation?.status==='CANCELLED'?'Re-allocate':'Allocate'} Seat · Rank #{row.rank}</DialogTitle><DialogDescription>{row.candidate_name} · {row.application_no}. The physical seat is consumed only after backend capacity and reservation validation.</DialogDescription></DialogHeader>
            <div className="space-y-5 py-2">
                <div className="space-y-2"><Label>Physical seat category</Label><Select value={physical} onValueChange={setPhysical}><SelectTrigger><SelectValue/></SelectTrigger><SelectContent><SelectItem value="OPEN" disabled={capacity.open.remaining<=0}>Open / Unreserved · {capacity.open.remaining} remaining of {capacity.open.capacity}</SelectItem>{capacity.vertical.map(v=><SelectItem key={v.category_id} value={String(v.category_id)} disabled={v.remaining<=0}>{v.name} ({v.code}) · {v.remaining} remaining of {v.capacity}</SelectItem>)}</SelectContent></Select><p className="text-xs text-muted-foreground">One candidate consumes exactly one physical seat. Horizontal quota never creates a second physical seat.</p></div>
                <div className="space-y-3"><div><Label>Applicable Horizontal quota</Label><p className="text-xs text-muted-foreground">Select verified applicable categories. Mark “Count toward target” only when this allotment should fulfil one required horizontal target.</p></div>{capacity.horizontal.length===0?<div className="rounded-md border p-3 text-sm text-muted-foreground">No Horizontal quota is configured for this seat bucket.</div>:capacity.horizontal.map(h=><div key={h.category_id} className="rounded-md border p-3"><div className="flex items-start justify-between gap-3"><label className="flex items-center gap-2 text-sm font-medium"><Checkbox checked={horizontal.includes(h.category_id)} onCheckedChange={v=>toggleHorizontal(h.category_id,v===true)}/>{h.name} ({h.code})</label><span className="text-xs text-muted-foreground">Target {h.fulfilled}/{h.target} · Actual {h.actual_candidates}</span></div>{horizontal.includes(h.category_id)&&<label className="mt-3 flex items-center gap-2 text-xs"><Checkbox checked={targets.includes(h.category_id)} disabled={h.remaining_target<=0&&!targets.includes(h.category_id)} onCheckedChange={v=>toggleTarget(h.category_id,v===true)}/>Count this candidate toward the required target {h.remaining_target<=0?'(target already fulfilled)':''}</label>}</div>)}</div>
                <div className="grid gap-4 sm:grid-cols-2"><div className="space-y-2"><Label>Allocation round</Label><Input type="number" min={1} max={999} value={round} onChange={e=>setRound(e.target.value)}/></div><div className="space-y-2 sm:col-span-2"><Label>Decision / counselling note <span className="text-muted-foreground">(optional)</span></Label><Textarea value={note} onChange={e=>setNote(e.target.value)} placeholder="Reference counselling round, verified category document, policy decision or special reason where needed."/></div></div>
            </div>
            <DialogFooter><Button type="button" variant="outline" onClick={()=>setOpen(false)} disabled={processing}>Cancel</Button><Button type="button" onClick={submit} disabled={processing}>{processing?'Allocating…':'Confirm Seat Allocation'}</Button></DialogFooter>
        </DialogContent>
    </Dialog>;
}

function CancelButton({collegeId,row}:{collegeId:number;row:Row}) {
    const [processing,setProcessing]=useState(false);
    const blockedByConfirmedAdmission=row.allocation?.admission_status==='CONFIRMED';
    const cancel=()=>{
        if(processing||blockedByConfirmedAdmission) return;
        const reason=window.prompt('Reason for cancelling this seat allocation:');
        if(!reason?.trim()) return;
        setProcessing(true);
        router.patch(`/college/${collegeId}/admission-seat-allocations/${row.allocation?.id}/cancel`,{reason:reason.trim()},{
            preserveScroll:true,
            onError:(errors)=>{
                const message=Object.values(errors??{})[0];
                window.alert(message ? String(message) : 'Seat allocation could not be cancelled. Please review the admission status and try again.');
            },
            onFinish:()=>setProcessing(false),
        });
    };
    return <Button
        size="sm"
        variant="outline"
        onClick={cancel}
        disabled={processing||blockedByConfirmedAdmission}
        title={blockedByConfirmedAdmission?'Admission is CONFIRMED. Revoke the admission before cancelling this seat allocation.':undefined}
    ><CircleX/>{processing?'Cancelling…':'Cancel Allocation'}</Button>;
}

export default function CollegeAdmissionSeatAllocations({college,rules,selectedRuleId,screen,can}:Props) {
    const selected=useMemo(()=>rules.find(r=>r.id===selectedRuleId)??null,[rules,selectedRuleId]);
    const choose=(id:number)=>router.get(`/college/${college.id}/admission-seat-allocations`,{rule_id:id},{preserveState:true,replace:true,preserveScroll:true});
    return <><Head title="Seat Allocation / Consumption"/><div className="space-y-6 p-4 md:p-6">
        <header className="border-b pb-5"><p className="text-sm font-medium text-primary">{college.code} · Admission Processing</p><h1 className="text-3xl font-semibold">Seat Allocation / Consumption</h1><p className="max-w-5xl text-sm text-muted-foreground">Consume the generated Merit / Roster against the exact Intake seat bucket and locked Reservation Plan. Vertical reservation consumes one physical seat; Horizontal quota is tracked separately and never increases capacity.</p></header>

        {rules.length===0?<Card><CardContent className="p-8 text-center text-sm text-muted-foreground">No generated Merit / Roster is available yet. Generate and lock a roster before Seat Allocation.</CardContent></Card>:<div className="grid gap-3 lg:grid-cols-3">{rules.map(rule=><button key={rule.id} onClick={()=>choose(rule.id)} className={`rounded-lg border p-4 text-left transition ${rule.id===selectedRuleId?'border-primary bg-primary/5':'hover:bg-muted/30'}`}><div className="flex items-start justify-between gap-3"><div><div className="font-semibold">{rule.code} V{rule.version_no}</div><div className="text-xs text-muted-foreground">{rule.name}</div></div><span className="rounded-full border px-2 py-0.5 text-xs">{rule.status}</span></div><div className="mt-3 text-sm">{rule.program_name??'Program'} · {rule.session_name??'Session'}</div><div className="mt-1 text-xs text-muted-foreground">{rule.bucket_label} · Capacity {rule.basis_capacity}</div></button>)}</div>}

        {selected&&screen&&<>
            <Card><CardHeader><CardTitle className="flex items-center gap-2"><ShieldCheck className="size-5"/>Locked Allocation Context</CardTitle></CardHeader><CardContent className="space-y-4"><div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Roster Candidates</div><div className="text-2xl font-semibold">{screen.summary.roster_count}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Physical Capacity</div><div className="text-2xl font-semibold">{screen.capacity.basis_capacity}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Allocated</div><div className="text-2xl font-semibold">{screen.summary.allocated_count}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Physical Seats Remaining</div><div className="text-2xl font-semibold">{screen.summary.remaining_physical_seats}</div></div></div>
                <div className="grid gap-3 lg:grid-cols-2"><div className="rounded-md border p-4"><div className="font-medium">Physical seat consumption</div><div className="mt-3 space-y-2 text-sm"><div className="flex justify-between"><span>Open / Unreserved</span><span>{screen.capacity.open.used}/{screen.capacity.open.capacity} used · {screen.capacity.open.remaining} remaining</span></div>{screen.capacity.vertical.map(v=><div key={v.category_id} className="flex justify-between gap-4"><span>{v.name} ({v.code})</span><span>{v.used}/{v.capacity} used · {v.remaining} remaining</span></div>)}</div></div><div className="rounded-md border p-4"><div className="font-medium">Horizontal quota fulfilment</div><div className="mt-3 space-y-2 text-sm">{screen.capacity.horizontal.length===0?<span className="text-muted-foreground">No Horizontal quota configured.</span>:screen.capacity.horizontal.map(h=><div key={h.category_id} className="flex justify-between gap-4"><span>{h.name} ({h.code})</span><span>Target {h.fulfilled}/{h.target} · Actual {h.actual_candidates}</span></div>)}</div></div></div>
                {screen.capacity.has_reservation_plan&&screen.capacity.reservation_plan_status!=='ACTIVE'&&<div className="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">The exact locked Reservation Plan is not ACTIVE. New allocation will remain blocked until its historical reservation context is restored.</div>}
            </CardContent></Card>

            <Card><CardHeader><CardTitle>Generated Merit / Roster → Seat Decisions</CardTitle></CardHeader><CardContent className="p-0"><div className="overflow-x-auto"><table className="w-full min-w-[1100px] text-sm"><thead className="bg-muted/30 text-left"><tr><th className="px-3 py-2">Rank</th><th className="px-3 py-2">Candidate</th><th className="px-3 py-2">Application</th><th className="px-3 py-2">Preference</th><th className="px-3 py-2 text-right">Final Score</th><th className="px-3 py-2">Documents</th><th className="px-3 py-2">Seat Decision</th><th className="px-3 py-2">Horizontal</th><th className="px-3 py-2">Actions</th></tr></thead><tbody>{screen.rows.map(row=><tr key={row.merit_entry_id} className="border-t align-top"><td className="px-3 py-3 font-semibold">#{row.rank}</td><td className="px-3 py-3"><div className="font-medium">{row.candidate_name}</div><div className="text-xs text-muted-foreground">{row.discipline_name??'—'}{row.specialization_name?` → ${row.specialization_name}`:''}</div></td><td className="px-3 py-3">{row.application_no}</td><td className="px-3 py-3">#{row.preference_no}</td><td className="px-3 py-3 text-right font-semibold">{Number(row.final_weighted_score).toFixed(3)}</td><td className="px-3 py-3">{row.document_verification?.status==='VERIFIED'?<span className="font-medium text-emerald-700">Verified</span>:<div><span className="font-medium text-amber-700">{row.document_verification?.status??'Pending'}</span><div className="text-xs text-muted-foreground">Verification required before allocation</div></div>}</td><td className="px-3 py-3">{!row.allocation?<span className="text-muted-foreground">Not allocated</span>:row.allocation.status==='ALLOCATED'?<div><div className="flex items-center gap-1 font-medium text-emerald-700"><CheckCircle2 className="size-4"/>{row.allocation.physical_category_name??'Open / Unreserved'}</div><div className="text-xs text-muted-foreground">Round {row.allocation.allocation_round}</div></div>:<div><div className="font-medium text-destructive">Cancelled</div><div className="max-w-64 text-xs text-muted-foreground">{row.allocation.cancellation_reason}</div></div>}</td><td className="px-3 py-3">{row.allocation?.horizontal_categories.length?<div className="flex flex-wrap gap-1">{row.allocation.horizontal_categories.map(h=><span key={h.id} className="rounded-full border px-2 py-0.5 text-xs">{h.code}{h.fulfills_target?' ✓':''}</span>)}</div>:<span className="text-muted-foreground">—</span>}</td><td className="px-3 py-3"><div className="flex flex-wrap gap-2">{can.allocate&&row.document_verification?.status==='VERIFIED'&&(!row.allocation||row.allocation.status==='CANCELLED')&&<AllocationDialog collegeId={college.id} row={row} capacity={screen.capacity}/>} {can.cancel&&row.allocation?.status==='ALLOCATED'&&<CancelButton collegeId={college.id} row={row}/>}</div></td></tr>)}</tbody></table></div></CardContent></Card>
        </>}
    </div></>;
}
