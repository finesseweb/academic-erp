import { Form, Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Eye, KeyRound, Pencil, Plus, Power, Search, Shield, UserRound, UsersRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';

type StaffUser = { id:number; name:string; email:string; mobile:string|null; status:string; roles:{id:number;name:string;code:string}[] };
type StudentUser = { id:number; name:string; email:string; mobile:string|null; status:string; student:{id:number;student_uid:string|null;university_roll_no:string|null;class_roll_no:string|null;source_type:string} };
type PageLink = { url:string|null; label:string; active:boolean };
type Paginated<T> = { data:T[]; links?:PageLink[] };
type SessionOption = { id:number; name:string; code:string; is_current:boolean };
type FilterOption = { id:number; name:string; code:string|null };

export default function Index({ college, users, sessions = [], offerings = [], disciplines = [], filters, can }: {
    college:{id:number;name:string;code:string};
    users:Paginated<StaffUser|StudentUser>;
    sessions?:SessionOption[]; offerings?:FilterOption[]; disciplines?:FilterOption[];
    filters:{search?:string;type?:'staff'|'students';session_id?:number;offering_id?:number;discipline_id?:number};
    can:{create:boolean;update:boolean;disable:boolean;enable:boolean;resetPassword:boolean;manageRoles:boolean;viewStudentProfile:boolean};
}) {
    const type = filters.type ?? 'staff';
    const students = type === 'students';
    return <>
        <Head title={`${college.name} Users`} />
        <div className="space-y-6 p-4 md:p-6">
            <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5">
                <div>
                    <p className="text-sm font-medium text-primary">{college.code} · College Administration</p>
                    <h1 className="text-3xl font-semibold">College Users</h1>
                    <p className="text-muted-foreground">{students ? `Student login accounts linked to ${college.name}.` : `Staff linked to ${college.name}.`}</p>
                </div>
                {!students && can.create && <Button asChild><Link href={`/college/${college.id}/users/create`}><Plus />Create user</Link></Button>}
            </header>

            <div className="inline-flex rounded-lg border bg-muted/30 p-1">
                <Button variant={!students ? 'secondary' : 'ghost'} size="sm" asChild><Link href={`/college/${college.id}/users?type=staff`}><UsersRound />College Staff</Link></Button>
                <Button variant={students ? 'secondary' : 'ghost'} size="sm" asChild><Link href={`/college/${college.id}/users?type=students`}><UserRound />Students</Link></Button>
            </div>

            <Card><CardContent className="pt-6">
                <Form action={`/college/${college.id}/users`} method="get" className={students ? 'grid gap-3 lg:grid-cols-[minmax(170px,.8fr)_minmax(220px,1.2fr)_minmax(180px,1fr)_minmax(260px,1.5fr)_auto]' : 'flex gap-2'}>
                    <input type="hidden" name="type" value={type} />
                    {students && <>
                        <Select name="session_id" defaultValue={String(filters.session_id ?? '')}>
                            <SelectTrigger><SelectValue placeholder="Session" /></SelectTrigger>
                            <SelectContent>{sessions.map(s=><SelectItem key={s.id} value={String(s.id)}>{s.name}{s.is_current?' · Current':''}</SelectItem>)}</SelectContent>
                        </Select>
                        <Select name="offering_id" defaultValue={String(filters.offering_id ?? 0)}>
                            <SelectTrigger><SelectValue placeholder="Programme Offering" /></SelectTrigger>
                            <SelectContent><SelectItem value="0">All Programme Offerings</SelectItem>{offerings.map(o=><SelectItem key={o.id} value={String(o.id)}>{o.name}{o.code?` · ${o.code}`:''}</SelectItem>)}</SelectContent>
                        </Select>
                        <Select name="discipline_id" defaultValue={String(filters.discipline_id ?? 0)}>
                            <SelectTrigger><SelectValue placeholder="Discipline" /></SelectTrigger>
                            <SelectContent><SelectItem value="0">All Disciplines</SelectItem>{disciplines.map(d=><SelectItem key={d.id} value={String(d.id)}>{d.name}{d.code?` · ${d.code}`:''}</SelectItem>)}</SelectContent>
                        </Select>
                    </>}
                    <div className="relative flex-1"><Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" /><Input name="search" defaultValue={filters.search ?? ''} className="pl-9" placeholder={students ? 'Search name, email, Student UID or roll number' : 'Search College staff'} /></div>
                    <Button>Search</Button>
                </Form>
            </CardContent></Card>

            <Card><CardContent className="p-0">
                {users.data.length ? <div className="overflow-x-auto"><table className="w-full text-sm">
                    <thead className="bg-muted/60 text-left"><tr><th className="p-4">Name</th><th className="p-4">Contact</th>{students ? <><th className="p-4">Student UID</th><th className="p-4">University Roll</th><th className="p-4">Class Roll</th></> : <th className="p-4">Role</th>}<th className="p-4">Status</th><th className="p-4 text-right">Actions</th></tr></thead>
                    <tbody>{users.data.map((raw) => {
                        if (students) {
                            const u=raw as StudentUser;
                            return <tr key={`${u.id}-${u.student.id}`} className="border-t"><td className="p-4 font-medium">{u.name}</td><td className="p-4">{u.email}<span className="block text-xs text-muted-foreground">{u.mobile}</span></td><td className="p-4">{u.student.student_uid || 'Pending'}</td><td className="p-4">{u.student.university_roll_no || 'Pending'}</td><td className="p-4">{u.student.class_roll_no || 'Pending'}</td><td className="p-4"><StatusBadge status={u.status} /></td><td className="p-4"><div className="flex justify-end gap-1">{can.viewStudentProfile && <Button size="icon" variant="ghost" asChild aria-label={`View ${u.name} profile`}><Link href={`/college/${college.id}/student-profiles/${u.student.id}`}><Eye /></Link></Button>}{can.resetPassword && <ActionDialog title={`Send reset link to ${u.name}?`} description="A secure password reset email will be sent to this student login account." action={`/college/${college.id}/student-accounts/${u.student.id}/password-reset`} method="post" label="Send reset link" icon={<KeyRound />} />}{((u.status==='ACTIVE'&&can.disable)||(u.status!=='ACTIVE'&&can.enable)) && <ActionDialog title={`${u.status==='ACTIVE'?'Disable':'Enable'} ${u.name} login?`} description={u.status==='ACTIVE'?'This student login account will no longer be able to sign in.':'This student login account will be allowed to sign in again.'} action={`/college/${college.id}/student-accounts/${u.student.id}/status`} method="patch" label={u.status==='ACTIVE'?'Disable':'Enable'} icon={<Power />} fields={{status:u.status==='ACTIVE'?'INACTIVE':'ACTIVE'}} />}</div></td></tr>;
                        }
                        const u=raw as StaffUser;
                        return <tr key={u.id} className="border-t"><td className="p-4 font-medium">{u.name}</td><td className="p-4">{u.email}<span className="block text-xs text-muted-foreground">{u.mobile}</span></td><td className="p-4">{u.roles.length ? u.roles.map(r=>r.name).join(', ') : <span className="text-muted-foreground">No role assigned</span>}</td><td className="p-4"><StatusBadge status={u.status} /></td><td className="p-4"><div className="flex justify-end gap-1">
                            {can.update && <Button size="icon" variant="ghost" asChild aria-label={`Edit ${u.name}`}><Link href={`/college/${college.id}/users/${u.id}/edit`}><Pencil /></Link></Button>}
                            {can.manageRoles && <Button size="icon" variant="ghost" asChild aria-label={`Manage ${u.name} roles`}><Link href={`/college/${college.id}/users/${u.id}/roles`}><Shield /></Link></Button>}
                            {can.resetPassword && <ActionDialog title={`Send reset link to ${u.name}?`} description="A secure password reset email will be sent to this College user." action={`/college/${college.id}/users/${u.id}/password-reset`} method="post" label="Send reset link" icon={<KeyRound />} />}
                            {((u.status==='ACTIVE'&&can.disable)||(u.status!=='ACTIVE'&&can.enable)) && <ActionDialog title={`${u.status==='ACTIVE'?'Disable':'Enable'} ${u.name}?`} description={u.status==='ACTIVE'?'This College staff account will no longer be able to sign in.':'This College staff account will be allowed to sign in again.'} action={`/college/${college.id}/users/${u.id}/status`} method="patch" label={u.status==='ACTIVE'?'Disable':'Enable'} icon={<Power />} fields={{status:u.status==='ACTIVE'?'INACTIVE':'ACTIVE'}} />}
                        </div></td></tr>;
                    })}</tbody>
                </table></div> : <div className="p-10 text-center text-muted-foreground"><UsersRound className="mx-auto mb-3 size-8" /><p>{students ? 'No linked Student login accounts found for this College.' : 'No College staff found.'}</p></div>}
            </CardContent></Card>

            {users.links && users.links.length > 3 && <div className="flex flex-wrap justify-end gap-1">{users.links.map((link,i)=><Button key={i} size="sm" variant={link.active?'default':'outline'} disabled={!link.url} asChild={!!link.url}>{link.url?<Link href={link.url} dangerouslySetInnerHTML={{__html:link.label}} />:<span dangerouslySetInnerHTML={{__html:link.label}} />}</Button>)}</div>}
        </div>
    </>;
}

function StatusBadge({status}:{status:string}) {
    return <span className={`rounded-full px-2.5 py-1 text-xs ${status === 'ACTIVE' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground'}`}>{status === 'ACTIVE' ? 'Active' : 'Inactive'}</span>;
}

function ActionDialog({title,description,action,method,label,icon,fields}:{title:string;description:string;action:string;method:'post'|'patch';label:string;icon:ReactNode;fields?:Record<string,string>}) {
    return <Dialog><DialogTrigger asChild><Button size="icon" variant="ghost" aria-label={label}>{icon}</Button></DialogTrigger><DialogContent><DialogTitle>{title}</DialogTitle><DialogDescription>{description}</DialogDescription><Form action={action} method={method}>{({processing})=><DialogFooter>{fields?Object.entries(fields).map(([name,value])=><input key={name} type="hidden" name={name} value={value}/>):null}<DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose><Button type="submit" disabled={processing}>{processing?<Spinner/>:icon}{processing?'Working...':label}</Button></DialogFooter>}</Form></DialogContent></Dialog>;
}
