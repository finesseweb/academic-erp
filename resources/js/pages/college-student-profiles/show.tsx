import { Form, Head, router } from '@inertiajs/react';
import { ArrowLeft, Camera, Save, UserRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Badge } from '@/components/ui/badge';

const valueOf=(p:any)=>p.value_json??p.value_text??'';
function Field({p,disabled}:{p:any;disabled:boolean}){
    if(['FILE','IMAGE'].includes(p.field_type)) return null;
    if(p.field_type==='TEXTAREA')return <div><Label>{p.label}</Label><textarea name={`profile_values[${p.key}]`} defaultValue={String(valueOf(p))} disabled={disabled} className="mt-2 min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"/></div>;
    if(['SELECT','RADIO','YES_NO'].includes(p.field_type)){const opts=p.field_type==='YES_NO'?[{value:'YES',label:'Yes'},{value:'NO',label:'No'}]:p.options;return <div><Label>{p.label}</Label><Select name={`profile_values[${p.key}]`} defaultValue={String(valueOf(p)||'__blank')} disabled={disabled}><SelectTrigger className="mt-2"><SelectValue/></SelectTrigger><SelectContent><SelectItem value="__blank">—</SelectItem>{opts.map((o:any)=><SelectItem key={o.value} value={String(o.value)}>{o.label}</SelectItem>)}</SelectContent></Select></div>}
    if(['MULTISELECT','CHECKBOX'].includes(p.field_type)){const vals=Array.isArray(p.value_json)?p.value_json:[];return <div><Label>{p.label}</Label><div className="mt-2 space-y-2 rounded-md border p-3">{p.options.map((o:any)=><label key={o.value} className="flex items-center gap-2 text-sm"><input type="checkbox" name={`profile_values[${p.key}][]`} value={o.value} defaultChecked={vals.includes(o.value)} disabled={disabled}/>{o.label}</label>)}</div></div>}
    if(p.field_type==='DATE') return <div><Label>{p.label}</Label><div className="mt-2"><DatePicker id={`profile-${p.key}`} name={`profile_values[${p.key}]`} defaultValue={String(valueOf(p))} disabled={disabled}/></div></div>;
    return <div><Label>{p.label}</Label><Input name={`profile_values[${p.key}]`} type={p.field_type==='NUMBER'?'number':p.field_type==='EMAIL'?'email':'text'} defaultValue={String(valueOf(p))} disabled={disabled} className="mt-2"/></div>;
}

