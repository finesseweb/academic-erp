import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Term={id:number;curriculum_id:number;sequence_no:number;name:string};
type Curriculum={id:number;name:string;code:string;version:string;terms:Term[]};
type RuleSet={
 name:string;applies_to_all_stages:boolean;curriculum_id:string;source_term_ids:number[];
 target_curriculum_term_id:string;evaluation_mode:'COMBINED'|'EACH_TERM';
 minimum_earned_credits:string;minimum_sgpa:string;minimum_cgpa:string;maximum_backlog_courses:string;
 mandatory_courses_must_be_passed:boolean;allow_carry_forward:boolean;allow_detention:boolean;
 allow_year_back:boolean;allow_readmission:boolean;maximum_attempts_per_course:string;notes:string;
};
const blank=(curricula:Curriculum[]):RuleSet=>({name:'',applies_to_all_stages:false,curriculum_id:curricula[0]?String(curricula[0].id):'',source_term_ids:[],target_curriculum_term_id:'',evaluation_mode:'COMBINED',minimum_earned_credits:'',minimum_sgpa:'',minimum_cgpa:'',maximum_backlog_courses:'',mandatory_courses_must_be_passed:false,allow_carry_forward:true,allow_detention:true,allow_year_back:true,allow_readmission:true,maximum_attempts_per_course:'',notes:''});

