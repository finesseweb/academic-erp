import { Form, Head } from '@inertiajs/react';
import { CalendarRange, Check, LockKeyhole, Pencil, Plus, Power, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Offering = {
    id:number; status:string;
    program_template:{id:number;name:string;code:string};
    academic_session:{id:number;name:string;code:string;starts_on:string;ends_on:string;status:string;is_current:boolean};
    curriculum?:{id:number;name:string;code:string;version?:string|null}|null;
    intake?:{id:number;status:string;approved_capacity:number}|null;
};
type Cycle = {
    id:number; college_program_offering_id:number|null; academic_session_id:number; name:string; code:string;
    application_start_date:string; application_end_date:string;
    admission_start_date:string; admission_end_date:string;
    status:'INACTIVE'|'ACTIVE'|'CLOSED'; remarks?:string|null;
    program_offering?:Offering|null;
    academic_session:{id:number;name:string;code:string;is_current:boolean};
};
type Props = {
    college:{id:number;name:string;code:string;status:string};
    offerings:Offering[]; cycles:Cycle[];
    can:{create:boolean;update:boolean;enable:boolean;disable:boolean};
};
type SearchableOption = {value:string;label:string;searchText?:string;meta?:string};

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

function CycleForm({ collegeId, offerings, cycle }:{collegeId:number;offerings:Offering[];cycle?:Cycle}) {
    const defaultOffering=cycle?.college_program_offering_id?.toString() ?? offerings[0]?.id.toString() ?? '';
    const [offeringId,setOfferingId] = useState(defaultOffering);
    const [applicationStart,setApplicationStart] = useState(cycle?.application_start_date?.slice(0,10) ?? '');
    const [applicationEnd,setApplicationEnd] = useState(cycle?.application_end_date?.slice(0,10) ?? '');
    const [admissionStart,setAdmissionStart] = useState(cycle?.admission_start_date?.slice(0,10) ?? '');
    const [admissionEnd,setAdmissionEnd] = useState(cycle?.admission_end_date?.slice(0,10) ?? '');
    const selected=offerings.find(o=>o.id.toString()===offeringId);
    const sessionMin=selected?.academic_session.starts_on?.slice(0,10) ?? '1800-01-01';
    const sessionMax=selected?.academic_session.ends_on?.slice(0,10);
    const handleOfferingChange=(nextOfferingId:string)=>{
        setOfferingId(nextOfferingId);
        const nextOffering=offerings.find(o=>String(o.id)===nextOfferingId);
        const min=nextOffering?.academic_session.starts_on?.slice(0,10);
        const max=nextOffering?.academic_session.ends_on?.slice(0,10);
        const keepIfInSession=(value:string)=>!value || !min || !max || (value>=min&&value<=max) ? value : '';
        setApplicationStart(value=>keepIfInSession(value));
        setApplicationEnd(value=>keepIfInSession(value));
        setAdmissionStart(value=>keepIfInSession(value));
        setAdmissionEnd(value=>keepIfInSession(value));
    };
    const options=useMemo<SearchableOption[]>(()=>offerings.map(o=>({
        value:String(o.id),
        label:`${o.program_template.name} · ${o.academic_session.name}`,
        searchText:`${o.program_template.name} ${o.program_template.code} ${o.academic_session.name} ${o.academic_session.code} ${o.curriculum?.name??''} ${o.curriculum?.code??''}`,
        meta:`${o.academic_session.is_current?'Current session · ':''}${o.intake?.status==='ACTIVE'?`ACTIVE Intake · ${o.intake.approved_capacity} seats`:'Intake not active yet'}${o.curriculum?` · ${o.curriculum.name}`:''}`,
    })),[offerings]);
    const action = cycle ? `/college/${collegeId}/admission-cycles/${cycle.id}` : `/college/${collegeId}/admission-cycles`;
    return <Dialog>
        <DialogTrigger asChild><Button variant={cycle?'ghost':'default'} size={cycle?'icon':'default'} disabled={cycle?.status==='CLOSED'}>{cycle?<Pencil/>:<Plus/>}{!cycle&&'Add Admission Cycle'}</Button></DialogTrigger>
        <DialogContent className="flex max-h-[92vh] w-[calc(100vw-2rem)] max-w-none flex-col overflow-hidden sm:!max-w-2xl">
            <DialogTitle>{cycle?'Edit Admission Cycle':'Add Admission Cycle'}</DialogTitle>
            <DialogDescription>Admission Cycle belongs to one ACTIVE Program Offering. Its Academic Session, Degree/Program and Curriculum context are inherited from that offering. Activation requires an ACTIVE Intake and at least one ACTIVE Selection Rule for that offering.</DialogDescription>
            <Form action={action} method={cycle?'patch':'post'} className="min-h-0 space-y-4 overflow-y-auto pr-1">
                {({processing,errors}) => <>
                    <SearchablePicker label="Program Offering" value={offeringId} onChange={handleOfferingChange} options={options} placeholder="Search and select Program Offering" disabled={cycle?.status==='ACTIVE'}/>
                    <input type="hidden" name="college_program_offering_id" value={offeringId}/>
                    {errors.college_program_offering_id&&<p className="text-sm text-destructive">{errors.college_program_offering_id}</p>}
                    {selected&&<div className="rounded-md border bg-muted/30 p-3 text-xs text-muted-foreground"><div><span className="font-medium text-foreground">Inherited context:</span> {selected.program_template.name} ({selected.program_template.code}) · {selected.academic_session.name} ({selected.academic_session.code})</div>{selected.curriculum&&<div className="mt-1">Curriculum: {selected.curriculum.name} ({selected.curriculum.code})</div>}<div className="mt-1">Intake: {selected.intake?.status??'NOT CONFIGURED'}{selected.intake?` · ${selected.intake.approved_capacity} seats`:''}</div></div>}
                    <div className="grid gap-4 md:grid-cols-2"><div className="space-y-2"><Label>Name</Label><Input name="name" defaultValue={cycle?.name} placeholder="BA Admission 2026-27"/>{errors.name&&<p className="text-sm text-destructive">{errors.name}</p>}</div><div className="space-y-2"><Label>Code</Label><Input name="code" defaultValue={cycle?.code} placeholder="BA-ADM-2026"/>{errors.code&&<p className="text-sm text-destructive">{errors.code}</p>}</div></div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor={`application-start-${cycle?.id??'new'}`}>Application Start</Label>
                            <DatePicker id={`application-start-${cycle?.id??'new'}`} name="application_start_date" value={applicationStart} onValueChange={setApplicationStart} min={sessionMin} max={sessionMax} invalid={Boolean(errors.application_start_date)}/>
                            {errors.application_start_date&&<p className="text-sm text-destructive">{errors.application_start_date}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor={`application-end-${cycle?.id??'new'}`}>Application End</Label>
                            <DatePicker id={`application-end-${cycle?.id??'new'}`} name="application_end_date" value={applicationEnd} onValueChange={setApplicationEnd} min={applicationStart||sessionMin} max={sessionMax} invalid={Boolean(errors.application_end_date)}/>
                            {errors.application_end_date&&<p className="text-sm text-destructive">{errors.application_end_date}</p>}
                        </div>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor={`admission-start-${cycle?.id??'new'}`}>Admission Start</Label>
                            <DatePicker id={`admission-start-${cycle?.id??'new'}`} name="admission_start_date" value={admissionStart} onValueChange={setAdmissionStart} min={applicationStart||sessionMin} max={sessionMax} invalid={Boolean(errors.admission_start_date)}/>
                            {errors.admission_start_date&&<p className="text-sm text-destructive">{errors.admission_start_date}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor={`admission-end-${cycle?.id??'new'}`}>Admission End</Label>
                            <DatePicker id={`admission-end-${cycle?.id??'new'}`} name="admission_end_date" value={admissionEnd} onValueChange={setAdmissionEnd} min={admissionStart||applicationStart||sessionMin} max={sessionMax} invalid={Boolean(errors.admission_end_date)}/>
                            {errors.admission_end_date&&<p className="text-sm text-destructive">{errors.admission_end_date}</p>}
                        </div>
                    </div>
                    <div className="space-y-2"><Label>Remarks</Label><Input name="remarks" defaultValue={cycle?.remarks ?? ''} placeholder="Optional notes"/></div>
                    <DialogFooter><DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" disabled={processing}>{processing&&<Spinner/>}{cycle?'Save changes':'Create cycle'}</Button></DialogFooter>
                </>}
            </Form>
        </DialogContent>
    </Dialog>;
}

function CycleLifecycleDialog({collegeId,cycle}:{collegeId:number;cycle:Cycle}) {
    const activating=cycle.status==='INACTIVE';
    return <Dialog>
        <DialogTrigger asChild>
            <Button
                size="icon"
                variant="ghost"
                aria-label={activating?'Activate Admission Cycle':'Deactivate Admission Cycle'}
                title={activating?'Activate Admission Cycle':'Deactivate Admission Cycle'}
            >
                <Power/>
            </Button>
        </DialogTrigger>
        <DialogContent className="w-[calc(100vw-2rem)] max-w-md sm:w-full">
            <DialogTitle>{activating?'Activate Admission Cycle?':'Deactivate Admission Cycle?'}</DialogTitle>
            <DialogDescription>
                {activating
                    ? 'Activation opens this Program Offering admission cycle for application processing. The offering must have an ACTIVE Intake and at least one eligible ACTIVE Selection Rule.'
                    : 'Deactivation stops this cycle from accepting new admission processing. Existing history is preserved and submitted applications may block deactivation.'}
            </DialogDescription>
            <Form action={`/college/${collegeId}/admission-cycles/${cycle.id}/status`} method="patch">
                {({processing})=><DialogFooter>
                    <input type="hidden" name="status" value={activating?'ACTIVE':'INACTIVE'}/>
                    <DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose>
                    <Button type="submit" disabled={processing}>{processing&&<Spinner/>}{activating?'Activate':'Deactivate'}</Button>
                </DialogFooter>}
            </Form>
        </DialogContent>
    </Dialog>;
}

function CloseCycleDialog({collegeId,cycle}:{collegeId:number;cycle:Cycle}) {
    return <Dialog>
        <DialogTrigger asChild><Button size="icon" variant="ghost" aria-label="Close Admission Cycle" title="Close Admission Cycle"><LockKeyhole/></Button></DialogTrigger>
        <DialogContent className="w-[calc(100vw-2rem)] max-w-md sm:w-full">
            <DialogTitle>Close Admission Cycle?</DialogTitle>
            <DialogDescription>Closing is final for this cycle. Use it only after the admission process for this Program Offering is complete.</DialogDescription>
            <Form action={`/college/${collegeId}/admission-cycles/${cycle.id}/status`} method="patch">
                {({processing})=><DialogFooter>
                    <input type="hidden" name="status" value="CLOSED"/>
                    <DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose>
                    <Button type="submit" disabled={processing}>{processing&&<Spinner/>}Close Cycle</Button>
                </DialogFooter>}
            </Form>
        </DialogContent>
    </Dialog>;
}

export default function AdmissionCycles({college,offerings,cycles,can}:Props) {
    return <><Head title="Admission Cycles"/><div className="space-y-6 p-4 md:p-6">
        <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5"><div><p className="text-sm font-medium text-primary">{college.code} · Admission</p><h1 className="text-3xl font-semibold">Admission Cycle</h1><p className="max-w-3xl text-sm text-muted-foreground">Each Admission Cycle is anchored to one ACTIVE Program Offering. The offering supplies the College, Academic Session, Program and Curriculum context; activation requires at least one eligible ACTIVE Selection Rule for that same offering.</p></div>{can.create&&college.status==='ACTIVE'&&offerings.length>0&&<CycleForm collegeId={college.id} offerings={offerings}/>}</header>
        {offerings.length===0&&<Card><CardContent className="py-8 text-center text-muted-foreground">No ACTIVE Program Offering is available. Activate a Program Offering before creating its Admission Cycle.</CardContent></Card>}
        {cycles.length===0?<Card><CardContent className="py-12 text-center text-muted-foreground"><CalendarRange className="mx-auto mb-3"/>No Admission Cycle has been configured yet.</CardContent></Card>:<div className="grid gap-4">{cycles.map(cycle=>{const offering=cycle.program_offering;return <Card key={cycle.id}><CardHeader className="flex-row items-start justify-between space-y-0"><div><CardTitle>{cycle.name} <span className="text-sm font-normal text-muted-foreground">({cycle.code})</span></CardTitle><p className="mt-1 text-sm text-muted-foreground">{offering?`${offering.program_template.name} · ${offering.academic_session.name}`:`Legacy cycle · ${cycle.academic_session.name} · Program Offering required`} · {cycle.status}</p></div><div className="flex flex-wrap gap-2">{can.update&&cycle.status!=='CLOSED'&&<CycleForm collegeId={college.id} offerings={offerings} cycle={cycle}/>} {cycle.status!=='CLOSED'&&can.update&&<CycleLifecycleDialog collegeId={college.id} cycle={cycle}/>} {cycle.status==='ACTIVE'&&can.update&&<CloseCycleDialog collegeId={college.id} cycle={cycle}/>}</div></CardHeader><CardContent><div className="grid gap-3 text-sm md:grid-cols-2"><div><span className="font-medium">Application:</span> {cycle.application_start_date?.slice(0,10)} → {cycle.application_end_date?.slice(0,10)}</div><div><span className="font-medium">Admission:</span> {cycle.admission_start_date?.slice(0,10)} → {cycle.admission_end_date?.slice(0,10)}</div></div>{cycle.remarks&&<p className="mt-3 text-sm text-muted-foreground">{cycle.remarks}</p>}</CardContent></Card>})}</div>}
    </div></>;
}
