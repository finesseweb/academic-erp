import { Form, Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import {
    KeyRound,
    Pencil,
    Plus,
    Power,
    Search,
    Shield,
    UsersRound,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
type U = {
    id: number;
    name: string;
    email: string;
    mobile: string | null;
    status: string;
};
export default function Index({
    college,
    users,
    filters,
    can,
}: {
    college: { id: number; name: string; code: string };
    users: { data: U[] };
    filters: { search?: string };
    can: {
        create: boolean;
        update: boolean;
        disable: boolean;
        enable: boolean;
        resetPassword: boolean;
        manageRoles: boolean;
    };
}) {
    return (
        <>
            <Head title={`${college.name} Users`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {college.code} · College Administration
                        </p>
                        <h1 className="text-3xl font-semibold">
                            College Users
                        </h1>
                        <p className="text-muted-foreground">
                            Only staff linked to {college.name} are shown.
                        </p>
                    </div>
                    {can.create && (
                        <Button asChild>
                            <Link href={`/college/${college.id}/users/create`}>
                                <Plus />
                                Create user
                            </Link>
                        </Button>
                    )}
                </header>
                <Card>
                    <CardContent className="pt-6">
                        <Form
                            action={`/college/${college.id}/users`}
                            method="get"
                            className="flex gap-2"
                        >
                            <div className="relative flex-1">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search ?? ''}
                                    className="pl-9"
                                    placeholder="Search College staff"
                                />
                            </div>
                            <Button>Search</Button>
                        </Form>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-0">
                        {users.data.length ? (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/60 text-left">
                                        <tr>
                                            <th className="p-4">Name</th>
                                            <th className="p-4">Contact</th>
                                            <th className="p-4">Status</th>
                                            <th className="p-4 text-right">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {users.data.map((u) => (
                                            <tr key={u.id} className="border-t">
                                                <td className="p-4 font-medium">
                                                    {u.name}
                                                </td>
                                                <td className="p-4">
                                                    {u.email}
                                                    <span className="block text-xs text-muted-foreground">
                                                        {u.mobile}
                                                    </span>
                                                </td>
                                                <td className="p-4">
                                                    {u.status}
                                                </td>
                                                <td className="p-4">
                                                    <div className="flex justify-end gap-1">
                                                        {can.update && (
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                asChild
                                                                aria-label={`Edit ${u.name}`}
                                                            >
                                                                <Link
                                                                    href={`/college/${college.id}/users/${u.id}/edit`}
                                                                >
                                                                    <Pencil />
                                                                </Link>
                                                            </Button>
                                                        )}
                                                        {can.manageRoles && (
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                asChild
                                                                aria-label={`Manage ${u.name} roles`}
                                                            >
                                                                <Link
                                                                    href={`/college/${college.id}/users/${u.id}/roles`}
                                                                >
                                                                    <Shield />
                                                                </Link>
                                                            </Button>
                                                        )}
                                                        {can.resetPassword && (
                                                            <ActionDialog
                                                                title={`Send reset link to ${u.name}?`}
                                                                description="A secure password reset email will be sent to this College user."
                                                                action={`/college/${college.id}/users/${u.id}/password-reset`}
                                                                method="post"
                                                                label="Send reset link"
                                                                icon={
                                                                    <KeyRound />
                                                                }
                                                            />
                                                        )}{' '}
                                                        {((u.status ===
                                                            'ACTIVE' &&
                                                            can.disable) ||
                                                            (u.status !==
                                                                'ACTIVE' &&
                                                                can.enable)) && (
                                                            <ActionDialog
                                                                title={`${u.status === 'ACTIVE' ? 'Disable' : 'Enable'} ${u.name}?`}
                                                                description={
                                                                    u.status ===
                                                                    'ACTIVE'
                                                                        ? 'The user will lose access and active sessions will be revoked.'
                                                                        : 'The user will regain access according to assigned roles and scopes.'
                                                                }
                                                                action={`/college/${college.id}/users/${u.id}/status`}
                                                                method="patch"
                                                                data={{
                                                                    status:
                                                                        u.status ===
                                                                        'ACTIVE'
                                                                            ? 'INACTIVE'
                                                                            : 'ACTIVE',
                                                                }}
                                                                label={
                                                                    u.status ===
                                                                    'ACTIVE'
                                                                        ? 'Disable'
                                                                        : 'Enable'
                                                                }
                                                                icon={<Power />}
                                                            />
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="grid place-items-center py-16 text-center">
                                <UsersRound className="size-10 text-muted-foreground" />
                                <h2 className="mt-3 font-semibold">
                                    No College users
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Create the first staff account for this
                                    College.
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function ActionDialog({
    title,
    description,
    action,
    method,
    data,
    label,
    icon,
}: {
    title: string;
    description: string;
    action: string;
    method: 'post' | 'patch';
    data?: Record<string, string>;
    label: string;
    icon: ReactNode;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="icon" variant="ghost" aria-label={label}>
                    {icon}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>
                <Form action={action} method={method}>
                    {({ processing }) => (
                        <DialogFooter>
                            {data
                                ? Object.entries(data).map(([name, value]) => (
                                      <input
                                          key={name}
                                          type="hidden"
                                          name={name}
                                          value={value}
                                      />
                                  ))
                                : null}
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={processing}>
                                {processing ? <Spinner /> : icon}
                                {processing ? 'Working...' : label}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
