import { Head, router } from '@inertiajs/react';
import { CheckCircle2, ChevronDown, ChevronRight, Layers3, Search, Users, XCircle } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type DemandOption = { id:number; demand_no:string; billing_period_label:string; total_amount:string; adjusted_amount:string; outstanding_amount:string; status:string; admission_no:string; application_no:string; candidate_name:string };
type Scheme = { id:number; name:string; code:string; owner_type:string; benefit_type:string; calculation_type:string; benefit_value:string; maximum_benefit_amount:string|null; approval_mode:string; eligibility_mode:string; eligible:boolean; eligibility_reason:string|null; already_assigned_status:string|null; already_assigned_benefit_id:number|null; eligible_base_amount:number; calculated_benefit_amount:number; fee_heads:{id:number;name:string;code:string}[] };
type BulkScheme = { id:number; name:string; code:string; owner_type:string; benefit_type:string; calculation_type:string; benefit_value:string; maximum_benefit_amount:string|null; approval_mode:string; eligibility_mode:string; academic_session:{id:number;name:string;code:string}|null; program_template:{id:number;name:string;code:string}|null; fee_heads:{id:number;name:string;code:string}[]; reservation_categories:{id:number;name:string;code:string}[] };
type AcademicNode = { id:number; name:string; code:string };
type BulkCandidate = { demand_id:number; demand_no:string; billing_period_label:string; total_amount:string; outstanding_amount:string; admission_no:string; application_no:string; candidate_name:string; degree_level:AcademicNode; degree:AcademicNode; discipline:AcademicNode|null; reservation_categories:string[]; eligible_base_amount:number; calculated_benefit_amount:number };
type BulkSummary = { eligible:number; ineligible:number; already_assigned:number; scanned:number; limited:boolean; ineligible_reasons:{reason:string;count:number}[] };
type InstallmentContextItem = { fee_demand_item_id:number; fee_head_name:string; gross_amount:number; benefit_calculated_amount:number; benefit_eligible_amount:number; current_net_payable:number; schedules:{id:number;installment_no:number;amount:number;paid_amount:number;due_date:string;allocation_percentage:number|null}[] };
type InstallmentContext = { has_active_installments:boolean; benefit_calculated_total:number; default_mode:string; modes:string[]; items:InstallmentContextItem[] };
type Benefit = { id:number; demand_no:string; admission_no:string; application_no:string; candidate_name:string; billing_period_label:string; scheme_name:string; scheme_code:string; benefit_type:string; application_mode:string; status:string; eligible_base_amount:string; calculated_benefit_amount:string; sanctioned_amount:string|null; application_note:string|null; decision_note:string|null; applied_at:string; decided_at:string|null; demand_total_amount:string; demand_adjusted_amount:string; demand_outstanding_amount:string; eligibility_snapshot?:{reservation_categories?:string[]}; installment_context?:InstallmentContext|null; installment_adjustment_mode?:string|null };

type TreeDiscipline = { key:string; label:string; candidates:BulkCandidate[] };
type TreeDegree = { key:string; label:string; disciplines:TreeDiscipline[] };
type TreeLevel = { key:string; label:string; degrees:TreeDegree[] };

