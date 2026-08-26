import { Form, Head, Link, router } from '@inertiajs/react';
import { Check, ChevronLeft, ChevronRight, ClipboardCheck, Pencil, Plus, Search, Send, Trash2, Undo2, UserCheck, UserX, XCircle } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Cycle = {
    id:number; college_program_offering_id:number|null; academic_session_id:number; name:string; code:string;
    application_start_date:string; application_end_date:string; admission_start_date:string; admission_end_date:string;
    status:'INACTIVE'|'ACTIVE'|'CLOSED';
    academic_session:{id:number;name:string;code:string;is_current:boolean};
    program_offering?:{id:number;program_template:{name:string;code:string};academic_session:{name:string;code:string;is_current:boolean}}|null;
};

type SelectionContext = {
    college_admission_selection_rule_id:number;
    college_program_intake_id:number;
    college_program_offering_id:number;
    academic_session_id:number;
    program_name:string; program_code:string; session_name:string; session_code:string; is_current_session:boolean;
    bucket_key:string; bucket_type:string; bucket_label:string; basis_capacity:number;
    reservation_plan_id:number|null; reservation_state:'ACTIVE'|'NOT_DEFINED';
    rule_name:string; rule_code:string; rule_version:number; selection_mode:string;
    merit_weight_percent:string; entrance_weight_percent:string; interview_weight_percent:string;
};

type Choice = {
    id:number; preference_no:number; college_program_intake_id:number; college_program_reservation_plan_id:number|null;
    college_admission_selection_rule_id:number|null; bucket_type:string; bucket_key:string; bucket_label:string; basis_capacity:number;
    eligibility_status:'PENDING'|'ELIGIBLE'|'INELIGIBLE'; eligibility_reason?:string|null; eligibility_checked_at?:string|null;
    intake:{offering:{id:number;program_template:{name:string;code:string};academic_session:{name:string;code:string;is_current:boolean}}};
    reservation_plan?:{id:number;status:string}|null;
    selection_rule?:{id:number;name:string;code:string;version_no:number;selection_mode:string;status:string;merit_weight_percent:string;entrance_weight_percent:string;interview_weight_percent:string}|null;
};

type Application = {
    id:number; college_id:number; college_admission_cycle_id:number; application_no:string; external_reference?:string|null;
    candidate_name:string; email?:string|null; phone?:string|null; date_of_birth:string; status:'DRAFT'|'SUBMITTED'|'WITHDRAWN';
    submitted_at?:string|null; withdrawn_at?:string|null; remarks?:string|null;
    admission_cycle:Cycle; choices:Choice[];
};

type Paginated<T> = { data:T[]; current_page:number; last_page:number; prev_page_url:string|null; next_page_url:string|null; total:number };
type Props = {
    college:{id:number;name:string;code:string;status:string}; cycles:Cycle[]; selectionContexts:SelectionContext[];
    applications:Paginated<Application>; filters:{search:string};
    can:{create:boolean;update:boolean;submit:boolean;eligibility:boolean;withdraw:boolean};
};
type SearchableOption = {value:string;label:string;searchText?:string;meta?:string};
type ChoiceDraft = {intakeId:string;bucketKey:string};

