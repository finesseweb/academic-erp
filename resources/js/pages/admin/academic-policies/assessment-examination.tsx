import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Policy = { id:number; code:string; name:string; version:string; scope_type:string; lifecycle_status:string; approval_status:string; academic_session:{id:number;name:string;code:string} };
type Rule = {
    minimum_overall_pass_percent:string|number|null;
    require_separate_component_pass:boolean;
    absence_result:'FAIL'|'INCOMPLETE'|'AS_PER_EXAM_RULE';
    allow_grace_marks:boolean; maximum_grace_marks:string|number|null;
    allow_improvement_exam:boolean; allow_supplementary_exam:boolean; notes:string|null;
};

export default function AssessmentExaminationPolicy({policy,rule,editable}:{policy:Policy;rule:Rule|null;editable:boolean}) {
 const form=useForm({
  minimum_overall_pass_percent:rule?.minimum_overall_pass_percent?.toString()??'',
  require_separate_component_pass:rule?.require_separate_component_pass??false,
  absence_result:rule?.absence_result??'AS_PER_EXAM_RULE',
  allow_grace_marks:rule?.allow_grace_marks??false,
  maximum_grace_marks:rule?.maximum_grace_marks?.toString()??'',
  allow_improvement_exam:rule?.allow_improvement_exam??false,
  allow_supplementary_exam:rule?.allow_supplementary_exam??true,
  notes:rule?.notes??'',
 });
 const submit=(e:FormEvent)=>{e.preventDefault();form.put(`/admin/academic-policies/${policy.id}/assessment-examination`,{preserveScroll:true});};
 return <><Head title={`${policy.name} Assessment / Examination Policy`}/><div className="space-y-6 p-4 md:p-6">
  <header className="border-b pb-5"><Button type="button" variant="ghost" className="mb-3 px-0" onClick={()=>window.history.back()}><ArrowLeft className="size-4"/>Academic Policies</Button><p className="text-sm font-medium text-primary">{policy.code} · Version {policy.version}</p><h1 className="mt-1 text-2xl font-semibold sm:text-3xl">Assessment / Examination Policy</h1><p className="mt-1 text-sm text-muted-foreground">{policy.name} · {policy.academic_session.name} · {policy.scope_type.replace('_',' ')}</p>{!editable&&<p className="mt-3 rounded-md border bg-muted/30 p-3 text-sm text-muted-foreground">This policy is read-only because it is not an editable Draft.</p>}</header>
  <form onSubmit={submit} className="space-y-6">
   <Card><CardHeader><CardTitle>Pass Requirement</CardTitle></CardHeader><CardContent className="grid gap-4 md:grid-cols-2">
    <Field label="Minimum Overall Pass %" error={form.errors.minimum_overall_pass_percent}><Input type="number" min="0" max="100" step="0.01" disabled={!editable} value={form.data.minimum_overall_pass_percent} onChange={e=>form.setData('minimum_overall_pass_percent',e.target.value)}/></Field>
    <Field label="Absence Result" error={form.errors.absence_result}><Select disabled={!editable} value={form.data.absence_result} onValueChange={v=>form.setData('absence_result',v as any)}><SelectTrigger><SelectValue/></SelectTrigger><SelectContent><SelectItem value="AS_PER_EXAM_RULE">As per examination rule</SelectItem><SelectItem value="FAIL">Fail</SelectItem><SelectItem value="INCOMPLETE">Incomplete / pending</SelectItem></SelectContent></Select></Field>
    <div className="md:col-span-2"><ToggleRow label="Require Separate Assessment Component Pass" description="Enable when a student must separately pass configured assessment components. Component names and their marks/weightage will come from the future Assessment Scheme, not hard-coded Mid/Internal/External fields here." checked={form.data.require_separate_component_pass} disabled={!editable} onChange={v=>form.setData('require_separate_component_pass',v)}/></div>
   </CardContent></Card>
   <Card><CardHeader><CardTitle>Failure / Re-attempt Controls</CardTitle></CardHeader><CardContent className="space-y-4">
    <ToggleRow label="Allow Grace Marks" description="Permits the future Result process to consider grace marks within the approved maximum. It does not automatically award grace marks." checked={form.data.allow_grace_marks} disabled={!editable} onChange={v=>{form.setData('allow_grace_marks',v);if(!v)form.setData('maximum_grace_marks','')}}/>
    {form.data.allow_grace_marks&&<div className="max-w-sm border-t pt-4"><Field label="Maximum Grace Marks" error={form.errors.maximum_grace_marks}><Input type="number" min="0" step="0.01" disabled={!editable} value={form.data.maximum_grace_marks} onChange={e=>form.setData('maximum_grace_marks',e.target.value)}/></Field></div>}
    <div className="border-t pt-4"><ToggleRow label="Allow Supplementary Examination" description="Allows a future supplementary examination workflow for eligible failed/absent students." checked={form.data.allow_supplementary_exam} disabled={!editable} onChange={v=>form.setData('allow_supplementary_exam',v)}/></div>
    <div className="border-t pt-4"><ToggleRow label="Allow Improvement Examination" description="Allows a future improvement examination workflow under the applicable examination rules." checked={form.data.allow_improvement_exam} disabled={!editable} onChange={v=>form.setData('allow_improvement_exam',v)}/></div>
   </CardContent></Card>
   <Card><CardHeader><CardTitle>Notes</CardTitle></CardHeader><CardContent><textarea className="min-h-28 w-full rounded-md border bg-background px-3 py-2 text-sm" disabled={!editable} value={form.data.notes} onChange={e=>form.setData('notes',e.target.value)} placeholder="Optional regulation reference or implementation note"/></CardContent></Card>
   {editable&&<div className="flex justify-end"><Button disabled={form.processing}><Save className="size-4"/>{form.processing?'Saving…':'Save Assessment / Examination Policy'}</Button></div>}
  </form>
 </div></>;
}
function Field({label,error,children}:{label:string;error?:string;children:React.ReactNode}){return <label className="space-y-1.5"><span className="text-sm font-medium">{label}</span>{children}{error&&<span className="block text-xs text-destructive">{error}</span>}</label>}
function ToggleRow({label,description,checked,disabled,onChange}:{label:string;description:string;checked:boolean;disabled:boolean;onChange:(v:boolean)=>void}){return <label className="flex items-start gap-3"><input type="checkbox" className="mt-1 size-4" checked={checked} disabled={disabled} onChange={e=>onChange(e.target.checked)}/><span><span className="block text-sm font-medium">{label}</span><span className="block text-sm text-muted-foreground">{description}</span></span></label>}
