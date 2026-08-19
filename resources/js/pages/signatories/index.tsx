import { Form, Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    Edit3,
    Plus,
    Search,
    ShieldCheck,
    UserRoundCheck,
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
import { authorityTypes } from './signatory-form';

type Signatory = {
    id: number;
    full_name: string;
    designation: string;
    authority_type: string;
    email: string | null;
    phone: string | null;
    effective_from: string;
    effective_until: string | null;
    status: 'ACTIVE' | 'INACTIVE';
};
type LinkData = { url: string | null; label: string; active: boolean };
type Props = {
    signatories: {
        data: Signatory[];
        links: LinkData[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string; status?: string; authority_type?: string };
    summary: { total: number; active: number; expiringSoon: number };
    can: { create: boolean; update: boolean; changeStatus: boolean };
};
const authorityLabel = (value: string) =>
    authorityTypes.find(([code]) => code === value)?.[1] ?? value;
const dateLabel = (value: string | null) =>
    value
        ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString(
              undefined,
              { day: '2-digit', month: 'short', year: 'numeric' },
          )
        : 'Open-ended';

function StatusAction({ signatory }: { signatory: Signatory }) {
    const next = signatory.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

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
                    {signatory.full_name}?
                </DialogTitle>
                <DialogDescription>
                    {next === 'INACTIVE'
                        ? 'The appointment will no longer be available for active signing operations. Its history remains preserved.'
                        : 'The appointment will become available for active signing operations.'}
                </DialogDescription>
                <Form
                    action={`/admin/university/signatories/${signatory.id}/status`}
                    method="patch"
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <>
                            <input type="hidden" name="status" value={next} />
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
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
                                    {processing ? <Spinner /> : null}
                                    {processing ? 'Updating...' : 'Confirm'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function SignatoriesIndex({
    signatories,
    filters,
    summary,
    can,
}: Props) {
    const filtered = Boolean(
        filters.search || filters.status || filters.authority_type,
    );

    return (
        <>
            <Head title="Authorized Signatories" />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            University foundation
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Authorized Signatories
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage governed signing appointments and effective
                            periods.
                        </p>
                    </div>
                    {can.create ? (
                        <Button asChild>
                            <Link href="/admin/university/signatories/create">
                                <Plus />
                                Add signatory
                            </Link>
                        </Button>
                    ) : null}
                </header>
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-6">
                            <ShieldCheck className="size-8 text-primary" />
                            <div>
                                <p className="text-2xl font-semibold">
                                    {summary.total}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Total appointments
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-6">
                            <UserRoundCheck className="size-8 text-emerald-600" />
                            <div>
                                <p className="text-2xl font-semibold">
                                    {summary.active}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Active
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-6">
                            <AlertTriangle className="size-8 text-amber-600" />
                            <div>
                                <p className="text-2xl font-semibold">
                                    {summary.expiringSoon}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Expiring in 30 days
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardContent className="pt-6">
                        <Form
                            action="/admin/university/signatories"
                            method="get"
                            className="grid gap-3 lg:grid-cols-[1fr_220px_180px_auto]"
                        >
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search ?? ''}
                                    placeholder="Search name, designation, or email"
                                    className="pl-9"
                                    aria-label="Search signatories"
                                />
                            </div>
                            <Select
                                name="authority_type"
                                defaultValue={filters.authority_type ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="All authority types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All authority types
                                    </SelectItem>
                                    {authorityTypes.map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
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
                            <div className="flex gap-2">
                                <Button type="submit">Filter</Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/admin/university/signatories">
                                        Reset
                                    </Link>
                                </Button>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        {signatories.data.length ? (
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Signatory
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Authority
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Effective period
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
                                    {signatories.data.map((item) => (
                                        <tr
                                            key={item.id}
                                            className="border-b transition-colors last:border-0 hover:bg-muted/40"
                                        >
                                            <td className="px-4 py-4">
                                                <p className="font-medium">
                                                    {item.full_name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {item.designation}
                                                    {item.email
                                                        ? ` · ${item.email}`
                                                        : ''}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {authorityLabel(
                                                    item.authority_type,
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <span>
                                                    {dateLabel(
                                                        item.effective_from,
                                                    )}
                                                </span>
                                                <span className="text-muted-foreground">
                                                    {' '}
                                                    –{' '}
                                                    {dateLabel(
                                                        item.effective_until,
                                                    )}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4">
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${item.status === 'ACTIVE' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground'}`}
                                                >
                                                    {item.status === 'ACTIVE'
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
                                                                href={`/admin/university/signatories/${item.id}/edit`}
                                                            >
                                                                <Edit3 />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {can.changeStatus ? (
                                                        <StatusAction
                                                            signatory={item}
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
                                    <ShieldCheck className="size-7 text-muted-foreground" />
                                </div>
                                <h2 className="mt-4 font-semibold">
                                    {filtered
                                        ? 'No matching signatories'
                                        : 'No authorized signatories yet'}
                                </h2>
                                <p className="mt-1 max-w-md text-sm text-muted-foreground">
                                    {filtered
                                        ? 'Adjust or reset the filters to see other appointments.'
                                        : 'Create the first governed signing appointment for the University.'}
                                </p>
                                {can.create && !filtered ? (
                                    <Button className="mt-5" asChild>
                                        <Link href="/admin/university/signatories/create">
                                            <Plus />
                                            Add signatory
                                        </Link>
                                    </Button>
                                ) : null}
                            </div>
                        )}
                    </div>
                    {signatories.total > 0 ? (
                        <div className="flex flex-col gap-3 border-t px-4 py-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                            <p>
                                Showing {signatories.from}–{signatories.to} of{' '}
                                {signatories.total}
                            </p>
                            <nav
                                className="flex flex-wrap gap-1"
                                aria-label="Pagination"
                            >
                                {signatories.links.map((link, index) =>
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
SignatoriesIndex.layout = {
    breadcrumbs: [
        { title: 'University', href: '/admin/university' },
        {
            title: 'Authorized Signatories',
            href: '/admin/university/signatories',
        },
    ],
};
