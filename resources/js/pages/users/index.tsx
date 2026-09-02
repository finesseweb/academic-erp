import { Form, Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    Edit3,
    KeyRound,
    Plus,
    Search,
    ShieldCheck,
    UserRoundCheck,
    UsersRound,
    UserX,
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
import { accountTypes } from './user-form';
type User = {
    id: number;
    name: string;
    email: string;
    mobile: string | null;
    account_type: string;
    status: 'ACTIVE' | 'INACTIVE';
    last_login_at: string | null;
    created_at: string;
    primary_college: { id: number; name: string; code: string } | null;
    roles: {
        id: number;
        name: string;
        pivot: { scope_type: string; scope_reference: string };
    }[];
};
type P = {
    users: {
        data: User[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    roles: { id: number; name: string }[];
    colleges: { id: number; name: string; code: string }[];
    filters: {
        search?: string;
        status?: string;
        account_type?: string;
        role_id?: number;
        college_id?: number;
    };
    summary: { total: number; active: number; inactive: number };
    can: {
        create: boolean;
        update: boolean;
        enable: boolean;
        disable: boolean;
        resetPassword: boolean;
        manageRoles: boolean;
    };
};
const label = (v: string) => accountTypes.find(([x]) => x === v)?.[1] ?? v;
const date = (v: string | null) => (v ? new Date(v).toLocaleString() : 'Never');
function Confirm({ user, kind }: { user: User; kind: 'status' | 'reset' }) {
    const next = user.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
    const reset = kind === 'reset';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    size="sm"
                    variant="ghost"
                    className={
                        !reset && next === 'INACTIVE' ? 'text-destructive' : ''
                    }
                >
                    {reset ? (
                        <KeyRound />
                    ) : next === 'INACTIVE' ? (
                        <XCircle />
                    ) : (
                        <CheckCircle2 />
                    )}
                    {reset
                        ? 'Send reset link'
                        : next === 'INACTIVE'
                          ? 'Disable'
                          : 'Enable'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {reset
                        ? 'Send password reset link?'
                        : `${next === 'INACTIVE' ? 'Disable' : 'Enable'} ${user.name}?`}
                </DialogTitle>
                <DialogDescription>
                    {reset
                        ? `A secure password reset email will be sent to ${user.email}.`
                        : 'The account status and active web sessions will be updated. Protected Super Administrator accounts cannot be disabled.'}
                </DialogDescription>
                <Form
                    action={
                        reset
                            ? `/admin/users/${user.id}/password-reset`
                            : `/admin/users/${user.id}/status`
                    }
                    method={reset ? 'post' : 'patch'}
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
                                        !reset && next === 'INACTIVE'
                                            ? 'destructive'
                                            : 'default'
                                    }
                                >
                                    {processing ? <Spinner /> : null}
                                    {processing ? 'Working...' : 'Confirm'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
export default function UsersIndex({ users, roles, colleges, filters, summary, can }: P) {
    const filtered = Boolean(
        filters.search ||
        filters.status ||
        filters.account_type ||
        filters.role_id ||
        filters.college_id,
    );

    return (
        <>
            <Head title="Users" />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            Access & Security
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Users
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Administer ERP login identities and account
                            lifecycle.
                        </p>
                    </div>
                    {can.create ? (
                        <Button asChild>
                            <Link href="/admin/users/create">
                                <Plus />
                                Create user
                            </Link>
                        </Button>
                    ) : null}
                </header>
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-6">
                            <UsersRound className="size-8 text-primary" />
                            <div>
                                <p className="text-2xl font-semibold">
                                    {summary.total}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Total users
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
                            <UserX className="size-8 text-muted-foreground" />
                            <div>
                                <p className="text-2xl font-semibold">
                                    {summary.inactive}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Inactive
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardContent className="pt-6">
                        <Form
                            action="/admin/users"
                            method="get"
                            className="grid gap-3 xl:grid-cols-[1fr_180px_180px_190px_180px_auto]"
                        >
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search ?? ''}
                                    placeholder="Search name, email, or mobile"
                                    className="pl-9"
                                    aria-label="Search users"
                                />
                            </div>
                            <Select
                                name="account_type"
                                defaultValue={filters.account_type ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All account types
                                    </SelectItem>
                                    {accountTypes.map(([v, l]) => (
                                        <SelectItem key={v} value={v}>
                                            {l}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                name="role_id"
                                defaultValue={String(filters.role_id ?? 'all')}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All roles
                                    </SelectItem>
                                    {roles.map((r) => (
                                        <SelectItem
                                            key={r.id}
                                            value={String(r.id)}
                                        >
                                            {r.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                name="college_id"
                                defaultValue={String(filters.college_id ?? 'all')}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All colleges
                                    </SelectItem>
                                    {colleges.map((college) => (
                                        <SelectItem key={college.id} value={String(college.id)}>
                                            {college.name}
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
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/admin/users">Reset</Link>
                                </Button>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        {users.data.length ? (
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3">User</th>
                                        <th className="px-4 py-3">
                                            Type / scope
                                        </th>
                                        <th className="px-4 py-3">College / Institute</th>
                                        <th className="px-4 py-3">Roles</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">
                                            Last login
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.data.map((u) => (
                                        <tr
                                            key={u.id}
                                            className="border-b transition-colors last:border-0 hover:bg-muted/40"
                                        >
                                            <td className="px-4 py-4">
                                                <p className="font-medium">
                                                    {u.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {u.email}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p>{label(u.account_type)}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {u.primary_college
                                                        ? 'COLLEGE'
                                                        : (u.roles[0]?.pivot.scope_type ?? 'No scope assigned')}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4">
                                                {u.primary_college ? (
                                                    <>
                                                        <p className="font-medium">{u.primary_college.name}</p>
                                                        <p className="text-xs text-muted-foreground">{u.primary_college.code}</p>
                                                    </>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        {u.account_type === 'COLLEGE_STAFF' ? 'Not assigned' : 'University / Global'}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                {u.roles
                                                    .map((r) => r.name)
                                                    .join(', ') || (
                                                    <span className="text-muted-foreground">
                                                        None
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <span
                                                    className={`rounded-full px-2.5 py-1 text-xs ${u.status === 'ACTIVE' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground'}`}
                                                >
                                                    {u.status === 'ACTIVE'
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4 text-muted-foreground">
                                                {date(u.last_login_at)}
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
                                                                href={`/admin/users/${u.id}/edit`}
                                                            >
                                                                <Edit3 />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {can.manageRoles ? (
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/admin/users/${u.id}/roles`}
                                                            >
                                                                <ShieldCheck />{' '}
                                                                Access
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {can.resetPassword ? (
                                                        <Confirm
                                                            user={u}
                                                            kind="reset"
                                                        />
                                                    ) : null}
                                                    {(
                                                        u.status === 'ACTIVE'
                                                            ? can.disable
                                                            : can.enable
                                                    ) ? (
                                                        <Confirm
                                                            user={u}
                                                            kind="status"
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
                                <UsersRound className="size-10 text-muted-foreground" />
                                <h2 className="mt-4 font-semibold">
                                    {filtered
                                        ? 'No matching users'
                                        : 'No user accounts'}
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {filtered
                                        ? 'Adjust or reset the filters.'
                                        : 'Create the first managed ERP account.'}
                                </p>
                            </div>
                        )}
                    </div>
                    {users.total > 0 ? (
                        <div className="flex flex-col gap-3 border-t px-4 py-3 text-sm text-muted-foreground sm:flex-row sm:justify-between">
                            <p>
                                Showing {users.from}–{users.to} of {users.total}
                            </p>
                            <nav className="flex gap-1" aria-label="Pagination">
                                {users.links.map((x, i) =>
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
UsersIndex.layout = {
    breadcrumbs: [
        { title: 'Access & Security', href: '/admin/users' },
        { title: 'Users', href: '/admin/users' },
    ],
};