export default function Page({ college, benefits, bulk_schemes, can }:{ college:{id:number;name:string;code:string}; benefits:Benefit[]; bulk_schemes:BulkScheme[]; can:{assign:boolean;approve:boolean;reject:boolean;cancel:boolean} }) {
    const [assignmentMode,setAssignmentMode]=useState<'individual'|'bulk'>('individual');
    const [query,setQuery]=useState('');
    const [demandOptions,setDemandOptions]=useState<DemandOption[]>([]);
    const [selectedDemand,setSelectedDemand]=useState<DemandOption|null>(null);
    const [schemes,setSchemes]=useState<Scheme[]>([]);
    const [schemeId,setSchemeId]=useState(0);
    const [note,setNote]=useState('');
    const [loading,setLoading]=useState(false);
    const [search,setSearch]=useState('');
    const [decisionAmounts,setDecisionAmounts]=useState<Record<number,string>>({});
    const [decisionNotes,setDecisionNotes]=useState<Record<number,string>>({});
    const [installmentModes,setInstallmentModes]=useState<Record<number,string>>({});
    const [customInstallments,setCustomInstallments]=useState<Record<number,Record<number,Record<number,string>>>>({});
    const [removingId,setRemovingId]=useState<number|null>(null);

    const [bulkSchemes]=useState<BulkScheme[]>(bulk_schemes??[]);
    const [bulkSchemeId,setBulkSchemeId]=useState(0);
    const [bulkCandidates,setBulkCandidates]=useState<BulkCandidate[]>([]);
    const [bulkSummary,setBulkSummary]=useState<BulkSummary|null>(null);
    const [bulkLoading,setBulkLoading]=useState(false);
    const [bulkError,setBulkError]=useState('');
    const [bulkSearch,setBulkSearch]=useState('');
    const [selectedBulkDemands,setSelectedBulkDemands]=useState<number[]>([]);
    const [bulkNote,setBulkNote]=useState('');
    const [expandedLevels,setExpandedLevels]=useState<Record<string,boolean>>({});
    const [expandedDegrees,setExpandedDegrees]=useState<Record<string,boolean>>({});
    const [expandedDisciplines,setExpandedDisciplines]=useState<Record<string,boolean>>({});

    const money=(value:string|number|null|undefined)=>`₹${Number(value??0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2})}`;

    useEffect(()=>{
        const q=query.trim();
        if(q.length<2){setDemandOptions([]);return;}
        const controller=new AbortController();
        const timer=window.setTimeout(async()=>{
            try{
                const response=await fetch(`/college/${college.id}/fee-student-benefits/search-demands?q=${encodeURIComponent(q)}`,{signal:controller.signal,headers:{Accept:'application/json'}});
                if(!response.ok)throw new Error();
                const data=await response.json();
                setDemandOptions(data.data??[]);
            }catch{if(!controller.signal.aborted)setDemandOptions([]);}
        },250);
        return()=>{window.clearTimeout(timer);controller.abort();};
    },[college.id,query]);

    useEffect(()=>{
        if(!selectedDemand){setSchemes([]);setSchemeId(0);return;}
        const controller=new AbortController();
        setLoading(true);
        fetch(`/college/${college.id}/fee-student-benefits/demands/${selectedDemand.id}/schemes`,{signal:controller.signal,headers:{Accept:'application/json'}})
            .then(r=>r.ok?r.json():Promise.reject())
            .then(data=>{setSchemes(data.data??[]);setSchemeId(0);})
            .catch(()=>{if(!controller.signal.aborted)setSchemes([]);})
            .finally(()=>{if(!controller.signal.aborted)setLoading(false);});
        return()=>controller.abort();
    },[college.id,selectedDemand]);

    useEffect(()=>{
        if(!bulkSchemeId){setBulkCandidates([]);setBulkSummary(null);setSelectedBulkDemands([]);setBulkError('');return;}
        const controller=new AbortController();
        setBulkLoading(true);
        setBulkError('');
        setSelectedBulkDemands([]);
        setExpandedLevels({});setExpandedDegrees({});setExpandedDisciplines({});
        fetch(`/college/${college.id}/fee-student-benefits/bulk-candidates?scheme_id=${bulkSchemeId}`,{signal:controller.signal,headers:{Accept:'application/json'}})
            .then(async r=>{
                const payload=await r.json().catch(()=>null);
                if(!r.ok)throw new Error(payload?.message||payload?.errors?.scheme_id?.[0]||'Unable to load eligible students.');
                return payload;
            })
            .then(data=>{setBulkCandidates(data.data?.students??[]);setBulkSummary(data.data?.summary??null);})
            .catch(error=>{if(!controller.signal.aborted){setBulkCandidates([]);setBulkSummary(null);setBulkError(error instanceof Error?error.message:'Unable to load eligible students.');}})
            .finally(()=>{if(!controller.signal.aborted)setBulkLoading(false);});
        return()=>controller.abort();
    },[bulkSchemeId,college.id]);

    const selectedScheme=schemes.find(s=>s.id===schemeId)??null;
    const selectedSchemeAssignable=!!selectedScheme&&selectedScheme.eligible&&!selectedScheme.already_assigned_status;
    const selectedBulkScheme=bulkSchemes.find(s=>s.id===bulkSchemeId)??null;
    const filteredBenefits=useMemo(()=>{const q=search.trim().toLowerCase(); if(!q)return benefits; return benefits.filter(b=>[b.candidate_name,b.application_no,b.admission_no,b.demand_no,b.scheme_name,b.scheme_code,b.status].some(v=>v?.toLowerCase().includes(q)));},[benefits,search]);
    const filteredBulkCandidates=useMemo(()=>{
        const q=bulkSearch.trim().toLowerCase();
        if(!q)return bulkCandidates;
        return bulkCandidates.filter(c=>[
            c.candidate_name,c.application_no,c.admission_no,c.demand_no,c.billing_period_label,
            c.degree_level.name,c.degree_level.code,c.degree.name,c.degree.code,c.discipline?.name,c.discipline?.code,
            ...(c.reservation_categories??[]),
        ].some(v=>v?.toLowerCase().includes(q)));
    },[bulkCandidates,bulkSearch]);

    const bulkTree=useMemo<TreeLevel[]>(()=>{
        const levels=new Map<string,{label:string;degrees:Map<string,{label:string;disciplines:Map<string,TreeDiscipline>}>}>();
        filteredBulkCandidates.forEach(candidate=>{
            const levelKey=String(candidate.degree_level.id);
            if(!levels.has(levelKey))levels.set(levelKey,{label:`${candidate.degree_level.name} (${candidate.degree_level.code})`,degrees:new Map()});
            const level=levels.get(levelKey)!;
            const degreeKey=String(candidate.degree.id);
            if(!level.degrees.has(degreeKey))level.degrees.set(degreeKey,{label:`${candidate.degree.name} (${candidate.degree.code})`,disciplines:new Map()});
            const degree=level.degrees.get(degreeKey)!;
            const disciplineKey=candidate.discipline?String(candidate.discipline.id):'none';
            const disciplineLabel=candidate.discipline?`${candidate.discipline.name} (${candidate.discipline.code})`:'General / No Discipline';
            if(!degree.disciplines.has(disciplineKey))degree.disciplines.set(disciplineKey,{key:disciplineKey,label:disciplineLabel,candidates:[]});
            degree.disciplines.get(disciplineKey)!.candidates.push(candidate);
        });
        return [...levels.entries()].map(([key,level])=>({
            key,label:level.label,degrees:[...level.degrees.entries()].map(([degreeKey,degree])=>({key:degreeKey,label:degree.label,disciplines:[...degree.disciplines.values()]})),
        }));
    },[filteredBulkCandidates]);

    const selectedSet=useMemo(()=>new Set(selectedBulkDemands),[selectedBulkDemands]);
    const selectedCandidates=useMemo(()=>bulkCandidates.filter(c=>selectedSet.has(c.demand_id)),[bulkCandidates,selectedSet]);
    const selectedBenefitTotal=useMemo(()=>selectedCandidates.reduce((sum,c)=>sum+Number(c.calculated_benefit_amount||0),0),[selectedCandidates]);

    const setSelection=(ids:number[],checked:boolean)=>setSelectedBulkDemands(current=>{
        const next=new Set(current);
        ids.forEach(id=>checked?next.add(id):next.delete(id));
        return [...next];
    });
    const allSelected=(ids:number[])=>ids.length>0&&ids.every(id=>selectedSet.has(id));

    const clearIndividualSelection=()=>{setSelectedDemand(null);setQuery('');setDemandOptions([]);setSchemes([]);setSchemeId(0);};
    const assign=()=>{if(!selectedDemand||!selectedSchemeAssignable||!selectedScheme||!can.assign)return; router.post(`/college/${college.id}/fee-student-benefits`,{fee_demand_id:selectedDemand.id,scheme_id:selectedScheme.id,application_note:note||null},{preserveScroll:true,onSuccess:()=>{setNote('');clearIndividualSelection();}});};
    const bulkAssign=()=>{if(!bulkSchemeId||selectedBulkDemands.length===0||!can.assign)return; router.post(`/college/${college.id}/fee-student-benefits/bulk`,{scheme_id:bulkSchemeId,fee_demand_ids:selectedBulkDemands,application_note:bulkNote||null},{preserveScroll:true,onSuccess:()=>{setBulkNote('');setSelectedBulkDemands([]);setBulkSchemeId(0);setBulkCandidates([]);setBulkSummary(null);}});};
    const approve=(b:Benefit)=>{
        const mode=installmentModes[b.id]??b.installment_context?.default_mode??'PROPORTIONAL';
        router.patch(`/college/${college.id}/fee-student-benefits/${b.id}/approve`,{
            sanctioned_amount:decisionAmounts[b.id]||b.calculated_benefit_amount,
            decision_note:decisionNotes[b.id]||null,
            installment_adjustment_mode:mode,
            custom_installments:mode==='CUSTOM'?(customInstallments[b.id]??{}):{},
        },{preserveScroll:true});
    };
    const reject=(b:Benefit)=>{const n=decisionNotes[b.id]?.trim();if(n)router.patch(`/college/${college.id}/fee-student-benefits/${b.id}/reject`,{decision_note:n},{preserveScroll:true});};
    const projectedItemNet=(b:Benefit,item:InstallmentContextItem)=>{
        const sanction=Number(decisionAmounts[b.id]||b.calculated_benefit_amount||0);
        const total=Number(b.installment_context?.benefit_calculated_total||0);
        const share=total>0?Math.min(item.benefit_eligible_amount, sanction*(item.benefit_calculated_amount/total)):0;
        return Math.max(0,item.current_net_payable-share);
    };
    const setCustomInstallment=(benefitId:number,itemId:number,scheduleId:number,value:string)=>setCustomInstallments(current=>({
        ...current,[benefitId]:{...(current[benefitId]??{}),[itemId]:{...(current[benefitId]?.[itemId]??{}),[scheduleId]:value}}
    }));

    const cancel=(b:Benefit)=>{
        const n=decisionNotes[b.id]?.trim();
        if(!n)return;
        const message=b.status==='APPROVED'
            ? `Remove ${b.scheme_name} from ${b.candidate_name}? The sanctioned Fee Demand adjustment of ${money(b.sanctioned_amount)} will be reversed.`
            : `Remove ${b.scheme_name} from ${b.candidate_name}?`;
        if(!window.confirm(message))return;
        router.patch(`/college/${college.id}/fee-student-benefits/${b.id}/cancel`,{reason:n},{
            preserveScroll:true,
            onStart:()=>setRemovingId(b.id),
            onFinish:()=>setRemovingId(null),
        });
    };

    return <><Head title="Student Benefits"/><div className="space-y-4">
        <div><h1 className="text-2xl font-semibold">Student Scholarship / Concession / Waiver</h1><p className="text-sm text-muted-foreground">Assign an ACTIVE eligible scheme, approve/sanction it, and post the approved amount as an auditable Fee Demand adjustment.</p></div>

        {can.assign&&<Card><CardHeader className="pb-3"><div className="flex flex-wrap items-center justify-between gap-3"><CardTitle>Assign Benefit to Fee Demand</CardTitle><div className="inline-flex rounded-md border p-1"><Button type="button" size="sm" variant={assignmentMode==='individual'?'default':'ghost'} onClick={()=>setAssignmentMode('individual')}>Individual</Button><Button type="button" size="sm" variant={assignmentMode==='bulk'?'default':'ghost'} onClick={()=>setAssignmentMode('bulk')}><Users className="mr-2 h-4 w-4"/>Bulk Assignment</Button></div></div></CardHeader><CardContent className="space-y-4">
            {assignmentMode==='individual'?<>
                <div className="grid gap-3 md:grid-cols-2"><div className="space-y-2"><label className="text-sm font-medium">Find student / demand</label><div className="relative"><Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground"/><Input className={selectedDemand?'pl-9 pr-20':'pl-9'} value={query} onChange={e=>{if(selectedDemand){setSelectedDemand(null);setSchemes([]);setSchemeId(0);}setQuery(e.target.value);}} placeholder="Name / Application No / Admission No / Demand No"/>{selectedDemand&&<Button type="button" size="sm" variant="ghost" className="absolute right-1 top-0.5 h-8 px-2" onClick={clearIndividualSelection}>Clear</Button>}</div>{demandOptions.length>0&&!selectedDemand&&<div className="rounded-md border divide-y">{demandOptions.map(d=><button type="button" key={d.id} className="block w-full p-3 text-left hover:bg-muted" onClick={()=>{setSelectedDemand(d);setQuery(`${d.candidate_name} · ${d.demand_no}`);setDemandOptions([]);}}><div className="font-medium">{d.candidate_name} · {d.demand_no}</div><div className="text-xs text-muted-foreground">{d.application_no} · {d.admission_no} · {d.billing_period_label} · Outstanding {money(d.outstanding_amount)}</div></button>)}</div>}</div>
                <div className="space-y-2"><label className="text-sm font-medium">Applicable scheme</label><select className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" value={schemeId} onChange={e=>setSchemeId(Number(e.target.value))} disabled={!selectedDemand||loading}><option value={0}>{loading?'Loading applicable schemes...':'Select scheme'}</option>{schemes.map(s=>{const assigned=!!s.already_assigned_status;return <option key={s.id} value={s.id} disabled={assigned||!s.eligible}>{s.name} ({s.code}) — {s.owner_type}{assigned?` — Already assigned — ${s.already_assigned_status}`:(s.eligible?'':` — Not eligible: ${s.eligibility_reason??'Eligibility rules not satisfied'}`)}</option>;})}</select></div></div>
                {selectedDemand&&<div className="rounded-md border p-3 text-sm"><div className="flex flex-wrap items-start justify-between gap-3"><div className="grid flex-1 gap-3 md:grid-cols-4"><div><span className="text-muted-foreground">Candidate</span><div className="font-medium">{selectedDemand.candidate_name}</div></div><div><span className="text-muted-foreground">Demand</span><div className="font-medium">{selectedDemand.demand_no}</div></div><div><span className="text-muted-foreground">Total</span><div className="font-medium">{money(selectedDemand.total_amount)}</div></div><div><span className="text-muted-foreground">Outstanding</span><div className="font-medium">{money(selectedDemand.outstanding_amount)}</div></div></div><Button type="button" size="sm" variant="outline" onClick={clearIndividualSelection}>Clear selection</Button></div></div>}
                {selectedDemand&&!loading&&schemes.length>0&&!schemes.some(s=>s.eligible&&!s.already_assigned_status)&&<div className="rounded-md border p-3 text-sm"><div className="font-medium">Why no scheme can be assigned to this Fee Demand</div><div className="mt-1 space-y-1 text-muted-foreground">{schemes.map(s=><div key={s.id}><strong className="text-foreground">{s.name}:</strong> {s.already_assigned_status?`Already assigned — ${s.already_assigned_status}.`:(s.eligibility_reason??'Eligibility rules not satisfied.')}</div>)}</div><div className="mt-2 text-xs text-muted-foreground">A PENDING or APPROVED benefit cannot be assigned again for the same Fee Demand + Scheme. Other schemes remain independently available when eligible.</div></div>}
                {selectedScheme&&<div className="rounded-md border p-3 space-y-2 text-sm"><div className="flex flex-wrap gap-2"><Badge>{selectedScheme.benefit_type}</Badge><Badge variant="outline">{selectedScheme.approval_mode}</Badge><Badge variant="outline">{selectedScheme.owner_type}</Badge></div><div>Fee Heads: {selectedScheme.fee_heads.map(h=>h.name).join(', ')}</div>{selectedScheme.eligible?<div className="grid gap-2 md:grid-cols-2"><div>Eligible base: <strong>{money(selectedScheme.eligible_base_amount)}</strong></div><div>Calculated benefit: <strong>{money(selectedScheme.calculated_benefit_amount)}</strong></div></div>:<div className="text-destructive">{selectedScheme.eligibility_reason}</div>}</div>}
                <Input value={note} onChange={e=>setNote(e.target.value)} placeholder="Application / assignment note (optional)"/><Button onClick={assign} disabled={!selectedSchemeAssignable||!can.assign}>Assign / Apply Benefit</Button>
            </>:<>
                <div className="rounded-md border bg-muted/20 p-3 text-sm"><strong>Bulk assignment is controlled selection, not automatic entitlement.</strong> The system only loads students who are eligible to be considered for the selected scheme. You decide which students actually receive it. Every selected student is validated again at submission.</div>
                <div className="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"><div className="space-y-2"><label className="text-sm font-medium">Benefit scheme</label><select className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm" value={bulkSchemeId} onChange={e=>setBulkSchemeId(Number(e.target.value))} disabled={bulkLoading&&bulkSchemes.length===0}><option value={0}>{bulkLoading&&bulkSchemes.length===0?'Loading ACTIVE schemes...':'Select scholarship / concession / waiver'}</option>{bulkSchemes.map(s=><option key={s.id} value={s.id}>{s.name} ({s.code}) — {s.owner_type} — {s.academic_session?.name??'Session'}</option>)}</select></div><div className="space-y-2"><label className="text-sm font-medium">Find within eligible students</label><div className="relative"><Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground"/><Input className="pl-9" value={bulkSearch} onChange={e=>setBulkSearch(e.target.value)} placeholder="Student / application / degree / discipline / category" disabled={!bulkSchemeId}/></div></div></div>
                {bulkSchemes.length===0&&<div className="rounded-md border p-3 text-sm text-muted-foreground">No ACTIVE University or College benefit scheme is currently available for this College. Activate/configure a scheme first, then return to Bulk Assignment.</div>}
                {bulkError&&<div className="rounded-md border p-3 text-sm text-destructive"><strong>Eligible students could not be loaded.</strong> {bulkError}</div>}

                {selectedBulkScheme&&<div className="rounded-md border p-3 text-sm space-y-2"><div className="flex flex-wrap gap-2"><Badge>{selectedBulkScheme.benefit_type}</Badge><Badge variant="outline">{selectedBulkScheme.approval_mode}</Badge><Badge variant="outline">{selectedBulkScheme.owner_type}</Badge><Badge variant="outline">{selectedBulkScheme.eligibility_mode}</Badge></div><div className="grid gap-2 md:grid-cols-3"><div><span className="text-muted-foreground">Academic Session</span><div className="font-medium">{selectedBulkScheme.academic_session?.name??'—'}</div></div><div><span className="text-muted-foreground">Program Scope</span><div className="font-medium">{selectedBulkScheme.program_template?.name??'All applicable programs'}</div></div><div><span className="text-muted-foreground">Fee Heads</span><div className="font-medium">{selectedBulkScheme.fee_heads.map(h=>h.name).join(', ')||'—'}</div></div></div>{selectedBulkScheme.reservation_categories.length>0&&<div className="text-xs text-muted-foreground">Category eligibility: {selectedBulkScheme.reservation_categories.map(c=>`${c.name} (${c.code})`).join(', ')}</div>}</div>}

                {bulkSchemeId>0&&<div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-5 text-sm"><div className="rounded-md border p-3"><div className="text-muted-foreground">Scanned</div><div className="text-lg font-semibold">{bulkSummary?.scanned??0}</div></div><div className="rounded-md border p-3"><div className="text-muted-foreground">Eligible to consider</div><div className="text-lg font-semibold">{bulkSummary?.eligible??0}</div></div><div className="rounded-md border p-3"><div className="text-muted-foreground">Selected</div><div className="text-lg font-semibold">{selectedBulkDemands.length}</div></div><div className="rounded-md border p-3"><div className="text-muted-foreground">Already assigned</div><div className="text-lg font-semibold">{bulkSummary?.already_assigned??0}</div></div><div className="rounded-md border p-3"><div className="text-muted-foreground">Selected benefit total</div><div className="text-lg font-semibold">{money(selectedBenefitTotal)}</div></div></div>}

                {bulkLoading&&bulkSchemeId>0&&<div className="rounded-md border p-4 text-sm text-muted-foreground">Loading and validating eligible Fee Demands...</div>}
                {!bulkLoading&&bulkSchemeId>0&&bulkTree.length===0&&<div className="rounded-md border p-4 text-sm"><div className="font-medium">No currently eligible student Fee Demands are available for this scheme.</div>{bulkSummary&&<div className="mt-1 text-muted-foreground">Scanned {bulkSummary.scanned} demand(s); {bulkSummary.ineligible} failed eligibility and {bulkSummary.already_assigned} already have this scheme pending/approved.</div>}{!!bulkSummary?.ineligible_reasons?.length&&<div className="mt-2 space-y-1">{bulkSummary.ineligible_reasons.map((r,i)=><div key={`${r.reason}:${i}`} className="text-muted-foreground"><strong className="text-foreground">{r.count}×</strong> {r.reason}</div>)}</div>}</div>}
                {!bulkLoading&&bulkSchemeId>0&&bulkTree.length>0&&!!bulkSummary?.ineligible_reasons?.length&&<div className="rounded-md border p-3 text-xs text-muted-foreground"><strong className="text-foreground">Eligibility exclusions:</strong> {bulkSummary.ineligible_reasons.map(r=>`${r.count}× ${r.reason}`).join(' · ')}</div>}

                {!bulkLoading&&bulkTree.length>0&&<div className="rounded-md border">
                    <div className="flex flex-wrap items-center justify-between gap-2 border-b px-3 py-2"><div><div className="font-medium">Eligible Students by Academic Hierarchy</div><div className="text-xs text-muted-foreground">Degree Level → Degree → Discipline → Student. Groups stay collapsed so the page remains compact.</div></div><Badge variant="outline">{filteredBulkCandidates.length} shown</Badge></div>
                    <div className="divide-y">{bulkTree.map(level=>{
                        const levelIds=level.degrees.flatMap(degree=>degree.disciplines.flatMap(discipline=>discipline.candidates.map(c=>c.demand_id)));
                        const levelOpen=!!expandedLevels[level.key];
                        return <div key={level.key}><div className="flex items-center gap-2 px-3 py-2.5 hover:bg-muted/30"><button type="button" className="flex h-7 w-7 items-center justify-center rounded hover:bg-muted" onClick={()=>setExpandedLevels(v=>({...v,[level.key]:!levelOpen}))}>{levelOpen?<ChevronDown className="h-4 w-4"/>:<ChevronRight className="h-4 w-4"/>}</button><input type="checkbox" checked={allSelected(levelIds)} onChange={e=>setSelection(levelIds,e.target.checked)} aria-label={`Select ${level.label}`}/><Layers3 className="h-4 w-4 text-muted-foreground"/><button type="button" className="flex-1 text-left font-semibold" onClick={()=>setExpandedLevels(v=>({...v,[level.key]:!levelOpen}))}><span className="text-xs font-normal text-muted-foreground">Degree Level</span><div>{level.label}</div></button><Badge variant="outline">{levelIds.length} students</Badge></div>
                        {levelOpen&&<div className="border-t bg-muted/10 pl-5">{level.degrees.map(degree=>{
                            const degreeIds=degree.disciplines.flatMap(discipline=>discipline.candidates.map(c=>c.demand_id));
                            const degreeKey=`${level.key}:${degree.key}`;
                            const degreeOpen=!!expandedDegrees[degreeKey];
                            return <div key={degreeKey} className="border-l"><div className="flex items-center gap-2 px-3 py-2"><button type="button" className="flex h-7 w-7 items-center justify-center rounded hover:bg-muted" onClick={()=>setExpandedDegrees(v=>({...v,[degreeKey]:!degreeOpen}))}>{degreeOpen?<ChevronDown className="h-4 w-4"/>:<ChevronRight className="h-4 w-4"/>}</button><input type="checkbox" checked={allSelected(degreeIds)} onChange={e=>setSelection(degreeIds,e.target.checked)} aria-label={`Select ${degree.label}`}/><button type="button" className="flex-1 text-left" onClick={()=>setExpandedDegrees(v=>({...v,[degreeKey]:!degreeOpen}))}><span className="text-xs text-muted-foreground">Degree</span><div className="font-medium">{degree.label}</div></button><Badge variant="outline">{degreeIds.length}</Badge></div>
                            {degreeOpen&&<div className="border-t pl-5">{degree.disciplines.map(discipline=>{
                                const disciplineIds=discipline.candidates.map(c=>c.demand_id);
                                const disciplineKey=`${degreeKey}:${discipline.key}`;
                                const disciplineOpen=!!expandedDisciplines[disciplineKey];
                                return <div key={disciplineKey} className="border-l"><div className="flex items-center gap-2 px-3 py-2"><button type="button" className="flex h-7 w-7 items-center justify-center rounded hover:bg-muted" onClick={()=>setExpandedDisciplines(v=>({...v,[disciplineKey]:!disciplineOpen}))}>{disciplineOpen?<ChevronDown className="h-4 w-4"/>:<ChevronRight className="h-4 w-4"/>}</button><input type="checkbox" checked={allSelected(disciplineIds)} onChange={e=>setSelection(disciplineIds,e.target.checked)} aria-label={`Select ${discipline.label}`}/><button type="button" className="flex-1 text-left" onClick={()=>setExpandedDisciplines(v=>({...v,[disciplineKey]:!disciplineOpen}))}><span className="text-xs text-muted-foreground">Discipline</span><div className="font-medium">{discipline.label}</div></button><Badge variant="outline">{disciplineIds.length}</Badge></div>
                                {disciplineOpen&&<div className="divide-y border-t">{discipline.candidates.map(candidate=><label key={candidate.demand_id} className="grid cursor-pointer gap-2 px-4 py-3 hover:bg-muted/30 md:grid-cols-[28px_minmax(180px,1.4fr)_minmax(130px,1fr)_minmax(130px,1fr)_120px_120px] md:items-center"><input type="checkbox" checked={selectedSet.has(candidate.demand_id)} onChange={e=>setSelection([candidate.demand_id],e.target.checked)}/><div><div className="font-medium">{candidate.candidate_name}</div><div className="text-xs text-muted-foreground">{candidate.application_no} · {candidate.admission_no}</div></div><div><div className="text-xs text-muted-foreground">Demand</div><div className="text-sm font-medium">{candidate.demand_no}</div><div className="text-xs text-muted-foreground">{candidate.billing_period_label}</div></div><div><div className="text-xs text-muted-foreground">Category</div><div className="text-sm">{candidate.reservation_categories.length?candidate.reservation_categories.join(', '):'Not mapped / Open eligibility'}</div></div><div className="text-sm"><div className="text-xs text-muted-foreground">Eligible Base</div><strong>{money(candidate.eligible_base_amount)}</strong></div><div className="text-sm"><div className="text-xs text-muted-foreground">Benefit</div><strong>{money(candidate.calculated_benefit_amount)}</strong></div></label>)}</div>}
                                </div>})}</div>}
                            </div>})}</div>}
                        </div>;
                    })}</div>
                </div>}

                {bulkSummary?.limited&&<div className="text-xs text-muted-foreground">Showing the first 1,000 scoped Fee Demands. Narrow the scheme scope if a larger cohort must be processed.</div>}
                {bulkSchemeId>0&&<><Input value={bulkNote} onChange={e=>setBulkNote(e.target.value)} placeholder="Bulk assignment / sanction note (optional)"/><div className="flex flex-wrap items-center justify-between gap-3 rounded-md border p-3"><div className="text-sm"><strong>{selectedBulkDemands.length}</strong> student(s) selected · calculated benefit total <strong>{money(selectedBenefitTotal)}</strong><div className="text-xs text-muted-foreground">{selectedBulkScheme?.approval_mode==='AUTOMATIC'?'Selected records will be automatically sanctioned after final validation.':'Selected records will be assigned as PENDING and remain subject to approval/sanction.'}</div></div><Button onClick={bulkAssign} disabled={selectedBulkDemands.length===0||bulkLoading}>Assign Selected Benefits</Button></div></>}
            </>}
        </CardContent></Card>}

        <Card><CardHeader><CardTitle>Student Benefit Register</CardTitle></CardHeader><CardContent className="space-y-3"><Input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search candidate / application / admission / demand / scheme / status"/>{filteredBenefits.length===0&&<p className="text-sm text-muted-foreground">No student benefit records configured.</p>}{filteredBenefits.map(b=><div key={b.id} className="rounded-md border p-4 space-y-3"><div className="flex flex-wrap items-start justify-between gap-3"><div><div className="font-semibold">{b.candidate_name} · {b.scheme_name} ({b.scheme_code})</div><div className="text-sm text-muted-foreground">{b.application_no} · {b.admission_no} · {b.demand_no} · {b.billing_period_label}</div></div><div className="flex gap-2"><Badge variant="outline">{b.benefit_type}</Badge><Badge>{b.status}</Badge></div></div><div className="grid gap-3 md:grid-cols-4 text-sm"><div><span className="text-muted-foreground">Eligible Base</span><div className="font-medium">{money(b.eligible_base_amount)}</div></div><div><span className="text-muted-foreground">Calculated</span><div className="font-medium">{money(b.calculated_benefit_amount)}</div></div><div><span className="text-muted-foreground">Sanctioned</span><div className="font-medium">{b.sanctioned_amount?money(b.sanctioned_amount):'Pending'}</div></div><div><span className="text-muted-foreground">Demand Outstanding</span><div className="font-medium">{money(b.demand_outstanding_amount)}</div></div></div>{!!b.eligibility_snapshot?.reservation_categories?.length&&<div className="text-xs text-muted-foreground">Admission category snapshot: {b.eligibility_snapshot.reservation_categories.join(', ')}</div>}{b.status==='PENDING'&&<div className="space-y-3">
                    <div className="grid gap-2 md:grid-cols-2"><Input value={decisionAmounts[b.id]??b.calculated_benefit_amount} onChange={e=>setDecisionAmounts(v=>({...v,[b.id]:e.target.value}))} placeholder="Sanction amount"/><Input value={decisionNotes[b.id]??''} onChange={e=>setDecisionNotes(v=>({...v,[b.id]:e.target.value}))} placeholder="Decision note / rejection reason"/></div>
                    {b.installment_context?.has_active_installments&&<div className="rounded-md border p-3 space-y-3 text-sm">
                        <div><div className="font-medium">Active installment schedule found</div><div className="text-xs text-muted-foreground">Choose how this student's approved benefit should change only the affected installment schedule. Already-paid amounts can never be reduced.</div></div>
                        <div className="grid gap-2 md:grid-cols-3">
                            {[['PROPORTIONAL','Proportional (Recommended)'],['NEXT_UNPAID_FIRST','Reduce next unpaid first'],['CUSTOM','Custom distribution']].map(([value,label])=><label key={value} className="flex cursor-pointer items-start gap-2 rounded-md border p-2"><input type="radio" name={`benefit-installment-${b.id}`} checked={(installmentModes[b.id]??'PROPORTIONAL')===value} onChange={()=>setInstallmentModes(v=>({...v,[b.id]:value}))}/><span>{label}</span></label>)}
                        </div>
                        {b.installment_context.items.map(item=><div key={item.fee_demand_item_id} className="rounded-md bg-muted/20 p-3 space-y-2"><div className="flex flex-wrap justify-between gap-2"><strong>{item.fee_head_name}</strong><span>Current net {money(item.current_net_payable)} · Projected net {money(projectedItemNet(b,item))}</span></div><div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">{item.schedules.map(schedule=><div key={schedule.id} className="rounded border p-2"><div className="text-xs text-muted-foreground">Installment {schedule.installment_no} · Due {schedule.due_date}</div><div>Current {money(schedule.amount)}{schedule.paid_amount>0?` · Paid ${money(schedule.paid_amount)}`:''}</div>{(installmentModes[b.id]??'PROPORTIONAL')==='CUSTOM'&&<Input className="mt-2" type="number" min={schedule.paid_amount} step="0.01" value={customInstallments[b.id]?.[item.fee_demand_item_id]?.[schedule.id]??''} onChange={e=>setCustomInstallment(b.id,item.fee_demand_item_id,schedule.id,e.target.value)} placeholder="New installment amount"/>}</div>)}</div>{(installmentModes[b.id]??'PROPORTIONAL')==='CUSTOM'&&<div className="text-xs text-muted-foreground">Enter the complete post-benefit installment amounts for this fee head. Their total must equal the projected net payable; backend validation is authoritative.</div>}</div>)}
                    </div>}
                    <div className="flex flex-wrap gap-2">{can.approve&&<Button size="sm" onClick={()=>approve(b)}><CheckCircle2 className="mr-2 h-4 w-4"/>Approve</Button>}{can.reject&&<Button size="sm" variant="outline" onClick={()=>reject(b)} disabled={!decisionNotes[b.id]?.trim()}><XCircle className="mr-2 h-4 w-4"/>Reject</Button>}{can.cancel&&<Button size="sm" variant="outline" onClick={()=>cancel(b)} disabled={!decisionNotes[b.id]?.trim()||removingId===b.id}>Remove</Button>}</div>
                </div>}{b.status==='APPROVED'&&<div className="space-y-2"><div className="rounded-md border p-3 text-sm">Fee Demand adjustment posted: <strong>{money(b.sanctioned_amount)}</strong>. Original demand remains unchanged; net outstanding reflects the sanctioned benefit.{b.installment_adjustment_mode&&<div className="mt-1 text-xs text-muted-foreground">Installment adjustment: {b.installment_adjustment_mode.replaceAll('_',' ')}</div>}</div>{can.cancel&&<div className="flex flex-col gap-2 md:flex-row"><Input value={decisionNotes[b.id]??''} onChange={e=>setDecisionNotes(v=>({...v,[b.id]:e.target.value}))} placeholder="Removal reason (required)"/><Button type="button" variant="outline" onClick={()=>cancel(b)} disabled={!decisionNotes[b.id]?.trim()||removingId===b.id}>{removingId===b.id?'Removing...':'Remove Benefit'}</Button></div>}</div>}</div>)}</CardContent></Card>
    </div></>;
}
