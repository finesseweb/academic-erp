import { Form, Head, Link } from '@inertiajs/react';
import {
    CalendarClock,
    ChevronLeft,
    Pencil,
    Plus,
    ShieldCheck,
    Trash2,
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
import { DatePicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
    permissions: { id: number; code: string; description?: string }[];
};
type Assignment = {
    id: number;
    status: string;
    effective_from: string | null;
    effective_until: string | null;
    role: Role;
};
type Props = {
    college: { id: number; name: string; code: string };
    managedUser: { id: number; name: string; email: string; status: string };
    availableRoles: Role[];
    assignments: Assignment[];
    can: { assign: boolean; unassign: boolean; updateScope: boolean };
};

export default function Roles({
    college,
    managedUser,
    availableRoles,
    assignments,
    can,
}: Props) {
    const [roleId, setRoleId] = useState('');
    const selected = availableRoles.find((r) => String(r.id) === roleId);

    return (
        <>
            <Head title={`${managedUser.name} · College Access`} />
            <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-6">
                <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {college.code} · College Access
                        </p>
                        <h1 className="text-3xl font-semibold">
                            {managedUser.name}
                        </h1>
                        <p className="text-muted-foreground">
                            Roles apply only inside {college.name}.
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={`/college/${college.id}/users`}>
                            <ChevronLeft />
                            Back to users
                        </Link>
                    </Button>
                </header>
                <div className="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Current College assignments</CardTitle>
                            <CardDescription>
                                Each grant has fixed College scope and an
                                optional effective period.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {assignments.length ? (
                                assignments.map((a) => (
                                    <div
                                        key={a.id}
                                        className="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <p className="font-medium">
                                                {a.role.name}
                                            </p>
                                            <code className="text-xs text-primary">
                                                {a.role.code}
                                            </code>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {a.status} ·{' '}
                                                {a.role.permissions.length}{' '}
                                                permissions
                                            </p>
                                            {(a.effective_from ||
                                                a.effective_until) && (
                                                <p className="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                                                    <CalendarClock className="size-3" />
                                                    {a.effective_from?.slice(
                                                        0,
                                                        10,
                                                    ) ?? 'Now'}{' '}
                                                    –{' '}
                                                    {a.effective_until?.slice(
                                                        0,
                                                        10,
                                                    ) ?? 'Ongoing'}
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex gap-2">
                                            {can.updateScope && (
                                                <LifecycleDialog
                                                    college={college}
                                                    userId={managedUser.id}
                                                    assignment={a}
                                                />
                                            )}{' '}
                                            {can.unassign && (
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
                                                            Remove {a.role.name}
                                                            ?
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            The user immediately
                                                            loses permissions
                                                            from this assignment
                                                            in {college.name}.
                                                        </DialogDescription>
                                                        <Form
                                                            action={`/college/${college.id}/users/${managedUser.id}/roles/${a.id}`}
                                                            method="delete"
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
                                            )}
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="py-12 text-center">
                                    <ShieldCheck className="mx-auto size-10 text-muted-foreground" />
                                    <h2 className="mt-3 font-semibold">
                                        No College roles assigned
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        This user has no custom permissions in
                                        this College.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                    <div className="space-y-6">
                        {can.assign && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Assign College role</CardTitle>
                                    <CardDescription>
                                        Only active roles owned by this College
                                        are available.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Form
                                        action={`/college/${college.id}/users/${managedUser.id}/roles`}
                                        method="post"
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
                                                            <SelectValue placeholder="Select College role" />
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
                                                    <InputError
                                                        message={errors.role_id}
                                                    />
                                                </div>
                                                <Button
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
                        )}
                        {selected && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Permission preview</CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-wrap gap-2">
                                    {selected.permissions.length ? (
                                        selected.permissions.map((p) => (
                                            <code
                                                key={p.id}
                                                className="rounded-full bg-primary/10 px-2 py-1 text-xs text-primary"
                                            >
                                                {p.code}
                                            </code>
                                        ))
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            This role has no delegated
                                            permissions.
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function LifecycleDialog({
    college,
    userId,
    assignment,
}: {
    college: Props['college'];
    userId: number;
    assignment: Assignment;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Pencil />
                    Lifecycle
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Update {assignment.role.name}</DialogTitle>
                <DialogDescription>
                    The College scope is fixed. Adjust status or the effective
                    period.
                </DialogDescription>
                <Form
                    action={`/college/${college.id}/users/${userId}/roles/${assignment.id}/scope`}
                    method="patch"
                    disableWhileProcessing
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label>Status</Label>
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
                                    <Label>Effective from</Label>
                                    <DatePicker
                                        id={`college-from-${assignment.id}`}
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
                                    <Label>Effective until</Label>
                                    <DatePicker
                                        id={`college-until-${assignment.id}`}
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
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing ? <Spinner /> : <ShieldCheck />}
                                    {processing
                                        ? 'Updating...'
                                        : 'Update lifecycle'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
