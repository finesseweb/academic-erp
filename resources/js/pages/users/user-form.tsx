import { Form, Link } from '@inertiajs/react';
import { KeyRound, Mail, Save, UserRound } from 'lucide-react';
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
export type UserFormData = {
    id?: number;
    name?: string;
    email?: string;
    mobile?: string | null;
    account_type?: string;
    status?: string;
    roles?: { id: number; name: string }[];
};
export const accountTypes = [
    ['SYSTEM_ADMIN', 'System administrator'],
    ['UNIVERSITY_STAFF', 'University staff'],
    ['COLLEGE_STAFF', 'College staff'],
    ['OTHER', 'Other'],
] as const;
function Field({
    name,
    label,
    value,
    type = 'text',
    required,
    errors,
}: {
    name: string;
    label: string;
    value?: string | null;
    type?: string;
    required?: boolean;
    errors: Record<string, string>;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={name}>
                {label}
                {required ? (
                    <span className="ml-1 text-destructive">*</span>
                ) : null}
            </Label>
            <Input
                id={name}
                name={name}
                type={type}
                defaultValue={value ?? ''}
                required={required}
                aria-invalid={Boolean(errors[name])}
            />
            <InputError message={errors[name]} />
        </div>
    );
}
export default function UserForm({
    managedUser = {},
    mode,
}: {
    managedUser?: UserFormData;
    mode: 'create' | 'edit';
}) {
    const action =
        mode === 'create' ? '/admin/users' : `/admin/users/${managedUser.id}`;

    return (
        <Form
            action={action}
            method={mode === 'create' ? 'post' : 'patch'}
            disableWhileProcessing
        >
            {({ processing, errors }) => (
                <div className="space-y-6">
                    <Card>
                        <CardHeader className="border-b">
                            <div className="flex gap-3">
                                <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                    <UserRound className="size-5" />
                                </div>
                                <div>
                                    <CardTitle>Account identity</CardTitle>
                                    <CardDescription>
                                        Login identity only; academic and
                                        employee profiles remain separate.
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                            <Field
                                name="name"
                                label="Full name"
                                value={managedUser.name}
                                required
                                errors={errors}
                            />
                            <Field
                                name="email"
                                label="Email address"
                                type="email"
                                value={managedUser.email}
                                required
                                errors={errors}
                            />
                            <Field
                                name="mobile"
                                label="Mobile number"
                                type="tel"
                                value={managedUser.mobile}
                                errors={errors}
                            />
                            <div className="grid gap-2">
                                <Label>Account type *</Label>
                                <Select
                                    name="account_type"
                                    defaultValue={
                                        managedUser.account_type ??
                                        'UNIVERSITY_STAFF'
                                    }
                                    required
                                >
                                    <SelectTrigger
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            errors.account_type,
                                        )}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {accountTypes.map(([v, l]) => (
                                            <SelectItem key={v} value={v}>
                                                {l}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.account_type} />
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
                                    <InputError message={errors.status} />
                                </div>
                            ) : null}
                        </CardContent>
                    </Card>
                    {mode === 'create' ? (
                        <Card>
                            <CardHeader className="border-b">
                                <div className="flex gap-3">
                                    <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                        <KeyRound className="size-5" />
                                    </div>
                                    <div>
                                        <CardTitle>Initial password</CardTitle>
                                        <CardDescription>
                                            Use at least 12 characters
                                            containing letters and numbers.
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                                <Field
                                    name="password"
                                    label="Temporary password"
                                    type="password"
                                    required
                                    errors={errors}
                                />
                                <Field
                                    name="password_confirmation"
                                    label="Confirm password"
                                    type="password"
                                    required
                                    errors={errors}
                                />
                            </CardContent>
                        </Card>
                    ) : (
                        <Card>
                            <CardContent className="flex gap-3 pt-6">
                                <Mail className="size-5 text-primary" />
                                <div>
                                    <p className="font-medium">
                                        Password changes are separate
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Use “Send reset link” from the Users
                                        list. Existing passwords are never
                                        displayed or edited here.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                    <div className="sticky bottom-4 flex justify-end gap-3 rounded-xl border bg-background/95 p-3 shadow-lg backdrop-blur">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/admin/users">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <Spinner /> : <Save />}
                            {processing
                                ? 'Saving...'
                                : mode === 'create'
                                  ? 'Create user'
                                  : 'Save changes'}
                        </Button>
                    </div>
                </div>
            )}
        </Form>
    );
}
