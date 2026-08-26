import { Form, Head } from '@inertiajs/react';
import { Archive, ArrowDown, ArrowUp, BookOpenCheck, Check, Pencil, Plus, Power, Search, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type Bucket = {
    college_program_intake_id:number;
    college_program_offering_id:number;
    bucket_key:string;
    bucket_type:string;
    bucket_label:string;
    basis_capacity:number;
    program_name:string;
    program_code:string;
    session_name:string;
    session_code:string;
    is_current_session:boolean;
    reservation_plan_id:number|null;
    reservation_state:'NOT_DEFINED'|'ACTIVE'|'INACTIVE';
    eligible:boolean;
};

type TieBreakerCriterion = 'QUALIFYING_EXAM_SCORE'|'ENTRANCE_SCORE'|'INTERVIEW_SCORE'|'RELEVANT_SUBJECT_SCORE'|'DATE_OF_BIRTH'|'APPLICATION_SUBMITTED_AT';
type ComparisonDirection = 'ASC'|'DESC';
type TieBreaker = { id?:number; priority?:number; criterion:TieBreakerCriterion; comparison_direction:ComparisonDirection; criterion_reference?:string|null };

type Rule = {
    id:number;
    college_program_intake_id:number;
    college_program_reservation_plan_id:number|null;
    bucket_type:string;
    bucket_key:string;
    bucket_label:string;
    basis_capacity:number;
    version_no:number;
    name:string;
    code:string;
    selection_mode:'MERIT'|'ENTRANCE'|'INTERVIEW'|'COMBINED';
    merit_weight_percent:string;
    entrance_weight_percent:string;
    interview_weight_percent:string;
    minimum_merit_score?:string|null;
    minimum_entrance_score?:string|null;
    minimum_interview_score?:string|null;
    minimum_final_score?:string|null;
    roster_rule_reference?:string|null;
    tie_breaker_rules?:string|null;
    tie_breakers:TieBreaker[];
    notes?:string|null;
    status:'INACTIVE'|'ACTIVE'|'RETIRED';
    reservation_state:'NOT_DEFINED'|'ACTIVE'|'INACTIVE';
    intake:{offering:{program_template:{name:string;code:string};academic_session:{name:string;code:string;is_current:boolean}}};
};

type Props = {
    college:{id:number;name:string;code:string;status:string};
    eligibleBuckets:Bucket[];
    blockedBuckets:Bucket[];
    rules:Rule[];
    can:{create:boolean;update:boolean;enable:boolean;disable:boolean};
};

const criterionOptions:{value:TieBreakerCriterion;label:string}[] = [
    {value:'QUALIFYING_EXAM_SCORE',label:'Qualifying Exam Score'},
    {value:'ENTRANCE_SCORE',label:'Entrance Score'},
    {value:'INTERVIEW_SCORE',label:'Interview Score'},
    {value:'RELEVANT_SUBJECT_SCORE',label:'Relevant Subject Score'},
    {value:'DATE_OF_BIRTH',label:'Date of Birth'},
    {value:'APPLICATION_SUBMITTED_AT',label:'Application Submitted At'},
];

function bucketValue(bucket:Bucket){ return `${bucket.college_program_intake_id}|${bucket.bucket_key}`; }
function reservationText(state:Bucket['reservation_state']|Rule['reservation_state']){
    if(state==='ACTIVE') return 'Reservation ACTIVE';
    if(state==='INACTIVE') return 'Reservation INACTIVE';
    return 'No Reservation · full bucket treated as Open/General';
}
function criterionLabel(value:TieBreakerCriterion){ return criterionOptions.find(option=>option.value===value)?.label ?? value; }
function directionLabel(criterion:TieBreakerCriterion,direction:ComparisonDirection){
    if(criterion==='DATE_OF_BIRTH') return direction==='ASC'?'Older first':'Younger first';
    if(criterion==='APPLICATION_SUBMITTED_AT') return direction==='ASC'?'Earlier first':'Later first';
    return direction==='DESC'?'Higher first':'Lower first';
}
function defaultDirection(criterion:TieBreakerCriterion):ComparisonDirection{
    return criterion==='DATE_OF_BIRTH'||criterion==='APPLICATION_SUBMITTED_AT'?'ASC':'DESC';
}

type SearchableOption = { value:string; label:string; searchText?:string; meta?:string };

function SearchablePicker({label,value,onChange,options,placeholder,disabled=false}:{label:string;value:string;onChange:(value:string)=>void;options:SearchableOption[];placeholder:string;disabled?:boolean}){
    const selected=options.find(option=>option.value===value);
    const [open,setOpen]=useState(false);
    const [query,setQuery]=useState('');
    const filtered=useMemo(()=>{
        const needle=query.trim().toLowerCase();
        if(!needle) return options;
        return options.filter(option=>`${option.label} ${option.searchText ?? ''} ${option.meta ?? ''}`.toLowerCase().includes(needle));
    },[options,query]);

    return <div className="relative min-w-0 space-y-2">
        <Label>{label}</Label>
        <button type="button" disabled={disabled} onClick={()=>{setOpen(current=>!current);setQuery('');}} className="flex min-h-10 w-full min-w-0 items-center justify-between gap-3 rounded-md border bg-background px-3 py-2 text-left text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50">
            <span className={selected?'min-w-0 truncate':'min-w-0 truncate text-muted-foreground'}>{selected?.label ?? placeholder}</span>
            <Search className="size-4 shrink-0 text-muted-foreground"/>
        </button>
        {open&&<div className="absolute z-50 mt-1 w-full min-w-0 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md">
            <div className="border-b p-2">
                <div className="relative">
                    <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"/>
                    <Input autoFocus value={query} onChange={event=>setQuery(event.target.value)} placeholder={`Search ${label.toLowerCase()}...`} className="w-full pl-8" onKeyDown={event=>{if(event.key==='Escape')setOpen(false);}}/>
                </div>
            </div>
            <div className="max-h-64 overflow-y-auto p-1">
                {filtered.length===0?<div className="px-3 py-6 text-center text-sm text-muted-foreground">No matching option found.</div>:filtered.map(option=><button key={option.value} type="button" onMouseDown={event=>event.preventDefault()} onClick={()=>{onChange(option.value);setOpen(false);setQuery('');}} className="flex w-full min-w-0 items-start gap-2 rounded-sm px-2 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground">
                    <Check className={`mt-0.5 size-4 shrink-0 ${option.value===value?'opacity-100':'opacity-0'}`}/>
                    <span className="min-w-0 flex-1"><span className="block break-words">{option.label}</span>{option.meta&&<span className="mt-0.5 block text-xs text-muted-foreground">{option.meta}</span>}</span>
                </button>)}
            </div>
        </div>}
    </div>;
}

function RuleForm({collegeId,buckets,rule}:{collegeId:number;buckets:Bucket[];rule?:Rule}){
    const [mode,setMode]=useState<Rule['selection_mode']>(rule?.selection_mode ?? 'MERIT');
    const offeringIds=useMemo(()=>Array.from(new Set(buckets.map(bucket=>bucket.college_program_offering_id))),[buckets]);
    const defaultOfferingId=offeringIds[0]?.toString() ?? '';
    const [offeringId,setOfferingId]=useState(defaultOfferingId);
    const filteredBuckets=useMemo(()=>buckets.filter(bucket=>bucket.college_program_offering_id.toString()===offeringId),[buckets,offeringId]);
    const [seatKey,setSeatKey]=useState('');
    const [tieBreakers,setTieBreakers]=useState<TieBreaker[]>(rule?.tie_breakers?.length ? rule.tie_breakers.map(item=>({...item})) : []);
    const selected=buckets.find(b=>bucketValue(b)===seatKey);
    const offeringOptions=useMemo<SearchableOption[]>(()=>offeringIds.map(id=>{
        const sample=buckets.find(bucket=>bucket.college_program_offering_id===id)!;
        return {
            value:id.toString(),
            label:`${sample.program_name} · ${sample.session_name}`,
            searchText:`${sample.program_code} ${sample.session_code} ${sample.program_name} ${sample.session_name}`,
            meta:sample.is_current_session?'Current academic session':'Eligible program offering',
        };
    }),[buckets,offeringIds]);
    const bucketOptions=useMemo<SearchableOption[]>(()=>filteredBuckets.map(bucket=>({
        value:bucketValue(bucket),
        label:`${bucket.bucket_label} · ${bucket.basis_capacity} seats`,
        searchText:`${bucket.bucket_label} ${bucket.bucket_type} ${reservationText(bucket.reservation_state)}`,
        meta:reservationText(bucket.reservation_state),
    })),[filteredBuckets]);
    const defaults=useMemo(()=>{
        if(mode==='MERIT') return ['100','0','0'];
        if(mode==='ENTRANCE') return ['0','100','0'];
        if(mode==='INTERVIEW') return ['0','0','100'];
        if(rule?.selection_mode==='COMBINED') return [rule.merit_weight_percent,rule.entrance_weight_percent,rule.interview_weight_percent ?? '0'];
        return ['40','40','20'];
    },[mode,rule]);
    const action=rule?`/college/${collegeId}/admission-selection-rules/${rule.id}`:`/college/${collegeId}/admission-selection-rules`;

    const addTieBreaker=()=>{
        if(tieBreakers.length>=10) return;
        setTieBreakers(items=>[...items,{criterion:'QUALIFYING_EXAM_SCORE',comparison_direction:'DESC',criterion_reference:''}]);
    };
    const updateTieBreaker=(index:number,patch:Partial<TieBreaker>)=>setTieBreakers(items=>items.map((item,i)=>i===index?{...item,...patch}:item));
    const removeTieBreaker=(index:number)=>setTieBreakers(items=>items.filter((_,i)=>i!==index));
    const moveTieBreaker=(index:number,direction:-1|1)=>setTieBreakers(items=>{
        const target=index+direction;
        if(target<0||target>=items.length) return items;
        const copy=[...items];
        [copy[index],copy[target]]=[copy[target],copy[index]];
        return copy;
    });

    return <Dialog>
        <DialogTrigger asChild><Button variant={rule?'ghost':'default'} size={rule?'icon':'default'}>{rule?<Pencil/>:<Plus/>}{!rule&&'Add Selection Rule'}</Button></DialogTrigger>
        <DialogContent className="flex max-h-[92vh] w-[calc(100vw-2rem)] min-w-0 flex-col gap-0 overflow-hidden p-0 sm:!max-w-3xl">
            <DialogHeader className="shrink-0 border-b px-5 py-4 pr-12 sm:px-6">
                <DialogTitle>{rule?'Edit Selection Rule':'Add Merit / Roster / Selection Rule'}</DialogTitle>
                <DialogDescription className="max-w-2xl">Define ranking weights, qualifying thresholds and an ordered machine-readable tie-break sequence for one effective Intake seat bucket.</DialogDescription>
            </DialogHeader>
            <Form action={action} method={rule?'patch':'post'} className="flex min-h-0 min-w-0 flex-1 flex-col">
                {({processing,errors})=><>
                    <div className="min-h-0 min-w-0 flex-1 space-y-5 overflow-y-auto px-5 py-4 sm:px-6">
                        {!rule&&<div className="grid min-w-0 gap-4 sm:grid-cols-2">
                            <SearchablePicker
                                label="Program Offering"
                                value={offeringId}
                                onChange={value=>{setOfferingId(value);setSeatKey('');}}
                                options={offeringOptions}
                                placeholder="Search and select Program Offering"
                            />
                            <SearchablePicker
                                label="Admission Seat Bucket"
                                value={seatKey}
                                onChange={setSeatKey}
                                options={bucketOptions}
                                placeholder={offeringId?'Search and select Seat Bucket':'Select Program Offering first'}
                                disabled={!offeringId}
                            />
                            <input type="hidden" name="college_program_intake_id" value={selected?.college_program_intake_id ?? ''}/>
                            <input type="hidden" name="bucket_key" value={selected?.bucket_key ?? ''}/>
                            <div className="sm:col-span-2">
                                {selected&&<p className="text-xs text-muted-foreground">{selected.program_name} · {selected.session_name} · {selected.bucket_label} · {reservationText(selected.reservation_state)}</p>}
                                {(errors.college_program_intake_id||errors.bucket_key)&&<p className="text-xs text-destructive">{errors.college_program_intake_id||errors.bucket_key}</p>}
                            </div>
                        </div>}

                        <div className="grid min-w-0 gap-4 sm:grid-cols-2">
                            <div className="min-w-0 space-y-2"><Label>Name</Label><Input className="w-full min-w-0" name="name" defaultValue={rule?.name} placeholder="UG Merit Rule"/>{errors.name&&<p className="text-xs text-destructive">{errors.name}</p>}</div>
                            <div className="min-w-0 space-y-2"><Label>Code</Label><Input className="w-full min-w-0" name="code" defaultValue={rule?.code} placeholder="UG-MERIT"/>{errors.code&&<p className="text-xs text-destructive">{errors.code}</p>}</div>
                        </div>

                        <div className="min-w-0 space-y-2"><Label>Selection Mode</Label><Select name="selection_mode" value={mode} onValueChange={v=>setMode(v as Rule['selection_mode'])}><SelectTrigger className="w-full min-w-0"><SelectValue/></SelectTrigger><SelectContent className="max-w-[calc(100vw-2rem)]"><SelectItem value="MERIT">Merit only</SelectItem><SelectItem value="ENTRANCE">Entrance only</SelectItem><SelectItem value="INTERVIEW">Interview only</SelectItem><SelectItem value="COMBINED">Combined components</SelectItem></SelectContent></Select></div>

                        <div className="space-y-3 rounded-lg border p-4">
                            <div><h3 className="font-medium">Selection Components</h3><p className="text-xs text-muted-foreground">Merit, Entrance and Interview are normalized 0-100 score components. In Combined mode, use at least two positive weights and keep the total exactly 100%.</p></div>
                            <div className="grid min-w-0 gap-4 md:grid-cols-3">
                                <div className="min-w-0 space-y-2"><Label>Merit Weight %</Label><Input className="w-full min-w-0" key={`m-${mode}`} type="number" step="0.01" min="0" max="100" name="merit_weight_percent" defaultValue={rule?.selection_mode===mode?rule.merit_weight_percent:defaults[0]} readOnly={mode!=='COMBINED'}/></div>
                                <div className="min-w-0 space-y-2"><Label>Entrance Weight %</Label><Input className="w-full min-w-0" key={`e-${mode}`} type="number" step="0.01" min="0" max="100" name="entrance_weight_percent" defaultValue={rule?.selection_mode===mode?rule.entrance_weight_percent:defaults[1]} readOnly={mode!=='COMBINED'}/></div>
                                <div className="min-w-0 space-y-2"><Label>Interview Weight %</Label><Input className="w-full min-w-0" key={`i-${mode}`} type="number" step="0.01" min="0" max="100" name="interview_weight_percent" defaultValue={rule?.selection_mode===mode?(rule.interview_weight_percent ?? '0'):defaults[2]} readOnly={mode!=='COMBINED'}/></div>
                            </div>
                        </div>
                        {errors.weights&&<p className="text-xs text-destructive">{errors.weights}</p>}

                        <div className="space-y-3 rounded-lg border p-4">
                            <div><h3 className="font-medium">Qualifying Thresholds</h3><p className="text-xs text-muted-foreground">Optional normalized scores from 0 to 100. In Combined mode, set a component minimum only when that component has a positive weight.</p></div>
                            <div className="grid min-w-0 gap-4 md:grid-cols-2">
                                {(mode==='MERIT'||mode==='COMBINED')?<div className="min-w-0 space-y-2"><Label>Minimum Merit Score</Label><Input className="w-full min-w-0" type="number" step="0.001" min="0" max="100" name="minimum_merit_score" defaultValue={rule?.minimum_merit_score ?? ''} placeholder="Optional"/>{errors.minimum_merit_score&&<p className="text-xs text-destructive">{errors.minimum_merit_score}</p>}</div>:<input type="hidden" name="minimum_merit_score" value=""/>}
                                {(mode==='ENTRANCE'||mode==='COMBINED')?<div className="min-w-0 space-y-2"><Label>Minimum Entrance Score</Label><Input className="w-full min-w-0" type="number" step="0.001" min="0" max="100" name="minimum_entrance_score" defaultValue={rule?.minimum_entrance_score ?? ''} placeholder="Optional"/>{errors.minimum_entrance_score&&<p className="text-xs text-destructive">{errors.minimum_entrance_score}</p>}</div>:<input type="hidden" name="minimum_entrance_score" value=""/>}
                                {(mode==='INTERVIEW'||mode==='COMBINED')?<div className="min-w-0 space-y-2"><Label>Minimum Interview Score</Label><Input className="w-full min-w-0" type="number" step="0.001" min="0" max="100" name="minimum_interview_score" defaultValue={rule?.minimum_interview_score ?? ''} placeholder="Optional"/>{errors.minimum_interview_score&&<p className="text-xs text-destructive">{errors.minimum_interview_score}</p>}</div>:<input type="hidden" name="minimum_interview_score" value=""/>}
                                {mode==='COMBINED'?<div className="min-w-0 space-y-2"><Label>Minimum Final Weighted Score</Label><Input className="w-full min-w-0" type="number" step="0.001" min="0" max="100" name="minimum_final_score" defaultValue={rule?.minimum_final_score ?? ''} placeholder="Optional"/>{errors.minimum_final_score&&<p className="text-xs text-destructive">{errors.minimum_final_score}</p>}</div>:<input type="hidden" name="minimum_final_score" value=""/>}
                            </div>
                        </div>

                        <div className="min-w-0 space-y-2"><Label>Roster / Policy Reference</Label><Input className="w-full min-w-0" name="roster_rule_reference" defaultValue={rule?.roster_rule_reference ?? ''} placeholder="University/Government notification, roster rule or policy reference"/></div>

                        <div className="space-y-3 rounded-lg border p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div><h3 className="font-medium">Structured Tie-breakers</h3><p className="text-xs text-muted-foreground">Applied from Priority 1 downward when candidates have the same ranking score. At least one is required before activation.</p></div>
                                <Button type="button" variant="outline" size="sm" onClick={addTieBreaker} disabled={tieBreakers.length>=10}><Plus/>Add Tie-breaker</Button>
                            </div>

                            {tieBreakers.length===0?<div className="rounded-md border border-dashed p-4 text-sm text-muted-foreground">No structured tie-breaker added yet. You may save the rule as INACTIVE, but activation will require at least one.</div>:<div className="space-y-3">{tieBreakers.map((item,index)=><div key={`${index}-${item.criterion}`} className="min-w-0 rounded-md border p-3">
                                <div className="mb-3 flex items-center justify-between gap-2"><span className="text-sm font-medium">Priority {index+1}</span><div className="flex gap-1"><Button type="button" size="icon" variant="ghost" onClick={()=>moveTieBreaker(index,-1)} disabled={index===0} aria-label="Move tie-breaker up"><ArrowUp/></Button><Button type="button" size="icon" variant="ghost" onClick={()=>moveTieBreaker(index,1)} disabled={index===tieBreakers.length-1} aria-label="Move tie-breaker down"><ArrowDown/></Button><Button type="button" size="icon" variant="ghost" onClick={()=>removeTieBreaker(index)} aria-label="Remove tie-breaker"><Trash2/></Button></div></div>
                                <input type="hidden" name={`tie_breakers[${index}][criterion]`} value={item.criterion}/>
                                <input type="hidden" name={`tie_breakers[${index}][comparison_direction]`} value={item.comparison_direction}/>
                                <div className="grid min-w-0 gap-3 sm:grid-cols-2">
                                    <div className="min-w-0 space-y-2"><Label>Criterion</Label><Select value={item.criterion} onValueChange={value=>{const criterion=value as TieBreakerCriterion;updateTieBreaker(index,{criterion,comparison_direction:defaultDirection(criterion),criterion_reference:criterion==='RELEVANT_SUBJECT_SCORE'?item.criterion_reference:''});}}><SelectTrigger className="w-full min-w-0"><SelectValue/></SelectTrigger><SelectContent>{criterionOptions.map(option=><SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent></Select></div>
                                    <div className="min-w-0 space-y-2"><Label>Preference</Label><Select value={item.comparison_direction} onValueChange={value=>updateTieBreaker(index,{comparison_direction:value as ComparisonDirection})}><SelectTrigger className="w-full min-w-0"><SelectValue/></SelectTrigger><SelectContent><SelectItem value="DESC">{directionLabel(item.criterion,'DESC')}</SelectItem><SelectItem value="ASC">{directionLabel(item.criterion,'ASC')}</SelectItem></SelectContent></Select></div>
                                </div>
                                {item.criterion==='RELEVANT_SUBJECT_SCORE'&&<div className="mt-3 min-w-0 space-y-2"><Label>Subject / Field Reference</Label><Input className="w-full min-w-0" name={`tie_breakers[${index}][criterion_reference]`} value={item.criterion_reference ?? ''} onChange={event=>updateTieBreaker(index,{criterion_reference:event.target.value})} placeholder="e.g. English, Mathematics, Physics"/></div>}
                            </div>)}</div>}
                            {errors.tie_breakers&&<p className="text-xs text-destructive">{errors.tie_breakers}</p>}
                        </div>

                        <div className="space-y-2"><Label>Tie-break Policy Notes <span className="text-muted-foreground">(optional)</span></Label><textarea name="tie_breaker_rules" defaultValue={rule?.tie_breaker_rules ?? ''} placeholder="Official wording or special policy explanation. This note is not executed by the ranking engine." className="min-h-20 w-full min-w-0 resize-y rounded-md border bg-background px-3 py-2 text-sm"/></div>
                        <div className="space-y-2"><Label>Notes</Label><textarea name="notes" defaultValue={rule?.notes ?? ''} className="min-h-20 w-full min-w-0 resize-y rounded-md border bg-background px-3 py-2 text-sm"/></div>
                    </div>
                    <DialogFooter className="shrink-0 border-t bg-background px-5 py-4 sm:px-6">
                        <DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose>
                        <Button type="submit" disabled={processing||(!rule&&!selected)}>{processing&&<Spinner/>}{rule?'Save changes':'Create inactive version'}</Button>
                    </DialogFooter>
                </>}
            </Form>
        </DialogContent>
    </Dialog>;
}

export default function SelectionRules({college,eligibleBuckets,blockedBuckets,rules,can}:Props){
    return <><Head title="Merit / Roster / Selection Rules"/><div className="space-y-6 p-4 md:p-6">
        <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5"><div><p className="text-sm font-medium text-primary">{college.code} · College Academic Setup</p><h1 className="text-3xl font-semibold">Merit / Roster / Selection Rules</h1><p className="max-w-3xl text-sm text-muted-foreground">Rules attach to the effective Intake admission seat bucket. Reservation is consumed only where it is configured.</p></div>{can.create&&college.status==='ACTIVE'&&eligibleBuckets.length>0&&<RuleForm collegeId={college.id} buckets={eligibleBuckets}/>}</header>

        {eligibleBuckets.length===0&&<Card><CardContent className="p-4 text-sm text-muted-foreground">No eligible seat bucket is available. Activate Program Offering and Intake / Seat Capacity first. If Reservation is configured for a bucket, that Reservation plan must also be ACTIVE.</CardContent></Card>}

        {blockedBuckets.length>0&&<Card><CardContent className="p-4 text-sm"><div className="font-medium">Reservation is defined but inactive for {blockedBuckets.length} seat bucket{blockedBuckets.length===1?'':'s'}.</div><div className="mt-1 text-muted-foreground">Activate the Reservation / Seat Distribution plan to use those buckets in Selection Rules.</div><ul className="mt-2 list-disc pl-5 text-muted-foreground">{blockedBuckets.map(b=><li key={bucketValue(b)}>{b.program_name} · {b.bucket_label} · {b.basis_capacity} seats</li>)}</ul></CardContent></Card>}

        {rules.length===0?<Card><CardContent className="grid place-items-center py-16 text-center"><BookOpenCheck className="size-10 text-muted-foreground"/><h2 className="mt-3 font-semibold">No selection rule configured</h2><p className="mt-1 text-sm text-muted-foreground">Create the first inactive rule version for any eligible Intake seat bucket.</p></CardContent></Card>:<div className="space-y-4">{rules.map(rule=><Card key={rule.id}><CardHeader className="gap-3"><div className="flex flex-wrap items-start justify-between gap-3"><div><CardTitle>{rule.name} <span className="text-sm font-normal text-muted-foreground">v{rule.version_no} · {rule.status}</span></CardTitle><p className="mt-1 text-sm text-muted-foreground">{rule.intake.offering.program_template.name} · {rule.bucket_label} · {rule.intake.offering.academic_session.name} · {rule.basis_capacity} seats</p><p className="mt-1 text-xs text-muted-foreground">{reservationText(rule.reservation_state)}</p></div><div className="flex gap-2">{rule.status==='INACTIVE'&&can.update&&<RuleForm collegeId={college.id} buckets={eligibleBuckets} rule={rule}/>} {rule.status==='INACTIVE'&&can.enable&&<Form action={`/college/${college.id}/admission-selection-rules/${rule.id}/activate`} method="patch">{({processing})=><Button size="sm" disabled={processing}>{processing?<Spinner/>:<Power/>}Activate</Button>}</Form>}{rule.status==='ACTIVE'&&can.disable&&<Form action={`/college/${college.id}/admission-selection-rules/${rule.id}/retire`} method="patch">{({processing})=><Button size="sm" variant="outline" disabled={processing}>{processing?<Spinner/>:<Archive/>}Retire</Button>}</Form>}</div></div></CardHeader><CardContent className="space-y-4 text-sm">
                <div className="grid gap-3 md:grid-cols-4 xl:grid-cols-8"><div><span className="text-muted-foreground">Mode</span><div className="font-medium">{rule.selection_mode}</div></div><div><span className="text-muted-foreground">Merit</span><div className="font-medium">{rule.merit_weight_percent}%</div></div><div><span className="text-muted-foreground">Entrance</span><div className="font-medium">{rule.entrance_weight_percent}%</div></div><div><span className="text-muted-foreground">Interview</span><div className="font-medium">{rule.interview_weight_percent ?? '0'}%</div></div><div><span className="text-muted-foreground">Merit Min.</span><div className="font-medium">{rule.minimum_merit_score ?? 'Not set'}</div></div><div><span className="text-muted-foreground">Entrance Min.</span><div className="font-medium">{rule.minimum_entrance_score ?? 'Not set'}</div></div><div><span className="text-muted-foreground">Interview Min.</span><div className="font-medium">{rule.minimum_interview_score ?? 'Not set'}</div></div><div><span className="text-muted-foreground">Final Min.</span><div className="font-medium">{rule.minimum_final_score ?? 'Not set'}</div></div></div>
                {rule.roster_rule_reference&&<p><span className="font-medium">Roster / Policy:</span> {rule.roster_rule_reference}</p>}
                <div><span className="font-medium">Tie-break order:</span>{rule.tie_breakers?.length?<ol className="mt-1 list-decimal pl-5 text-muted-foreground">{rule.tie_breakers.map(item=><li key={item.id ?? `${item.priority}-${item.criterion}`}>{criterionLabel(item.criterion)}{item.criterion_reference?` (${item.criterion_reference})`:''} · {directionLabel(item.criterion,item.comparison_direction)}</li>)}</ol>:<span className="ml-1 text-muted-foreground">Not configured</span>}</div>
                {rule.tie_breaker_rules&&<p><span className="font-medium">Tie-break policy notes:</span> {rule.tie_breaker_rules}</p>}
            </CardContent></Card>)}</div>}
    </div></>;
}
