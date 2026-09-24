import { Form, Head } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
export default function ChangePassword({email}:{email:string}){
 return <><Head title="Change Temporary Password"/><main className="flex min-h-screen items-center justify-center bg-muted/20 p-6"><Card className="w-full max-w-md"><CardHeader><KeyRound className="mb-2 size-8 text-primary"/><CardTitle>Change your temporary password</CardTitle><CardDescription>{email}<br/>For security, choose your own password before entering the Student Portal.</CardDescription></CardHeader><CardContent><Form action="/student/change-password" method="put">{({processing,errors})=><div className="space-y-4"><div><Label htmlFor="current_password">Temporary password</Label><Input id="current_password" name="current_password" type="password" autoComplete="current-password" required/>{errors.current_password&&<p className="mt-1 text-sm text-destructive">{errors.current_password}</p>}</div><div><Label htmlFor="password">New password</Label><Input id="password" name="password" type="password" autoComplete="new-password" required/>{errors.password&&<p className="mt-1 text-sm text-destructive">{errors.password}</p>}</div><div><Label htmlFor="password_confirmation">Confirm new password</Label><Input id="password_confirmation" name="password_confirmation" type="password" autoComplete="new-password" required/></div><Button className="w-full" type="submit" disabled={processing}>{processing&&<Spinner/>}Change Password & Continue</Button></div>}</Form></CardContent></Card></main></>;
}