export default function ProgressionPolicy({policy,ruleSets,availableCurricula,editable}:{policy:any;ruleSets:any[];availableCurricula:Curriculum[];editable:boolean}) {
 const initial:RuleSet[]=(ruleSets??[]).map(r=>({name:r.name,applies_to_all_stages:Boolean(r.applies_to_all_stages),curriculum_id:r.curriculum_id?String(r.curriculum_id):'',source_term_ids:(r.source_terms??[]).map((t:any)=>Number(t.id)),target_curriculum_term_id:r.target_curriculum_term_id?String(r.target_curriculum_term_id):'',evaluation_mode:r.evaluation_mode,minimum_earned_credits:r.minimum_earned_credits==null?'':String(r.minimum_earned_credits),minimum_sgpa:r.minimum_sgpa==null?'':String(r.minimum_sgpa),minimum_cgpa:r.minimum_cgpa==null?'':String(r.minimum_cgpa),maximum_backlog_courses:r.maximum_backlog_courses==null?'':String(r.maximum_backlog_courses),mandatory_courses_must_be_passed:Boolean(r.mandatory_courses_must_be_passed),allow_carry_forward:Boolean(r.allow_carry_forward),allow_detention:Boolean(r.allow_detention),allow_year_back:Boolean(r.allow_year_back),allow_readmission:Boolean(r.allow_readmission),maximum_attempts_per_course:r.maximum_attempts_per_course==null?'':String(r.maximum_attempts_per_course),notes:r.notes??''}));
 const form=useForm({rule_sets:initial});
 const patchRule=(i:number,patch:Partial<RuleSet>)=>{
  const n=form.data.rule_sets.map((rule,index)=>index===i?{...rule,...patch}:rule);
  form.setData('rule_sets',n);
 };
 const update=(i:number,key:keyof RuleSet,value:any)=>patchRule(i,{[key]:value} as Partial<RuleSet>);
 const toggleAllStages=(i:number,enabled:boolean)=>{
  const current=form.data.rule_sets[i];
  patchRule(i, enabled
   ? {
      applies_to_all_stages:true,
      curriculum_id:'',
      source_term_ids:[],
      target_curriculum_term_id:'',
     }
   : {
      applies_to_all_stages:false,
      curriculum_id:current.curriculum_id || (availableCurricula[0]?String(availableCurricula[0].id):''),
      source_term_ids:[],
      target_curriculum_term_id:'',
     }
  );
 };
 const curriculaFor=(r:RuleSet)=>availableCurricula;
 const termsFor=(r:RuleSet)=>availableCurricula.find(c=>String(c.id)===r.curriculum_id)?.terms??[];
 const submit=(e:FormEvent)=>{e.preventDefault();form.put(`/admin/academic-policies/${policy.id}/progression`,{preserveScroll:true});};
 return <><Head title={`${policy.name} Promotion / Progression Policy`}/><div className="space-y-6 p-4 md:p-6">
 <header className="flex items-end justify-between border-b pb-5"><div><Button variant="ghost" className="mb-3 px-0" onClick={()=>history.back()}><ArrowLeft className="size-4"/>Academic Policies</Button><p className="text-sm font-medium text-primary">{policy.code} · Version {policy.version}</p><h1 className="mt-1 text-2xl font-semibold sm:text-3xl">Promotion / Progression Policy</h1><p className="text-sm text-muted-foreground">{policy.name} · {policy.academic_session.name}</p></div>{editable&&<Button type="button" variant="outline" onClick={()=>form.setData('rule_sets',[...form.data.rule_sets,blank(availableCurricula)])}><Plus className="size-4"/>Add Rule Set</Button>}</header>
 <div className="rounded-lg border bg-muted/20 p-4 text-sm text-muted-foreground">Create separate rules for one semester, or select 2–3 terms together. <b>Combined</b> evaluates selected terms together; <b>Each Term Separately</b> applies the thresholds to every selected term. A single Default / All Stages rule can act as fallback.</div>
 <form onSubmit={submit} className="space-y-5">
 {form.data.rule_sets.map((r,i)=><Card key={i}><CardHeader><div className="flex items-center justify-between"><CardTitle>{r.name||`Progression Rule ${i+1}`}</CardTitle>{editable&&<Button type="button" variant="ghost" onClick={()=>form.setData('rule_sets',form.data.rule_sets.filter((_,x)=>x!==i))}><Trash2 className="size-4"/></Button>}</div></CardHeader><CardContent className="space-y-5">
 <div className="grid gap-4 md:grid-cols-3"><Field label="Rule Name"><Input disabled={!editable} value={r.name} onChange={e=>update(i,'name',e.target.value)} placeholder="e.g. Sem 1 to Sem 2"/></Field><Toggle label="Default / All Stages" description="Use this as the fallback progression rule when no term-specific rule matches. When enabled, Term/Semester and Progress To Term selection are not required." checked={r.applies_to_all_stages} disabled={!editable} onChange={v=>toggleAllStages(i,v)}/></div>
 {!r.applies_to_all_stages&&<><div className="grid gap-4 md:grid-cols-2"><div className="rounded-lg border bg-muted/20 p-3"><div className="text-sm font-medium">Applicable Structure</div><div className="mt-1 text-sm text-muted-foreground">{policy.scope_type==='CURRICULUM'?'Uses the Curriculum selected in the Policy scope automatically.':policy.scope_type==='PROGRAM_TEMPLATE'?'Uses the current applicable Curriculum structure of this Program Template automatically. No Curriculum version is hard-bound by the rule.':'Uses the current applicable structure resolved for this University-scoped policy.'}</div></div><Field label="Evaluation Mode"><Select disabled={!editable} value={r.evaluation_mode} onValueChange={v=>{update(i,'evaluation_mode',v as any);if(v==='COMBINED'&&r.source_term_ids.length>1)update(i,'minimum_sgpa','')}}><SelectTrigger><SelectValue/></SelectTrigger><SelectContent><SelectItem value="COMBINED">Combined selected terms</SelectItem><SelectItem value="EACH_TERM">Each term separately</SelectItem></SelectContent></Select></Field></div>
 <div><div className="mb-2 text-sm font-medium">Terms / Semesters to Evaluate</div><div className="grid gap-2 md:grid-cols-3">{termsFor(r).map(t=><label key={t.id} className="flex items-center gap-2 rounded-md border p-3 text-sm"><input type="checkbox" disabled={!editable} checked={r.source_term_ids.includes(t.id)} onChange={e=>update(i,'source_term_ids',e.target.checked?[...r.source_term_ids,t.id]:r.source_term_ids.filter(id=>id!==t.id))}/>{t.sequence_no}. {t.name}</label>)}</div></div>
 <Field label="Progress To Term"><Select disabled={!editable} value={r.target_curriculum_term_id} onValueChange={v=>update(i,'target_curriculum_term_id',v)}><SelectTrigger><SelectValue placeholder="Select target term"/></SelectTrigger><SelectContent>{termsFor(r).filter(t=>!r.source_term_ids.includes(t.id)).map(t=><SelectItem key={t.id} value={String(t.id)}>{t.sequence_no}. {t.name}</SelectItem>)}</SelectContent></Select></Field></>}
 <div className="grid gap-4 md:grid-cols-3"><Field label={r.evaluation_mode==='EACH_TERM'?'Minimum Credits per Term':'Minimum Combined Earned Credits'}><Input disabled={!editable} type="number" min="0" step=".01" value={r.minimum_earned_credits} onChange={e=>update(i,'minimum_earned_credits',e.target.value)}/></Field>{!(r.evaluation_mode==='COMBINED'&&r.source_term_ids.length>1)&&<Field label="Minimum SGPA"><Input disabled={!editable} type="number" min="0" step=".001" value={r.minimum_sgpa} onChange={e=>update(i,'minimum_sgpa',e.target.value)}/></Field>}<Field label="Minimum CGPA"><Input disabled={!editable} type="number" min="0" step=".001" value={r.minimum_cgpa} onChange={e=>update(i,'minimum_cgpa',e.target.value)}/></Field><Field label={r.evaluation_mode==='EACH_TERM'?'Max Backlogs per Term':'Maximum Combined Backlogs'}><Input disabled={!editable} type="number" min="0" value={r.maximum_backlog_courses} onChange={e=>update(i,'maximum_backlog_courses',e.target.value)}/></Field><Field label="Maximum Attempts per Course"><Input disabled={!editable} type="number" min="1" value={r.maximum_attempts_per_course} onChange={e=>update(i,'maximum_attempts_per_course',e.target.value)}/></Field></div>
 <div className="grid gap-3 md:grid-cols-2">
 <Toggle label="Mandatory Courses Must Be Passed" description="When enabled, every course marked mandatory in the applicable academic structure must be cleared before this progression rule can pass." checked={r.mandatory_courses_must_be_passed} disabled={!editable} onChange={v=>update(i,'mandatory_courses_must_be_passed',v)}/>
 <Toggle label="Allow Carry Forward / ATKT" description="Allows an eligible student to move to the next stage with permitted backlog courses, subject to the backlog and attempt limits configured in this rule." checked={r.allow_carry_forward} disabled={!editable} onChange={v=>update(i,'allow_carry_forward',v)}/>
 <Toggle label="Allow Detention" description="Allows the progression process to stop promotion and place a student in detained academic status when this rule is not satisfied." checked={r.allow_detention} disabled={!editable} onChange={v=>update(i,'allow_detention',v)}/>
 <Toggle label="Allow Year Back" description="Allows a controlled year-back decision when the student does not satisfy the applicable progression requirements." checked={r.allow_year_back} disabled={!editable} onChange={v=>update(i,'allow_year_back',v)}/>
 <Toggle label="Allow Re-admission" description="Allows a future re-admission workflow for eligible detained, year-back or discontinued students according to the approved regulations." checked={r.allow_readmission} disabled={!editable} onChange={v=>update(i,'allow_readmission',v)}/>
 </div>
 <Field label="Notes"><textarea disabled={!editable} className="min-h-20 w-full rounded-md border bg-background p-3 text-sm" value={r.notes} onChange={e=>update(i,'notes',e.target.value)}/></Field>
 </CardContent></Card>)}
 {!form.data.rule_sets.length&&<div className="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground">No progression rules configured. Use “Add Rule Set”.</div>}
 {form.errors.rule_sets&&<p className="text-sm text-destructive">{form.errors.rule_sets}</p>}
 {editable&&<div className="flex justify-end"><Button disabled={form.processing}><Save className="size-4"/>{form.processing?'Saving…':'Save Progression Rule Sets'}</Button></div>}
 </form></div></>
}
function Field({label,children}:{label:string;children:React.ReactNode}){return <label className="space-y-1.5"><span className="text-sm font-medium">{label}</span>{children}</label>}
function Toggle({label,description,checked,disabled,onChange}:{label:string;description?:string;checked:boolean;disabled:boolean;onChange:(v:boolean)=>void}){return <label className="flex items-start gap-3 rounded-md border p-3"><input className="mt-0.5" type="checkbox" checked={checked} disabled={disabled} onChange={e=>onChange(e.target.checked)}/><span><span className="block text-sm font-medium">{label}</span>{description&&<span className="mt-1 block text-sm text-muted-foreground">{description}</span>}</span></label>}
