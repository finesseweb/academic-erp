import { Form, Link } from '@inertiajs/react';
import { Building2, Contact, MapPin, Save } from 'lucide-react';
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

export type CollegeFormData = {
    id?: number;
    name?: string;
    code?: string;
    affiliation_type?: string;
    status?: string;
    official_email?: string | null;
    official_phone?: string | null;
    website?: string | null;
    address_line_1?: string | null;
    address_line_2?: string | null;
    city?: string | null;
    state?: string | null;
    postal_code?: string | null;
    country?: string;
    timezone?: string;
};

const types = [
    'Constituent',
    'Affiliated',
    'Autonomous',
    'Government',
    'Private Aided',
    'Private Unaided',
    'Other',
];

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

export default function CollegeForm({
    college = {},
    mode,
}: {
    college?: CollegeFormData;
    mode: 'create' | 'edit';
}) {
    const action =
        mode === 'create' ? '/admin/colleges' : `/admin/colleges/${college.id}`;

    return (
        <Form
            action={action}
            method={mode === 'create' ? 'post' : 'patch'}
            disableWhileProcessing
            resetOnSuccess={mode === 'create'}
        >
            {({ processing, errors }) => (
                <div className="space-y-6">
                    <Card>
                        <CardHeader className="border-b">
                            <div className="flex gap-3">
                                <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                    <Building2 className="size-5" />
                                </div>
                                <div>
                                    <CardTitle>College identity</CardTitle>
                                    <CardDescription>
                                        Official identity, affiliation
                                        classification, and operational status.
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                            <Field
                                name="name"
                                label="College name"
                                value={college.name}
                                required
                                errors={errors}
                            />
                            <Field
                                name="code"
                                label="College code"
                                value={college.code}
                                required
                                errors={errors}
                            />
                            <div className="grid gap-2">
                                <Label>Affiliation type *</Label>
                                <Select
                                    name="affiliation_type"
                                    defaultValue={
                                        college.affiliation_type ?? 'Affiliated'
                                    }
                                    required
                                >
                                    <SelectTrigger
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            errors.affiliation_type,
                                        )}
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((type) => (
                                            <SelectItem key={type} value={type}>
                                                {type}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.affiliation_type} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Status *</Label>
                                <Select
                                    name="status"
                                    defaultValue={college.status ?? 'ACTIVE'}
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
                                    <Contact className="size-5" />
                                </div>
                                <div>
                                    <CardTitle>Official contact</CardTitle>
                                    <CardDescription>
                                        Verified communication channels for
                                        University administration.
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                            <Field
                                name="official_email"
                                label="Official email"
                                type="email"
                                value={college.official_email}
                                errors={errors}
                            />
                            <Field
                                name="official_phone"
                                label="Official phone"
                                type="tel"
                                value={college.official_phone}
                                errors={errors}
                            />
                            <div className="md:col-span-2">
                                <Field
                                    name="website"
                                    label="Website"
                                    type="url"
                                    value={college.website}
                                    errors={errors}
                                />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="border-b">
                            <div className="flex gap-3">
                                <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                    <MapPin className="size-5" />
                                </div>
                                <div>
                                    <CardTitle>Address and locale</CardTitle>
                                    <CardDescription>
                                        Primary administrative address and
                                        operating timezone.
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <Field
                                    name="address_line_1"
                                    label="Address line 1"
                                    value={college.address_line_1}
                                    errors={errors}
                                />
                            </div>
                            <div className="md:col-span-2">
                                <Field
                                    name="address_line_2"
                                    label="Address line 2"
                                    value={college.address_line_2}
                                    errors={errors}
                                />
                            </div>
                            <Field
                                name="city"
                                label="City"
                                value={college.city}
                                errors={errors}
                            />
                            <Field
                                name="state"
                                label="State / province"
                                value={college.state}
                                errors={errors}
                            />
                            <Field
                                name="postal_code"
                                label="Postal code"
                                value={college.postal_code}
                                errors={errors}
                            />
                            <Field
                                name="country"
                                label="Country"
                                value={college.country ?? 'India'}
                                required
                                errors={errors}
                            />
                            <div className="md:col-span-2">
                                <Field
                                    name="timezone"
                                    label="Timezone"
                                    value={college.timezone ?? 'Asia/Kolkata'}
                                    required
                                    errors={errors}
                                />
                            </div>
                        </CardContent>
                    </Card>
                    <div className="sticky bottom-4 flex justify-end gap-3 rounded-xl border bg-background/95 p-3 shadow-lg backdrop-blur">
                        <Button type="button" variant="outline" asChild>
                            <Link href="/admin/colleges">Cancel</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <Spinner /> : <Save />}
                            {processing
                                ? 'Saving…'
                                : mode === 'create'
                                  ? 'Create College'
                                  : 'Save changes'}
                        </Button>
                    </div>
                </div>
            )}
        </Form>
    );
}
