import { Form, Head, Link } from '@inertiajs/react';
import {
    Building2,
    CalendarClock,
    ChevronLeft,
    Pencil,
    Plus,
    ShieldCheck,
    Trash2,
    University,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { DatePicker } from '@/components/ui/date-picker';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
type Perm = { id: number; code: string; description?: string };
type Role = {
    id: number;
    name: string;
    code: string;
    description: string | null;
    permissions: Perm[];
    pivot?: {
        id: number;
        scope_type: string;
        scope_reference: string;
        status: string;
        effective_from: string | null;
        effective_until: string | null;
    };
};
type P = {
    managedUser: {
        id: number;
        name: string;
        email: string;
        status: string;
        roles: Role[];
    };
    availableRoles: Role[];
    colleges: { id: number; name: string; code: string }[];
    can: { assign: boolean; unassign: boolean; updateScope: boolean };
};

function ScopeEditor({
    managedUserId,
    role,
    colleges,
}: {
    managedUserId: number;
    role: Role;
    colleges: P['colleges'];
}) {
    const assignment = role.pivot!;
    const [scopeType, setScopeType] = useState(assignment.scope_type);
    const selectedCollegeId = assignment.scope_reference.startsWith('college:')
        ? assignment.scope_reference.replace('college:', '')
        : undefined;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Pencil />
                    Edit scope
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-lg">
                <DialogTitle>Edit {role.name} access</DialogTitle>
                <DialogDescription>
                    Choose exactly where this role applies and when the grant is
                    effective. Changes take effect on the next authorization
                    check.
                </DialogDescription>
                <Form
                    action={`/admin/users/${managedUserId}/roles/${assignment.id}/scope`}
                    method="patch"
                    options={{ preserveScroll: true }}
                    disableWhileProcessing
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label>Authorization scope</Label>
                                <Select
                                    name="scope_type"
                                    value={scopeType}
                                    onValueChange={setScopeType}
                                >
                                    <SelectTrigger
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            errors.scope_type,
                                        )}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="UNIVERSITY">
                                            University-wide
                                        </SelectItem>
                                        <SelectItem value="COLLEGE">
                                            One affiliated College
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.scope_type} />
                            </div>
                            {scopeType === 'COLLEGE' ? (
                                <div className="grid gap-2">
                                    <Label>Affiliated College</Label>
                                    <Select
                                        name="college_id"
                                        defaultValue={selectedCollegeId}
                                    >
                                        <SelectTrigger
                                            className="w-full"
                                            aria-invalid={Boolean(
                                                errors.college_id,
                                            )}
                                        >
                                            <SelectValue placeholder="Select College" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {colleges.map((college) => (
                                                <SelectItem
                                                    key={college.id}
                                                    value={String(college.id)}
                                                >
                                                    {college.name} (
                                                    {college.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.college_id} />
                                </div>
                            ) : null}
                            <div className="grid gap-2">
                                <Label>Assignment status</Label>
                                <Select
                                    name="status"
                                    defaultValue={assignment.status}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ACTIVE">
                                            Active
                                        </SelectItem>
                                        <SelectItem value="INACTIVE">
                                            Inactive
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor={`from-${assignment.id}`}>
                                        Effective from
                                    </Label>
                                    <DatePicker
                                        id={`from-${assignment.id}`}
                                        name="effective_from"
                                        defaultValue={assignment.effective_from}
                                        min="2000-01-01"
                                        max="2100-12-31"
                                        invalid={Boolean(errors.effective_from)}
                                    />
                                    <InputError
                                        message={errors.effective_from}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor={`until-${assignment.id}`}>
                                        Effective until
                                    </Label>
                                    <DatePicker
                                        id={`until-${assignment.id}`}
                                        name="effective_until"
                                        defaultValue={
                                            assignment.effective_until
                                        }
                                        min="2000-01-01"
                                        max="2100-12-31"
                                        invalid={Boolean(
                                            errors.effective_until,
                                        )}
                                    />
                                    <InputError
                                        message={errors.effective_until}
                                    />
                                </div>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Leave both dates empty for an ongoing
                                assignment. The end date remains valid through
                                the end of that day.
                            </p>
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing ? <Spinner /> : <ShieldCheck />}
                                    {processing
                                        ? 'Updating scope...'
                                        : 'Update scope'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
export default function UserRoles({
    managedUser,
    availableRoles,
    colleges,
    can,
}: P) {
    const [roleId, setRoleId] = useState('');
    const [scopeType, setScopeType] = useState('UNIVERSITY');
    const selected = availableRoles.find((r) => String(r.id) === roleId);

    return (
        <>
            <Head title={`${managedUser.name} Access`} />
            <div className="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            User role assignment
                        </p>
                        <h1 className="text-2xl font-semibold sm:text-3xl">
                            {managedUser.name}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {managedUser.email} ·{' '}
                            {managedUser.status === 'ACTIVE'
                                ? 'Active'
                                : 'Inactive'}{' '}
                            account
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href="/admin/users">
                            <ChevronLeft />
                            Back to users
                        </Link>
                    </Button>
                </header>
                <div className="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Current assignments</CardTitle>
                            <CardDescription>
                                Each role grant is tied to an explicit
                                authorization scope.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {managedUser.roles.length ? (
                                managedUser.roles.map((role) => (
                                    <div
                                        key={role.pivot?.id}
                                        className="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div className="flex gap-3">
                                            <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                                {role.pivot?.scope_type ===
                                                'COLLEGE' ? (
                                                    <Building2 />
                                                ) : (
                                                    <University />
                                                )}
                                            </div>
                                            <div>
                                                <p className="font-medium">
                                                    {role.name}
                                                </p>
                                                <p className="font-mono text-xs text-muted-foreground">
                                                    {role.code}
                                                </p>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {role.pivot?.scope_type ===
                                                    'COLLEGE'
                                                        ? (colleges.find(
                                                              (c) =>
                                                                  `college:${c.id}` ===
                                                                  role.pivot
                                                                      ?.scope_reference,
                                                          )?.name ??
                                                          role.pivot
                                                              ?.scope_reference)
                                                        : 'University-wide'}{' '}
                                                    · {role.permissions.length}{' '}
                                                    permissions
                                                </p>
                                                <div className="mt-2 flex flex-wrap gap-2 text-xs">
                                                    <span
                                                        className={
                                                            role.pivot
                                                                ?.status ===
                                                            'ACTIVE'
                                                                ? 'bg-success/10 text-success rounded-full px-2 py-1'
                                                                : 'rounded-full bg-muted px-2 py-1 text-muted-foreground'
                                                        }
                                                    >
                                                        {role.pivot?.status ===
                                                        'ACTIVE'
                                                            ? 'Active'
                                                            : 'Inactive'}
                                                    </span>
                                                    {(role.pivot
                                                        ?.effective_from ||
                                                        role.pivot
                                                            ?.effective_until) && (
                                                        <span className="bg-info/10 text-info inline-flex items-center gap-1 rounded-full px-2 py-1">
                                                            <CalendarClock className="size-3" />
                                                            {role.pivot?.effective_from?.slice(
                                                                0,
                                                                10,
                                                            ) ?? 'Now'}{' '}
                                                            –{' '}
                                                            {role.pivot?.effective_until?.slice(
                                                                0,
                                                                10,
                                                            ) ?? 'Ongoing'}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        {!role.code.includes('SUPER_ADMIN') ? (
                                            <div className="flex flex-wrap gap-2">
                                                {can.updateScope &&
                                                role.pivot ? (
                                                    <ScopeEditor
                                                        managedUserId={
                                                            managedUser.id
                                                        }
                                                        role={role}
                                                        colleges={colleges}
                                                    />
                                                ) : null}
                                                {can.unassign ? (
                                                    <Dialog>
                                                        <DialogTrigger asChild>
                                                            <Button
                                                                size="sm"
                                                                variant="ghost"
                                                                className="text-destructive"
                                                            >
                                                                <Trash2 />
                                                                Remove
                                                            </Button>
                                                        </DialogTrigger>
                                                        <DialogContent>
                                                            <DialogTitle>
                                                                Remove{' '}
                                                                {role.name}?
                                                            </DialogTitle>
                                                            <DialogDescription>
                                                                This user loses
                                                                permissions
                                                                granted by this
                                                                assignment at
                                                                its stated
                                                                scope. Other
                                                                assignments
                                                                remain
                                                                unchanged.
                                                            </DialogDescription>
                                                            <Form
                                                                action={`/admin/users/${managedUser.id}/roles/${role.pivot?.id}`}
                                                                method="delete"
                                                                options={{
                                                                    preserveScroll: true,
                                                                }}
                                                            >
                                                                {({
                                                                    processing,
                                                                }) => (
                                                                    <DialogFooter>
                                                                        <DialogClose
                                                                            asChild
                                                                        >
                                                                            <Button
                                                                                type="button"
                                                                                variant="outline"
                                                                            >
                                                                                Cancel
                                                                            </Button>
                                                                        </DialogClose>
                                                                        <Button
                                                                            type="submit"
                                                                            variant="destructive"
                                                                            disabled={
                                                                                processing
                                                                            }
                                                                        >
                                                                            {processing ? (
                                                                                <Spinner />
                                                                            ) : (
                                                                                <Trash2 />
                                                                            )}
                                                                            {processing
                                                                                ? 'Removing...'
                                                                                : 'Remove assignment'}
                                                                        </Button>
                                                                    </DialogFooter>
                                                                )}
                                                            </Form>
                                                        </DialogContent>
                                                    </Dialog>
                                                ) : null}
                                            </div>
                                        ) : null}
                                    </div>
                                ))
                            ) : (
                                <div className="grid place-items-center py-12 text-center">
                                    <ShieldCheck className="size-10 text-muted-foreground" />
                                    <h2 className="mt-3 font-semibold">
                                        No roles assigned
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        This account has no effective ERP
                                        permissions yet.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                    <div className="space-y-6">
                        {can.assign ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Add role assignment</CardTitle>
                                    <CardDescription>
                                        Select a custom role and an explicit
                                        University or College scope.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Form
                                        action={`/admin/users/${managedUser.id}/roles`}
                                        method="put"
                                        disableWhileProcessing
                                        className="space-y-4"
                                    >
                                        {({ processing, errors }) => (
                                            <>
                                                <div className="grid gap-2">
                                                    <Label>Role</Label>
                                                    <Select
                                                        name="role_id"
                                                        value={roleId}
                                                        onValueChange={
                                                            setRoleId
                                                        }
                                                    >
                                                        <SelectTrigger
                                                            className="w-full"
                                                            aria-invalid={Boolean(
                                                                errors.role_id,
                                                            )}
                                                        >
                                                            <SelectValue placeholder="Select a role" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {availableRoles.map(
                                                                (r) => (
                                                                    <SelectItem
                                                                        key={
                                                                            r.id
                                                                        }
                                                                        value={String(
                                                                            r.id,
                                                                        )}
                                                                    >
                                                                        {r.name}
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectContent>
                                                    </Select>
                                                    {errors.role_id ? (
                                                        <p className="text-sm text-destructive">
                                                            {errors.role_id}
                                                        </p>
                                                    ) : null}
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label>Scope</Label>
                                                    <Select
                                                        name="scope_type"
                                                        value={scopeType}
                                                        onValueChange={
                                                            setScopeType
                                                        }
                                                    >
                                                        <SelectTrigger className="w-full">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="UNIVERSITY">
                                                                University-wide
                                                            </SelectItem>
                                                            <SelectItem value="COLLEGE">
                                                                One affiliated
                                                                College
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                                {scopeType === 'COLLEGE' ? (
                                                    <div className="grid gap-2">
                                                        <Label>
                                                            Affiliated College
                                                        </Label>
                                                        <Select name="college_id">
                                                            <SelectTrigger
                                                                className="w-full"
                                                                aria-invalid={Boolean(
                                                                    errors.college_id,
                                                                )}
                                                            >
                                                                <SelectValue placeholder="Select College" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {colleges.map(
                                                                    (c) => (
                                                                        <SelectItem
                                                                            key={
                                                                                c.id
                                                                            }
                                                                            value={String(
                                                                                c.id,
                                                                            )}
                                                                        >
                                                                            {
                                                                                c.name
                                                                            }{' '}
                                                                            (
                                                                            {
                                                                                c.code
                                                                            }
                                                                            )
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                        {errors.college_id ? (
                                                            <p className="text-sm text-destructive">
                                                                {
                                                                    errors.college_id
                                                                }
                                                            </p>
                                                        ) : null}
                                                    </div>
                                                ) : null}
                                                <Button
                                                    type="submit"
                                                    className="w-full"
                                                    disabled={
                                                        processing || !roleId
                                                    }
                                                >
                                                    {processing ? (
                                                        <Spinner />
                                                    ) : (
                                                        <Plus />
                                                    )}
                                                    {processing
                                                        ? 'Assigning...'
                                                        : 'Assign role'}
                                                </Button>
                                            </>
                                        )}
                                    </Form>
                                </CardContent>
                            </Card>
                        ) : null}
                        {selected ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle>
                                        Effective permission preview
                                    </CardTitle>
                                    <CardDescription>
                                        {selected.description ??
                                            'Permissions bundled in the selected role.'}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="flex flex-wrap gap-2">
                                    {selected.permissions.length ? (
                                        selected.permissions.map((p) => (
                                            <span
                                                key={p.id}
                                                className="rounded-full bg-primary/10 px-2.5 py-1 text-xs text-primary"
                                            >
                                                {p.code}
                                            </span>
                                        ))
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            This role currently has no
                                            permissions.
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        ) : null}
                    </div>
                </div>
            </div>
        </>
    );
}
UserRoles.layout = {
    breadcrumbs: [
        { title: 'Users', href: '/admin/users' },
        { title: 'Role Assignment', href: '#' },
    ],
};
