import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Policy={id:number;code:string;name:string;version:string;scope_type:string;academic_session:{name:string}};
type Band={minimum_percent:string;maximum_percent:string;grade_code:string;grade_label:string;grade_point:string;is_passing:boolean};
export default function GradingPolicy({policy,rule,bands,editable}:{policy:Policy;rule:any;bands:any[];editable:boolean}) {
 const form=useForm({
  grading_basis:rule?.grading_basis??'LETTER_GRADE', maximum_grade_point:rule?.maximum_grade_point?.toString()??'10',
  calculate_sgpa:rule?.calculate_sgpa??true, calculate_cgpa:rule?.calculate_cgpa??true,
  sgpa_decimal_places:rule?.sgpa_decimal_places?.toString()??'2', cgpa_decimal_places:rule?.cgpa_decimal_places?.toString()??'2',
  rounding_rule:rule?.rounding_rule??'NEAREST', notes:rule?.notes??'',
  bands:(bands??[]).map(b=>({minimum_percent:String(b.minimum_percent),maximum_percent:String(b.maximum_percent),grade_code:b.grade_code,grade_label:b.grade_label??'',grade_point:b.grade_point==null?'':String(b.grade_point),is_passing:Boolean(b.is_passing)})) as Band[]
 });
 const setBand=(i:number,key:keyof Band,value:any)=>{const next=[...form.data.bands];next[i]={...next[i],[key]:value};form.setData('bands',next)};
 const addBand=()=>form.setData('bands',[...form.data.bands,{minimum_percent:'',maximum_percent:'',grade_code:'',grade_label:'',grade_point:'',is_passing:true}]);
 const submit=(e:FormEvent)=>{e.preventDefault();form.put(`/admin/academic-policies/${policy.id}/grading`,{preserveScroll:true})};
 return <><Head title={`${policy.name} Grading Policy`}/><div className="space-y-6 p-4 md:p-6">
 <header className="border-b pb-5"><Button variant="ghost" className="mb-3 px-0" onClick={()=>history.back()}><ArrowLeft className="size-4"/>Academic Policies</Button><p className="text-sm font-medium text-primary">{policy.code} · Version {policy.version}</p><h1 className="mt-1 text-2xl font-semibold sm:text-3xl">Grading Policy</h1><p className="text-sm text-muted-foreground">{policy.name} · {policy.academic_session.name} · {policy.scope_type.replace('_',' ')}</p></header>
 <form onSubmit={submit} className="space-y-6">
 <Card><CardHeader><CardTitle>Grading Method</CardTitle></CardHeader><CardContent className="grid gap-4 md:grid-cols-3">
 <Field label="Grading Basis"><Select disabled={!editable} value={form.data.grading_basis} onValueChange={v=>form.setData('grading_basis',v)}><SelectTrigger><SelectValue/></SelectTrigger><SelectContent><SelectItem value="LETTER_GRADE">Letter Grade</SelectItem><SelectItem value="GRADE_POINT">Grade Point</SelectItem><SelectItem value="PASS_FAIL">Pass / Fail Only</SelectItem></SelectContent></Select></Field>
 {form.data.grading_basis!=='PASS_FAIL'&&<Field label="Maximum Grade Point"><Input disabled={!editable} type="number" min="0" step=".01" value={form.data.maximum_grade_point} onChange={e=>form.setData('maximum_grade_point',e.target.value)}/></Field>}
 <Field label="Rounding Rule"><Select disabled={!editable} value={form.data.rounding_rule} onValueChange={v=>form.setData('rounding_rule',v)}><SelectTrigger><SelectValue/></SelectTrigger><SelectContent><SelectItem value="NONE">No rounding</SelectItem><SelectItem value="NEAREST">Nearest</SelectItem><SelectItem value="FLOOR">Round down</SelectItem><SelectItem value="CEIL">Round up</SelectItem></SelectContent></Select></Field>
 </CardContent></Card>
 {form.data.grading_basis!=='PASS_FAIL'&&<Card><CardHeader><div className="flex items-center justify-between"><div><CardTitle>Grade Bands</CardTitle><p className="mt-1 text-sm text-muted-foreground">Dynamic percentage ranges. Do not hard-code A/B/C grades in application code.</p></div>{editable&&<Button type="button" variant="outline" onClick={addBand}><Plus className="size-4"/>Add Grade</Button>}</div></CardHeader><CardContent className="space-y-3">
 {form.data.bands.map((b,i)=><div key={i} className="grid gap-3 rounded-lg border p-3 md:grid-cols-[1fr_1fr_1fr_1.3fr_1fr_auto_auto]">
 <Field label="Min %"><Input disabled={!editable} type="number" min="0" max="100" step=".01" value={b.minimum_percent} onChange={e=>setBand(i,'minimum_percent',e.target.value)}/></Field>
 <Field label="Max %"><Input disabled={!editable} type="number" min="0" max="100" step=".01" value={b.maximum_percent} onChange={e=>setBand(i,'maximum_percent',e.target.value)}/></Field>
 <Field label="Grade"><Input disabled={!editable} value={b.grade_code} onChange={e=>setBand(i,'grade_code',e.target.value.toUpperCase())}/></Field>
 <Field label="Label"><Input disabled={!editable} value={b.grade_label} onChange={e=>setBand(i,'grade_label',e.target.value)}/></Field>
 <Field label="Point"><Input disabled={!editable} type="number" min="0" step=".01" value={b.grade_point} onChange={e=>setBand(i,'grade_point',e.target.value)}/></Field>
 <label className="flex items-end gap-2 pb-2 text-sm"><input type="checkbox" disabled={!editable} checked={b.is_passing} onChange={e=>setBand(i,'is_passing',e.target.checked)}/>Pass</label>
 {editable&&<Button type="button" variant="ghost" className="self-end" onClick={()=>form.setData('bands',form.data.bands.filter((_,x)=>x!==i))}><Trash2 className="size-4"/></Button>}
 </div>)}
 {form.errors.bands&&<p className="text-sm text-destructive">{form.errors.bands}</p>}
 </CardContent></Card>}
 <Card><CardHeader><CardTitle>SGPA / CGPA</CardTitle></CardHeader><CardContent className="grid gap-4 md:grid-cols-2">
 <Toggle label="Calculate SGPA" checked={form.data.calculate_sgpa} disabled={!editable} onChange={v=>form.setData('calculate_sgpa',v)}/><Toggle label="Calculate CGPA" checked={form.data.calculate_cgpa} disabled={!editable} onChange={v=>form.setData('calculate_cgpa',v)}/>
 {form.data.calculate_sgpa&&<Field label="SGPA Decimal Places"><Input disabled={!editable} type="number" min="0" max="4" value={form.data.sgpa_decimal_places} onChange={e=>form.setData('sgpa_decimal_places',e.target.value)}/></Field>}
 {form.data.calculate_cgpa&&<Field label="CGPA Decimal Places"><Input disabled={!editable} type="number" min="0" max="4" value={form.data.cgpa_decimal_places} onChange={e=>form.setData('cgpa_decimal_places',e.target.value)}/></Field>}
 </CardContent></Card>
 <Card><CardHeader><CardTitle>Notes</CardTitle></CardHeader><CardContent><textarea disabled={!editable} className="min-h-24 w-full rounded-md border bg-background p-3 text-sm" value={form.data.notes} onChange={e=>form.setData('notes',e.target.value)}/></CardContent></Card>
 {editable&&<div className="flex justify-end"><Button disabled={form.processing}><Save className="size-4"/>Save Grading Policy</Button></div>}
 </form></div></>
}
function Field({label,children}:{label:string;children:React.ReactNode}){return <label className="space-y-1.5"><span className="text-sm font-medium">{label}</span>{children}</label>}
function Toggle({label,checked,disabled,onChange}:{label:string;checked:boolean;disabled:boolean;onChange:(v:boolean)=>void}){return <label className="flex items-center gap-3 rounded-lg border p-4"><input type="checkbox" checked={checked} disabled={disabled} onChange={e=>onChange(e.target.checked)}/><span className="text-sm font-medium">{label}</span></label>}
