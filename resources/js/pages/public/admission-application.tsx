import { Form, Head } from '@inertiajs/react';
import { CheckCircle2, Clock3, FileText, GraduationCap } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Option={value:string;label:string};
type Condition={source_field_id:number;operator:string;compare_values:string[]};
type Field={id:number;field_key:string;label:string;field_type:string;placeholder?:string|null;help_text?:string|null;is_required:boolean;college_admission_form_panel_id?:number|null;options:Option[];conditions?:Condition[];condition_match_mode?:'ALL'|'ANY'};
type Panel={id:number;title:string;code:string;description?:string|null;display_order:number};
type Step={id:number;title:string;code:string;description?:string|null;panels:Panel[];fields:Field[]};
type PublicForm={slug:string;step_display_mode?:'SAME_WINDOW'|'NEW_WINDOW';college:{id:number;name:string;code:string};cycle:{id:number;name:string;code:string;application_start_date:string;application_end_date:string;status:string};program:{name:string;code:string;session?:string|null};template:{id:number;name:string;code:string;steps:Step[]}|null;fee:{required:boolean;amount:string;currency:string};choices:{college_program_intake_id:number;bucket_key:string;label:string;basis_capacity:number}[];availability:{can_submit:boolean;state:string;message:string;opens_on?:string;closes_on?:string}};
type Props={publicForm:PublicForm;successApplicationNo?:string|null};

const controlClass='h-11 w-full rounded-xl border border-border/80 bg-background px-3.5 text-sm shadow-sm outline-none transition duration-150 placeholder:text-muted-foreground/70 hover:border-primary/35 focus:border-primary/60 focus:ring-[3px] focus:ring-primary/15 disabled:cursor-not-allowed disabled:opacity-60';
const selectClass=`${controlClass} appearance-none pr-10`;
const textareaClass='min-h-28 w-full resize-y rounded-xl border border-border/80 bg-background px-3.5 py-3 text-sm shadow-sm outline-none transition duration-150 placeholder:text-muted-foreground/70 hover:border-primary/35 focus:border-primary/60 focus:ring-[3px] focus:ring-primary/15';
const fileClass='min-h-12 cursor-pointer rounded-xl border border-dashed border-border bg-muted/20 px-2 py-1.5 text-sm shadow-none transition hover:border-primary/40 hover:bg-primary/[0.03] file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/15';
const errorFor=(errors:Record<string,string>,key:string)=>errors[key]??errors[key.replaceAll('[','.').replaceAll(']','')];

const canonicalConditionValue=(value:unknown)=>String(value??'').trim().toLowerCase().replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');
function conditionMatches(actual:unknown, operator:string, expected:string[]){
    const blank=actual===null||actual===undefined||actual===''||(Array.isArray(actual)&&actual.length===0);
    if(operator==='IS_EMPTY')return blank;
    if(operator==='IS_NOT_EMPTY')return !blank;
    const actualValues=Array.isArray(actual)?actual.map(String):[String(actual??'')];
    const actualCanonical=actualValues.map(canonicalConditionValue);
    const expectedCanonical=expected.map(canonicalConditionValue);
    const overlap=actualCanonical.some(v=>expectedCanonical.includes(v));
    if(operator==='EQUALS'||operator==='IN')return overlap;
    if(operator==='NOT_EQUALS'||operator==='NOT_IN')return !overlap;
    if(operator==='CONTAINS')return actualValues.some(v=>expected.some(e=>e&&v.toLowerCase().includes(String(e).toLowerCase())));
    return false;
}

