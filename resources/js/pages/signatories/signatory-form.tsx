import { Form, Link } from '@inertiajs/react';
import { CalendarRange, Save, ShieldCheck, UserRound } from 'lucide-react';
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

export type SignatoryFormData = {
    id?: number;
    full_name?: string;
    designation?: string;
    authority_type?: string;
    email?: string | null;
    phone?: string | null;
    effective_from?: string;
    effective_until?: string | null;
    status?: string;
    notes?: string | null;
};
export const authorityTypes = [
    ['GENERAL', 'General'],
    ['ACADEMIC_RECORDS', 'Academic records'],
    ['EXAMINATION', 'Examination'],
    ['CERTIFICATES', 'Certificates'],
    ['FINANCE', 'Finance'],
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

export default function SignatoryForm({
    signatory = {},
    mode,
}: {
    signatory?: SignatoryFormData;
    mode: 'create' | 'edit';
}) {
    const action =
        mode === 'create'
            ? '/admin/university/signatories'
            : `/admin/university/signatories/${signatory.id}`;

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
                                    <CardTitle>Signatory identity</CardTitle>
                                    <CardDescription>
                                        Official identity and contact details
                                        for this appointment.
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                            <Field
                                name="full_name"
                                label="Full name"
                                value={signatory.full_name}
                                required
                                errors={errors}
                            />
                            <Field
                                name="designation"
                                label="Designation"
                                value={signatory.designation}
                                required
                                errors={errors}
                            />
                            <Field
                                name="email"
                                label="Official email"
                                type="email"
                                value={signatory.email}
                                errors={errors}
                            />
                            <Field
                                name="phone"
                                label="Official phone"
                                type="tel"
                                value={signatory.phone}
                                errors={errors}
                            />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="border-b">
                            <div className="flex gap-3">
                                <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                    <ShieldCheck className="size-5" />
                                </div>
                                <div>
                                    <CardTitle>Signing authority</CardTitle>
                                    <CardDescription>
                                        Define the governed record category and
                                        operational state.
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Authority type *</Label>
                                <Select
                                    name="authority_type"
                                    defaultValue={
                                        signatory.authority_type ?? 'GENERAL'
                                    }
                                    required
                                >
                                    <SelectTrigger
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            errors.authority_type,
                                        )}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {authorityTypes.map(
                                            ([value, label]) => (
                                                <SelectItem
                                                    key={value}
                                                    value={value}
                                                >
                                                    {label}
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.authority_type} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Status *</Label>
                                <Select
                                    name="status"
                                    defaultValue={signatory.status ?? 'ACTIVE'}
                                    required
                                >
                                    <SelectTrigger
                                        className="w-full"
                                        aria-invalid={Boolean(errors.status)}
                                    >
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
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="border-b">
                            <div className="flex gap-3">
                                <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                    <CalendarRange className="size-5" />
                                </div>
                                <div>
                                    <CardTitle>Appointment period</CardTitle>
                                    <CardDescription>
                                        Select month and year directly for
                                        long-range appointments.
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="effective_from">
                                    Effective from{' '}
                                    <span className="text-destructive">*</span>
                                </Label>
                                <DatePicker
                                    id="effective_from"
                                    name="effective_from"
                                    defaultValue={signatory.effective_from}
                                    invalid={Boolean(errors.effective_from)}
                                />
                                <InputError message={errors.effective_from} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="effective_until">
                                    Effective until
                                </Label>
                                <DatePicker
                                    id="effective_until"
                                    name="effective_until"
                                    defaultValue={signatory.effective_until}
                                    invalid={Boolean(errors.effective_until)}
                                />
                                <InputError message={errors.effective_until} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="notes">
                                    Administrative notes
                                </Label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    defaultValue={signatory.notes ?? ''}
                                    rows={4}
                                    aria-invalid={Boolean(errors.notes)}
                                    className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs transition-colors outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive"
                                />
                                <InputError message={errors.notes} />
                            </div>
                        </CardContent>
                    </Card>
                    <div className="sticky bottom-4 flex justify-end gap-3 rounded-xl border bg-background/95 p-3 shadow-lg backdrop-blur">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/admin/university/signatories">
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <Spinner /> : <Save />}
                            {processing
                                ? 'Saving...'
                                : mode === 'create'
                                  ? 'Create signatory'
                                  : 'Save changes'}
                        </Button>
                    </div>
                </div>
            )}
        </Form>
    );
}
