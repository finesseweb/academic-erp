import { Head, router, useForm } from '@inertiajs/react';
import { BookCheck, CheckCircle2, ChevronDown, Circle, ClipboardCheck, Copy, GitBranch, MoreHorizontal, Pencil, Plus, Search, Send, Settings2, ShieldCheck, X } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DatePicker } from '@/components/ui/date-picker';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

type Option = { id: number; name: string; code: string };
type AcademicSessionOption = Option & { is_current: boolean };
type CurriculumOption = { id: number; name: string; code: string; version: string; program_template_id: number; academic_session_id: number };
type Policy = {
    id: number; name: string; code: string; version: string; scope_type: 'UNIVERSITY'|'DEGREE_LEVEL'|'PROGRAM_TEMPLATE'|'CURRICULUM';
    effective_from: string|null; effective_to: string|null; lifecycle_status: 'DRAFT'|'ACTIVE'|'RETIRED'; approval_status: string;
    validation_current: boolean;
    credit_completion_configured: boolean;
    attendance_configured: boolean;
    assessment_exam_configured: boolean;
    grading_configured: boolean;
    progression_configured: boolean;
    can_submit_for_approval: boolean;
    can_amend: boolean;
    is_current_version: boolean;
    is_previous_version: boolean;
    amendment_of_version: string|null;
    description: string|null; academic_session: Option; degree_level?: Option|null; program_template?: Option|null; curriculum?: {id:number;name:string;code:string;version:string}|null;
};
type Props = {
    policies: { data: Policy[]; links: {url:string|null;label:string;active:boolean}[]; total:number };
    filters: {search?:string;status?:string}; academicSessions: AcademicSessionOption[]; degreeLevels: Option[]; programTemplates: Option[]; curricula: CurriculumOption[];
    approvalWorkflows: {id:number;name:string;code:string;applies_to:string}[];
    permissions: {create:boolean;update:boolean;disable:boolean;submitApproval:boolean;viewApproval:boolean};
};
const empty = { academic_session_id:'', degree_level_id:'', program_template_id:'', curriculum_id:'', name:'', code:'', version:'1.0', scope_type:'UNIVERSITY', effective_from:'', effective_to:'', description:'' };