function DynamicField({field,values,setValue,errors}:{field:Field;values:Record<number,unknown>;setValue:(id:number,value:unknown)=>void;errors:Record<string,string>}){
    const results=(field.conditions??[]).map(c=>conditionMatches(values[c.source_field_id],c.operator,c.compare_values??[]));
    const visible=results.length===0?true:(field.condition_match_mode==='ANY'?results.some(Boolean):results.every(Boolean));
    if(!visible)return null;
    const name=`custom_fields[${field.id}]`;
    const error=errorFor(errors,`custom_fields.${field.id}`);
    const common=<><Label htmlFor={`field-${field.id}`} className="text-sm font-semibold tracking-tight">{field.label}{field.is_required&&<span className="ml-0.5 text-destructive">*</span>}</Label>{field.help_text&&<p className="text-xs leading-relaxed text-muted-foreground">{field.help_text}</p>}</>;
    if(field.field_type==='SELECT')return <div className="space-y-2.5">{common}<div className="relative"><select id={`field-${field.id}`} name={name} className={selectClass} required={field.is_required} value={String(values[field.id]??'')} onChange={e=>setValue(field.id,e.target.value)}><option value="">{field.placeholder??`Select ${field.label}`}</option>{field.options.map(o=><option key={o.value} value={o.value}>{o.label}</option>)}</select><span aria-hidden="true" className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-muted-foreground">⌄</span></div>{error&&<p className="text-xs font-medium text-destructive">{error}</p>}</div>;
    if(field.field_type==='RADIO'||field.field_type==='YES_NO')return <div className="space-y-2.5">{common}<div className="flex flex-wrap gap-2.5">{field.options.map(o=><label key={o.value} className="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-border/80 bg-background px-3.5 py-2.5 text-sm font-medium shadow-sm transition hover:border-primary/35 hover:bg-muted/30 has-[:checked]:border-primary/60 has-[:checked]:bg-primary/[0.06] has-[:checked]:text-foreground has-[:checked]:shadow-sm"><input className="size-4 shrink-0 accent-primary" type="radio" name={name} value={o.value} required={field.is_required} checked={String(values[field.id]??'')===o.value} onChange={()=>setValue(field.id,o.value)}/><span>{o.label}</span></label>)}</div>{error&&<p className="text-xs font-medium text-destructive">{error}</p>}</div>;
    if(field.field_type==='CHECKBOX'||field.field_type==='MULTISELECT')return <div className="space-y-2.5">{common}<div className="grid gap-2.5 sm:grid-cols-2">{field.options.map(o=>{const selected=Array.isArray(values[field.id])?(values[field.id] as unknown[]).map(String):[];return <label key={o.value} className="flex min-h-11 cursor-pointer items-center gap-3 rounded-xl border border-border/80 bg-background px-3.5 py-2.5 text-sm font-medium shadow-sm transition hover:border-primary/35 hover:bg-muted/30 has-[:checked]:border-primary/60 has-[:checked]:bg-primary/[0.06] has-[:checked]:shadow-sm"><input className="size-4 shrink-0 rounded accent-primary" type="checkbox" name={`${name}[]`} value={o.value} checked={selected.includes(o.value)} onChange={e=>setValue(field.id,e.target.checked?[...selected,o.value]:selected.filter(v=>v!==o.value))}/><span>{o.label}</span></label>})}</div>{error&&<p className="text-xs font-medium text-destructive">{error}</p>}</div>;
    if(field.field_type==='FILE'||field.field_type==='IMAGE')return <div className="space-y-2.5">{common}<Input className={fileClass} id={`field-${field.id}`} type="file" name={name} required={field.is_required} accept={field.field_type==='IMAGE'?'image/*':undefined}/>{error&&<p className="text-xs font-medium text-destructive">{error}</p>}</div>;
    if(field.field_type==='TEXTAREA')return <div className="space-y-2.5">{common}<textarea id={`field-${field.id}`} name={name} required={field.is_required} placeholder={field.placeholder??''} value={String(values[field.id]??'')} onChange={e=>setValue(field.id,e.target.value)} className={textareaClass}/>{error&&<p className="text-xs font-medium text-destructive">{error}</p>}</div>;
    const inputType=field.field_type==='NUMBER'?'number':field.field_type==='DATE'?'date':field.field_type==='EMAIL'?'email':field.field_type==='PHONE'?'tel':'text';
    return <div className="space-y-2.5">{common}<Input className={controlClass} id={`field-${field.id}`} type={inputType} name={name} required={field.is_required} placeholder={field.placeholder??''} value={String(values[field.id]??'')} onChange={e=>setValue(field.id,e.target.value)}/>{error&&<p className="text-xs font-medium text-destructive">{error}</p>}</div>;
}

