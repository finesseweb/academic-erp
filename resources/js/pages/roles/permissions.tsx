import { Form, Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    ChevronLeft,
    KeyRound,
    Search,
    ShieldCheck,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Spinner } from '@/components/ui/spinner';
type Permission = {
    id: number;
    module: string;
    resource: string;
    action: string;
    code: string;
    description: string;
    is_sensitive: boolean;
    can_assign?: boolean;
};
type Role = {
    id: number;
    name: string;
    code: string;
    is_system_role: boolean;
    users_count: number;
    permissions: { id: number }[];
};
type P = {
    role: Role;
    permissions: { module: string; permissions: Permission[] }[];
    can: { assign: boolean; remove: boolean };
    context?: { eyebrow: string; backUrl: string; updateUrl: string };
};
const actionName = (action: string) =>
    action
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
export default function RolePermissions({
    role,
    permissions,
    can,
    context,
}: P) {
    const initial = useMemo(
        () => role.permissions.map((p) => p.id),
        [role.permissions],
    );
    const [selected, setSelected] = useState<number[]>(initial);
    const [search, setSearch] = useState('');
    const added = selected.filter((id) => !initial.includes(id));
    const removed = initial.filter((id) => !selected.includes(id));
    const dirty = added.length + removed.length > 0;
    const editable = !role.is_system_role && (can.assign || can.remove);
    const filtered = permissions
        .map((group) => ({
            ...group,
            permissions: group.permissions.filter((p) =>
                `${p.code} ${p.description} ${p.resource}`
                    .toLowerCase()
                    .includes(search.toLowerCase()),
            ),
        }))
        .filter((group) => group.permissions.length);
    const toggle = (id: number, value: boolean) => {
        const originally = initial.includes(id);
        const permission = permissions
            .flatMap((group) => group.permissions)
            .find((item) => item.id === id);

        if (
            value &&
            !originally &&
            (!can.assign || permission?.can_assign === false)
        ) {
            return;
        }

        if (!value && originally && !can.remove) {
            return;
        }

        setSelected((current) =>
            value
                ? [...new Set([...current, id])]
                : current.filter((item) => item !== id),
        );
    };
    const moduleSelection = (items: Permission[], value: boolean) =>
        items.forEach((p) => toggle(p.id, value));

    return (
        <>
            <Head title={`${role.name} Permissions`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="sticky top-0 z-10 flex flex-col gap-4 rounded-xl border bg-background/95 p-4 shadow-sm backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex gap-3">
                        <div className="rounded-lg bg-primary/10 p-2 text-primary">
                            <ShieldCheck className="size-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium tracking-wide text-primary uppercase">
                                {context?.eyebrow ?? 'Role permission matrix'}
                            </p>
                            <h1 className="text-xl font-semibold">
                                {role.name}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                <code>{role.code}</code> · {role.users_count}{' '}
                                assigned users · {selected.length} selected
                            </p>
                        </div>
                    </div>
                    <Button variant="outline" asChild>
                        <Link href={context?.backUrl ?? '/admin/roles'}>
                            <ChevronLeft />
                            Back to roles
                        </Link>
                    </Button>
                </header>
                {role.is_system_role ? (
                    <Card className="border-amber-500/30 bg-amber-500/5">
                        <CardContent className="flex gap-3 pt-6">
                            <AlertTriangle className="size-5 shrink-0 text-amber-600" />
                            <div>
                                <p className="font-medium">
                                    Protected system role
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Permissions are read-only and synchronized
                                    by application migrations to prevent
                                    lockout.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : null}
                <Card>
                    <CardContent className="pt-6">
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Search description, permission code, or resource"
                                className="pl-9"
                            />
                        </div>
                    </CardContent>
                </Card>
                <div className="space-y-4">
                    {filtered.map((group) => (
                        <details
                            key={group.module}
                            open
                            className="rounded-xl border bg-card shadow-sm"
                        >
                            <summary className="flex cursor-pointer list-none items-center justify-between gap-3 p-4 focus-visible:ring-2 focus-visible:ring-ring">
                                <div>
                                    <h2 className="font-semibold">
                                        {group.module}
                                    </h2>
                                    <p className="text-sm text-muted-foreground">
                                        {
                                            group.permissions.filter((p) =>
                                                selected.includes(p.id),
                                            ).length
                                        }{' '}
                                        of {group.permissions.length} selected
                                    </p>
                                </div>
                                {editable ? (
                                    <div
                                        className="flex gap-2"
                                        onClick={(e) => e.preventDefault()}
                                    >
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            onClick={() =>
                                                moduleSelection(
                                                    group.permissions,
                                                    true,
                                                )
                                            }
                                            disabled={!can.assign}
                                        >
                                            Select module
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            onClick={() =>
                                                moduleSelection(
                                                    group.permissions,
                                                    false,
                                                )
                                            }
                                            disabled={!can.remove}
                                        >
                                            Clear
                                        </Button>
                                    </div>
                                ) : null}
                            </summary>
                            <div className="grid gap-3 border-t p-4 lg:grid-cols-2">
                                {group.permissions.map((p) => {
                                    const checked = selected.includes(p.id);
                                    const disabled =
                                        role.is_system_role ||
                                        (checked
                                            ? initial.includes(p.id) &&
                                              !can.remove
                                            : !initial.includes(p.id) &&
                                              (!can.assign ||
                                                  p.can_assign === false));

                                    return (
                                        <label
                                            key={p.id}
                                            className="flex gap-3 rounded-lg border p-4 transition-colors hover:bg-muted/40"
                                        >
                                            <Checkbox
                                                checked={checked}
                                                onCheckedChange={(v) =>
                                                    toggle(p.id, v === true)
                                                }
                                                disabled={disabled}
                                                aria-label={p.description}
                                            />
                                            <div>
                                                <div className="flex flex-wrap gap-2">
                                                    <span className="font-medium">
                                                        {actionName(p.action)}{' '}
                                                        {p.resource.replaceAll(
                                                            '_',
                                                            ' ',
                                                        )}
                                                    </span>
                                                    {p.is_sensitive ? (
                                                        <span className="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs text-amber-700 dark:text-amber-300">
                                                            Sensitive
                                                        </span>
                                                    ) : null}
                                                </div>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {p.description}
                                                </p>
                                                <code className="mt-2 block text-xs text-primary">
                                                    {p.code}
                                                </code>
                                            </div>
                                        </label>
                                    );
                                })}
                            </div>
                        </details>
                    ))}
                </div>
                {!filtered.length ? (
                    <Card>
                        <CardContent className="grid place-items-center py-14 text-center">
                            <KeyRound className="size-9 text-muted-foreground" />
                            <h2 className="mt-3 font-semibold">
                                No matching permissions
                            </h2>
                        </CardContent>
                    </Card>
                ) : null}
                {editable ? (
                    <div className="sticky bottom-4 flex flex-col gap-3 rounded-xl border bg-background/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-2">
                            {dirty ? (
                                <AlertTriangle className="size-5 text-amber-600" />
                            ) : (
                                <CheckCircle2 className="size-5 text-emerald-600" />
                            )}
                            <div>
                                <p className="font-medium">
                                    {dirty
                                        ? 'Unsaved permission changes'
                                        : 'Permissions are up to date'}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {added.length} to add · {removed.length} to
                                    remove
                                </p>
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                disabled={!dirty}
                                onClick={() => setSelected(initial)}
                            >
                                Discard
                            </Button>
                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button disabled={!dirty}>
                                        Review and save
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogTitle>
                                        Apply role permission changes?
                                    </DialogTitle>
                                    <DialogDescription>
                                        Add {added.length} and remove{' '}
                                        {removed.length} permissions for{' '}
                                        {role.name}. Assigned users receive
                                        updated access within their existing
                                        scopes.
                                    </DialogDescription>
                                    <Form
                                        action={
                                            context?.updateUrl ??
                                            `/admin/roles/${role.id}/permissions`
                                        }
                                        method="put"
                                        disableWhileProcessing
                                    >
                                        {({ processing }) => (
                                            <>
                                                {selected.map((id) => (
                                                    <input
                                                        key={id}
                                                        type="hidden"
                                                        name="permission_ids[]"
                                                        value={id}
                                                    />
                                                ))}
                                                <DialogFooter>
                                                    <DialogClose asChild>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                        >
                                                            Cancel
                                                        </Button>
                                                    </DialogClose>
                                                    <Button
                                                        type="submit"
                                                        disabled={processing}
                                                    >
                                                        {processing ? (
                                                            <Spinner />
                                                        ) : null}
                                                        {processing
                                                            ? 'Saving...'
                                                            : 'Apply changes'}
                                                    </Button>
                                                </DialogFooter>
                                            </>
                                        )}
                                    </Form>
                                </DialogContent>
                            </Dialog>
                        </div>
                    </div>
                ) : null}
            </div>
        </>
    );
}
RolePermissions.layout = {
    breadcrumbs: [
        { title: 'Roles', href: '/admin/roles' },
        { title: 'Permission Matrix', href: '#' },
    ],
};
