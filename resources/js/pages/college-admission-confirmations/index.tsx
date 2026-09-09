import { Head, router } from '@inertiajs/react';
import { BadgeCheck, CircleX, RotateCcw, ShieldCheck } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type Rule = { id:number; name:string; code:string; version_no:number; program_name:string|null; program_code:string|null; session_name:string|null };
type Admission = { id:number; admission_no:string; status:'CONFIRMED'|'REVOKED'; decision_note:string|null; confirmed_at:string|null; revoked_at:string|null; revocation_reason:string|null };
type Row = {
    seat_allocation_id:number; application_id:number; application_no:string; candidate_name:string; application_status:string;
    preference_no:number; admission_mode:'REGULAR'|'DIRECT'; merit_rank:number|null; final_weighted_score:number|null; program_name:string|null; program_code:string|null; session_name:string|null; document_verification_status:string|null;
    physical_seat_type:'OPEN'|'RESERVED'; physical_category_code:string|null; physical_category_name:string|null;
    allocation_round:number; allocated_at:string|null; horizontal_categories:{id:number;code:string;name:string;fulfills_target:boolean}[];
    rule:{id:number;name:string;code:string;version_no:number;bucket_key:string;program_name:string|null;program_code:string|null;degree_name:string|null;degree_level_name:string|null;session_name:string|null}|null;
    admission:Admission|null;
};
type Screen = { summary:{allocated_candidates:number;ready_to_confirm:number;confirmed:number;revoked:number}; rows:Row[] };
type Props = { college:{id:number;name:string;code:string;status:string}; rules:Rule[]; selectedRuleId:number|null; screen:Screen; can:{confirm:boolean;revoke:boolean} };

function ConfirmDialog({collegeId,row}:{collegeId:number;row:Row}) {
    const [open,setOpen]=useState(false);
    const [note,setNote]=useState(row.admission?.decision_note??'');
    const [processing,setProcessing]=useState(false);
    const submit=()=>{
        setProcessing(true);
        router.post(`/college/${collegeId}/admission-confirmations/seat-allocation/${row.seat_allocation_id}`,{decision_note:note||null},{preserveScroll:true,onSuccess:()=>setOpen(false),onFinish:()=>setProcessing(false)});
    };
    return <Dialog open={open} onOpenChange={setOpen}>
        <DialogTrigger asChild><Button size="sm"><BadgeCheck/>{row.admission?.status==='REVOKED'?'Re-confirm Admission':'Confirm Admission'}</Button></DialogTrigger>
        <DialogContent>
            <DialogHeader><DialogTitle>Confirm Admission · {row.application_no}</DialogTitle><DialogDescription>This confirms the candidate against Seat Allocation #{row.seat_allocation_id}. Capacity, reservation and document verification will not be recalculated. Merit / Selection Rule data applies only to Regular Admission.</DialogDescription></DialogHeader>
            <div className="space-y-3 py-2"><div className="rounded-md border p-3 text-sm"><div className="font-medium">{row.candidate_name} · {row.admission_mode==='DIRECT'?'Direct Admission':`Merit Rank #${row.merit_rank}`}</div><div className="mt-1 text-muted-foreground">{row.program_name??row.rule?.program_name??'Program'} · {row.session_name??row.rule?.session_name??'Session'} · {row.physical_category_name??'Open / Unreserved'} · Round {row.allocation_round}</div></div><div className="space-y-2"><Label>Approval / confirmation note <span className="text-muted-foreground">(optional)</span></Label><Textarea value={note} onChange={e=>setNote(e.target.value)} placeholder="Record counselling/committee reference or other approval note where required."/></div></div>
            <DialogFooter><Button variant="outline" onClick={()=>setOpen(false)} disabled={processing}>Cancel</Button><Button onClick={submit} disabled={processing}>{processing?'Confirming…':'Final Confirm Admission'}</Button></DialogFooter>
        </DialogContent>
    </Dialog>;
}

function RevokeButton({collegeId,row}:{collegeId:number;row:Row}) {
    const revoke=()=>{
        const reason=window.prompt('Reason for revoking this Admission Confirmation:');
        if(!reason?.trim()||!row.admission) return;
        router.patch(`/college/${collegeId}/admission-confirmations/${row.admission.id}/revoke`,{reason:reason.trim()},{preserveScroll:true});
    };
    return <Button size="sm" variant="outline" onClick={revoke}><CircleX/>Revoke</Button>;
}