export default function PublicAdmissionApplication({publicForm,successApplicationNo}:Props){
    const [values,setValues]=useState<Record<number,unknown>>({});
    const setValue=(id:number,value:unknown)=>setValues(prev=>({...prev,[id]:value}));
    const steps=publicForm.template?.steps??[];
    const initialChoice=publicForm.choices.length===1?`${publicForm.choices[0].college_program_intake_id}|${publicForm.choices[0].bucket_key}`:'';
    const [choice,setChoice]=useState(initialChoice);
    const dateLabel=(value?:string)=>value?new Date(`${value}T00:00:00`).toLocaleDateString():'';
    const [choiceIntake,choiceBucket]=choice.split('|');
    const stepByStep=publicForm.step_display_mode==='NEW_WINDOW';
    const totalPages=steps.length+2; // core details + dynamic steps + final fee/submit
    const [activePage,setActivePage]=useState(0);
    const validateCurrentPage=()=>{
        if(!stepByStep)return true;
        const page=document.querySelector(`[data-public-form-page="${activePage}"]`);
        if(!page)return true;
        const controls=Array.from(page.querySelectorAll('input, select, textarea')) as Array<HTMLInputElement|HTMLSelectElement|HTMLTextAreaElement>;
        for(const control of controls){
            if(!control.checkValidity()){control.reportValidity();return false;}
        }
        return true;
    };
    const nextPage=()=>{if(validateCurrentPage())setActivePage(page=>Math.min(page+1,totalPages-1));};
    const previousPage=()=>setActivePage(page=>Math.max(page-1,0));

    if(successApplicationNo)return <><Head title="Application submitted"/><main className="mx-auto grid min-h-screen max-w-3xl place-items-center p-4"><Card className="w-full"><CardContent className="py-12 text-center"><CheckCircle2 className="mx-auto size-12 text-primary"/><h1 className="mt-4 text-2xl font-semibold">Application submitted successfully</h1><p className="mt-2 text-muted-foreground">Your application number is</p><div className="mt-3 text-xl font-semibold">{successApplicationNo}</div><p className="mt-4 text-sm text-muted-foreground">Keep this number for future reference.</p></CardContent></Card></main></>;

    return <><Head title={`${publicForm.program.name??'Admission'} Application`}/><main className="min-h-screen bg-muted/25"><div className="mx-auto max-w-5xl space-y-6 px-4 py-6 md:px-8 md:py-10">
        <header className="overflow-hidden rounded-2xl border border-border/70 bg-background p-5 shadow-sm md:p-7"><div className="flex items-start gap-4"><div className="rounded-xl border border-primary/15 bg-primary/[0.07] p-3 text-primary"><GraduationCap className="size-6"/></div><div><p className="text-sm font-medium text-primary">{publicForm.college.name} · {publicForm.college.code}</p><h1 className="mt-1 text-2xl font-semibold tracking-tight md:text-3xl">{publicForm.program.name} Admission Application</h1><p className="mt-2 text-sm text-muted-foreground">{publicForm.cycle.name}{publicForm.program.session?` · ${publicForm.program.session}`:''}</p><p className="mt-1 text-xs text-muted-foreground">Application window: {dateLabel(publicForm.cycle.application_start_date)} – {dateLabel(publicForm.cycle.application_end_date)}</p></div></div></header>

        {!publicForm.availability.can_submit?<Card><CardContent className="py-10 text-center"><Clock3 className="mx-auto size-10 text-muted-foreground"/><h2 className="mt-3 text-xl font-semibold">{publicForm.availability.state==='UPCOMING'?'Applications are not open yet':'Applications are currently closed'}</h2><p className="mt-2 text-muted-foreground">{publicForm.availability.message}</p>{publicForm.availability.opens_on&&<p className="mt-2 text-sm">Open: {dateLabel(publicForm.availability.opens_on)} · Close: {dateLabel(publicForm.availability.closes_on)}</p>}</CardContent></Card>:
        <Form action={`/apply/${publicForm.slug}`} method="post">{({processing,errors})=><div className="space-y-6">
            {errors.application&&<div className="rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">{errors.application}</div>}

            {stepByStep&&<Card className="border-border/70 shadow-sm"><CardContent className="flex flex-wrap items-center justify-between gap-3 py-4"><div><div className="text-sm font-medium">Application progress</div><p className="text-xs text-muted-foreground">Step {activePage+1} of {totalPages}</p></div><div className="text-sm text-muted-foreground">{activePage===0?'Candidate & category':activePage<=steps.length?steps[activePage-1]?.title:'Review & submit'}</div></CardContent></Card>}

            <div data-public-form-page="0" className={stepByStep&&activePage!==0?'hidden':'space-y-6'}>
                <Card className="border-border/70 shadow-sm"><CardHeader className="border-b border-border/60 pb-4"><CardTitle className="text-lg tracking-tight">Candidate Details</CardTitle></CardHeader><CardContent className="grid gap-5 pt-5 md:grid-cols-2"><div className="space-y-2.5"><Label className="text-sm font-semibold">Candidate Name <span className="text-destructive">*</span></Label><Input className={controlClass} name="candidate_name" required/><p className="text-xs text-destructive">{errors.candidate_name}</p></div><div className="space-y-2.5"><Label className="text-sm font-semibold">Date of Birth <span className="text-destructive">*</span></Label><Input className={controlClass} name="date_of_birth" type="date" required/><p className="text-xs text-destructive">{errors.date_of_birth}</p></div><div className="space-y-2.5"><Label className="text-sm font-semibold">Email</Label><Input className={controlClass} name="email" type="email"/><p className="text-xs text-destructive">{errors.email}</p></div><div className="space-y-2.5"><Label className="text-sm font-semibold">Phone</Label><Input className={controlClass} name="phone" type="tel"/><p className="text-xs text-destructive">{errors.phone}</p></div></CardContent></Card>

                <Card className="border-border/70 shadow-sm"><CardHeader className="border-b border-border/60 pb-4"><CardTitle className="text-lg tracking-tight">Admission Seat Category</CardTitle></CardHeader><CardContent className="space-y-2.5 pt-5"><Label className="text-sm font-semibold">Select applicable category / seat bucket <span className="text-destructive">*</span></Label><div className="relative"><select name="choice_selector" className={selectClass} value={choice} required onChange={e=>setChoice(e.target.value)}><option value="">Select category</option>{publicForm.choices.map(c=><option key={`${c.college_program_intake_id}-${c.bucket_key}`} value={`${c.college_program_intake_id}|${c.bucket_key}`}>{c.label}</option>)}</select><span aria-hidden="true" className="pointer-events-none absolute inset-y-0 right-3 flex items-center text-muted-foreground">⌄</span></div><input type="hidden" name="choices[0][college_program_intake_id]" value={choiceIntake??''}/><input type="hidden" name="choices[0][bucket_key]" value={choiceBucket??''}/>{errors.choices&&<p className="text-xs text-destructive">{errors.choices}</p>}</CardContent></Card>
            </div>

            {steps.map((step,index)=><div key={step.id} data-public-form-page={index+1} className={stepByStep&&activePage!==index+1?'hidden':''}><Card className="border-border/70 shadow-sm"><CardHeader className="border-b border-border/60 pb-4"><CardTitle className="text-lg tracking-tight">{step.title}</CardTitle>{step.description&&<p className="text-sm leading-relaxed text-muted-foreground">{step.description}</p>}</CardHeader><CardContent className="space-y-6 pt-5">{step.panels.map(panel=>{const panelFields=step.fields.filter(f=>f.college_admission_form_panel_id===panel.id);if(!panelFields.length)return null;return <section key={panel.id} className="rounded-2xl border border-border/70 bg-muted/[0.18] p-4 md:p-5"><div className="mb-5 border-b border-border/50 pb-3"><h3 className="font-semibold tracking-tight">{panel.title}</h3>{panel.description&&<p className="mt-1 text-sm leading-relaxed text-muted-foreground">{panel.description}</p>}</div><div className="grid gap-x-5 gap-y-6 md:grid-cols-2">{panelFields.map(field=><DynamicField key={field.id} field={field} values={values} setValue={setValue} errors={errors}/>)}</div></section>})}<div className="grid gap-x-5 gap-y-6 md:grid-cols-2">{step.fields.filter(f=>!f.college_admission_form_panel_id).map(field=><DynamicField key={field.id} field={field} values={values} setValue={setValue} errors={errors}/>)}</div></CardContent></Card></div>)}

            <div data-public-form-page={totalPages-1} className={stepByStep&&activePage!==totalPages-1?'hidden':''}>
                <Card className="border-border/70 shadow-sm"><CardContent className="flex flex-wrap items-center justify-between gap-4 p-5"><div><div className="flex items-center gap-2 font-medium"><FileText className="size-4"/>Application Fee</div><p className="mt-1 text-sm text-muted-foreground">{publicForm.fee.required?`${publicForm.fee.currency} ${Number(publicForm.fee.amount).toFixed(2)} will be recorded with this application.`:'No application fee is configured for this application.'}</p></div>{!stepByStep&&<Button type="submit" disabled={processing}>{processing&&<Spinner/>}Submit Application</Button>}</CardContent></Card>
            </div>

            {stepByStep&&<Card className="sticky bottom-4 z-10 border-border/70 bg-background/95 shadow-lg backdrop-blur"><CardContent className="flex items-center justify-between gap-3 py-4"><Button type="button" variant="outline" onClick={previousPage} disabled={activePage===0}>Previous</Button>{activePage<totalPages-1?<Button type="button" onClick={nextPage}>Next</Button>:<Button type="submit" disabled={processing}>{processing&&<Spinner/>}Submit Application</Button>}</CardContent></Card>}
        </div>}</Form>}
    </div></main></>;
}
