import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, Save } from 'lucide-react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Policy = {
    id: number;
    code: string;
    name: string;
    version: string;
    scope_type: 'UNIVERSITY' | 'PROGRAM_TEMPLATE' | 'CURRICULUM';
    lifecycle_status: 'DRAFT' | 'ACTIVE' | 'RETIRED';
    approval_status: string;
    academic_session: { id: number; name: string; code: string };
};

type AttendanceRule = {
    minimum_attendance_percent: string | number;
    calculation_level: 'COURSE' | 'TERM' | 'OVERALL';
    allow_condonation: boolean;
    condonation_minimum_percent: string | number | null;
    maximum_condonable_shortage_percent: string | number | null;
    attendance_required_for_exam: boolean;
    allow_special_exemption: boolean;
    rounding_rule: 'NONE' | 'NEAREST' | 'FLOOR' | 'CEIL';
    notes: string | null;
};

export default function AttendancePolicy({
    policy,
    rule,
    editable,
}: {
    policy: Policy;
    rule: AttendanceRule | null;
    editable: boolean;
}) {
    const form = useForm({
        minimum_attendance_percent: rule?.minimum_attendance_percent?.toString() ?? '75',
        calculation_level: rule?.calculation_level ?? 'COURSE',
        allow_condonation: rule?.allow_condonation ?? false,
        condonation_minimum_percent: rule?.condonation_minimum_percent?.toString() ?? '',
        maximum_condonable_shortage_percent:
            rule?.maximum_condonable_shortage_percent?.toString() ?? '',
        attendance_required_for_exam: rule?.attendance_required_for_exam ?? true,
        allow_special_exemption: rule?.allow_special_exemption ?? false,
        rounding_rule: rule?.rounding_rule ?? 'NONE',
        notes: rule?.notes ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/admin/academic-policies/${policy.id}/attendance`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`${policy.name} Attendance Policy`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="border-b pb-5">
                    <Button
                        type="button"
                        variant="ghost"
                        className="mb-3 px-0"
                        onClick={() => window.history.back()}
                    >
                        <ArrowLeft className="size-4" />
                        Academic Policies
                    </Button>
                    <p className="text-sm font-medium text-primary">
                        {policy.code} · Version {policy.version}
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold sm:text-3xl">
                        Attendance Policy
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {policy.name} · {policy.academic_session.name} ·{' '}
                        {policy.scope_type.replace('_', ' ')}
                    </p>
                    {!editable && (
                        <p className="mt-3 rounded-md border bg-muted/30 p-3 text-sm text-muted-foreground">
                            This policy is read-only because it is not an editable Draft.
                        </p>
                    )}
                </header>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Attendance Requirement</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-3">
                            <Field
                                label="Minimum Attendance %"
                                error={form.errors.minimum_attendance_percent}
                            >
                                <Input
                                    type="number"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    disabled={!editable}
                                    value={form.data.minimum_attendance_percent}
                                    onChange={(e) =>
                                        form.setData('minimum_attendance_percent', e.target.value)
                                    }
                                />
                            </Field>

                            <Field
                                label="Calculation Level"
                                error={form.errors.calculation_level}
                            >
                                <Select
                                    disabled={!editable}
                                    value={form.data.calculation_level}
                                    onValueChange={(value) =>
                                        form.setData(
                                            'calculation_level',
                                            value as 'COURSE' | 'TERM' | 'OVERALL',
                                        )
                                    }
                                >
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="COURSE">Course-wise</SelectItem>
                                        <SelectItem value="TERM">Term / Semester-wise</SelectItem>
                                        <SelectItem value="OVERALL">Overall</SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>

                            <Field label="Rounding Rule" error={form.errors.rounding_rule}>
                                <Select
                                    disabled={!editable}
                                    value={form.data.rounding_rule}
                                    onValueChange={(value) =>
                                        form.setData(
                                            'rounding_rule',
                                            value as 'NONE' | 'NEAREST' | 'FLOOR' | 'CEIL',
                                        )
                                    }
                                >
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="NONE">No rounding</SelectItem>
                                        <SelectItem value="NEAREST">Nearest whole %</SelectItem>
                                        <SelectItem value="FLOOR">Round down</SelectItem>
                                        <SelectItem value="CEIL">Round up</SelectItem>
                                    </SelectContent>
                                </Select>
                            </Field>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Condonation / Shortage</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <ToggleRow
                                label="Allow Attendance Condonation"
                                description="Allows a controlled shortage band below the normal minimum. This does not approve a student automatically; the later student workflow must still verify/approve it."
                                checked={form.data.allow_condonation}
                                disabled={!editable}
                                onChange={(checked) => {
                                    form.setData('allow_condonation', checked);
                                    if (!checked) {
                                        form.setData('condonation_minimum_percent', '');
                                        form.setData('maximum_condonable_shortage_percent', '');
                                    }
                                }}
                            />

                            {form.data.allow_condonation && (
                                <div className="grid gap-4 border-t pt-4 md:grid-cols-2">
                                    <Field
                                        label="Condonation Minimum Attendance %"
                                        error={form.errors.condonation_minimum_percent}
                                    >
                                        <Input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            disabled={!editable}
                                            value={form.data.condonation_minimum_percent}
                                            onChange={(e) =>
                                                form.setData(
                                                    'condonation_minimum_percent',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                    <Field
                                        label="Maximum Condonable Shortage %"
                                        error={form.errors.maximum_condonable_shortage_percent}
                                    >
                                        <Input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            disabled={!editable}
                                            value={form.data.maximum_condonable_shortage_percent}
                                            onChange={(e) =>
                                                form.setData(
                                                    'maximum_condonable_shortage_percent',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Eligibility & Exceptions</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <ToggleRow
                                label="Attendance Required for Exam Eligibility"
                                description="When enabled, the future examination eligibility process must evaluate the applicable attendance rule."
                                checked={form.data.attendance_required_for_exam}
                                disabled={!editable}
                                onChange={(checked) =>
                                    form.setData('attendance_required_for_exam', checked)
                                }
                            />
                            <ToggleRow
                                label="Allow Medical / Special Exemption"
                                description="Allows a later controlled exemption workflow. This setting only permits the process; it does not grant an exemption by itself."
                                checked={form.data.allow_special_exemption}
                                disabled={!editable}
                                onChange={(checked) =>
                                    form.setData('allow_special_exemption', checked)
                                }
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle>Notes</CardTitle></CardHeader>
                        <CardContent>
                            <textarea
                                rows={4}
                                disabled={!editable}
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Optional policy notes or administrative guidance"
                            />
                            {form.errors.notes && (
                                <p className="mt-1 text-xs text-destructive">{form.errors.notes}</p>
                            )}
                        </CardContent>
                    </Card>

                    {editable && (
                        <div className="flex justify-end">
                            <Button disabled={form.processing}>
                                <Save className="size-4" />
                                {form.processing ? 'Saving…' : 'Save Attendance Policy'}
                            </Button>
                        </div>
                    )}
                </form>
            </div>
        </>
    );
}

function Field({
    label,
    error,
    children,
}: {
    label: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <label className="space-y-1.5">
            <span className="text-sm font-medium">{label}</span>
            {children}
            {error && <span className="block text-xs text-destructive">{error}</span>}
        </label>
    );
}

function ToggleRow({
    label,
    description,
    checked,
    disabled,
    onChange,
}: {
    label: string;
    description: string;
    checked: boolean;
    disabled: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <label className="flex items-start justify-between gap-4 rounded-lg border p-4">
            <span>
                <span className="block text-sm font-medium">{label}</span>
                <span className="mt-1 block text-xs text-muted-foreground">{description}</span>
            </span>
            <input
                type="checkbox"
                className="mt-1 size-4"
                checked={checked}
                disabled={disabled}
                onChange={(e) => onChange(e.target.checked)}
            />
        </label>
    );
}
