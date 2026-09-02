import { Head, router } from '@inertiajs/react';
import { CheckCircle2, ClipboardCheck, LockKeyhole, RefreshCw, Trophy } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type TieBreaker = {
    priority:number;
    criterion:string;
    comparison_direction:'ASC'|'DESC';
    criterion_reference:string|null;
};
type Summary = {
    total_candidates:number;
    qualified_count:number;
    not_qualified_count:number;
    pending_count:number;
    generated:boolean;
    ranked_count:number;
    generation_batch:string|null;
    generated_at:string|null;
};
type Rule = {
    id:number;
    name:string;
    code:string;
    version_no:number;
    status:'INACTIVE'|'ACTIVE'|'RETIRED';
    selection_mode:string;
    bucket_key:string;
    bucket_label:string;
    basis_capacity:number;
    merit_weight_percent:string;
    entrance_weight_percent:string;
    interview_weight_percent:string;
    minimum_merit_score:string|null;
    minimum_entrance_score:string|null;
    minimum_interview_score:string|null;
    minimum_final_score:string|null;
    program_name:string|null;
    program_code:string|null;
    degree_level_name:string|null;
    degree_level_code:string|null;
    degree_name:string|null;
    degree_code:string|null;
    session_name:string|null;
    session_code:string|null;
    tie_breakers:TieBreaker[];
    summary:Summary;
};
type TieSnapshot = {
    priority:number|null;
    criterion:string;
    direction:string;
    reference:string|null;
    value:unknown;
    source:string;
};
type RosterRow = {
    rank:number;
    choice_id:number;
    application_id:number;
    application_no:string;
    candidate_name:string;
    discipline_name:string|null;
    discipline_code:string|null;
    specialization_name:string|null;
    specialization_code:string|null;
    final_weighted_score:number;
    merit_score:number|null;
    entrance_score:number|null;
    interview_score:number|null;
    tie_break_snapshot:TieSnapshot[];
};
type Preview = {
    mode:'PREVIEW'|'GENERATED';
    rows:RosterRow[];
    total_ranked:number;
    truncated:boolean;
    generation_batch:string|null;
    generated_at:string|null;
}|null;
type Props = {
    college:{id:number;name:string;code:string;status:string};
    rules:Rule[];
    selectedRuleId:number|null;
    preview:Preview;
    can:{generate:boolean};
};

const score=(value:number|null|undefined)=>value==null?'—':Number(value).toFixed(3);
const criterionLabel=(value:string)=>({
    QUALIFYING_EXAM_SCORE:'Qualifying Exam Score',
    ENTRANCE_SCORE:'Entrance Score',
    INTERVIEW_SCORE:'Interview Score',
    RELEVANT_SUBJECT_SCORE:'Relevant Subject Score',
    DATE_OF_BIRTH:'Date of Birth',
    APPLICATION_SUBMITTED_AT:'Application Submitted At',
}[value]??value.replaceAll('_',' '));
const directionLabel=(tie:TieBreaker)=>{
    if(tie.criterion==='DATE_OF_BIRTH') return tie.comparison_direction==='ASC'?'Older first':'Younger first';
    if(tie.criterion==='APPLICATION_SUBMITTED_AT') return tie.comparison_direction==='ASC'?'Earlier first':'Later first';
    return tie.comparison_direction==='DESC'?'Higher first':'Lower first';
};
const formatDateTime=(seconds:number)=>{
    const date=new Date(seconds*1000);
    if(Number.isNaN(date.getTime())) return String(seconds);
    return new Intl.DateTimeFormat('en-IN',{
        day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:true,
    }).format(date);
};
const formatDate=(value:string)=>{
    const date=new Date(`${value}T00:00:00`);
    if(Number.isNaN(date.getTime())) return value;
    return new Intl.DateTimeFormat('en-IN',{day:'2-digit',month:'short',year:'numeric'}).format(date);
};
const snapshotValue=(tie:TieSnapshot)=>{
    const value=tie.value;
    if(value===null||value===undefined) return 'Unavailable';
    if(tie.criterion==='APPLICATION_SUBMITTED_AT'&&typeof value==='number') return formatDateTime(value);
    if(tie.criterion==='DATE_OF_BIRTH'&&typeof value==='string') return formatDate(value);
    if(typeof value==='number') return Number(value).toFixed(3);
    if(typeof value==='string') return value;
    return 'System fallback';
};
const snapshotDirection=(tie:TieSnapshot)=>{
    if(tie.criterion==='DATE_OF_BIRTH') return tie.direction==='ASC'?'Older first':'Younger first';
    if(tie.criterion==='APPLICATION_SUBMITTED_AT') return tie.direction==='ASC'?'Earlier first':'Later first';
    return tie.direction==='DESC'?'Higher first':'Lower first';
};

