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
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    description: string | null;
    status: 'ACTIVE' | 'INACTIVE';
    permissions_count: number;
    users_count: number;
};

type Props = {
    college: { id: number; name: string; code: string };
    roles: {
        data: Role[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { search?: string; status?: string };
    summary: { total: number; active: number; inactive: number };
    can: {
        create: boolean;
        update: boolean;
        disable: boolean;
        managePermissions: boolean;
    };
};

function CreateRoleDialog({ collegeId }: { collegeId: number }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button>
                    <Plus />
                    Create role
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Create College role</DialogTitle>
                    <DialogDescription>
                        Create a reusable permission role owned by this College.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    action={`/college/${collegeId}/roles`}
                    method="post"
                    disableWhileProcessing
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Role name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    aria-invalid={Boolean(errors.name)}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="code">Role code</Label>
                                <Input
                                    id="code"
                                    name="code"
                                    required
                                    aria-invalid={Boolean(errors.code)}
                                />
                                <InputError message={errors.code} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Input
                                    id="description"
                                    name="description"
                                    aria-invalid={Boolean(errors.description)}
                                />
                                <InputError message={errors.description} />
                            </div>
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing ? <Spinner /> : <Plus />}
                                    {processing ? 'Creating...' : 'Create role'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function StatusAction({
    collegeId,
    role,
}: {
    collegeId: number;
    role: Role;
}) {
    const next = role.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    size="sm"
                    variant="ghost"
                    className={
                        next === 'INACTIVE' ? 'text-destructive' : 'text-primary'
                    }
                >
                    {next === 'INACTIVE' ? <XCircle /> : <CheckCircle2 />}
                    {next === 'INACTIVE' ? 'Deactivate' : 'Activate'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {next === 'INACTIVE' ? 'Deactivate' : 'Activate'} {role.name}?
                </DialogTitle>
                <DialogDescription>
                    {next === 'INACTIVE'
                        ? 'Assignments remain recorded, but this role will stop granting College access.'
                        : 'Active assignments may grant the configured College permissions again.'}
                </DialogDescription>
                <Form
                    action={`/college/${collegeId}/roles/${role.id}/status`}
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

export default function CollegeRolesIndex({
    college,
    roles,
    filters,
    summary,
    can,
}: Props) {
    const filtered = Boolean(filters.search || filters.status);
    const summaryCards: { value: number; text: string; Icon: LucideIcon }[] = [
        { value: summary.total, text: 'Total roles', Icon: Shield },
        { value: summary.active, text: 'Active roles', Icon: ShieldCheck },
        { value: summary.inactive, text: 'Inactive roles', Icon: ShieldOff },
    ];

    return (
        <>
            <Head title={`${college.name} Roles`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {college.code} · Access & Security
                        </p>
                        <h1 className="text-2xl font-semibold sm:text-3xl">
                            College Roles
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage roles owned by {college.name}. College-owned
                            roles cannot cross College boundaries.
                        </p>
                    </div>
                    {can.create ? <CreateRoleDialog collegeId={college.id} /> : null}
                </header>

                <div className="grid gap-4 sm:grid-cols-3">
                    {summaryCards.map(({ value, text, Icon }) => (
                        <Card key={text}>
                            <CardContent className="flex items-center gap-3 pt-6">
                                <Icon className="size-8 text-primary" />
                                <div>
                                    <p className="text-2xl font-semibold">{value}</p>
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
                            action={`/college/${college.id}/roles`}
                            method="get"
                            className="grid gap-3 md:grid-cols-[1fr_190px_auto]"
                        >
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search ?? ''}
                                    placeholder="Search role name, code or description"
                                    className="pl-9"
                                />
                            </div>
                            <Select
                                name="status"
                                defaultValue={filters.status || 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All statuses</SelectItem>
                                    <SelectItem value="ACTIVE">Active</SelectItem>
                                    <SelectItem value="INACTIVE">Inactive</SelectItem>
                                </SelectContent>
                            </Select>
                            <div className="flex gap-2">
                                <Button type="submit">Filter</Button>
                                <Button variant="outline" asChild>
                                    <Link href={`/college/${college.id}/roles`}>
                                        Reset
                                    </Link>
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
                                        <th className="px-4 py-3">Description</th>
                                        <th className="px-4 py-3">Permissions</th>
                                        <th className="px-4 py-3">Assigned users</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {roles.data.map((role) => (
                                        <tr
                                            key={role.id}
                                            className="border-b transition-colors last:border-0 hover:bg-muted/40"
                                        >
                                            <td className="px-4 py-4">
                                                <p className="font-medium">
                                                    {role.name}
                                                </p>
                                                <p className="font-mono text-xs text-muted-foreground">
                                                    {role.code}
                                                </p>
                                            </td>
                                            <td className="max-w-md px-4 py-4 text-muted-foreground">
                                                {role.description || '—'}
                                            </td>
                                            <td className="px-4 py-4">
                                                {role.permissions_count}
                                            </td>
                                            <td className="px-4 py-4">
                                                {role.users_count}
                                            </td>
                                            <td className="px-4 py-4">
                                                <span
                                                    className={`rounded-full px-2.5 py-1 text-xs ${
                                                        role.status === 'ACTIVE'
                                                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                            : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {role.status === 'ACTIVE'
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
                                                                href={`/college/${college.id}/roles/${role.id}/edit`}
                                                            >
                                                                <Edit3 /> Edit
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {can.managePermissions ? (
                                                        <Button
                                                            size="sm"
                                                            variant="ghost"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/college/${college.id}/roles/${role.id}/permissions`}
                                                            >
                                                                <KeyRound /> Permissions
                                                            </Link>
                                                        </Button>
                                                    ) : null}
                                                    {(role.status === 'ACTIVE'
                                                        ? can.disable
                                                        : can.update) ? (
                                                        <StatusAction
                                                            collegeId={college.id}
                                                            role={role}
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
                                        ? 'No matching College roles'
                                        : 'No College roles found'}
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {filtered
                                        ? 'Adjust or reset the filters.'
                                        : 'Create the first College-owned role.'}
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
                                {roles.links.map((link, index) =>
                                    link.url ? (
                                        <Button
                                            key={index}
                                            size="sm"
                                            variant={
                                                link.active ? 'default' : 'outline'
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

CollegeRolesIndex.layout = {
    breadcrumbs: [
        { title: 'Access & Security', href: '/college' },
        { title: 'College Roles', href: '#' },
    ],
};