function SearchablePicker({label,value,onChange,options,placeholder,disabled=false}:{label:string;value:string;onChange:(value:string)=>void;options:SearchableOption[];placeholder:string;disabled?:boolean}){
    const selected=options.find(option=>option.value===value);
    const [open,setOpen]=useState(false);
    const [query,setQuery]=useState('');
    const filtered=useMemo(()=>{
        const needle=query.trim().toLowerCase();
        if(!needle) return options;
        return options.filter(option=>`${option.label} ${option.searchText??''} ${option.meta??''}`.toLowerCase().includes(needle));
    },[options,query]);
    return <div className="relative min-w-0 space-y-2">
        <Label>{label}</Label>
        <button type="button" disabled={disabled} onClick={()=>{setOpen(current=>!current);setQuery('');}} className="flex min-h-10 w-full min-w-0 items-center justify-between gap-3 rounded-md border bg-background px-3 py-2 text-left text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50">
            <span className={selected?'min-w-0 truncate':'min-w-0 truncate text-muted-foreground'}>{selected?.label??placeholder}</span><Search className="size-4 shrink-0 text-muted-foreground"/>
        </button>
        {open&&<div className="absolute z-50 mt-1 w-full min-w-0 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md">
            <div className="border-b p-2"><div className="relative"><Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"/><Input autoFocus value={query} onChange={e=>setQuery(e.target.value)} placeholder={`Search ${label.toLowerCase()}...`} className="w-full pl-8" onKeyDown={e=>{if(e.key==='Escape')setOpen(false);}}/></div></div>
            <div className="max-h-64 overflow-y-auto p-1">{filtered.length===0?<div className="px-3 py-6 text-center text-sm text-muted-foreground">No matching option found.</div>:filtered.map(option=><button key={option.value} type="button" onMouseDown={e=>e.preventDefault()} onClick={()=>{onChange(option.value);setOpen(false);setQuery('');}} className="flex w-full min-w-0 items-start gap-2 rounded-sm px-2 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground"><Check className={`mt-0.5 size-4 shrink-0 ${option.value===value?'opacity-100':'opacity-0'}`}/><span className="min-w-0 flex-1"><span className="block break-words">{option.label}</span>{option.meta&&<span className="mt-0.5 block text-xs text-muted-foreground">{option.meta}</span>}</span></button>)}</div>
        </div>}
    </div>;
}

function reservationText(context:SelectionContext){return context.reservation_state==='ACTIVE'?'Reservation ACTIVE':'No Reservation · Open/General';}
function ruleText(context:SelectionContext){return `${context.rule_code} v${context.rule_version} · ${context.selection_mode} · M ${context.merit_weight_percent}% / E ${context.entrance_weight_percent}% / I ${context.interview_weight_percent}%`;}

