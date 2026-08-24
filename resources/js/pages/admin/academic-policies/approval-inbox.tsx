import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, RotateCcw, XCircle } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

type Stage={sequence_no:number;name:string;status:string;remarks:string|null;decided_at:string|null};
type ApprovalRow={
 id:number;status:string;submitted_at:string;policy_id:number;policy_name:string;policy_code:string;
 policy_version:string;scope_type:string;policy_approval_status:string;current_stage_name:string|null;
 workflow_name:string|null;remarks_required_on_reject:boolean;remarks_required_on_return:boolean;
 can_decide:boolean;stages:Stage[];
};
type Props={requests:{data:ApprovalRow[];links:{url:string|null;label:string;active:boolean}[];total:number}};

export default function AcademicPolicyApprovalInbox({requests}:Props){
 const [selected,setSelected]=useState<ApprovalRow|null>(null);
 const form=useForm({decision:'APPROVE',remarks:''});
 const decide=(decision:'APPROVE'|'RETURN'|'REJECT')=>{
  if(!selected)return;
  form.setData('decision',decision);
  form.post(`/admin/academic-policies/approval-inbox/${selected.id}/decision`,{
   preserveScroll:true,onSuccess:()=>{setSelected(null);form.reset()}
  });
 };
 return <><Head title="Academic Policy Approval Inbox"/><div className="space-y-6 p-4 md:p-6">
  <header className="border-b pb-5">
   <Button variant="ghost" className="mb-3 px-0" onClick={()=>router.get('/admin/academic-policies')}><ArrowLeft className="size-4"/>Academic Policies</Button>
   <h1 className="text-2xl font-semibold sm:text-3xl">Academic Policy Approval Inbox</h1>
   <p className="mt-1 text-sm text-muted-foreground">Review submitted Academic Policy versions through the configured role-based approval stages.</p>
  </header>
  <Card className="overflow-hidden"><div className="overflow-x-auto"><table className="w-full text-sm"><thead className="border-b bg-muted/50 text-left"><tr><th className="px-4 py-4">Policy</th><th className="px-4 py-4">Workflow</th><th className="px-4 py-4">Current Stage</th><th className="px-4 py-4">Status</th><th className="px-4 py-4 text-right">Action</th></tr></thead><tbody>
   {requests.data.map(r=><tr key={r.id} className="border-b last:border-0"><td className="px-4 py-4"><div className="font-medium">{r.policy_name} · V{r.policy_version}</div><div className="text-xs text-muted-foreground">{r.policy_code} · {r.scope_type.replace('_',' ')}</div></td><td className="px-4 py-4">{r.workflow_name??'—'}</td><td className="px-4 py-4">{r.current_stage_name??'Completed'}</td><td className="px-4 py-4"><span className="rounded-full border px-2 py-1 text-xs">{r.status}</span></td><td className="px-4 py-4 text-right"><Button size="sm" variant="outline" onClick={()=>{setSelected(r);form.setData({decision:'APPROVE',remarks:''})}}>{r.can_decide?'Review':'View'}</Button></td></tr>)}
   {!requests.data.length&&<tr><td colSpan={5} className="px-6 py-14 text-center text-muted-foreground">No Academic Policy approval requests found.</td></tr>}
  </tbody></table></div></Card>

  {selected&&<div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm"><div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border bg-card shadow-xl"><div className="border-b p-5"><h2 className="text-lg font-semibold">{selected.policy_name} · V{selected.policy_version}</h2><p className="text-sm text-muted-foreground">{selected.workflow_name} · {selected.current_stage_name??'Completed'}</p></div><div className="space-y-5 p-5"><div><div className="mb-2 text-sm font-medium">Approval History</div><div className="space-y-2">{selected.stages.map(s=><div key={s.sequence_no} className="rounded-lg border p-3"><div className="flex justify-between gap-3"><span className="font-medium">{s.sequence_no}. {s.name}</span><span className="text-xs">{s.status}</span></div>{s.remarks&&<p className="mt-1 text-sm text-muted-foreground">{s.remarks}</p>}</div>)}</div></div>
   {selected.can_decide&&<><label className="space-y-1.5"><span className="text-sm font-medium">Remarks</span><textarea className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" value={form.data.remarks} onChange={e=>form.setData('remarks',e.target.value)} placeholder="Required for Return/Reject when configured by workflow"/></label>{form.errors.remarks&&<p className="text-sm text-destructive">{form.errors.remarks}</p>}<div className="flex flex-wrap justify-end gap-2"><Button variant="outline" onClick={()=>setSelected(null)}>Close</Button><Button type="button" variant="outline" onClick={()=>decide('RETURN')} disabled={form.processing}><RotateCcw className="size-4"/>Return</Button><Button type="button" variant="destructive" onClick={()=>decide('REJECT')} disabled={form.processing}><XCircle className="size-4"/>Reject</Button><Button type="button" onClick={()=>decide('APPROVE')} disabled={form.processing}><Check className="size-4"/>Approve</Button></div></>}
   {!selected.can_decide&&<div className="flex justify-end"><Button variant="outline" onClick={()=>setSelected(null)}>Close</Button></div>}
  </div></div></div>}
 </div></>
}
