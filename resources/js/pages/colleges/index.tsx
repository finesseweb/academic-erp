import { Form, Head, Link } from '@inertiajs/react';
import {
    Building2,
    CheckCircle2,
    Edit3,
    Plus,
    Search,
    University,
    XCircle,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type College = {
    id: number;
    name: string;
    code: string;
    affiliation_type: string;
    status: 'ACTIVE' | 'INACTIVE';
    official_email: string | null;
    official_phone: string | null;
    city: string | null;
    principal: { name: string } | null;
    created_at: string;
};
type PageLink = { url: string | null; label: string; active: boolean };
type Props = {
    colleges: {
        data: College[];
        links: PageLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string; status?: string; affiliation_type?: string };
    summary: { total: number; active: number; inactive: number };
    can: { create: boolean; update: boolean; changeStatus: boolean };
};

function StatusAction({ college }: { college: College }) {
    const next = college.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    size="sm"
                    variant="ghost"
                    className={
                        next === 'INACTIVE'
                            ? 'text-destructive'
                            : 'text-primary'
                    }
                >
                    {next === 'INACTIVE' ? <XCircle /> : <CheckCircle2 />}
                    {next === 'INACTIVE' ? 'Deactivate' : 'Activate'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {next === 'INACTIVE' ? 'Deactivate' : 'Activate'}{' '}
                    {college.name}?
                </DialogTitle>
                <DialogDescription>
                    {next === 'INACTIVE'
                        ? 'Users and future operations should no longer treat this College as active. Existing history will be preserved.'
                        : 'This College will become available for active University operations.'}
                </DialogDescription>
                <Form
                    action={`/admin/colleges/${college.id}/status`}
                    method="patch"
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <>
                            <input type="hidden" name="status" value={next} />
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant={
                                        next === 'INACTIVE'
                                            ? 'destructive'
                                            : 'default'
                                    }
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Updating…'
                                        : `Confirm ${next === 'INACTIVE' ? 'deactivation' : 'activation'}`}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function CollegesIndex({
    colleges,
    filters,
    summary,
    can,
}: Props) {
    return (
        <>
            <Head title="Affiliated Colleges" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <p className="flex items-center gap-2 text-sm font-medium text-primary">
                            <University className="size-4" />
                            University administration
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Affiliated Colleges
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Manage College identities governed by the
                            University.
                        </p>
                    </div>
                    {can.create ? (
                        <Button asChild>
                            <Link href="/admin/colleges/create">
                                <Plus />
                                Add College
                            </Link>
                        </Button>
                    ) : null}
                </header>
                <div className="grid gap-4 sm:grid-cols-3">
                    {[
                        [summary.total, 'Total Colleges', Building2],
                        [summary.active, 'Active', CheckCircle2],
                        [summary.inactive, 'Inactive', XCircle],
                    ].map(([value, label, Icon]) => {
                        const I = Icon as typeof Building2;

                        return (
                            <Card key={label as string}>
                                <CardContent className="flex items-center gap-4 p-5">
                                    <div className="rounded-xl bg-primary/10 p-3 text-primary">
                                        <I className="size-5" />
                                    </div>
                                    <div>
                                        <p className="text-2xl font-semibold">
                                            {value as number}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {label as string}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
                <Card>
                    <CardContent className="p-4">
                        <Form
                            action="/admin/colleges"
                            method="get"
                            className="grid gap-3 md:grid-cols-[1fr_220px_220px_auto]"
                        >
                            <div className="relative">
                                <Search className="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search ?? ''}
                                    placeholder="Search name, code, or city"
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                name="status"
                                defaultValue={filters.status ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All statuses
                                    </SelectItem>
                                    <SelectItem value="ACTIVE">
                                        Active
                                    </SelectItem>
                                    <SelectItem value="INACTIVE">
                                        Inactive
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Select
                                name="affiliation_type"
                                defaultValue={filters.affiliation_type ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="All affiliation types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All affiliation types
                                    </SelectItem>
                                    {[
                                        'Constituent',
                                        'Affiliated',
                                        'Autonomous',
                                        'Government',
                                        'Private Aided',
                                        'Private Unaided',
                                        'Other',
                                    ].map((type) => (
                                        <SelectItem key={type} value={type}>
                                            {type}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <div className="flex gap-2">
                                <Button type="submit">Filter</Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/admin/colleges">Reset</Link>
                                </Button>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        {colleges.data.length ? (
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            College
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Affiliation
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Contact
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Principal / Admin
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Status
                                        </th>
                                        <th className="px-4 py-3 text-right font-medium">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {colleges.data.map((college) => (
                                        <tr
                                            key={college.id}
                                            className="border-b transition-colors last:border-0 hover:bg-muted/40"
                                        >
                                            <td className="px-4 py-4">
                                                <p className="font-medium">
                                                    {college.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {college.code}
                                                    {college.city
                                                        ? ` · ${college.city}`
                                                        : ''}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {college.affiliation_type}
                                            </td>
                                            <td className="px-4 py-4">
                                                <p>
                                                    {college.official_email ??
                                                        '—'}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {college.official_phone ??
                                                        'No phone'}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {college.principal?.name ?? (
                                                    <span className="text-muted-foreground">
                                                        Not assigned
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${college.status === 'ACTIVE' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground'}`}
                                                >
                                                    {college.status === 'ACTIVE'
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex justify-end gap-1">
                                                    {can.update ? (
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/admin/colleges/${college.id}/edit`}
                                                            >
                                                                <Edit3 />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {can.changeStatus ? (
                                                        <StatusAction
                                                            college={college}
                                                        />
                                                    ) : null}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <div className="grid place-items-center px-6 py-16 text-center">
                                <div className="rounded-full bg-muted p-4">
                                    <Building2 className="size-7 text-muted-foreground" />
                                </div>
                                <h2 className="mt-4 font-semibold">
                                    {filters.search ||
                                    filters.status ||
                                    filters.affiliation_type
                                        ? 'No matching Colleges'
                                        : 'No affiliated Colleges yet'}
                                </h2>
                                <p className="mt-1 max-w-md text-sm text-muted-foreground">
                                    {filters.search ||
                                    filters.status ||
                                    filters.affiliation_type
                                        ? 'Adjust or reset the filters to see other records.'
                                        : 'Create the first College identity under the University.'}
                                </p>
                                {can.create &&
                                !(
                                    filters.search ||
                                    filters.status ||
                                    filters.affiliation_type
                                ) ? (
                                    <Button className="mt-5" asChild>
                                        <Link href="/admin/colleges/create">
                                            <Plus />
                                            Add College
                                        </Link>
                                    </Button>
                                ) : null}
                            </div>
                        )}
                    </div>
                    {colleges.total > 0 ? (
                        <div className="flex flex-col gap-3 border-t px-4 py-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                            <p>
                                Showing {colleges.from}–{colleges.to} of{' '}
                                {colleges.total}
                            </p>
                            <nav
                                className="flex flex-wrap gap-1"
                                aria-label="Pagination"
                            >
                                {colleges.links.map((link, index) =>
                                    link.url ? (
                                        <Button
                                            key={index}
                                            size="sm"
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            asChild
                                        >
                                            <Link
                                                href={link.url}
                                                preserveScroll
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        </Button>
                                    ) : (
                                        <Button
                                            key={index}
                                            size="sm"
                                            variant="outline"
                                            disabled
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ),
                                )}
                            </nav>
                        </div>
                    ) : null}
                </Card>
            </div>
        </>
    );
}
CollegesIndex.layout = {
    breadcrumbs: [
        { title: 'University', href: '/admin/university' },
        { title: 'Affiliated Colleges', href: '/admin/colleges' },
    ],
};