function ApplicationForm({collegeId,cycles,contexts,application}:{collegeId:number;cycles:Cycle[];contexts:SelectionContext[];application?:Application}){
    const activeCycles=cycles.filter(c=>c.status==='ACTIVE'&&c.college_program_offering_id);
    const initialCycle=application?.college_admission_cycle_id?.toString()??activeCycles[0]?.id.toString()??'';
    const [cycleId,setCycleId]=useState(initialCycle);
    const selectedCycle=activeCycles.find(c=>c.id.toString()===cycleId);
    const cycleContexts=useMemo(()=>contexts.filter(context=>context.college_program_offering_id===selectedCycle?.college_program_offering_id),[contexts,selectedCycle]);
    const initialChoices:ChoiceDraft[]=application?.choices?.length?application.choices.map(choice=>({intakeId:String(choice.college_program_intake_id),bucketKey:choice.bucket_key})):[{intakeId:'',bucketKey:''}];
    const [choices,setChoices]=useState<ChoiceDraft[]>(initialChoices);
    const cycleOptions=activeCycles.map(c=>({value:String(c.id),label:`${c.name} · ${c.program_offering?.program_template.name??'Program Offering required'} · ${c.academic_session.name}`,searchText:`${c.code} ${c.program_offering?.program_template.code??''} ${c.academic_session.code}`,meta:`Applications ${c.application_start_date?.slice(0,10)} → ${c.application_end_date?.slice(0,10)}`}));
    const action=application?`/college/${collegeId}/admission-applications/${application.id}`:`/college/${collegeId}/admission-applications`;
    const updateChoice=(index:number,patch:Partial<ChoiceDraft>)=>setChoices(items=>items.map((item,i)=>i===index?{...item,...patch}:item));
    const resetCycle=(value:string)=>{setCycleId(value);setChoices([{intakeId:'',bucketKey:''}]);};
    return <Dialog>
        <DialogTrigger asChild>{application?<Button size="sm" variant="outline"><Pencil/>Edit Draft</Button>:<Button><Plus/>Add Application</Button>}</DialogTrigger>
        <DialogContent className="flex max-h-[92vh] w-[calc(100vw-2rem)] max-w-none flex-col overflow-hidden sm:!max-w-4xl">
            <DialogHeader className="shrink-0"><DialogTitle>{application?'Edit Admission Application':'Add Admission Application'}</DialogTitle><DialogDescription>Create a draft first. On Submit, the system revalidates each seat bucket and locks the exact ACTIVE Selection Rule version for future Score / Interview / Merit processing.</DialogDescription></DialogHeader>
            <Form action={action} method={application?'patch':'post'} className="flex min-h-0 flex-1 flex-col overflow-hidden">
                {({processing,errors})=><>
                    <div className="min-h-0 flex-1 space-y-5 overflow-y-auto px-1 py-2 pr-2">
                        <SearchablePicker label="Admission Cycle" value={cycleId} onChange={resetCycle} options={cycleOptions} placeholder="Select active Admission Cycle"/>
                        <input type="hidden" name="college_admission_cycle_id" value={cycleId}/>
                        {errors.college_admission_cycle_id&&<p className="text-xs text-destructive">{errors.college_admission_cycle_id}</p>}
                        <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                            <div className="min-w-0 space-y-2"><Label>Candidate Name</Label><Input className="w-full min-w-0" name="candidate_name" defaultValue={application?.candidate_name??''} placeholder="Full candidate name"/>{errors.candidate_name&&<p className="text-xs text-destructive">{errors.candidate_name}</p>}</div>
                            <div className="min-w-0 space-y-2"><Label>Date of Birth</Label><DatePicker id="admission-application-date-of-birth" name="date_of_birth" defaultValue={application?.date_of_birth?.slice(0,10)??''} max={new Date().toISOString().slice(0,10)} invalid={Boolean(errors.date_of_birth)}/>{errors.date_of_birth&&<p className="text-xs text-destructive">{errors.date_of_birth}</p>}</div>
                            <div className="min-w-0 space-y-2"><Label>Email <span className="text-muted-foreground">(optional)</span></Label><Input className="w-full min-w-0" name="email" type="email" defaultValue={application?.email??''}/>{errors.email&&<p className="text-xs text-destructive">{errors.email}</p>}</div>
                            <div className="min-w-0 space-y-2"><Label>Phone <span className="text-muted-foreground">(optional)</span></Label><Input className="w-full min-w-0" name="phone" defaultValue={application?.phone??''}/>{errors.phone&&<p className="text-xs text-destructive">{errors.phone}</p>}</div>
                            <div className="min-w-0 space-y-2 sm:col-span-2"><Label>External / Portal Reference <span className="text-muted-foreground">(optional)</span></Label><Input className="w-full min-w-0" name="external_reference" defaultValue={application?.external_reference??''} placeholder="Reference from external application portal, if any"/></div>
                        </div>
                        <div className="space-y-3 rounded-lg border p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3"><div><h3 className="font-medium">Seat Bucket Choices</h3><p className="text-sm text-muted-foreground">The Admission Cycle already fixes the Program Offering. Add one or more eligible seat buckets/specializations from that offering in preference order.</p></div><Button type="button" variant="outline" size="sm" onClick={()=>setChoices(items=>[...items,{intakeId:'',bucketKey:''}])} disabled={choices.length>=10}><Plus/>Add Choice</Button></div>
                            {choices.map((choice,index)=>{
                                const bucketContexts=cycleContexts;
                                const bucketOptions=bucketContexts.map(c=>({value:`${c.college_program_intake_id}|${c.bucket_key}`,label:`${c.bucket_label} · ${c.basis_capacity} seats`,searchText:`${c.bucket_label} ${c.bucket_type} ${c.rule_name} ${c.rule_code}`,meta:`${reservationText(c)} · ${ruleText(c)}`}));
                                const bucketValue=choice.intakeId&&choice.bucketKey?`${choice.intakeId}|${choice.bucketKey}`:'';
                                return <div key={index} className="min-w-0 rounded-md border p-3">
                                    <div className="mb-3 flex items-center justify-between"><span className="text-sm font-medium">Preference {index+1}</span>{choices.length>1&&<Button type="button" variant="ghost" size="icon" onClick={()=>setChoices(items=>items.filter((_,i)=>i!==index))} aria-label="Remove program choice"><Trash2/></Button>}</div>
                                    <div className="min-w-0">
                                        <SearchablePicker label="Admission Seat Bucket" value={bucketValue} onChange={value=>{const split=value.indexOf('|');updateChoice(index,{intakeId:value.slice(0,split),bucketKey:value.slice(split+1)});}} options={bucketOptions} placeholder={cycleId?'Search seat bucket for this Program Offering':'Select Admission Cycle first'} disabled={!cycleId}/>
                                    </div>
                                    <input type="hidden" name={`choices[${index}][college_program_intake_id]`} value={choice.intakeId}/><input type="hidden" name={`choices[${index}][bucket_key]`} value={choice.bucketKey}/>
                                </div>;
                            })}
                            {errors.choices&&<p className="text-xs text-destructive">{errors.choices}</p>}
                        </div>
                        <div className="space-y-2"><Label>Remarks <span className="text-muted-foreground">(optional)</span></Label><textarea name="remarks" defaultValue={application?.remarks??''} className="min-h-20 w-full min-w-0 resize-y rounded-md border bg-background px-3 py-2 text-sm"/></div>
                    </div>
                    <DialogFooter className="shrink-0 border-t bg-background pt-4"><DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" disabled={processing||!cycleId||choices.some(c=>!c.intakeId||!c.bucketKey)}>{processing&&<Spinner/>}{application?'Save Draft':'Create Draft'}</Button></DialogFooter>
                </>}
            </Form>
        </DialogContent>
    </Dialog>;
}

