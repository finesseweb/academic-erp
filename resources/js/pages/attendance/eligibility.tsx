import { Head, router, useForm } from '@inertiajs/react';
import { CheckCircle2, FileCheck2, ShieldCheck, Stethoscope, XCircle } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type Offering={id:number;batch:{name:string};curriculum_course_mapping:{course:{code:string;name:string}}};
type StudentRow={enrollment:{id:number;class_roll_no:string|null;student:{full_name:string;student_uid:string|null;university_roll_no:string|null}};evaluation:null|{held:number;attended:number;percent:number;normal:boolean;eligible:boolean;basis:string;approved_type:string|null};policy:any;finalized:any|null};
type ExceptionRow={id:number;type:string;status:string;attendance_percent_snapshot:string;reason:string;supporting_reference:string|null;decision_remarks:string|null;enrollment:{student:{full_name:string;student_uid:string|null}}};
type Props={college:{id:number;name:string;code:string};offerings:Offering[];selectedOfferingId:number|null;students:StudentRow[];requests:ExceptionRow[];can:{request:boolean;decide:boolean;finalize:boolean}};

export default function AttendanceEligibility({college,offerings,selectedOfferingId,students,requests,can}:Props){
 const [exception,setException]=useState<{row:StudentRow;type:'CONDONATION'|'SPECIAL_EXEMPTION'}|null>(null);
 const [decision,setDecision]=useState<{row:ExceptionRow;value:'APPROVED'|'REJECTED'}|null>(null);
 const requestForm=useForm({type:'CONDONATION',reason:'',supporting_reference:''});
 const decisionForm=useForm({decision:'APPROVED',remarks:''});
 const finalize=useForm({});
 const offering=offerings.find(o=>o.id===selectedOfferingId);
 const submitException=()=>{
if(!exception||!selectedOfferingId){
return;
}

requestForm.transform(d=>({...d,type:exception.type}));requestForm.post(`/college/${college.id}/attendance-eligibility/${exception.row.enrollment.id}/${selectedOfferingId}/exceptions`,{onSuccess:()=>setException(null)});
};
 const submitDecision=()=>{
if(!decision){
return;
}

decisionForm.transform(d=>({...d,decision:decision.value}));decisionForm.patch(`/college/${college.id}/attendance-exceptions/${decision.row.id}`,{onSuccess:()=>setDecision(null)});
};

 return <><Head title={`${college.name} Attendance Eligibility`}/><div className="space-y-6 p-4 md:p-6">
  <header className="border-b pb-5"><p className="text-sm font-medium text-primary">{college.code} · Attendance Operations</p><h1 className="text-3xl font-semibold">Exceptions & Examination Eligibility</h1><p className="max-w-3xl text-muted-foreground">Approve controlled attendance exceptions and finalize the result consumed by Examination. Raw attendance is never changed.</p></header>
  <Card><CardHeader><CardTitle className="flex items-center gap-2"><FileCheck2 className="size-5"/>Course Context</CardTitle></CardHeader><CardContent><select className="h-10 w-full max-w-xl rounded-md border bg-background px-3 text-sm" value={selectedOfferingId??''} onChange={e=>router.get(`/college/${college.id}/attendance-eligibility`,{course_offering_id:e.target.value},{preserveState:true})}><option value="">Select Course Offering</option>{offerings.map(o=><option key={o.id} value={o.id}>{o.curriculum_course_mapping.course.code} · {o.curriculum_course_mapping.course.name} · {o.batch.name}</option>)}</select></CardContent></Card>
  <Card><CardHeader><CardTitle>Student Attendance Decisions{offering?` · ${offering.curriculum_course_mapping.course.code}`:''}</CardTitle></CardHeader><CardContent><div className="overflow-x-auto"><table className="w-full min-w-[1050px] text-sm"><thead className="bg-muted/40 text-left"><tr>{['Student','Finalized Attendance','Policy Result','Approved Exception','Final Eligibility','Actions'].map(x=><th key={x} className="p-3">{x}</th>)}</tr></thead><tbody className="divide-y">{students.map(row=>{
const e=row.evaluation;

return <tr key={row.enrollment.id}><td className="p-3 font-medium">{row.enrollment.class_roll_no??row.enrollment.student.university_roll_no??row.enrollment.student.student_uid??'Pending ID'} · {row.enrollment.student.full_name}</td><td className="p-3">{e?`${e.attended}/${e.held} · ${e.percent}%`:<span className="text-muted-foreground">No finalized attendance</span>}</td><td className="p-3"><Badge variant={e?.normal?'default':'secondary'}>{e?.normal?'THRESHOLD MET':'SHORTAGE'}</Badge></td><td className="p-3">{e?.approved_type?.replace('_',' ')??'—'}</td><td className="p-3">{row.finalized?<Badge variant={row.finalized.is_eligible?'default':'destructive'}>{row.finalized.is_eligible?'ELIGIBLE':'NOT ELIGIBLE'} · {row.finalized.basis.replace('_',' ')}</Badge>:<span className="text-muted-foreground">Not finalized</span>}</td><td className="p-3"><div className="flex flex-wrap gap-2">{can.request&&e&&!e.normal&&<><Button size="sm" variant="outline" onClick={()=>setException({row,type:'CONDONATION'})}><ShieldCheck/>Condonation</Button><Button size="sm" variant="outline" onClick={()=>setException({row,type:'SPECIAL_EXEMPTION'})}><Stethoscope/>Special Exemption</Button></>}{can.finalize&&e&&<Button size="sm" disabled={finalize.processing} onClick={()=>finalize.post(`/college/${college.id}/attendance-eligibility/${row.enrollment.id}/${selectedOfferingId}/finalize`)}><CheckCircle2/>Finalize</Button>}</div></td></tr>
})}{!students.length&&<tr><td colSpan={6} className="p-12 text-center text-muted-foreground">No eligible enrolled students with this Course Offering, or no Course Offering is selected.</td></tr>}</tbody></table></div></CardContent></Card>
  <Card><CardHeader><CardTitle>Exception Decision Queue</CardTitle></CardHeader><CardContent><div className="overflow-x-auto"><table className="w-full min-w-[850px] text-sm"><thead className="bg-muted/40 text-left"><tr>{['Student','Type','Snapshot','Reason / Evidence','Status','Decision'].map(x=><th key={x} className="p-3">{x}</th>)}</tr></thead><tbody className="divide-y">{requests.map(r=><tr key={r.id}><td className="p-3 font-medium">{r.enrollment.student.full_name}</td><td className="p-3">{r.type.replace('_',' ')}</td><td className="p-3">{r.attendance_percent_snapshot}%</td><td className="p-3">{r.reason}<div className="text-xs text-muted-foreground">{r.supporting_reference??'No supporting reference'}</div></td><td className="p-3"><Badge variant={r.status==='APPROVED'?'default':r.status==='REJECTED'?'destructive':'secondary'}>{r.status}</Badge></td><td className="p-3">{can.decide&&r.status==='PENDING'?<div className="flex gap-2"><Button size="sm" onClick={()=>setDecision({row:r,value:'APPROVED'})}><CheckCircle2/>Approve</Button><Button size="sm" variant="destructive" onClick={()=>setDecision({row:r,value:'REJECTED'})}><XCircle/>Reject</Button></div>:r.decision_remarks??'—'}</td></tr>)}{!requests.length&&<tr><td colSpan={6} className="p-10 text-center text-muted-foreground">No attendance exception requests in this course context.</td></tr>}</tbody></table></div></CardContent></Card>
 </div>
 <Dialog open={!!exception} onOpenChange={v=>!v&&setException(null)}><DialogContent><DialogTitle>{exception?.type==='CONDONATION'?'Request Condonation':'Request Medical / Special Exemption'}</DialogTitle><DialogDescription>The applicable policy and current finalized percentage are validated again by the server.</DialogDescription><Textarea value={requestForm.data.reason} onChange={e=>requestForm.setData('reason',e.target.value)} placeholder="Reason (minimum 10 characters)"/><Input value={requestForm.data.supporting_reference} onChange={e=>requestForm.setData('supporting_reference',e.target.value)} placeholder="Supporting document/reference (optional)"/><p className="text-sm text-destructive">{requestForm.errors.reason||requestForm.errors.type||(requestForm.errors as Record<string,string>).attendance}</p><DialogFooter><Button disabled={requestForm.processing||requestForm.data.reason.trim().length<10} onClick={submitException}>{requestForm.processing&&<Spinner/>}Submit Request</Button></DialogFooter></DialogContent></Dialog>
 <Dialog open={!!decision} onOpenChange={v=>!v&&setDecision(null)}><DialogContent><DialogTitle>{decision?.value==='APPROVED'?'Approve':'Reject'} Exception Request</DialogTitle><DialogDescription>This audited decision affects final eligibility but never changes raw attendance.</DialogDescription><Textarea value={decisionForm.data.remarks} onChange={e=>decisionForm.setData('remarks',e.target.value)} placeholder="Decision remarks"/><p className="text-sm text-destructive">{decisionForm.errors.remarks}</p><DialogFooter><Button variant={decision?.value==='REJECTED'?'destructive':'default'} disabled={decisionForm.processing||decisionForm.data.remarks.trim().length<5} onClick={submitDecision}>{decisionForm.processing&&<Spinner/>}{decision?.value==='APPROVED'?<CheckCircle2/>:<XCircle/>}{decision?.value==='APPROVED'?'Approve':'Reject'}</Button></DialogFooter></DialogContent></Dialog>
 </>;
}
