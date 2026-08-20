import { Form, Head, Link } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Power, Shield } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
type R = {
    id: number;
    name: string;
    code: string;
    description: string | null;
    status: string;
};
export default function Index({
    college,
    roles,
    can,
}: {
    college: { id: number; name: string; code: string };
    roles: { data: R[] };
    can: {
        create: boolean;
        update: boolean;
        disable: boolean;
        managePermissions: boolean;
    };
}) {
    return (
        <>
            <Head title={`${college.name} Roles`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        {college.code} · Access & Security
                    </p>
                    <h1 className="text-3xl font-semibold">College Roles</h1>
                    <p className="text-muted-foreground">
                        Roles created here are owned by {college.name} and
                        cannot cross College boundaries.
                    </p>
                </header>
                <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                    <Card>
                        <CardContent className="p-0">
                            {roles.data.length ? (
                                <div className="divide-y">
                                    {roles.data.map((r) => (
                                        <div
                                            key={r.id}
                                            className="flex gap-3 p-4"
                                        >
                                            <Shield className="text-primary" />
                                            <div className="min-w-0 flex-1">
                                                <p className="font-medium">
                                                    {r.name}
                                                </p>
                                                <p className="font-mono text-xs text-muted-foreground">
                                                    {r.code}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {r.description}
                                                </p>
                                            </div>
                                            <div className="flex gap-1">
                                                {can.managePermissions && (
                                                    <Button
                                                        size="icon"
                                                        variant="ghost"
                                                        asChild
                                                        aria-label={`Manage ${r.name} permissions`}
                                                    >
                                                        <Link
                                                            href={`/college/${college.id}/roles/${r.id}/permissions`}
                                                        >
                                                            <KeyRound />
                                                        </Link>
                                                    </Button>
                                                )}
                                                {can.update && (
                                                    <Button
                                                        size="icon"
                                                        variant="ghost"
                                                        asChild
                                                        aria-label={`Edit ${r.name}`}
                                                    >
                                                        <Link
                                                            href={`/college/${college.id}/roles/${r.id}/edit`}
                                                        >
                                                            <Pencil />
                                                        </Link>
                                                    </Button>
                                                )}
                                                {((r.status === 'ACTIVE' &&
                                                    can.disable) ||
                                                    (r.status !== 'ACTIVE' &&
                                                        can.update)) && (
                                                    <Dialog>
                                                        <DialogTrigger asChild>
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                aria-label={`${r.status === 'ACTIVE' ? 'Disable' : 'Enable'} ${r.name}`}
                                                            >
                                                                <Power />
                                                            </Button>
                                                        </DialogTrigger>
                                                        <DialogContent>
                                                            <DialogTitle>
                                                                {r.status ===
                                                                'ACTIVE'
                                                                    ? 'Disable'
                                                                    : 'Enable'}{' '}
                                                                {r.name}?
                                                            </DialogTitle>
                                                            <DialogDescription>
                                                                {r.status ===
                                                                'ACTIVE'
                                                                    ? 'New authorization decisions will no longer use this role.'
                                                                    : 'This role becomes available for authorized College assignments.'}
                                                            </DialogDescription>
                                                            <Form
                                                                action={`/college/${college.id}/roles/${r.id}/status`}
                                                                method="patch"
                                                            >
                                                                {({
                                                                    processing,
                                                                }) => (
                                                                    <DialogFooter>
                                                                        <input
                                                                            type="hidden"
                                                                            name="status"
                                                                            value={
                                                                                r.status ===
                                                                                'ACTIVE'
                                                                                    ? 'INACTIVE'
                                                                                    : 'ACTIVE'
                                                                            }
                                                                        />
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
                                                                            disabled={
                                                                                processing
                                                                            }
                                                                        >
                                                                            {processing ? (
                                                                                <Spinner />
                                                                            ) : (
                                                                                <Power />
                                                                            )}
                                                                            {processing
                                                                                ? 'Updating...'
                                                                                : r.status ===
                                                                                    'ACTIVE'
                                                                                  ? 'Disable'
                                                                                  : 'Enable'}
                                                                        </Button>
                                                                    </DialogFooter>
                                                                )}
                                                            </Form>
                                                        </DialogContent>
                                                    </Dialog>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="p-12 text-center text-muted-foreground">
                                    No custom College roles yet.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                    {can.create && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Create College role</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Form
                                    action={`/college/${college.id}/roles`}
                                    method="post"
                                    disableWhileProcessing
                                    className="space-y-4"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            {[
                                                ['name', 'Role name'],
                                                ['code', 'Role code'],
                                                ['description', 'Description'],
                                            ].map(([name, label]) => (
                                                <div
                                                    key={name}
                                                    className="grid gap-2"
                                                >
                                                    <Label htmlFor={name}>
                                                        {label}
                                                    </Label>
                                                    <Input
                                                        id={name}
                                                        name={name}
                                                        required={
                                                            name !==
                                                            'description'
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors[name],
                                                        )}
                                                    />
                                                    <InputError
                                                        message={errors[name]}
                                                    />
                                                </div>
                                            ))}
                                            <Button
                                                className="w-full"
                                                disabled={processing}
                                            >
                                                {processing ? (
                                                    <Spinner />
                                                ) : (
                                                    <Plus />
                                                )}
                                                {processing
                                                    ? 'Creating...'
                                                    : 'Create role'}
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}
