import { Form, Link } from '@inertiajs/react';
import { Save, ShieldCheck } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
export type RoleData = {
    id?: number;
    name?: string;
    code?: string;
    description?: string | null;
    status?: string;
    is_system_role?: boolean;
    permissions_count?: number;
    users_count?: number;
};
export default function RoleForm({
    role = {},
    mode,
}: {
    role?: RoleData;
    mode: 'create' | 'edit';
}) {
    const protectedRole = Boolean(role.is_system_role);

    return (
        <Form
            action={
                mode === 'create' ? '/admin/roles' : `/admin/roles/${role.id}`
            }
            method={mode === 'create' ? 'post' : 'patch'}
            disableWhileProcessing
        >
            {({ processing, errors }) => (
                <div className="space-y-6">
                    <Card>
                        <CardHeader className="border-b">
                            <div className="flex gap-3">
                                <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                    <ShieldCheck className="size-5" />
                                </div>
                                <div>
                                    <CardTitle>Role identity</CardTitle>
                                    <CardDescription>
                                        A stable permission bundle. System role
                                        identities are protected.
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Role name *</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={role.name ?? ''}
                                    required
                                    disabled={protectedRole}
                                    aria-invalid={Boolean(errors.name)}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="code">Stable role code *</Label>
                                <Input
                                    id="code"
                                    name="code"
                                    defaultValue={role.code ?? ''}
                                    required
                                    disabled={protectedRole}
                                    placeholder="ACADEMIC_REVIEWER"
                                    aria-invalid={Boolean(errors.code)}
                                />
                                <InputError message={errors.code} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    defaultValue={role.description ?? ''}
                                    disabled={protectedRole}
                                    rows={4}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm transition outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:opacity-60"
                                />
                                <InputError message={errors.description} />
                            </div>
                            {mode === 'create' ? (
                                <div className="grid gap-2">
                                    <Label>Status *</Label>
                                    <Select name="status" defaultValue="ACTIVE">
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
                                </div>
                            ) : null}
                        </CardContent>
                    </Card>
                    {protectedRole ? (
                        <Card>
                            <CardContent className="flex gap-3 pt-6 text-sm text-muted-foreground">
                                <ShieldCheck className="size-5 shrink-0 text-primary" />
                                <p>
                                    This system role is read-only. Its identity
                                    and lifecycle cannot be changed through
                                    normal administration.
                                </p>
                            </CardContent>
                        </Card>
                    ) : null}
                    <div className="sticky bottom-4 flex justify-end gap-3 rounded-xl border bg-background/95 p-3 shadow-lg backdrop-blur">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/admin/roles">Back</Link>
                        </Button>
                        {!protectedRole ? (
                            <Button type="submit" disabled={processing}>
                                {processing ? <Spinner /> : <Save />}
                                {processing
                                    ? 'Saving...'
                                    : mode === 'create'
                                      ? 'Create role'
                                      : 'Save changes'}
                            </Button>
                        ) : null}
                    </div>
                </div>
            )}
        </Form>
    );
}