export default function AcademicPolicyIndex({policies, filters, academicSessions, degreeLevels, programTemplates, curricula, approvalWorkflows, permissions}:Props) {
    const [search,setSearch]=useState(filters.search??''); const [status,setStatus]=useState(filters.status??'');
    const [showForm,setShowForm]=useState(false); const [editing,setEditing]=useState<Policy|null>(null);
    const [approvalPolicy,setApprovalPolicy]=useState<Policy|null>(null);
    const [amendPolicy,setAmendPolicy]=useState<Policy|null>(null);
    const [clonePolicy,setClonePolicy]=useState<Policy|null>(null);
    const form=useForm(empty);
    const approvalForm=useForm({approval_workflow_id:''});
    const amendForm=useForm({version:'',revision_type:'AMENDMENT',revision_reason:'',revision_effective_from:''});
    const cloneForm=useForm({name:'',code:'',version:'1.0',academic_session_id:'',effective_from:'',effective_to:''});
    const title=useMemo(()=>editing?'Edit Academic Policy':'Add Academic Policy',[editing]);

    const currentAcademicSession=academicSessions.find(session=>session.is_current);
    const openCreate=()=>{ setEditing(null); form.setData({...empty,academic_session_id:currentAcademicSession?String(currentAcademicSession.id):''}); form.clearErrors(); setShowForm(true); };
    const openEdit=(p:Policy)=>{ setEditing(p); form.setData({academic_session_id:String(p.academic_session.id),degree_level_id:p.degree_level?String(p.degree_level.id):'',program_template_id:p.program_template?String(p.program_template.id):'',curriculum_id:p.curriculum?String(p.curriculum.id):'',name:p.name,code:p.code,version:p.version,scope_type:p.scope_type,effective_from:p.effective_from??'',effective_to:p.effective_to??'',description:p.description??''}); form.clearErrors(); setShowForm(true); };
    const submit=(e:FormEvent)=>{e.preventDefault(); const opt={preserveScroll:true,onSuccess:()=>setShowForm(false)}; editing?form.patch(`/admin/academic-policies/${editing.id}`,opt):form.post('/admin/academic-policies',opt);};
    const applyFilters=()=>router.get('/admin/academic-policies',{search,status},{preserveState:true,replace:true});
    const validatePolicy=(p:Policy)=>router.post(`/admin/academic-policies/${p.id}/validate`,{}, {preserveScroll:true});

    const availableCurricula=curricula.filter(c=>!form.data.academic_session_id || String(c.academic_session_id)===form.data.academic_session_id).filter(c=>!form.data.program_template_id || String(c.program_template_id)===form.data.program_template_id);

    return <>
        <Head title="Academic Policies" />
        <div className="space-y-6 p-4 md:p-6">
            <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div><p className="flex items-center gap-2 text-sm font-medium text-primary"><BookCheck className="size-4"/>Academic governance</p><h1 className="mt-1 text-2xl font-semibold sm:text-3xl">Academic Policies</h1><p className="mt-1 text-sm text-muted-foreground">Create versioned academic policy headers, then configure rule sections under each policy.</p></div>
                <div className="flex gap-2">
                    {permissions.viewApproval&&<Button variant="outline" onClick={()=>router.get('/admin/academic-policies/approval-inbox')}><ShieldCheck className="size-4"/>Policy Approval Inbox</Button>}
                    {permissions.create&&<Button onClick={openCreate}><Plus className="size-4"/>Add Academic Policy</Button>}
                </div>
            </header>
            <Card><CardContent className="pt-6"><div className="grid gap-3 md:grid-cols-[1fr_220px_auto]"><div className="relative"><Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"/><Input className="pl-9" value={search} onChange={e=>setSearch(e.target.value)} onKeyDown={e=>e.key==='Enter'&&applyFilters()} placeholder="Search policy name, code or version"/></div><Select value={status||'all'} onValueChange={v=>setStatus(v==='all'?'':v)}><SelectTrigger><SelectValue/></SelectTrigger><SelectContent><SelectItem value="all">All statuses</SelectItem><SelectItem value="DRAFT">Draft</SelectItem><SelectItem value="ACTIVE">Active</SelectItem><SelectItem value="RETIRED">Retired</SelectItem></SelectContent></Select><Button onClick={applyFilters}>Filter</Button></div></CardContent></Card>
            <Card className="overflow-hidden"><div className="overflow-x-auto"><table className="w-full text-sm"><thead className="border-b bg-muted/50 text-left"><tr><th className="px-4 py-4">Policy</th><th className="px-4 py-4">Scope</th><th className="px-4 py-4">Academic Session</th><th className="px-4 py-4">Version</th><th className="px-4 py-4">Effective</th><th className="px-4 py-4">Status</th><th className="px-4 py-4">Approval</th><th className="px-4 py-4">Validation</th><th className="px-4 py-4 text-right">Actions</th></tr></thead><tbody>
                {policies.data.map(p=><tr key={p.id} className="border-b transition-colors last:border-0 hover:bg-muted/40"><td className="px-4 py-4"><div className="font-medium">{p.name}</div><div className="text-xs text-muted-foreground">{p.code}</div></td><td className="px-4 py-4"><div>{p.scope_type.replace('_',' ')}</div><div className="text-xs text-muted-foreground">{p.curriculum?`${p.curriculum.name} · ${p.curriculum.version}`:p.program_template?.name??p.degree_level?.name??'University-wide'}</div></td><td className="px-4 py-4">{p.academic_session.name}</td><td className="px-4 py-4"><div>{p.version}</div>{p.is_current_version&&<div className="mt-1"><span className="rounded-full bg-emerald-500/10 px-2 py-1 text-xs text-emerald-700 dark:text-emerald-300">Current</span></div>}{p.amendment_of_version&&<div className="mt-1 text-xs text-muted-foreground">Amendment of v{p.amendment_of_version}</div>}{p.is_previous_version&&<div className="mt-1"><span className="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground">Previous</span></div>}</td><td className="px-4 py-4 text-xs">{p.effective_from||'—'}{p.effective_to?` → ${p.effective_to}`:''}</td><td className="px-4 py-4"><span className={`rounded-full px-2 py-1 text-xs ${p.lifecycle_status==='ACTIVE'?'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300':p.lifecycle_status==='DRAFT'?'bg-amber-500/10 text-amber-700 dark:text-amber-300':'bg-muted text-muted-foreground'}`}>{p.lifecycle_status==='ACTIVE'?'Active':p.lifecycle_status==='DRAFT'?'Draft':'Retired'}</span></td><td className="px-4 py-4"><span className="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground">{p.approval_status==='NOT_SUBMITTED'?'Not Submitted':p.approval_status.replaceAll('_',' ').toLowerCase().replace(/^./,v=>v.toUpperCase())}</span></td><td className="px-4 py-4"><span className={p.validation_current?'text-sm font-medium text-emerald-600':'text-sm text-muted-foreground'}>{p.validation_current?'Current PASS':'Not validated'}</span></td><td className="px-4 py-4"><div className="flex items-center justify-end gap-1">
                    <DropdownMenu><DropdownMenuTrigger asChild><Button type="button" size="sm" variant="ghost"><Settings2 className="size-4"/>Configure<ChevronDown className="size-3.5"/></Button></DropdownMenuTrigger><DropdownMenuContent align="end" className="w-72"><DropdownMenuLabel><div>Configure Policy</div><div className="mt-0.5 text-xs font-normal text-muted-foreground">Open one rule section at a time.</div></DropdownMenuLabel><DropdownMenuSeparator/><PolicyConfigItem configured={p.credit_completion_configured} label="Credit / Completion" onSelect={()=>router.get(`/admin/academic-policies/${p.id}/credit-completion`)}/><PolicyConfigItem configured={p.attendance_configured} label="Attendance" onSelect={()=>router.get(`/admin/academic-policies/${p.id}/attendance`)}/><PolicyConfigItem configured={p.assessment_exam_configured} label="Assessment / Exam" onSelect={()=>router.get(`/admin/academic-policies/${p.id}/assessment-examination`)}/><PolicyConfigItem configured={p.grading_configured} label="Grading" onSelect={()=>router.get(`/admin/academic-policies/${p.id}/grading`)}/><PolicyConfigItem configured={p.progression_configured} label="Promotion / Progression" onSelect={()=>router.get(`/admin/academic-policies/${p.id}/progression`)}/></DropdownMenuContent></DropdownMenu>
                    <DropdownMenu><DropdownMenuTrigger asChild><Button type="button" size="sm" variant="outline"><MoreHorizontal className="size-4"/>More</Button></DropdownMenuTrigger><DropdownMenuContent align="end" className="w-56">
                        {p.can_submit_for_approval&&<DropdownMenuItem onSelect={()=>{setApprovalPolicy(p);approvalForm.setData('approval_workflow_id',approvalWorkflows[0]?String(approvalWorkflows[0].id):'')}}><Send className="mr-2 size-4"/>Submit for Approval</DropdownMenuItem>}
                        {permissions.create&&<DropdownMenuItem onSelect={()=>{setClonePolicy(p);cloneForm.setData({name:`${p.name} Copy`,code:`${p.code}-COPY`,version:'1.0',academic_session_id:String(p.academic_session.id),effective_from:p.effective_from??'',effective_to:p.effective_to??''});cloneForm.clearErrors()}}><Copy className="mr-2 size-4"/>Clone Full Policy</DropdownMenuItem>}
                        {p.can_amend&&<DropdownMenuItem onSelect={()=>{setAmendPolicy(p);amendForm.setData({version:'',revision_type:'AMENDMENT',revision_reason:'',revision_effective_from:''})}}><GitBranch className="mr-2 size-4"/>Amend Policy</DropdownMenuItem>}
                        {permissions.update&&p.lifecycle_status==='DRAFT'&&!['SUBMITTED','UNDER_APPROVAL','APPROVED'].includes(p.approval_status)&&<><DropdownMenuSeparator/><DropdownMenuItem onSelect={()=>openEdit(p)}><Pencil className="mr-2 size-4"/>Edit Policy Header</DropdownMenuItem><DropdownMenuItem onSelect={()=>validatePolicy(p)}><ClipboardCheck className="mr-2 size-4"/>Validate Complete Policy</DropdownMenuItem></>}
                    </DropdownMenuContent></DropdownMenu>
                </div></td></tr>)}
                {!policies.data.length&&<tr><td colSpan={9} className="px-6 py-14 text-center text-muted-foreground">No Academic Policies found.</td></tr>}
            </tbody></table></div></Card>
        </div>
        {showForm&&<div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm"><div className="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-xl border bg-card shadow-xl"><div className="flex items-start justify-between border-b p-5"><div><h2 className="text-lg font-semibold">{title}</h2><p className="mt-1 text-sm text-muted-foreground">Phase 1 policy header. Approval and amendment will use this same versioned record.</p></div><Button variant="ghost" size="icon" onClick={()=>setShowForm(false)}><X className="size-4"/></Button></div><form onSubmit={submit} className="grid gap-4 p-5 md:grid-cols-2">
            <Field label="Policy Name" error={form.errors.name}><Input value={form.data.name} onChange={e=>form.setData('name',e.target.value)}/></Field><Field label="Policy Code" error={form.errors.code}><Input value={form.data.code} onChange={e=>form.setData('code',e.target.value)}/></Field>
            <Field label="Academic Session" error={form.errors.academic_session_id}><Select value={form.data.academic_session_id} onValueChange={v=>{form.setData('academic_session_id',v);form.setData('curriculum_id','')}}><SelectTrigger><SelectValue placeholder="Select Session"/></SelectTrigger><SelectContent>{academicSessions.map(o=><SelectItem key={o.id} value={String(o.id)}>{o.name} ({o.code}){o.is_current?' · Current':''}</SelectItem>)}</SelectContent></Select></Field>
            <Field label="Version" error={form.errors.version}><Input value={form.data.version} onChange={e=>form.setData('version',e.target.value)}/></Field>
            <Field label="Scope" error={form.errors.scope_type}><Select value={form.data.scope_type} onValueChange={v=>{form.setData('scope_type',v);form.setData('degree_level_id','');form.setData('program_template_id','');form.setData('curriculum_id','')}}><SelectTrigger><SelectValue/></SelectTrigger><SelectContent><SelectItem value="UNIVERSITY">University-wide</SelectItem><SelectItem value="DEGREE_LEVEL">Degree Level</SelectItem><SelectItem value="PROGRAM_TEMPLATE">Program Template</SelectItem><SelectItem value="CURRICULUM">Curriculum</SelectItem></SelectContent></Select></Field>
            {form.data.scope_type==='DEGREE_LEVEL'&&<Field label="Degree Level" error={form.errors.degree_level_id}><Select value={form.data.degree_level_id} onValueChange={v=>form.setData('degree_level_id',v)}><SelectTrigger><SelectValue placeholder="Select Degree Level"/></SelectTrigger><SelectContent>{degreeLevels.map(o=><SelectItem key={o.id} value={String(o.id)}>{o.name} ({o.code}){o.is_current?' · Current':''}</SelectItem>)}</SelectContent></Select></Field>}
            {(form.data.scope_type==='PROGRAM_TEMPLATE'||form.data.scope_type==='CURRICULUM')&&<Field label="Program Template" error={form.errors.program_template_id}><Select value={form.data.program_template_id} onValueChange={v=>{form.setData('program_template_id',v);form.setData('curriculum_id','')}}><SelectTrigger><SelectValue placeholder="Select Program"/></SelectTrigger><SelectContent>{programTemplates.map(o=><SelectItem key={o.id} value={String(o.id)}>{o.name} ({o.code}){o.is_current?' · Current':''}</SelectItem>)}</SelectContent></Select></Field>}
            {form.data.scope_type==='CURRICULUM'&&<Field label="Current Approved Curriculum" error={form.errors.curriculum_id}><Select value={form.data.curriculum_id} onValueChange={v=>form.setData('curriculum_id',v)}><SelectTrigger><SelectValue placeholder="Select Curriculum"/></SelectTrigger><SelectContent>{availableCurricula.map(o=><SelectItem key={o.id} value={String(o.id)}>{o.name} · V{o.version} · Current</SelectItem>)}</SelectContent></Select></Field>}
            <Field label="Effective From" error={form.errors.effective_from}><DatePicker id="academic-policy-effective-from" name="effective_from" value={form.data.effective_from} onValueChange={v=>form.setData('effective_from',v)}/></Field><Field label="Effective To" error={form.errors.effective_to}><DatePicker id="academic-policy-effective-to" name="effective_to" value={form.data.effective_to} onValueChange={v=>form.setData('effective_to',v)} min={form.data.effective_from||'1800-01-01'}/></Field>
            <div className="md:col-span-2"><Field label="Description" error={form.errors.description}><textarea className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" value={form.data.description} onChange={e=>form.setData('description',e.target.value)}/></Field></div><div className="flex justify-end gap-2 border-t pt-4 md:col-span-2"><Button type="button" variant="outline" onClick={()=>setShowForm(false)}>Cancel</Button><Button disabled={form.processing}>{form.processing?'Saving…':editing?'Update Policy':'Create Policy'}</Button></div>
        </form></div></div>}
        {approvalPolicy&&<div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm"><div className="w-full max-w-lg rounded-xl border bg-card shadow-xl"><div className="flex items-start justify-between border-b p-5"><div><h2 className="text-lg font-semibold">Submit for Approval</h2><p className="mt-1 text-sm text-muted-foreground">{approvalPolicy.name} · V{approvalPolicy.version}</p></div><Button variant="ghost" size="icon" onClick={()=>setApprovalPolicy(null)}><X className="size-4"/></Button></div><form className="space-y-4 p-5" onSubmit={e=>{e.preventDefault();approvalForm.post(`/admin/academic-policies/${approvalPolicy.id}/submit-for-approval`,{preserveScroll:true,onSuccess:()=>setApprovalPolicy(null)})}}><Field label="Approval Workflow" error={approvalForm.errors.approval_workflow_id}><Select value={approvalForm.data.approval_workflow_id} onValueChange={v=>approvalForm.setData('approval_workflow_id',v)}><SelectTrigger><SelectValue placeholder="Select Workflow"/></SelectTrigger><SelectContent>{approvalWorkflows.map(w=><SelectItem key={w.id} value={String(w.id)}>{w.name} ({w.code})</SelectItem>)}</SelectContent></Select></Field><p className="text-sm text-muted-foreground">Submission is allowed only after the complete policy has a current validation PASS. The policy becomes read-only while approval is pending.</p><div className="flex justify-end gap-2"><Button type="button" variant="outline" onClick={()=>setApprovalPolicy(null)}>Cancel</Button><Button disabled={approvalForm.processing||!approvalForm.data.approval_workflow_id}><Send className="size-4"/>{approvalForm.processing?'Submitting…':'Submit for Approval'}</Button></div></form></div></div>}
        {clonePolicy&&<div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm"><div className="w-full max-w-2xl rounded-xl border bg-card shadow-xl"><div className="flex items-start justify-between border-b p-5"><div><h2 className="text-lg font-semibold">Clone Full Academic Policy</h2><p className="mt-1 text-sm text-muted-foreground">Copies Credit / Completion, Attendance, Assessment / Exam, Grading and the complete Promotion / Progression structure from {clonePolicy.name} · V{clonePolicy.version}.</p></div><Button variant="ghost" size="icon" onClick={()=>setClonePolicy(null)}><X className="size-4"/></Button></div><form className="grid gap-4 p-5 md:grid-cols-2" onSubmit={e=>{e.preventDefault();cloneForm.post(`/admin/academic-policies/${clonePolicy.id}/clone-full`,{preserveScroll:true,onSuccess:()=>setClonePolicy(null)})}}><Field label="New Policy Name" error={cloneForm.errors.name}><Input value={cloneForm.data.name} onChange={e=>cloneForm.setData('name',e.target.value)}/></Field><Field label="New Policy Code" error={cloneForm.errors.code}><Input value={cloneForm.data.code} onChange={e=>cloneForm.setData('code',e.target.value)}/></Field><Field label="Version" error={cloneForm.errors.version}><Input value={cloneForm.data.version} onChange={e=>cloneForm.setData('version',e.target.value)}/></Field><Field label="Academic Session" error={cloneForm.errors.academic_session_id}><Select value={cloneForm.data.academic_session_id} onValueChange={v=>cloneForm.setData('academic_session_id',v)}><SelectTrigger><SelectValue placeholder="Select target Academic Session"/></SelectTrigger><SelectContent>{academicSessions.map(o=><SelectItem key={o.id} value={String(o.id)}>{o.name} ({o.code}){o.is_current?' · Current':''}</SelectItem>)}</SelectContent></Select></Field><div className="rounded-lg border bg-muted/20 p-3 md:col-span-2"><div className="text-sm font-medium">Inherited Scope</div><div className="mt-1 text-sm text-muted-foreground">{clonePolicy.scope_type.replaceAll('_',' ')}{clonePolicy.degree_level?` · ${clonePolicy.degree_level.name}`:clonePolicy.program_template?` · ${clonePolicy.program_template.name}`:''}. Scope stays inherited; Academic Session can be changed. Cross-session progression terms are safely remapped.</div></div><Field label="Effective From" error={cloneForm.errors.effective_from}><DatePicker id="clone-policy-effective-from" name="effective_from" value={cloneForm.data.effective_from} onValueChange={v=>cloneForm.setData('effective_from',v)}/></Field><Field label="Effective To" error={cloneForm.errors.effective_to}><DatePicker id="clone-policy-effective-to" name="effective_to" value={cloneForm.data.effective_to} onValueChange={v=>cloneForm.setData('effective_to',v)} min={cloneForm.data.effective_from||'1800-01-01'}/></Field><div className="md:col-span-2 rounded-lg border p-3 text-sm text-muted-foreground">The clone is created as <strong className="text-foreground">Draft / Not Submitted</strong>. Validation and approval are intentionally not copied.</div><div className="flex justify-end gap-2 border-t pt-4 md:col-span-2"><Button type="button" variant="outline" onClick={()=>setClonePolicy(null)}>Cancel</Button><Button disabled={cloneForm.processing}><Copy className="size-4"/>{cloneForm.processing?'Cloning…':'Clone Full Policy'}</Button></div></form></div></div>}
        {amendPolicy&&<div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm"><div className="w-full max-w-lg rounded-xl border bg-card shadow-xl"><div className="flex items-start justify-between border-b p-5"><div><h2 className="text-lg font-semibold">Amend Academic Policy</h2><p className="mt-1 text-sm text-muted-foreground">{amendPolicy.name} · Current V{amendPolicy.version}</p></div><Button variant="ghost" size="icon" onClick={()=>setAmendPolicy(null)}><X className="size-4"/></Button></div><form className="space-y-4 p-5" onSubmit={e=>{e.preventDefault();amendForm.post(`/admin/academic-policies/${amendPolicy.id}/amend`,{preserveScroll:true,onSuccess:()=>setAmendPolicy(null)})}}><Field label="New Version (optional)" error={amendForm.errors.version}><Input value={amendForm.data.version} onChange={e=>amendForm.setData('version',e.target.value)} placeholder="Leave blank for automatic next version"/></Field><Field label="Amendment Reason" error={amendForm.errors.revision_reason}><textarea className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" value={amendForm.data.revision_reason} onChange={e=>amendForm.setData('revision_reason',e.target.value)}/></Field><Field label="Effective From" error={amendForm.errors.revision_effective_from}><DatePicker id="policy-amend-effective" name="revision_effective_from" value={amendForm.data.revision_effective_from} onValueChange={v=>amendForm.setData('revision_effective_from',v)}/></Field><p className="text-sm text-muted-foreground">All configured policy sections are copied into the new Draft version. The current approved version remains active until the amendment receives final approval.</p><div className="flex justify-end gap-2"><Button type="button" variant="outline" onClick={()=>setAmendPolicy(null)}>Cancel</Button><Button disabled={amendForm.processing}><GitBranch className="size-4"/>{amendForm.processing?'Creating…':'Create Amendment'}</Button></div></form></div></div>}

    </>;
}
function PolicyConfigItem({
    configured,
    label,
    onSelect,
}: {
    configured: boolean;
    label: string;
    onSelect: () => void;
}) {
    return (
        <DropdownMenuItem onSelect={onSelect} className="gap-3">
            {configured ? (
                <CheckCircle2 className="size-4 text-emerald-600" />
            ) : (
                <Circle className="size-4 text-muted-foreground" />
            )}
            <span className="flex-1">{label}</span>
            <span className={configured ? 'text-xs text-emerald-600' : 'text-xs text-muted-foreground'}>
                {configured ? 'Configured' : 'Not configured'}
            </span>
        </DropdownMenuItem>
    );
}

function Field({label,error,children}:{label:string;error?:string;children:React.ReactNode}){return <label className="space-y-1.5"><span className="text-sm font-medium">{label}</span>{children}{error&&<span className="block text-xs text-destructive">{error}</span>}</label>}