export default function Show({college,student,profiles,enrollments,can}:any){
    const photo=profiles.find((p:any)=>p.system_purpose==='CANDIDATE_PROFILE_PHOTO');
    const normalProfiles=profiles.filter((p:any)=>p.system_purpose!=='CANDIDATE_PROFILE_PHOTO');
    const currentEnrollment=enrollments[0]??null;
    return <><Head title={`Student Profile · ${student.full_name}`}/><div className="space-y-5 p-4 md:p-6">
        <Button variant="ghost" onClick={()=>router.visit(`/college/${college.id}/student-profiles`)}><ArrowLeft className="size-4"/>Back to Student Profiles</Button>
        <Card><CardContent className="p-5 md:p-6"><div className="flex flex-col gap-5 md:flex-row md:items-start">
            <div className="flex min-w-0 flex-1 items-start gap-4">
                <div className="rounded-lg border bg-card p-2"><UserRound className="size-5"/></div>
                <div className="min-w-0"><h1 className="text-xl font-semibold">{student.full_name}</h1><p className="mt-1 text-sm text-muted-foreground">Student UID: {student.student_uid??'Pending'} · University Roll: {student.university_roll_no??'Pending'}</p>
                    {currentEnrollment&&<div className="mt-4 space-y-3"><div className="flex flex-wrap items-center gap-2"><Badge>{currentEnrollment.programme??'Programme'}</Badge><Badge variant="secondary">{currentEnrollment.session??'Session'}</Badge>{currentEnrollment.discipline&&<Badge variant="outline">Discipline: {currentEnrollment.discipline}</Badge>}<Badge variant="outline">Class Roll: {currentEnrollment.class_roll_no??'Pending'}</Badge></div>
                    {currentEnrollment.academic_choices?.length>0&&<div className="grid gap-2 text-sm sm:grid-cols-2">{currentEnrollment.academic_choices.map((choice:any)=><div key={choice.category}><span className="text-muted-foreground">{choice.category}:</span> <strong>{choice.values.join(', ')}</strong></div>)}</div>}</div>}
                </div>
            </div>
            {photo&&<div className="w-full md:w-56"><div className="aspect-square overflow-hidden rounded-2xl border bg-muted/20">{photo.file_url?<img src={photo.file_url} alt={`${student.full_name} profile`} className="h-full w-full object-cover"/>:<div className="grid h-full place-items-center text-muted-foreground"><UserRound className="size-16"/></div>}</div>{can.edit&&<Form action={`/college/${college.id}/student-profiles/${student.id}/profile-photo`} method="post" className="mt-2">{({processing,errors})=><><Label htmlFor="profile_photo" className="sr-only">Update profile photo</Label><Input id="profile_photo" name="profile_photo" type="file" accept="image/*" disabled={processing}/><Button type="submit" size="sm" variant="outline" className="mt-2 w-full" disabled={processing}>{processing?<Spinner/>:<Camera className="size-4"/>}{photo.file_url?'Update Photo':'Add Photo'}</Button>{errors.profile_photo&&<p className="mt-1 text-xs text-destructive">{errors.profile_photo}</p>}</>}</Form>}</div>}
        </div></CardContent></Card>
        <Form action={`/college/${college.id}/student-profiles/${student.id}`} method="patch">{({processing,errors})=><div className="space-y-5">
            <Card><CardHeader><CardTitle>Core Student Profile</CardTitle></CardHeader><CardContent className="grid gap-4 md:grid-cols-2"><div><Label>Full Name *</Label><Input name="full_name" defaultValue={student.full_name} disabled={!can.edit||processing} className="mt-2"/><p className="text-xs text-destructive">{errors.full_name}</p></div><div><Label>Date of Birth</Label><div className="mt-2"><DatePicker id="student-date-of-birth" name="date_of_birth" defaultValue={student.date_of_birth??''} max={new Date().toISOString().slice(0,10)} disabled={!can.edit||processing} invalid={Boolean(errors.date_of_birth)}/></div>{errors.date_of_birth&&<p className="mt-1 text-xs text-destructive">{errors.date_of_birth}</p>}</div><div><Label>Email</Label><Input name="email" type="email" defaultValue={student.email??''} disabled={!can.edit||processing} className="mt-2"/></div><div><Label>Phone</Label><Input name="phone" defaultValue={student.phone??''} disabled={!can.edit||processing} className="mt-2"/></div><div><Label>Student UID</Label><Input value={student.student_uid??'Pending'} disabled className="mt-2 font-mono"/><p className="mt-1 text-xs text-muted-foreground">Managed by Student Identity.</p></div><div><Label>University Roll No.</Label><Input value={student.university_roll_no??'Pending'} disabled className="mt-2 font-mono"/><p className="mt-1 text-xs text-muted-foreground">Managed by Student Identity.</p></div></CardContent></Card>
            <Card><CardHeader><CardTitle>Student Profile Fields</CardTitle></CardHeader><CardContent>{normalProfiles.length?<div className="grid gap-4 md:grid-cols-2">{normalProfiles.map((p:any)=><Field key={p.key} p={p} disabled={!can.edit||processing}/>)}</div>:<p className="text-sm text-muted-foreground">No governed STUDENT_PROFILE values are stored for this student.</p>}</CardContent></Card>
            {can.edit&&<Button type="submit" disabled={processing}>{processing?<Spinner/>:<Save className="size-4"/>}Save Student Profile</Button>}
        </div>}</Form>
    </div></>;
}
