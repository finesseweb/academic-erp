import { Form, Head } from '@inertiajs/react';
import {
    Building2,
    Landmark,
    Mail,
    MapPin,
    Save,
    ShieldCheck,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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

type University = {
    id: number;
    name: string;
    code: string;
    short_name: string | null;
    established_on: string | null;
    university_type: string | null;
    accreditation: string | null;
    official_email: string | null;
    official_phone: string | null;
    website: string | null;
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country: string;
    timezone: string;
};

const fields = (university: University) => [
    {
        name: 'name',
        label: 'University name',
        value: university.name,
        required: true,
    },
    {
        name: 'code',
        label: 'University code',
        value: university.code,
        required: true,
        help: 'Uppercase letters, numbers, hyphens, and underscores.',
    },
    { name: 'short_name', label: 'Short name', value: university.short_name },
    {
        name: 'accreditation',
        label: 'Accreditation',
        value: university.accreditation,
    },
];

const universityTypes = [
    'Central University',
    'State University',
    'Private University',
    'Deemed-to-be University',
    'Open University',
    'Institute of National Importance',
] as const;

function Field({
    name,
    label,
    value,
    required,
    type = 'text',
    help,
    errors,
    disabled,
}: {
    name: string;
    label: string;
    value: string | null;
    required?: boolean;
    type?: string;
    help?: string;
    errors: Record<string, string>;
    disabled: boolean;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={name}>
                {label}
                {required ? (
                    <span className="ml-1 text-destructive" aria-hidden="true">
                        *
                    </span>
                ) : null}
            </Label>
            <Input
                id={name}
                name={name}
                type={type}
                defaultValue={value ?? ''}
                required={required}
                disabled={disabled}
                aria-invalid={Boolean(errors[name])}
                aria-describedby={`${name}-help ${name}-error`}
            />
            {help ? (
                <p
                    id={`${name}-help`}
                    className="text-xs text-muted-foreground"
                >
                    {help}
                </p>
            ) : null}
            <InputError id={`${name}-error`} message={errors[name]} />
        </div>
    );
}

export default function UniversityProfile({
    university,
    can,
}: {
    university: University;
    can: { update: boolean };
}) {
    const storedType = university.university_type ?? '';
    const isStandardType = universityTypes.includes(
        storedType as (typeof universityTypes)[number],
    );
    const [selectedType, setSelectedType] = useState(
        storedType === '' || isStandardType ? storedType : 'Other',
    );

    return (
        <>
            <Head title="University Profile" />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <div className="flex items-center gap-2 text-sm font-medium text-primary">
                            <Landmark className="size-4" />
                            University administration
                        </div>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            University Profile
                        </h1>
                        <p className="max-w-2xl text-sm text-muted-foreground">
                            Maintain the official identity and contact details
                            for the University at the root of the ERP.
                        </p>
                    </div>
                    <div className="inline-flex w-fit items-center gap-2 rounded-full border bg-card px-3 py-1.5 text-xs font-medium">
                        <ShieldCheck className="size-4 text-primary" />
                        University scope
                    </div>
                </header>

                {!can.update ? (
                    <Alert>
                        <ShieldCheck className="size-4" />
                        <AlertTitle>Read-only access</AlertTitle>
                        <AlertDescription>
                            You can view this profile, but you do not have
                            permission to update it.
                        </AlertDescription>
                    </Alert>
                ) : null}

                <Form
                    action={`/admin/university/${university.id}`}
                    method="patch"
                    options={{ preserveScroll: true }}
                    disableWhileProcessing
                >
                    {({ processing, errors, recentlySuccessful }) => (
                        <div className="space-y-6">
                            <Card>
                                <CardHeader className="border-b">
                                    <div className="flex gap-3">
                                        <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                            <Building2 className="size-5" />
                                        </div>
                                        <div>
                                            <CardTitle>
                                                Identity and governance
                                            </CardTitle>
                                            <CardDescription>
                                                Core institutional details used
                                                across University records and
                                                reports.
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                                    {fields(university).map((field) => (
                                        <Field
                                            key={field.name}
                                            {...field}
                                            errors={errors}
                                            disabled={!can.update || processing}
                                        />
                                    ))}
                                    <div className="grid gap-2">
                                        <Label htmlFor="established_on">
                                            Established on
                                        </Label>
                                        <DatePicker
                                            id="established_on"
                                            name="established_on"
                                            defaultValue={
                                                university.established_on
                                            }
                                            max={new Date()
                                                .toISOString()
                                                .slice(0, 10)}
                                            disabled={!can.update || processing}
                                            invalid={Boolean(
                                                errors.established_on,
                                            )}
                                        />
                                        <InputError
                                            message={errors.established_on}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="university_type">
                                            University type
                                        </Label>
                                        <Select
                                            name="university_type"
                                            value={selectedType}
                                            onValueChange={setSelectedType}
                                            disabled={!can.update || processing}
                                        >
                                            <SelectTrigger
                                                id="university_type"
                                                className="w-full"
                                                aria-invalid={Boolean(
                                                    errors.university_type,
                                                )}
                                            >
                                                <SelectValue placeholder="Select university type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {universityTypes.map((type) => (
                                                    <SelectItem
                                                        key={type}
                                                        value={type}
                                                    >
                                                        {type}
                                                    </SelectItem>
                                                ))}
                                                <SelectItem value="Other">
                                                    Other
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.university_type}
                                        />
                                    </div>
                                    {selectedType === 'Other' ? (
                                        <Field
                                            name="university_type_other"
                                            label="Specify university type"
                                            value={
                                                isStandardType ? '' : storedType
                                            }
                                            required
                                            errors={errors}
                                            disabled={!can.update || processing}
                                        />
                                    ) : null}
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader className="border-b">
                                    <div className="flex gap-3">
                                        <div className="rounded-lg bg-primary/10 p-2 text-primary">
                                            <Mail className="size-5" />
                                        </div>
                                        <div>
                                            <CardTitle>
                                                Official contact
                                            </CardTitle>
                                            <CardDescription>
                                                Public contact channels and the
                                                University website.
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                                    <Field
                                        name="official_email"
                                        label="Official email"
                                        type="email"
                                        value={university.official_email}
                                        errors={errors}
                                        disabled={!can.update || processing}
                                    />
                                    <Field
                                        name="official_phone"
                                        label="Official phone"
                                        type="tel"
                                        value={university.official_phone}
                                        errors={errors}
                                        disabled={!can.update || processing}
                                    />
                                    <div className="md:col-span-2">
                                        <Field
                                            name="website"
                                            label="Website"
                                            type="url"
                                            value={university.website}
                                            help="Include https://"
                                            errors={errors}
                                            disabled={!can.update || processing}
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
                                            <CardTitle>
                                                Address and locale
                                            </CardTitle>
                                            <CardDescription>
                                                Postal address, operating
                                                timezone, and country.
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="grid gap-5 pt-6 md:grid-cols-2">
                                    <div className="md:col-span-2">
                                        <Field
                                            name="address_line_1"
                                            label="Address line 1"
                                            value={university.address_line_1}
                                            errors={errors}
                                            disabled={!can.update || processing}
                                        />
                                    </div>
                                    <div className="md:col-span-2">
                                        <Field
                                            name="address_line_2"
                                            label="Address line 2"
                                            value={university.address_line_2}
                                            errors={errors}
                                            disabled={!can.update || processing}
                                        />
                                    </div>
                                    <Field
                                        name="city"
                                        label="City"
                                        value={university.city}
                                        errors={errors}
                                        disabled={!can.update || processing}
                                    />
                                    <Field
                                        name="state"
                                        label="State / province"
                                        value={university.state}
                                        errors={errors}
                                        disabled={!can.update || processing}
                                    />
                                    <Field
                                        name="postal_code"
                                        label="Postal code"
                                        value={university.postal_code}
                                        errors={errors}
                                        disabled={!can.update || processing}
                                    />
                                    <Field
                                        name="country"
                                        label="Country"
                                        value={university.country}
                                        required
                                        errors={errors}
                                        disabled={!can.update || processing}
                                    />
                                    <div className="md:col-span-2">
                                        <Field
                                            name="timezone"
                                            label="Timezone"
                                            value={university.timezone}
                                            required
                                            help="Use an IANA timezone such as Asia/Kolkata."
                                            errors={errors}
                                            disabled={!can.update || processing}
                                        />
                                    </div>
                                </CardContent>
                            </Card>

                            {can.update ? (
                                <div
                                    className="sticky bottom-4 flex items-center justify-end gap-3 rounded-xl border bg-background/95 p-3 shadow-lg backdrop-blur supports-[backdrop-filter]:bg-background/80"
                                    aria-live="polite"
                                >
                                    {recentlySuccessful ? (
                                        <span className="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                            Changes saved.
                                        </span>
                                    ) : null}
                                    <Button type="submit" disabled={processing}>
                                        {processing ? <Spinner /> : <Save />}
                                        {processing
                                            ? 'Saving…'
                                            : 'Save profile'}
                                    </Button>
                                </div>
                            ) : null}
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}

UniversityProfile.layout = {
    breadcrumbs: [
        { title: 'University', href: '/admin/university' },
        { title: 'Profile', href: '/admin/university' },
    ],
};