function ConfirmAction({label,title,description,action,method='patch',icon}:{label:string;title:string;description:string;action:string;method?:'patch'|'post';icon:'submit'|'withdraw'}){
    return <Dialog><DialogTrigger asChild><Button size="sm" variant="outline">{icon==='submit'?<Send/>:<XCircle/>}{label}</Button></DialogTrigger><DialogContent className="w-[calc(100vw-2rem)] sm:!max-w-lg"><DialogHeader><DialogTitle>{title}</DialogTitle><DialogDescription>{description}</DialogDescription></DialogHeader><Form action={action} method={method}>{({processing,errors})=><><div className="space-y-2">{Object.values(errors).filter(Boolean).map((message,index)=><div key={index} className="rounded-md border border-destructive/40 bg-destructive/5 px-3 py-2 text-sm text-destructive">{String(message)}</div>)}</div><DialogFooter><DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" variant="outline" disabled={processing}>{processing&&<Spinner/>}{icon==='submit'?<Send/>:<XCircle/>}{label}</Button></DialogFooter></>}</Form></DialogContent></Dialog>;
}

function IneligibleDialog({collegeId,choice}:{collegeId:number;choice:Choice}){
    return <Dialog><DialogTrigger asChild><Button size="sm" variant="outline"><UserX/>Mark Ineligible</Button></DialogTrigger><DialogContent className="w-[calc(100vw-2rem)] sm:!max-w-lg"><DialogHeader><DialogTitle>Mark Candidate Ineligible</DialogTitle><DialogDescription>This is preliminary/basic eligibility for this program choice. Selection-rule score thresholds are evaluated later by Score / Merit processing.</DialogDescription></DialogHeader><Form action={`/college/${collegeId}/admission-application-choices/${choice.id}/eligibility`} method="patch">{({processing,errors})=><><input type="hidden" name="eligibility_status" value="INELIGIBLE"/><div className="space-y-2"><Label>Reason</Label><textarea name="eligibility_reason" defaultValue={choice.eligibility_reason??''} className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Why this candidate is not eligible for this program choice"/>{errors.eligibility_reason&&<p className="text-xs text-destructive">{errors.eligibility_reason}</p>}</div><DialogFooter><DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" variant="outline" disabled={processing}>{processing&&<Spinner/>}<UserX/>Mark Ineligible</Button></DialogFooter></>}</Form></DialogContent></Dialog>;
}

function EligibilityActions({collegeId,choice}:{collegeId:number;choice:Choice}){
    return <div className="flex flex-wrap gap-2">
        <Form action={`/college/${collegeId}/admission-application-choices/${choice.id}/eligibility`} method="patch">{({processing})=><><input type="hidden" name="eligibility_status" value="ELIGIBLE"/><Button type="submit" size="sm" variant="outline" disabled={processing||choice.eligibility_status==='ELIGIBLE'}>{processing?<Spinner/>:<UserCheck/>}Mark Eligible</Button></>}</Form>
        <IneligibleDialog collegeId={collegeId} choice={choice}/>
        {choice.eligibility_status!=='PENDING'&&<Form action={`/college/${collegeId}/admission-application-choices/${choice.id}/eligibility`} method="patch">{({processing})=><><input type="hidden" name="eligibility_status" value="PENDING"/><Button type="submit" size="sm" variant="ghost" disabled={processing}>{processing?<Spinner/>:<Undo2/>}Reset Pending</Button></>}</Form>}
    </div>;
}

function statusClass(status:string){if(status==='SUBMITTED'||status==='ELIGIBLE')return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300';if(status==='INELIGIBLE'||status==='WITHDRAWN')return 'border-destructive/30 bg-destructive/5 text-destructive';return 'border-border bg-muted text-muted-foreground';}

export default function AdmissionApplications({college,cycles,selectionContexts,applications,filters,can}:Props){
    const [search,setSearch]=useState(filters.search??'');
    const activeCycles=cycles.filter(c=>c.status==='ACTIVE'&&c.college_program_offering_id);
    const submitSearch=(e:FormEvent)=>{e.preventDefault();router.get(`/college/${college.id}/admission-applications`,search.trim()?{search:search.trim()}:{},{preserveState:true,replace:true});};
    return <><Head title="Applications / Candidate Eligibility"/><div className="space-y-6 p-4 md:p-6">
        <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5"><div><p className="text-sm font-medium text-primary">{college.code} · Student Admission Processing</p><h1 className="text-3xl font-semibold">Applications / Candidate Eligibility</h1><p className="max-w-4xl text-sm text-muted-foreground">Capture candidate applications against the exact Program Offering → Intake seat bucket. Submission locks the current ACTIVE Selection Rule version for later Merit, Entrance and Interview processing.</p></div>{can.create&&college.status==='ACTIVE'&&activeCycles.length>0&&selectionContexts.length>0&&<ApplicationForm collegeId={college.id} cycles={cycles} contexts={selectionContexts}/>}</header>

        {(activeCycles.length===0||selectionContexts.length===0)&&<Card><CardContent className="space-y-1 p-4 text-sm"><div className="font-medium">Application entry is not ready yet.</div>{activeCycles.length===0&&<p className="text-muted-foreground">Create and activate an Admission Cycle first.</p>}{selectionContexts.length===0&&<p className="text-muted-foreground">At least one eligible seat bucket must have an ACTIVE Merit / Roster / Selection Rule.</p>}</CardContent></Card>}

        <Card><CardContent className="p-4"><form onSubmit={submitSearch} className="flex flex-wrap gap-2"><div className="relative min-w-[240px] flex-1"><Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"/><Input value={search} onChange={e=>setSearch(e.target.value)} className="pl-9" placeholder="Search application no, candidate, email, phone or external reference"/></div><Button type="submit" variant="outline"><Search/>Search</Button>{filters.search&&<Button type="button" variant="ghost" onClick={()=>{setSearch('');router.get(`/college/${college.id}/admission-applications`,{}, {preserveState:true,replace:true});}}>Clear</Button>}</form></CardContent></Card>

        {applications.data.length===0?<Card><CardContent className="grid place-items-center py-16 text-center"><ClipboardCheck className="size-10 text-muted-foreground"/><h2 className="mt-3 font-semibold">No Admission Application found</h2><p className="mt-1 text-sm text-muted-foreground">Create a draft when an Admission Cycle and Selection Rule are active.</p></CardContent></Card>:<div className="space-y-4">{applications.data.map(application=><Card key={application.id}><CardHeader className="gap-3"><div className="flex flex-wrap items-start justify-between gap-3"><div className="min-w-0"><div className="flex flex-wrap items-center gap-2"><CardTitle>{application.candidate_name}</CardTitle><span className={`rounded-full border px-2 py-0.5 text-xs font-medium ${statusClass(application.status)}`}>{application.status}</span></div><p className="mt-1 text-sm text-muted-foreground">{application.application_no} · {application.admission_cycle.name} · {application.admission_cycle.program_offering?.program_template.name??'Program Offering'} · {application.admission_cycle.academic_session.name}</p><p className="mt-1 text-xs text-muted-foreground">DOB {application.date_of_birth?.slice(0,10)}{application.email?` · ${application.email}`:''}{application.phone?` · ${application.phone}`:''}{application.external_reference?` · Ref ${application.external_reference}`:''}</p></div><div className="flex flex-wrap gap-2">{application.status==='DRAFT'&&can.update&&<ApplicationForm collegeId={college.id} cycles={cycles} contexts={selectionContexts} application={application}/>} {application.status==='DRAFT'&&can.submit&&<ConfirmAction label="Submit" title="Submit Admission Application?" description="Submission revalidates every choice and locks the exact ACTIVE Selection Rule version. Candidate/program choices can no longer be edited from this stage." action={`/college/${college.id}/admission-applications/${application.id}/submit`} icon="submit"/>}{application.status!=='WITHDRAWN'&&can.withdraw&&<ConfirmAction label="Withdraw" title="Withdraw Admission Application?" description="Use this only when the candidate/application is being withdrawn. Future downstream Admission records will block withdrawal once processing has progressed." action={`/college/${college.id}/admission-applications/${application.id}/withdraw`} icon="withdraw"/>}</div></div></CardHeader><CardContent className="space-y-3">
                {application.submitted_at&&<p className="text-xs text-muted-foreground">Submitted at: {new Date(application.submitted_at).toLocaleString()}</p>}
                <div className="space-y-3">{application.choices.map(choice=><div key={choice.id} className="rounded-md border p-3"><div className="flex flex-wrap items-start justify-between gap-3"><div className="min-w-0"><div className="font-medium">Preference {choice.preference_no} · {choice.intake.offering.program_template.name}</div><div className="mt-1 text-sm text-muted-foreground">{choice.bucket_label} · {choice.basis_capacity} seats · {choice.reservation_plan?'Reservation ACTIVE':'Open/General'}</div>{choice.selection_rule&&<div className="mt-1 text-xs text-muted-foreground">Rule: {choice.selection_rule.code} v{choice.selection_rule.version_no} · {choice.selection_rule.selection_mode} · M {choice.selection_rule.merit_weight_percent}% / E {choice.selection_rule.entrance_weight_percent}% / I {choice.selection_rule.interview_weight_percent}%</div>}</div><span className={`rounded-full border px-2 py-0.5 text-xs font-medium ${statusClass(choice.eligibility_status)}`}>{choice.eligibility_status}</span></div>{choice.eligibility_reason&&<p className="mt-2 text-sm text-muted-foreground"><span className="font-medium text-foreground">Eligibility note:</span> {choice.eligibility_reason}</p>}<div className="mt-3 rounded-md border bg-muted/20 p-3"><div className="flex flex-wrap items-center justify-between gap-3"><div><div className="text-sm font-medium">Eligibility Review</div><p className="mt-0.5 text-xs text-muted-foreground">{application.status==='DRAFT'?'Submit this application first. Eligibility is reviewed only after the candidate locks the submitted program/seat-bucket choice.':'Review this submitted preference before Score / Entrance / Interview processing.'}</p></div>{application.status==='SUBMITTED'&&can.eligibility?<EligibilityActions collegeId={college.id} choice={choice}/>:application.status==='DRAFT'?<div className="flex flex-wrap gap-2"><Button type="button" size="sm" variant="outline" disabled><UserCheck/>Mark Eligible</Button><Button type="button" size="sm" variant="outline" disabled><UserX/>Mark Ineligible</Button></div>:null}</div></div></div>)}</div>
                {application.remarks&&<p className="text-sm text-muted-foreground"><span className="font-medium text-foreground">Remarks:</span> {application.remarks}</p>}
            </CardContent></Card>)}</div>}

        {applications.last_page>1&&<div className="flex items-center justify-between"><p className="text-sm text-muted-foreground">Page {applications.current_page} of {applications.last_page} · {applications.total} applications</p><div className="flex gap-2">{applications.prev_page_url?<Link href={applications.prev_page_url}><Button variant="outline" size="sm"><ChevronLeft/>Previous</Button></Link>:<Button variant="outline" size="sm" disabled><ChevronLeft/>Previous</Button>}{applications.next_page_url?<Link href={applications.next_page_url}><Button variant="outline" size="sm">Next<ChevronRight/></Button></Link>:<Button variant="outline" size="sm" disabled>Next<ChevronRight/></Button>}</div></div>}
    </div></>;
}
