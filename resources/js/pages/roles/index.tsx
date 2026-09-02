import { Form, Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    Edit3,
    KeyRound,
    Plus,
    Search,
    Shield,
    ShieldCheck,
    ShieldOff,
    XCircle,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
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
type Role = {
    id: number;
    name: string;
    code: string;
    is_system_role: boolean;
    owner_scope_type: string;
    owner_scope_reference: string;
    owner_college_id: number | null;
    owner_college: { id: number; name: string; code: string } | null;
    status: 'ACTIVE' | 'INACTIVE';
    permissions_count: number;
    users_count: number;
};
type P = {
    roles: {
        data: Role[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
        type?: string;
        college_id?: number | string;
    };
    colleges: {
        id: number;
        name: string;
        code: string;
        status: string;
    }[];
    summary: { total: number; system: number; custom: number };
    can: {
        create: boolean;
        update: boolean;
        changeStatus: boolean;
        viewPermissions: boolean;
    };
};
function StatusAction({ role }: { role: Role }) {
    const next = role.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

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
                    {role.name}?
                </DialogTitle>
                <DialogDescription>
                    {next === 'INACTIVE'
                        ? 'Assignments remain recorded but this role will stop granting access.'
                        : 'Active assignments may grant the configured permissions again.'}
                </DialogDescription>
                <Form
                    action={`/admin/roles/${role.id}/status`}
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
                                    disabled={processing}
                                    variant={
                                        next === 'INACTIVE'
                                            ? 'destructive'
                                            : 'default'
                                    }
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
export default function RolesIndex({ roles, filters, colleges, summary, can }: P) {
    const filtered = Boolean(
        filters.search || filters.status || filters.type || filters.college_id,
    );
    const summaryCards: { value: number; text: string; Icon: LucideIcon }[] = [
        { value: summary.total, text: 'Total roles', Icon: Shield },
        { value: summary.system, text: 'System roles', Icon: ShieldCheck },
        { value: summary.custom, text: 'Custom roles', Icon: ShieldOff },
    ];

    return (
        <>
            <Head title="Roles" />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            Access & Security
                        </p>
                        <h1 className="text-2xl font-semibold sm:text-3xl">
                            Roles
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage reusable permission-bundle identities and
                            lifecycle.
                        </p>
                    </div>
                    {can.create ? (
                        <Button asChild>
                            <Link href="/admin/roles/create">
                                <Plus />
                                Create role
                            </Link>
                        </Button>
                    ) : null}
                </header>
                <div className="grid gap-4 sm:grid-cols-3">
                    {summaryCards.map(({ value, text, Icon }) => (
                        <Card key={text}>
                            <CardContent className="flex items-center gap-3 pt-6">
                                <Icon className="size-8 text-primary" />
                                <div>
                                    <p className="text-2xl font-semibold">
                                        {value}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {text}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
                <Card>
                    <CardContent className="pt-6">
                        <Form
                            action="/admin/roles"
                            method="get"
                            className="grid gap-3 xl:grid-cols-[1fr_190px_230px_180px_auto]"
                        >
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search ?? ''}
                                    placeholder="Search role name or code"
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                name="type"
                                defaultValue={filters.type ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All role types
                                    </SelectItem>
                                    <SelectItem value="SYSTEM">
                                        System
                                    </SelectItem>
                                    <SelectItem value="CUSTOM">
                                        Custom
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Select
                                name="college_id"
                                defaultValue={
                                    filters.college_id
                                        ? String(filters.college_id)
                                        : 'all'
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="All colleges" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All colleges
                                    </SelectItem>
                                    {colleges.map((college) => (
                                        <SelectItem
                                            key={college.id}
                                            value={String(college.id)}
                                        >
                                            {college.name} ({college.code})
                                            {college.status !== 'ACTIVE'
                                                ? ' — inactive'
                                                : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                name="status"
                                defaultValue={filters.status ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
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
                                <Button variant="outline" asChild>
                                    <Link href="/admin/roles">Reset</Link>
                                </Button>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        {roles.data.length ? (
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3">Role</th>
                                        <th className="px-4 py-3">Type</th>
                                        <th className="px-4 py-3">
                                            Owner / Institution
                                        </th>
                                        <th className="px-4 py-3">
                                            Permissions
                                        </th>
                                        <th className="px-4 py-3">
                                            Assigned users
                                        </th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {roles.data.map((r) => (
                                        <tr
                                            key={r.id}
                                            className="border-b transition-colors last:border-0 hover:bg-muted/40"
                                        >
                                            <td className="px-4 py-4">
                                                <p className="font-medium">
                                                    {r.name}
                                                </p>
                                                <p className="font-mono text-xs text-muted-foreground">
                                                    {r.code}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {r.is_system_role
                                                    ? 'System'
                                                    : 'Custom'}
                                                <p className="text-xs text-muted-foreground">
                                                    {r.owner_scope_type}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {r.owner_scope_type ===
                                                'COLLEGE' ? (
                                                    r.owner_college ? (
                                                        <>
                                                            <p className="font-medium">
                                                                {r.owner_college.name}
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                {r.owner_college.code}
                                                            </p>
                                                        </>
                                                    ) : (
                                                        <>
                                                            <p className="font-medium text-destructive">
                                                                College record unavailable
                                                            </p>
                                                            <p className="text-xs text-muted-foreground">
                                                                {r.owner_scope_reference}
                                                            </p>
                                                        </>
                                                    )
                                                ) : r.owner_scope_type ===
                                                  'UNIVERSITY' ? (
                                                    <>
                                                        <p className="font-medium">
                                                            University
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            University-owned role
                                                        </p>
                                                    </>
                                                ) : (
                                                    <>
                                                        <p className="font-medium">
                                                            Global
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            System-wide role
                                                        </p>
                                                    </>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                {r.permissions_count}
                                            </td>
                                            <td className="px-4 py-4">
                                                {r.users_count}
                                            </td>
                                            <td className="px-4 py-4">
                                                <span
                                                    className={`rounded-full px-2.5 py-1 text-xs ${r.status === 'ACTIVE' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground'}`}
                                                >
                                                    {r.status === 'ACTIVE'
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
                                                                href={`/admin/roles/${r.id}/edit`}
                                                            >
                                                                <Edit3 />
                                                                {r.is_system_role
                                                                    ? 'View'
                                                                    : 'Edit'}
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {can.viewPermissions ? (
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/admin/roles/${r.id}/permissions`}
                                                            >
                                                                <KeyRound />{' '}
                                                                Permissions
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {can.changeStatus &&
                                                    !r.is_system_role ? (
                                                        <StatusAction
                                                            role={r}
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
                                <Shield className="size-10 text-muted-foreground" />
                                <h2 className="mt-4 font-semibold">
                                    {filtered
                                        ? 'No matching roles'
                                        : 'No roles found'}
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {filtered
                                        ? 'Adjust or reset the filters.'
                                        : 'Create the first custom role.'}
                                </p>
                            </div>
                        )}
                    </div>
                    {roles.total ? (
                        <div className="flex flex-col gap-3 border-t px-4 py-3 text-sm text-muted-foreground sm:flex-row sm:justify-between">
                            <p>
                                Showing {roles.from}–{roles.to} of {roles.total}
                            </p>
                            <nav className="flex gap-1" aria-label="Pagination">
                                {roles.links.map((x, i) =>
                                    x.url ? (
                                        <Button
                                            key={i}
                                            size="sm"
                                            variant={
                                                x.active ? 'default' : 'outline'
                                            }
                                            asChild
                                        >
                                            <Link
                                                href={x.url}
                                                preserveScroll
                                                dangerouslySetInnerHTML={{
                                                    __html: x.label,
                                                }}
                                            />
                                        </Button>
                                    ) : (
                                        <Button
                                            key={i}
                                            size="sm"
                                            variant="outline"
                                            disabled
                                            dangerouslySetInnerHTML={{
                                                __html: x.label,
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
RolesIndex.layout = {
    breadcrumbs: [
        { title: 'Access & Security', href: '/admin/users' },
        { title: 'Roles', href: '/admin/roles' },
    ],
};
