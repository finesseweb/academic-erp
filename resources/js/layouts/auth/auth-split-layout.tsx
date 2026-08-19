import { Link, usePage } from '@inertiajs/react';
import {
    BookOpenCheck,
    Building2,
    GraduationCap,
    ShieldCheck,
} from 'lucide-react';
import AppearanceToggleTab from '@/components/appearance-tabs';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

const highlights = [
    { icon: ShieldCheck, label: 'Scope-aware security' },
    { icon: Building2, label: 'University governance' },
    { icon: BookOpenCheck, label: 'Academic clarity' },
];

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <main className="grid min-h-svh bg-background lg:grid-cols-[minmax(0,1.05fr)_minmax(440px,.95fr)]">
            <section className="relative hidden overflow-hidden border-r bg-sidebar p-10 text-sidebar-foreground lg:flex lg:flex-col lg:justify-between xl:p-14">
                <div className="absolute -top-32 -left-24 size-96 rounded-full bg-sidebar-primary/15 blur-3xl" />
                <Link
                    href={home()}
                    className="relative z-20 flex items-center gap-3 text-lg font-semibold tracking-tight"
                >
                    <span className="grid size-11 place-items-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground shadow-lg">
                        <GraduationCap className="size-6" />
                    </span>
                    <span>
                        Academic ERP
                        <span className="block text-xs font-normal text-sidebar-foreground/65">
                            University management platform
                        </span>
                    </span>
                </Link>
                <div className="relative z-10 max-w-xl space-y-7">
                    <div className="space-y-4">
                        <p className="text-sm font-semibold tracking-[.2em] text-sidebar-primary uppercase">
                            Secure academic operations
                        </p>
                        <h2 className="text-4xl leading-tight font-semibold xl:text-5xl">
                            One trusted workspace for your university.
                        </h2>
                        <p className="max-w-lg text-base leading-7 text-sidebar-foreground/70">
                            Manage academic administration with governed access,
                            auditable workflows, and a consistent experience
                            across every affiliated college.
                        </p>
                    </div>
                    <div className="grid gap-3 sm:grid-cols-3">
                        {highlights.map(({ icon: Icon, label }) => (
                            <div
                                key={label}
                                className="rounded-xl border border-sidebar-border bg-sidebar-accent/40 p-4"
                            >
                                <Icon className="mb-3 size-5 text-sidebar-primary" />
                                <p className="text-sm font-medium">{label}</p>
                            </div>
                        ))}
                    </div>
                </div>
                <p className="relative z-10 text-xs text-sidebar-foreground/55">
                    Protected by enterprise authentication and role-based
                    access.
                </p>
            </section>

            <section className="flex min-h-svh flex-col p-5 sm:p-8 lg:p-10">
                <div className="flex items-center justify-between lg:justify-end">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 font-semibold lg:hidden"
                    >
                        <span className="grid size-9 place-items-center rounded-lg bg-primary text-primary-foreground">
                            <GraduationCap className="size-5" />
                        </span>
                        Academic ERP
                    </Link>
                    <AppearanceToggleTab className="max-w-[230px] justify-end [&_button]:px-2 [&_span]:sr-only xl:[&_span]:not-sr-only" />
                </div>
                <div className="flex flex-1 items-center py-10">
                    <div className="mx-auto flex w-full max-w-md flex-col justify-center space-y-7">
                        <Link href={home()} className="sr-only">
                            {name}
                        </Link>
                        <div className="space-y-2 text-left">
                            <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                {title}
                            </h1>
                            <p className="text-sm text-balance text-muted-foreground">
                                {description}
                            </p>
                        </div>
                        <div className="rounded-2xl border bg-card p-6 shadow-sm sm:p-8">
                            {children}
                        </div>
                    </div>
                </div>
                <p className="text-center text-xs text-muted-foreground">
                    © {new Date().getFullYear()} Academic ERP · Authorized
                    access only
                </p>
            </section>
        </main>
    );
}
