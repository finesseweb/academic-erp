import { Form, Head, Link } from '@inertiajs/react';
import { BookOpenCheck, GraduationCap, KeyRound, LogIn, Sparkles, UserPlus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card,CardContent,CardHeader,CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Props={portal:{slug:string;registration_enabled:boolean;email_verification_required:boolean;captcha_required:boolean;captcha?:{question:string}|null}};

export default function ApplicantGateway({portal}:Props){
 return <><Head title="Applicant Portal"/><main className="relative min-h-screen overflow-hidden bg-background p-4 py-8 md:py-12">
  <div aria-hidden className="pointer-events-none absolute inset-0 overflow-hidden">
   <div className="absolute -left-24 -top-24 size-72 rounded-full bg-primary/10 blur-3xl"/>
   <div className="absolute -right-20 top-28 size-80 rounded-full bg-accent/40 blur-3xl"/>
   <div className="absolute bottom-0 left-1/3 size-64 rounded-full bg-secondary/50 blur-3xl"/>
   <Sparkles className="absolute left-[8%] top-[18%] size-8 rotate-12 text-primary/25"/>
   <GraduationCap className="absolute right-[10%] top-[12%] size-12 -rotate-12 text-primary/20"/>
   <BookOpenCheck className="absolute bottom-[15%] left-[10%] size-10 rotate-6 text-primary/20"/>
  </div>
  <div className="relative mx-auto max-w-6xl space-y-7">
   <header className="mx-auto max-w-3xl text-center">
    <div className="mx-auto flex size-14 items-center justify-center rounded-2xl border bg-card shadow-sm"><GraduationCap className="size-7 text-primary"/></div>
    <p className="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-primary">Admissions</p>
    <h1 className="mt-2 text-3xl font-bold tracking-tight md:text-4xl">Applicant Portal</h1>
    <p className="mx-auto mt-3 max-w-2xl text-sm leading-6 text-muted-foreground">Create your applicant account, complete your admission form and track the same identity through the admission journey.</p>
   </header>

   <div className={`grid gap-6 ${portal.registration_enabled?'lg:grid-cols-2':'mx-auto max-w-xl'}`}>
    {portal.registration_enabled&&<Card className="border-primary/15 bg-card/95 shadow-lg shadow-primary/5 backdrop-blur"><CardHeader className="border-b bg-muted/20"><CardTitle className="flex items-center gap-2"><span className="flex size-9 items-center justify-center rounded-xl bg-primary/10"><UserPlus className="size-5 text-primary"/></span>New Applicant Registration</CardTitle></CardHeader><CardContent className="pt-6"><Form action={`/apply/${portal.slug}/register`} method="post">{({processing,errors})=><div className="space-y-4">
      <div><Label>Full Name *</Label><Input name="name" required placeholder="Enter your full name"/><p className="text-xs text-destructive">{errors.name}</p></div>
      <div><Label>Date of Birth *</Label><DatePicker id="applicant-date-of-birth" name="date_of_birth" max={new Date().toISOString().slice(0,10)} invalid={Boolean(errors.date_of_birth)}/><p className="text-xs text-destructive">{errors.date_of_birth}</p></div>
      <div className="grid gap-4 sm:grid-cols-2"><div><Label>Email *</Label><Input name="email" type="email" required placeholder="name@example.com"/><p className="text-xs text-destructive">{errors.email}</p></div><div><Label>Mobile Number *</Label><Input name="phone" required placeholder="Mobile number"/><p className="text-xs text-destructive">{errors.phone}</p></div></div>
      <div className="grid gap-4 sm:grid-cols-2"><div><Label>Password *</Label><Input name="password" type="password" required autoComplete="new-password"/></div><div><Label>Confirm Password *</Label><Input name="password_confirmation" type="password" required autoComplete="new-password"/><p className="text-xs text-destructive">{errors.password}</p></div></div>
      {portal.captcha_required&&<div className="rounded-xl border bg-muted/20 p-4"><Label>Security Check: {portal.captcha?.question}</Label><Input className="mt-2" name="captcha_answer" required/><p className="mt-1 text-xs text-destructive">{errors.captcha_answer}</p></div>}
      <Button className="w-full" disabled={processing}>{processing&&<Spinner/>}Create Applicant Account</Button>
      {portal.email_verification_required&&<p className="rounded-lg bg-primary/5 px-3 py-2 text-xs text-muted-foreground">Email verification is required by this College. A verification link will be sent after registration.</p>}
     </div>}</Form></CardContent></Card>}

    <Card className="border-primary/15 bg-card/95 shadow-lg shadow-primary/5 backdrop-blur"><CardHeader className="border-b bg-muted/20"><CardTitle className="flex items-center gap-2"><span className="flex size-9 items-center justify-center rounded-xl bg-primary/10"><LogIn className="size-5 text-primary"/></span>Applicant Login</CardTitle></CardHeader><CardContent className="pt-6"><Form action={`/apply/${portal.slug}/login`} method="post">{({processing,errors})=><div className="space-y-4"><div><Label>Registered Email</Label><Input name="email" type="email" required autoComplete="email" placeholder="name@example.com"/><p className="text-xs text-destructive">{errors.email}</p></div><div><Label>Password</Label><Input name="password" type="password" required autoComplete="current-password"/></div><div className="flex justify-end"><Link href="/forgot-password" className="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline"><KeyRound className="size-3.5"/>Forgot password?</Link></div><Button className="w-full" disabled={processing}>{processing&&<Spinner/>}Sign In</Button></div>}</Form></CardContent></Card>
   </div>
  </div>
 </main></>;
}
