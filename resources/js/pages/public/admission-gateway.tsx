import { Form, Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import {
    ArrowRight,
    BadgeCheck,
    BookOpenCheck,
    CalendarDays,
    GraduationCap,
    HelpCircle,
    KeyRound,
    LockKeyhole,
    ShieldCheck,
    Sparkles,
    UserPlus,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Portal = {
    slug: string;
    college_id: number;
    registration_enabled: boolean;
    email_verification_required: boolean;
    captcha_required: boolean;
    captcha?: { question: string } | null;
    institution?: {
        college_name?: string | null;
        college_code?: string | null;
        university_name?: string | null;
    };
    application_context?: {
        program_name?: string | null;
        program_code?: string | null;
        cycle_name?: string | null;
        cycle_code?: string | null;
        session_name?: string | null;
    };
};

type Props = { portal: Portal };
type PortalMode = 'register' | 'login';

function ApplicantPublicGateway({ portal }: Props) {
    const [mode, setMode] = useState<PortalMode>(portal.registration_enabled ? 'register' : 'login');
    const institution = portal.institution ?? {};
    const context = portal.application_context ?? {};
    const collegeName = institution.college_name || 'College Admissions';
    const universityName = institution.university_name || 'Official Admissions Portal';
    const programTitle = context.program_name || 'Admission Application';

    return (
        <>
            <Head title={`${collegeName} · Admissions`} />
            <main className="relative min-h-screen overflow-hidden bg-background text-foreground">
                <div aria-hidden className="pointer-events-none absolute inset-0">
                    <div className="absolute inset-x-0 top-0 h-[30rem] bg-gradient-to-b from-primary/12 via-primary/5 to-transparent" />
                    <div className="absolute -left-40 top-40 size-[34rem] rounded-full bg-primary/10 blur-3xl" />
                    <div className="absolute -right-44 bottom-[-10rem] size-[38rem] rounded-full bg-accent/60 blur-3xl" />
                    <div className="absolute left-[5%] top-[22%] hidden h-40 w-28 rotate-12 rounded-[2.2rem] border border-primary/10 lg:block" />
                    <div className="absolute right-[7%] top-[16%] hidden size-24 -rotate-12 rounded-full border border-primary/10 lg:block" />
                    <Sparkles className="absolute left-[10%] top-[14%] hidden size-8 rotate-12 text-primary/20 xl:block" />
                    <GraduationCap className="absolute right-[10%] top-[13%] hidden size-16 -rotate-12 text-primary/15 xl:block" />
                    <BookOpenCheck className="absolute bottom-[11%] left-[8%] hidden size-12 rotate-6 text-primary/15 xl:block" />
                </div>

                <header className="relative z-20 border-b border-border/60 bg-background/80 backdrop-blur-xl">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                        <div className="flex min-w-0 items-center gap-3">
                            <div className="flex size-11 shrink-0 items-center justify-center rounded-2xl border border-primary/20 bg-primary/10 shadow-sm">
                                <GraduationCap className="size-6 text-primary" />
                            </div>
                            <div className="min-w-0">
                                <div className="truncate text-sm font-semibold sm:text-base">{collegeName}</div>
                                <div className="truncate text-xs text-muted-foreground">{universityName}</div>
                            </div>
                        </div>
                        <div className="hidden items-center gap-2 rounded-full border border-border/70 bg-card/70 px-3 py-2 text-xs text-muted-foreground sm:flex">
                            <HelpCircle className="size-4 text-primary" /> Admissions Help
                        </div>
                    </div>
                </header>

                <div className="relative z-10 mx-auto grid min-h-[calc(100vh-77px)] max-w-7xl items-center gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-14 xl:gap-16">
                    <section className="mx-auto max-w-xl lg:mx-0">
                        <div className="inline-flex items-center gap-2 rounded-full border border-primary/15 bg-primary/5 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-primary">
                            <Sparkles className="size-3.5" /> Admissions {context.session_name || 'Portal'}
                        </div>
                        <h1 className="mt-5 text-4xl font-bold tracking-[-0.035em] sm:text-5xl lg:text-[3.55rem] lg:leading-[1.04]">
                            Your admission journey starts here.
                        </h1>
                        <p className="mt-5 max-w-lg text-base leading-7 text-muted-foreground sm:text-lg">
                            Create one secure applicant account, complete your form at your pace, upload documents and return anytime to continue.
                        </p>

                        <div className="mt-8 overflow-hidden rounded-3xl border border-border/70 bg-card/80 shadow-xl shadow-primary/5 backdrop-blur">
                            <div className="border-b border-border/60 bg-muted/25 px-5 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                Application context
                            </div>
                            <div className="p-5">
                                <div className="flex items-start gap-3">
                                    <div className="mt-0.5 flex size-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10">
                                        <BookOpenCheck className="size-5 text-primary" />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="text-lg font-semibold">{programTitle}</div>
                                        <div className="mt-1 flex flex-wrap gap-2 text-sm text-muted-foreground">
                                            {context.program_code && <span>{context.program_code}</span>}
                                            {context.cycle_name && <span>• {context.cycle_name}</span>}
                                            {context.session_name && <span>• {context.session_name}</span>}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-7 grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                            {[
                                [ShieldCheck, 'Secure account'],
                                [CalendarDays, 'Save & continue'],
                                [BadgeCheck, 'Track one identity'],
                            ].map(([Icon, label]) => {
                                const IconComponent = Icon as typeof ShieldCheck;
                                return (
                                    <div key={String(label)} className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <span className="flex size-8 items-center justify-center rounded-xl bg-primary/8"><IconComponent className="size-4 text-primary" /></span>
                                        <span>{String(label)}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </section>

                    <section className="mx-auto w-full max-w-2xl">
                        <Card className="overflow-hidden rounded-[2rem] border-border/70 bg-card/95 shadow-2xl shadow-primary/10 backdrop-blur-xl">
                            <div className="border-b border-border/60 bg-muted/20 p-2.5">
                                <div className={`grid ${portal.registration_enabled ? 'grid-cols-2' : 'grid-cols-1'} rounded-2xl bg-muted/60 p-1.5`}>
                                    {portal.registration_enabled && (
                                        <button
                                            type="button"
                                            onClick={() => setMode('register')}
                                            className={`flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition ${mode === 'register' ? 'bg-background text-foreground shadow-sm ring-1 ring-border/60' : 'text-muted-foreground hover:text-foreground'}`}
                                        >
                                            <UserPlus className="size-4" /> New Applicant
                                        </button>
                                    )}
                                    <button
                                        type="button"
                                        onClick={() => setMode('login')}
                                        className={`flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition ${mode === 'login' ? 'bg-background text-foreground shadow-sm ring-1 ring-border/60' : 'text-muted-foreground hover:text-foreground'}`}
                                    >
                                        <LockKeyhole className="size-4" /> Existing Applicant
                                    </button>
                                </div>
                            </div>

                            <CardContent className="p-6 sm:p-8">
                                {mode === 'register' && portal.registration_enabled ? (
                                    <>
                                        <div className="mb-6">
                                            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Create your account</p>
                                            <h2 className="mt-1.5 text-2xl font-semibold tracking-tight">Start a new application</h2>
                                            <p className="mt-2 text-sm leading-6 text-muted-foreground">Use an email and mobile number you can access throughout the admission process.</p>
                                        </div>
                                        <Form action={`/apply/${portal.slug}/register`} method="post">
                                            {({ processing, errors }) => (
                                                <div className="space-y-4">
                                                    <div>
                                                        <Label>Full Name *</Label>
                                                        <Input className="mt-1.5 h-11 rounded-xl" name="name" required placeholder="Enter your full name" autoComplete="name" />
                                                        {errors.name && <p className="mt-1 text-xs text-destructive">{errors.name}</p>}
                                                    </div>
                                                    <div>
                                                        <Label>Date of Birth *</Label>
                                                        <div className="mt-1.5"><DatePicker id="applicant-date-of-birth" name="date_of_birth" max={new Date().toISOString().slice(0, 10)} invalid={Boolean(errors.date_of_birth)} /></div>
                                                        {errors.date_of_birth && <p className="mt-1 text-xs text-destructive">{errors.date_of_birth}</p>}
                                                    </div>
                                                    <div className="grid gap-4 sm:grid-cols-2">
                                                        <div>
                                                            <Label>Email *</Label>
                                                            <Input className="mt-1.5 h-11 rounded-xl" name="email" type="email" required placeholder="name@example.com" autoComplete="email" />
                                                            {errors.email && <p className="mt-1 text-xs text-destructive">{errors.email}</p>}
                                                        </div>
                                                        <div>
                                                            <Label>Mobile Number *</Label>
                                                            <Input className="mt-1.5 h-11 rounded-xl" name="phone" required placeholder="Mobile number" autoComplete="tel" />
                                                            {errors.phone && <p className="mt-1 text-xs text-destructive">{errors.phone}</p>}
                                                        </div>
                                                    </div>
                                                    <div className="grid gap-4 sm:grid-cols-2">
                                                        <div><Label>Password *</Label><Input className="mt-1.5 h-11 rounded-xl" name="password" type="password" required autoComplete="new-password" placeholder="Minimum 8 characters" /></div>
                                                        <div><Label>Confirm Password *</Label><Input className="mt-1.5 h-11 rounded-xl" name="password_confirmation" type="password" required autoComplete="new-password" placeholder="Repeat password" />{errors.password && <p className="mt-1 text-xs text-destructive">{errors.password}</p>}</div>
                                                    </div>
                                                    {portal.captcha_required && (
                                                        <div className="rounded-2xl border bg-muted/25 p-4">
                                                            <Label>Security Check: {portal.captcha?.question}</Label>
                                                            <Input className="mt-2 h-11 rounded-xl bg-background" name="captcha_answer" required />
                                                            {errors.captcha_answer && <p className="mt-1 text-xs text-destructive">{errors.captcha_answer}</p>}
                                                        </div>
                                                    )}
                                                    <Button size="lg" className="mt-2 h-12 w-full rounded-xl text-sm font-semibold" disabled={processing}>
                                                        {processing && <Spinner />} Create Applicant Account {!processing && <ArrowRight className="size-4" />}
                                                    </Button>
                                                    {portal.email_verification_required && (
                                                        <div className="flex gap-2 rounded-2xl border border-primary/15 bg-primary/5 p-3.5 text-xs leading-5 text-muted-foreground">
                                                            <ShieldCheck className="mt-0.5 size-4 shrink-0 text-primary" /> Email verification is required for this admission. A secure verification link will be sent after registration.
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </Form>
                                    </>
                                ) : (
                                    <>
                                        <div className="mb-6">
                                            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Welcome back</p>
                                            <h2 className="mt-1.5 text-2xl font-semibold tracking-tight">Continue your application</h2>
                                            <p className="mt-2 text-sm leading-6 text-muted-foreground">Sign in with the applicant account created for this admission.</p>
                                        </div>
                                        <Form action={`/apply/${portal.slug}/login`} method="post">
                                            {({ processing, errors }) => (
                                                <div className="space-y-4">
                                                    <div><Label>Registered Email</Label><Input className="mt-1.5 h-11 rounded-xl" name="email" type="email" required autoComplete="email" placeholder="name@example.com" />{errors.email && <p className="mt-1 text-xs text-destructive">{errors.email}</p>}</div>
                                                    <div><Label>Password</Label><Input className="mt-1.5 h-11 rounded-xl" name="password" type="password" required autoComplete="current-password" placeholder="Enter your password" /></div>
                                                    <div className="flex justify-end"><Link href="/forgot-password" className="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"><KeyRound className="size-3.5" /> Forgot password?</Link></div>
                                                    <Button size="lg" className="h-12 w-full rounded-xl text-sm font-semibold" disabled={processing}>{processing && <Spinner />} Sign In {!processing && <ArrowRight className="size-4" />}</Button>
                                                    {portal.registration_enabled && <p className="pt-2 text-center text-sm text-muted-foreground">First time here? <button type="button" onClick={() => setMode('register')} className="font-medium text-primary hover:underline">Create an applicant account</button></p>}
                                                </div>
                                            )}
                                        </Form>
                                    </>
                                )}
                            </CardContent>
                        </Card>
                        <div className="mt-5 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-xs text-muted-foreground">
                            <span>Official portal of {collegeName}</span><span className="hidden sm:inline">•</span><span>Secure applicant access</span>
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}

// Critical boundary: this page must never inherit the internal ERP AppLayout.
ApplicantPublicGateway.layout = (page: ReactNode) => page;

export default ApplicantPublicGateway;