export default function CollegeAdmissionConfirmations({college,rules,selectedRuleId,screen,can}:Props) {
    const selected=useMemo(()=>rules.find(r=>r.id===selectedRuleId)??null,[rules,selectedRuleId]);
    const choose=(value:string)=>router.get(`/college/${college.id}/admission-confirmations`,value==='ALL'?{}:{rule_id:Number(value)},{preserveState:true,replace:true,preserveScroll:true});
    return <><Head title="Admission Confirmation / Approval"/><div className="space-y-6 p-4 md:p-6">
        <header className="border-b pb-5"><p className="text-sm font-medium text-primary">{college.code} · Admission Processing</p><h1 className="text-3xl font-semibold">Admission Confirmation / Approval</h1><p className="max-w-5xl text-sm text-muted-foreground">Confirm admission only from an existing ACTIVE Seat Allocation. This stage consumes the VERIFIED documents, Intake bucket and reservation decision. Regular Admission also consumes its frozen Merit / Selection context; Direct Admission correctly has no Merit / Selection Rule context.</p></header>

        <Card><CardHeader><CardTitle className="flex items-center gap-2"><ShieldCheck className="size-5"/>Confirmation Gate</CardTitle></CardHeader><CardContent className="space-y-4"><div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Active Seat Allocations</div><div className="text-2xl font-semibold">{screen.summary.allocated_candidates}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Ready to Confirm</div><div className="text-2xl font-semibold">{screen.summary.ready_to_confirm}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Confirmed</div><div className="text-2xl font-semibold">{screen.summary.confirmed}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Revoked</div><div className="text-2xl font-semibold">{screen.summary.revoked}</div></div></div>
            {rules.length>0&&<div className="max-w-xl space-y-2"><Label>Merit / Selection Rule filter</Label><Select value={selectedRuleId?String(selectedRuleId):'ALL'} onValueChange={choose}><SelectTrigger><SelectValue/></SelectTrigger><SelectContent><SelectItem value="ALL">All active seat allocations</SelectItem>{rules.map(rule=><SelectItem key={rule.id} value={String(rule.id)}>{rule.code} V{rule.version_no} · {rule.program_name??rule.name} · {rule.session_name??'Session'}</SelectItem>)}</SelectContent></Select>{selected&&<p className="text-xs text-muted-foreground">Showing {selected.code} V{selected.version_no} · {selected.name}</p>}</div>}
        </CardContent></Card>

        <Card><CardHeader><CardTitle>Allocated Candidates → Admission Decision</CardTitle></CardHeader><CardContent className="p-0">{screen.rows.length===0?<div className="p-8 text-center text-sm text-muted-foreground">No ACTIVE Seat Allocation is available for Admission Confirmation.</div>:<div className="overflow-x-auto"><table className="w-full min-w-[1150px] text-sm"><thead className="bg-muted/30 text-left"><tr><th className="px-3 py-2">Rank</th><th className="px-3 py-2">Candidate</th><th className="px-3 py-2">Application</th><th className="px-3 py-2">Program / Rule</th><th className="px-3 py-2">Documents</th><th className="px-3 py-2">Allocated Seat</th><th className="px-3 py-2">Admission</th><th className="px-3 py-2">Actions</th></tr></thead><tbody>{screen.rows.map(row=>{
            const ready=row.document_verification_status==='VERIFIED'&&row.application_status==='SUBMITTED';
            return <tr key={row.seat_allocation_id} className="border-t align-top"><td className="px-3 py-3 font-semibold">{row.admission_mode==='DIRECT'?'DIRECT':`#${row.merit_rank}`}</td><td className="px-3 py-3"><div className="font-medium">{row.candidate_name}</div><div className="text-xs text-muted-foreground">{row.admission_mode==='DIRECT'?'Selection Rule bypassed':`Final score ${row.final_weighted_score==null?'—':Number(row.final_weighted_score).toFixed(3)}`}</div></td><td className="px-3 py-3"><div>{row.application_no}</div><div className="text-xs text-muted-foreground">Preference #{row.preference_no}</div></td><td className="px-3 py-3"><div className="font-medium">{row.program_name??row.rule?.program_name??'—'}</div><div className="text-xs text-muted-foreground">{row.admission_mode==='DIRECT'?'DIRECT ADMISSION':(row.rule?`${row.rule.code} V${row.rule.version_no}`:'—')} · {row.session_name??row.rule?.session_name??'Session'}</div></td><td className="px-3 py-3">{row.document_verification_status==='VERIFIED'?<span className="font-medium text-emerald-700">VERIFIED</span>:<span className="font-medium text-amber-700">{row.document_verification_status??'Pending'}</span>}</td><td className="px-3 py-3"><div className="font-medium">{row.physical_category_name??'Open / Unreserved'}</div><div className="text-xs text-muted-foreground">Allocation #{row.seat_allocation_id} · Round {row.allocation_round}</div>{row.horizontal_categories.length>0&&<div className="mt-1 flex flex-wrap gap-1">{row.horizontal_categories.map(h=><span key={h.id} className="rounded-full border px-2 py-0.5 text-xs">{h.code}{h.fulfills_target?' ✓':''}</span>)}</div>}</td><td className="px-3 py-3">{!row.admission?<span className="text-muted-foreground">Not confirmed</span>:row.admission.status==='CONFIRMED'?<div><div className="flex items-center gap-1 font-medium text-emerald-700"><BadgeCheck className="size-4"/>{row.admission.admission_no}</div><div className="text-xs text-muted-foreground">Confirmed {row.admission.confirmed_at?new Date(row.admission.confirmed_at).toLocaleString():'—'}</div></div>:<div><div className="flex items-center gap-1 font-medium text-amber-700"><RotateCcw className="size-4"/>Revoked</div><div className="max-w-72 text-xs text-muted-foreground">{row.admission.revocation_reason}</div></div>}</td><td className="px-3 py-3"><div className="flex flex-wrap gap-2">{can.confirm&&ready&&(!row.admission||row.admission.status==='REVOKED')&&<ConfirmDialog collegeId={college.id} row={row}/>} {can.revoke&&row.admission?.status==='CONFIRMED'&&<RevokeButton collegeId={college.id} row={row}/>} {!ready&&<span className="text-xs text-muted-foreground">Backend gate not satisfied</span>}</div></td></tr>;
        })}</tbody></table></div>}</CardContent></Card>
    </div></>;
}
