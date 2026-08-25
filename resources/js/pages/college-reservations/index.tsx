import { Form, Head, router } from '@inertiajs/react';
import { Plus, Power, ShieldCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type Category={id:number;name:string;code:string;nature:'VERTICAL'|'HORIZONTAL';display_order:number};
type Bucket={college_program_intake_id:number;bucket_key:string;bucket_type:string;label:string;basis_capacity:number;program_name:string;session_name:string;is_current_session:boolean};
type Allocation={id:number;reservation_category_id:number;seat_capacity:number;status:string;category:Category};
type Plan={id:number;college_program_intake_id:number;bucket_type:string;bucket_key:string;basis_capacity:number;status:'ACTIVE'|'INACTIVE';notes:string|null;intake:{offering:{program_template:{name:string;code:string};academic_session:{name:string;code:string;is_current:boolean}}};discipline_allocation?:{discipline?:{name:string;code:string}}|null;specialization_allocation?:{specialization?:{name:string;code:string}}|null;allocations:Allocation[]};
type Props={college:{id:number;name:string;code:string;status:string};plans:Plan[];availableBuckets:Bucket[];categories:Category[];can:{create:boolean;update:boolean;enable:boolean;disable:boolean}};

const planBucketLabel=(p:Plan)=>p.bucket_type==='PROGRAM'?'Program seat bucket':p.bucket_type==='SPECIALIZATION'?`${p.discipline_allocation?.discipline?.name??'Discipline'} → ${p.specialization_allocation?.specialization?.name??'Specialization'}`:`${p.discipline_allocation?.discipline?.name??'Discipline'} · General / No Specialization`;

function PlanForm({collegeId,buckets}:{collegeId:number;buckets:Bucket[]}){
    const [key,setKey]=useState(buckets[0]?`${buckets[0].college_program_intake_id}|${buckets[0].bucket_key}`:'');
    const selected=buckets.find(b=>`${b.college_program_intake_id}|${b.bucket_key}`===key);
    return <Dialog><DialogTrigger asChild><Button disabled={!buckets.length}><Plus/>Add Reservation Plan</Button></DialogTrigger>
        <DialogContent className="w-[calc(100vw-2rem)] max-w-xl overflow-hidden sm:w-full">
            <DialogTitle>Add Reservation / Seat Distribution</DialogTitle><DialogDescription>Select one effective admission seat bucket. Parent and child capacity are never double-counted.</DialogDescription>
            <Form action={`/college/${collegeId}/reservations`} method="post" className="min-w-0 space-y-4">{({processing,errors})=><>
                <div className="space-y-2"><Label>Admission Seat Bucket</Label><Select value={key} onValueChange={setKey}><SelectTrigger className="w-full min-w-0"><SelectValue className="truncate"/></SelectTrigger><SelectContent className="max-w-[calc(100vw-2rem)]">{buckets.map(b=>{const k=`${b.college_program_intake_id}|${b.bucket_key}`;return <SelectItem key={k} value={k} className="whitespace-normal">{b.program_name} · {b.session_name} · {b.label} · {b.basis_capacity} seats{b.is_current_session?' · CURRENT':''}</SelectItem>})}</SelectContent></Select>
                <input type="hidden" name="college_program_intake_id" value={selected?.college_program_intake_id??''}/><input type="hidden" name="bucket_key" value={selected?.bucket_key??''}/>{errors.bucket_key&&<p className="text-xs text-destructive">{errors.bucket_key}</p>}</div>
                {selected&&<div className="rounded-md border bg-muted/30 p-3 text-sm"><b>{selected.basis_capacity} seats</b><div className="text-xs text-muted-foreground">{selected.label}</div></div>}
                <div className="space-y-2"><Label>Notes</Label><textarea name="notes" className="min-h-24 w-full resize-y rounded-md border bg-background px-3 py-2 text-sm"/></div>
                <DialogFooter className="flex-wrap"><DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" disabled={processing||!selected}>{processing&&<Spinner/>}Create Plan</Button></DialogFooter>
            </>}</Form>
        </DialogContent>
    </Dialog>
}

function AllocationForm({collegeId,plan,categories,allocation}:{collegeId:number;plan:Plan;categories:Category[];allocation?:Allocation}){
    const action=allocation?`/college/${collegeId}/reservations/${plan.id}/allocations/${allocation.id}`:`/college/${collegeId}/reservations/${plan.id}/allocations`;
    return <Dialog><DialogTrigger asChild><Button variant="outline" size="sm">{allocation?'Edit':<><Plus className="size-4"/>Add Quota</>}</Button></DialogTrigger>
        <DialogContent className="w-[calc(100vw-2rem)] max-w-md sm:w-full">
            <DialogTitle>{allocation?'Edit':'Add'} Reservation / Quota</DialogTitle><DialogDescription>Vertical quota reduces Open/Unreserved remaining. Horizontal quota overlays the same physical seat bucket.</DialogDescription>
            <Form action={action} method={allocation?'patch':'post'} className="space-y-4">{({processing,errors})=><>
                <div className="space-y-2"><Label>Category</Label><Select name="reservation_category_id" defaultValue={allocation?String(allocation.reservation_category_id):undefined}><SelectTrigger className="w-full"><SelectValue placeholder="Select Category"/></SelectTrigger><SelectContent>{categories.map(c=><SelectItem key={c.id} value={String(c.id)}>{c.name} ({c.code}) · {c.nature}</SelectItem>)}</SelectContent></Select>{errors.reservation_category_id&&<p className="text-xs text-destructive">{errors.reservation_category_id}</p>}</div>
                <div className="space-y-2"><Label>Seat Capacity</Label><Input type="number" min={1} max={plan.basis_capacity} name="seat_capacity" defaultValue={allocation?.seat_capacity}/><p className="text-xs text-muted-foreground">Bucket capacity: {plan.basis_capacity}</p>{errors.seat_capacity&&<p className="text-xs text-destructive">{errors.seat_capacity}</p>}</div>
                <DialogFooter><DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" disabled={processing}>{processing&&<Spinner/>}Save</Button></DialogFooter>
            </>}</Form>
        </DialogContent>
    </Dialog>
}

export default function CollegeReservations({college,plans,availableBuckets,categories,can}:Props){
    const del=(plan:Plan,a:Allocation)=>{if(confirm('Remove this Reservation / Quota allocation?'))router.delete(`/college/${college.id}/reservations/${plan.id}/allocations/${a.id}`,{preserveScroll:true});};
    return <><Head title={`${college.name} Reservation / Seat Distribution`}/><div className="space-y-6 p-4 md:p-6">
        <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5"><div><p className="text-sm font-medium text-primary">{college.code} · College Academic Setup</p><h1 className="text-3xl font-semibold">Reservation / Seat Distribution</h1><p className="max-w-3xl text-sm text-muted-foreground">Apply configurable University quota categories to effective Intake admission seat buckets.</p></div>{can.create&&college.status==='ACTIVE'&&<PlanForm collegeId={college.id} buckets={availableBuckets}/>}</header>
        {categories.length===0&&<Card><CardContent className="p-4 text-sm text-muted-foreground">No active Reservation / Quota categories exist. Configure the University category master first.</CardContent></Card>}
        {plans.length===0?<Card><CardContent className="grid place-items-center py-16 text-center"><ShieldCheck className="size-10 text-muted-foreground"/><h2 className="mt-3 font-semibold">No Reservation plan configured</h2><p className="mt-1 text-sm text-muted-foreground">Activate Intake first, then create a plan for the appropriate Program, General Discipline, or Specialization bucket.</p></CardContent></Card>:
        <div className="space-y-4">{plans.map(plan=>{const vertical=plan.allocations.filter(a=>a.status==='ACTIVE'&&a.category.nature==='VERTICAL').reduce((s,a)=>s+Number(a.seat_capacity),0);const open=Number(plan.basis_capacity)-vertical;return <Card key={plan.id}>
            <CardHeader className="gap-4"><div className="flex flex-wrap items-start justify-between gap-3"><div><CardTitle>{plan.intake.offering.program_template.name} · {planBucketLabel(plan)}</CardTitle><p className="mt-1 text-sm text-muted-foreground">{plan.intake.offering.academic_session.name} · Capacity {plan.basis_capacity}</p></div>{(plan.status==='ACTIVE'?can.disable:can.enable)&&<Form action={`/college/${college.id}/reservations/${plan.id}/status`} method="patch"><input type="hidden" name="status" value={plan.status==='ACTIVE'?'INACTIVE':'ACTIVE'}/>{({processing})=><Button type="submit" size="sm" variant={plan.status==='ACTIVE'?'outline':'default'} disabled={processing}><Power className="size-4"/>{plan.status==='ACTIVE'?'Deactivate':'Activate'}</Button>}</Form>}</div>
            <div className="grid gap-2 sm:grid-cols-3"><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Seat Bucket</div><div className="font-medium">{plan.basis_capacity}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Vertical Reserved</div><div className="font-medium">{vertical}</div></div><div className="rounded-md border p-3"><div className="text-xs text-muted-foreground">Open / Unreserved Remaining</div><div className="font-medium">{open}</div></div></div>
            </CardHeader>
            <CardContent className="space-y-3"><div className="flex flex-wrap items-center justify-between gap-2"><div><h3 className="font-medium">Quota Allocations</h3><p className="text-xs text-muted-foreground">Horizontal quotas overlay capacity and do not reduce Open/Unreserved remaining.</p></div>{can.update&&plan.status==='INACTIVE'&&categories.length>0&&<AllocationForm collegeId={college.id} plan={plan} categories={categories}/>}</div>
            {plan.allocations.length===0?<div className="rounded-md border border-dashed p-5 text-sm text-muted-foreground">No allocations added.</div>:<div className="overflow-x-auto rounded-md border"><table className="w-full text-sm"><thead className="bg-muted/50 text-left"><tr><th className="p-3">Category</th><th className="p-3">Nature</th><th className="p-3 text-right">Seats</th><th className="p-3 text-right">Actions</th></tr></thead><tbody>{plan.allocations.map(a=><tr key={a.id} className="border-t"><td className="p-3">{a.category.name} ({a.category.code})</td><td className="p-3">{a.category.nature}</td><td className="p-3 text-right font-medium">{a.seat_capacity}</td><td className="p-3"><div className="flex justify-end gap-1">{can.update&&plan.status==='INACTIVE'&&<><AllocationForm collegeId={college.id} plan={plan} categories={categories} allocation={a}/><Button type="button" variant="ghost" size="icon" onClick={()=>del(plan,a)}><Trash2 className="size-4"/></Button></>}</div></td></tr>)}</tbody></table></div>}</CardContent>
        </Card>})}</div>}
    </div></>;
}