export default function AdmissionMerit({college,rules,selectedRuleId,preview,can}:Props){
    const selected=rules.find(rule=>rule.id===selectedRuleId)??rules[0]??null;
    const choose=(id:number)=>router.get(`/college/${college.id}/admission-merit`,{rule_id:id},{preserveState:true,replace:true,preserveScroll:true});
    const generate=()=>{
        if(!selected) return;
        if(!window.confirm(`Generate and lock final Merit / Roster for ${selected.code} V${selected.version_no}? Scores and Interview results consumed by this roster will become downstream-locked.`)) return;
        router.post(`/college/${college.id}/admission-selection-rules/${selected.id}/merit-roster/generate`,{}, {preserveScroll:true});
    };
    const generateDisabled=!selected||selected.summary.generated||selected.summary.pending_count>0||selected.summary.qualified_count===0||selected.status==='INACTIVE';

    return <><Head title="Merit / Roster Generation"/><div className="space-y-6 p-4 md:p-6">
        <header className="border-b pb-5">
            <p className="text-sm font-medium text-primary">{college.code} · Student Admission Processing</p>
            <h1 className="text-3xl font-semibold">Merit / Roster Generation</h1>
            <p className="max-w-5xl text-sm text-muted-foreground">Rank only SUBMITTED + ELIGIBLE candidates using each candidate's exact locked Selection Rule version, stored normalized scores, qualifying result and ordered structured tie-breakers. Reservation / quota seat consumption is intentionally deferred to the next Seat Allocation stage.</p>
        </header>

        {rules.length===0?<Card><CardContent className="grid place-items-center py-16 text-center"><Trophy className="size-10 text-muted-foreground"/><h2 className="mt-3 font-semibold">No candidate group is ready for Merit processing</h2><p className="mt-1 max-w-xl text-sm text-muted-foreground">A candidate must first have a SUBMITTED application, an ELIGIBLE preference and a locked Selection Rule version.</p></CardContent></Card>:<>
            <div className="grid gap-3 lg:grid-cols-2 xl:grid-cols-3">{rules.map(rule=><button key={rule.id} type="button" onClick={()=>choose(rule.id)} className={`min-w-0 rounded-lg border p-4 text-left transition-colors ${selected?.id===rule.id?'border-primary bg-primary/5':'bg-card hover:bg-muted/40'}`}>
                <div className="flex items-start justify-between gap-3"><div className="min-w-0"><div className="truncate font-semibold">{rule.program_name??'Program'} · {rule.bucket_label}</div><div className="mt-1 text-sm text-muted-foreground">{rule.code} V{rule.version_no} · {rule.session_name??rule.session_code??'Session'}</div><div className="mt-1 text-xs text-muted-foreground">{rule.degree_level_name??'Degree Level —'} · {rule.degree_name??'Degree —'}</div></div><span className="shrink-0 rounded-full border px-2 py-0.5 text-xs">{rule.summary.generated?'GENERATED':rule.status}</span></div>
                <div className="mt-3 grid grid-cols-4 gap-2 text-center text-xs"><div><div className="font-semibold text-base">{rule.summary.total_candidates}</div><div className="text-muted-foreground">Eligible</div></div><div><div className="font-semibold text-base">{rule.summary.qualified_count}</div><div className="text-muted-foreground">Qualified</div></div><div><div className="font-semibold text-base">{rule.summary.not_qualified_count}</div><div className="text-muted-foreground">Failed</div></div><div><div className="font-semibold text-base">{rule.summary.pending_count}</div><div className="text-muted-foreground">Pending</div></div></div>
            </button>)}</div>

            {selected&&<Card><CardHeader><div className="flex flex-wrap items-start justify-between gap-4"><div><CardTitle>{selected.program_name} · {selected.bucket_label}</CardTitle><p className="mt-1 text-sm text-muted-foreground">Locked rule {selected.code} V{selected.version_no} · {selected.selection_mode} · basis capacity {selected.basis_capacity}</p><div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground"><span><span className="font-medium text-foreground">Degree Level:</span> {selected.degree_level_name??'—'}{selected.degree_level_code?` (${selected.degree_level_code})`:''}</span><span><span className="font-medium text-foreground">Degree:</span> {selected.degree_name??'—'}{selected.degree_code?` (${selected.degree_code})`:''}</span></div></div>{can.generate&&<Button onClick={generate} disabled={generateDisabled}>{selected.summary.generated?<LockKeyhole/>:<Trophy/>}{selected.summary.generated?'Roster Locked':'Generate Final Roster'}</Button>}</div></CardHeader><CardContent className="space-y-5">
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Merit Weight</div><div className="font-semibold">{Number(selected.merit_weight_percent).toFixed(2)}%</div><div className="text-xs text-muted-foreground">Minimum {selected.minimum_merit_score??'Not set'}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Entrance Weight</div><div className="font-semibold">{Number(selected.entrance_weight_percent).toFixed(2)}%</div><div className="text-xs text-muted-foreground">Minimum {selected.minimum_entrance_score??'Not set'}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Interview Weight</div><div className="font-semibold">{Number(selected.interview_weight_percent).toFixed(2)}%</div><div className="text-xs text-muted-foreground">Minimum {selected.minimum_interview_score??'Not set'}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Final Minimum</div><div className="font-semibold">{selected.minimum_final_score??'Not set'}</div><div className="text-xs text-muted-foreground">Normalized / 100</div></div></div>

                <div className="rounded-md border p-4"><div className="font-medium">Ordered Tie-break Policy</div><div className="mt-2 flex flex-wrap gap-2">{selected.tie_breakers.length===0?<span className="text-sm text-destructive">No structured tie-breakers configured.</span>:selected.tie_breakers.map(tie=><span key={`${tie.priority}-${tie.criterion}-${tie.criterion_reference??''}`} className="rounded-full border bg-muted/30 px-3 py-1 text-xs">{tie.priority}. {criterionLabel(tie.criterion)}{tie.criterion_reference?` · ${tie.criterion_reference}`:''} · {directionLabel(tie)}</span>)}</div><p className="mt-2 text-xs text-muted-foreground">Primary ranking is Final Weighted Score (highest first). The structured tie-breakers run in displayed priority order. If every configured value is still equal/unavailable, the system uses submitted time, application number and choice ID only as a deterministic final fallback.</p></div>

                {selected.summary.pending_count>0&&<div className="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900"><div className="font-medium">Final generation blocked</div><p>{selected.summary.pending_count} candidate(s) still need Score Capture and/or required Interview completion. Finish them first; the system will not generate a partial final roster.</p></div>}
                {selected.summary.generated&&<div className="rounded-md border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900"><div className="flex items-center gap-2 font-medium"><CheckCircle2 className="size-4"/>Final Merit / Roster generated and locked</div><p>Ranked {selected.summary.ranked_count} candidate(s). Batch {selected.summary.generation_batch}. Existing scores/interviews are now protected from downstream changes.</p></div>}

                <div className="overflow-hidden rounded-md border"><div className="flex flex-wrap items-center justify-between gap-3 border-b bg-muted/20 px-4 py-3"><div><div className="font-medium">{preview?.mode==='GENERATED'?'Generated Roster':'Ranking Preview'}</div><p className="text-xs text-muted-foreground">Only QUALIFIED candidates are ranked. NOT_QUALIFIED candidates remain auditable in Score Capture but are excluded from this roster.</p></div>{preview?.mode==='PREVIEW'&&<Button variant="outline" size="sm" onClick={()=>choose(selected.id)}><RefreshCw/>Refresh Preview</Button>}</div>
                    {!preview||preview.rows.length===0?<div className="p-8 text-center text-sm text-muted-foreground">No qualified ranking rows are available yet.</div>:<div className="overflow-x-auto"><table className="w-full min-w-[900px] text-sm"><thead className="bg-muted/30 text-left"><tr><th className="px-3 py-2">Rank</th><th className="px-3 py-2">Candidate</th><th className="px-3 py-2">Application</th><th className="px-3 py-2 text-right">Final</th><th className="px-3 py-2 text-right">Merit</th><th className="px-3 py-2 text-right">Entrance</th><th className="px-3 py-2 text-right">Interview</th><th className="px-3 py-2">Tie-break Snapshot</th></tr></thead><tbody>{preview.rows.map(row=><tr key={row.choice_id} className="border-t align-top"><td className="px-3 py-3 font-semibold">#{row.rank}</td><td className="px-3 py-3"><div className="font-medium">{row.candidate_name}</div><div className="mt-1 text-xs text-muted-foreground"><span className="font-medium text-foreground">Discipline:</span> {row.discipline_name??'—'}{row.discipline_code?` (${row.discipline_code})`:''}{row.specialization_name?<> <span className="mx-1">·</span><span className="font-medium text-foreground">Specialization:</span> {row.specialization_name}{row.specialization_code?` (${row.specialization_code})`:''}</>:null}</div></td><td className="px-3 py-3">{row.application_no}</td><td className="px-3 py-3 text-right font-semibold">{score(row.final_weighted_score)}</td><td className="px-3 py-3 text-right">{score(row.merit_score)}</td><td className="px-3 py-3 text-right">{score(row.entrance_score)}</td><td className="px-3 py-3 text-right">{score(row.interview_score)}</td><td className="px-3 py-3"><div className="space-y-1">{row.tie_break_snapshot.filter(t=>t.criterion!=='DETERMINISTIC_FALLBACK').map(t=><div key={`${t.priority}-${t.criterion}`} className="text-xs"><span className="font-medium">{t.priority}. {criterionLabel(t.criterion)}:</span> {snapshotValue(t)} <span className="text-muted-foreground">· {snapshotDirection(t)}</span></div>)}</div></td></tr>)}</tbody></table></div>}
                    {preview?.truncated&&<div className="border-t px-4 py-2 text-xs text-muted-foreground">Showing the first 100 of {preview.total_ranked} ranked candidates in this screen. Final generation processes the complete candidate set.</div>}
                </div>
            </CardContent></Card>}
        </>}
    </div></>;
}
