import { Head, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
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
    name: string;
    code: string;
    version: string;
    lifecycle_status: string;
    approval_status: string;
    scope_type: string;
    academic_session: { name: string; code: string };
    program_template?: { name: string; code: string } | null;
    curriculum?: { name: string; code: string; version: string } | null;
};

type Rule = {
    minimum_total_credits?: string | number | null;
    minimum_completion_cgpa?: string | number | null;
    maximum_program_duration_months?: string | number | null;
    allow_credit_transfer?: boolean;
    maximum_credit_transfer_percent?: string | number | null;
    allow_credit_exemption?: boolean;
    notes?: string | null;
} | null;

type CourseCategory = {
    id: number;
    name: string;
    code: string;
    category_group?: string | null;
};

type CategoryRequirement = {
    id?: number;
    course_category_id: number | string;
    minimum_credits: string | number;
    maximum_credits: string | number | null;
    display_order: number;
    course_category?: CourseCategory;
};

type FormCategoryRequirement = {
    course_category_id: string;
    minimum_credits: string;
    maximum_credits: string;
    display_order: number;
};

export default function CreditCompletion({
    policy,
    rule,
    categoryRequirements,
    courseCategories,
    editable,
}: {
    policy: Policy;
    rule: Rule;
    categoryRequirements: CategoryRequirement[];
    courseCategories: CourseCategory[];
    editable: boolean;
}) {
    const form = useForm({
        minimum_total_credits: rule?.minimum_total_credits ?? '',
        minimum_completion_cgpa: rule?.minimum_completion_cgpa ?? '',
        maximum_program_duration_months:
            rule?.maximum_program_duration_months ?? '',
        allow_credit_transfer: Boolean(rule?.allow_credit_transfer),
        maximum_credit_transfer_percent:
            rule?.maximum_credit_transfer_percent ?? '',
        allow_credit_exemption: Boolean(rule?.allow_credit_exemption),
        notes: rule?.notes ?? '',
        category_requirements: categoryRequirements.map(
            (requirement): FormCategoryRequirement => ({
                course_category_id: String(requirement.course_category_id),
                minimum_credits: String(requirement.minimum_credits ?? ''),
                maximum_credits:
                    requirement.maximum_credits === null ||
                    requirement.maximum_credits === undefined
                        ? ''
                        : String(requirement.maximum_credits),
                display_order: requirement.display_order,
            }),
        ),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/admin/academic-policies/${policy.id}/credit-completion`, {
            preserveScroll: true,
        });
    };

    const addRequirement = () => {
        form.setData('category_requirements', [
            ...form.data.category_requirements,
            {
                course_category_id: '',
                minimum_credits: '',
                maximum_credits: '',
                display_order: form.data.category_requirements.length + 1,
            },
        ]);
    };

    const updateRequirement = (
        index: number,
        field: keyof FormCategoryRequirement,
        value: string | number,
    ) => {
        const next = [...form.data.category_requirements];
        next[index] = { ...next[index], [field]: value };
        form.setData('category_requirements', next);
    };

    const removeRequirement = (index: number) => {
        form.setData(
            'category_requirements',
            form.data.category_requirements
                .filter((_, rowIndex) => rowIndex !== index)
                .map((requirement, rowIndex) => ({
                    ...requirement,
                    display_order: rowIndex + 1,
                })),
        );
    };

    const selectedCategoryIds = form.data.category_requirements
        .map((requirement) => requirement.course_category_id)
        .filter(Boolean);

    return (
        <>
            <Head title={`Credit / Completion - ${policy.name}`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="border-b pb-5">
                    <Button
                        variant="ghost"
                        className="mb-3 -ml-2"
                        onClick={() => router.get('/admin/academic-policies')}
                    >
                        <ArrowLeft className="size-4" />
                        Academic Policies
                    </Button>
                    <p className="text-sm font-medium text-primary">
                        {policy.code} · Version {policy.version}
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold sm:text-3xl">
                        Credit / Completion Policy
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {policy.name} · {policy.academic_session.name} ·{' '}
                        {policy.scope_type.replace('_', ' ')}
                    </p>
                    {!editable && (
                        <p className="mt-3 rounded-md border bg-muted/30 p-3 text-sm text-muted-foreground">
                            This policy is read-only because it is not an editable
                            Draft.
                        </p>
                    )}
                </header>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>General Completion Requirements</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 md:grid-cols-3">
                            <Field
                                label="Minimum Total Credits"
                                error={form.errors.minimum_total_credits}
                            >
                                <Input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    disabled={!editable}
                                    value={form.data.minimum_total_credits}
                                    onChange={(event) =>
                                        form.setData(
                                            'minimum_total_credits',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Minimum Completion CGPA"
                                error={form.errors.minimum_completion_cgpa}
                            >
                                <Input
                                    type="number"
                                    min="0"
                                    max="10"
                                    step="0.01"
                                    disabled={!editable}
                                    value={form.data.minimum_completion_cgpa}
                                    onChange={(event) =>
                                        form.setData(
                                            'minimum_completion_cgpa',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Maximum Program Duration (Months)"
                                error={
                                    form.errors.maximum_program_duration_months
                                }
                            >
                                <Input
                                    type="number"
                                    min="1"
                                    disabled={!editable}
                                    value={
                                        form.data.maximum_program_duration_months
                                    }
                                    onChange={(event) =>
                                        form.setData(
                                            'maximum_program_duration_months',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-start justify-between gap-4">
                            <div>
                                <CardTitle>Credit Category Requirements</CardTitle>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Add only categories that have a completion
                                    threshold. Categories come from Course Category
                                    Master; nothing is hard-coded here.
                                </p>
                            </div>
                            {editable && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={addRequirement}
                                >
                                    <Plus className="size-4" />
                                    Add Requirement
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {form.data.category_requirements.length === 0 ? (
                                <div className="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                    No Course Category minimum has been defined. Add
                                    a row only when this policy requires a category
                                    threshold.
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {form.data.category_requirements.map(
                                        (requirement, index) => {
                                            const categoryError = (form.errors as Record<string, string>)[
                                                `category_requirements.${index}.course_category_id`
                                            ];
                                            const minimumError = (form.errors as Record<string, string>)[
                                                `category_requirements.${index}.minimum_credits`
                                            ];
                                            const maximumError = (form.errors as Record<string, string>)[
                                                `category_requirements.${index}.maximum_credits`
                                            ];

                                            return (
                                                <div
                                                    key={`${index}-${requirement.course_category_id}`}
                                                    className="grid gap-3 rounded-lg border p-4 md:grid-cols-[1.4fr_0.7fr_0.7fr_auto] md:items-start"
                                                >
                                                    <Field
                                                        label="Course Category"
                                                        error={categoryError}
                                                    >
                                                        <Select
                                                            disabled={!editable}
                                                            value={
                                                                requirement.course_category_id
                                                            }
                                                            onValueChange={(value) =>
                                                                updateRequirement(
                                                                    index,
                                                                    'course_category_id',
                                                                    value,
                                                                )
                                                            }
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Select category" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {courseCategories.map(
                                                                    (category) => {
                                                                        const usedElsewhere =
                                                                            selectedCategoryIds.includes(
                                                                                String(category.id),
                                                                            ) &&
                                                                            requirement.course_category_id !==
                                                                                String(category.id);

                                                                        return (
                                                                            <SelectItem
                                                                                key={category.id}
                                                                                value={String(
                                                                                    category.id,
                                                                                )}
                                                                                disabled={
                                                                                    usedElsewhere
                                                                                }
                                                                            >
                                                                                {category.name}{' '}
                                                                                ({category.code})
                                                                            </SelectItem>
                                                                        );
                                                                    },
                                                                )}
                                                            </SelectContent>
                                                        </Select>
                                                    </Field>

                                                    <Field
                                                        label="Minimum Credits"
                                                        error={minimumError}
                                                    >
                                                        <Input
                                                            type="number"
                                                            min="0.01"
                                                            step="0.01"
                                                            disabled={!editable}
                                                            value={
                                                                requirement.minimum_credits
                                                            }
                                                            onChange={(event) =>
                                                                updateRequirement(
                                                                    index,
                                                                    'minimum_credits',
                                                                    event.target.value,
                                                                )
                                                            }
                                                        />
                                                    </Field>

                                                    <Field
                                                        label="Maximum Credits"
                                                        error={maximumError}
                                                    >
                                                        <Input
                                                            type="number"
                                                            min="0.01"
                                                            step="0.01"
                                                            disabled={!editable}
                                                            placeholder="Optional"
                                                            value={
                                                                requirement.maximum_credits
                                                            }
                                                            onChange={(event) =>
                                                                updateRequirement(
                                                                    index,
                                                                    'maximum_credits',
                                                                    event.target.value,
                                                                )
                                                            }
                                                        />
                                                    </Field>

                                                    {editable && (
                                                        <Button
                                                            type="button"
                                                            size="icon"
                                                            variant="ghost"
                                                            className="mt-6 text-destructive"
                                                            aria-label="Remove credit category requirement"
                                                            onClick={() =>
                                                                removeRequirement(index)
                                                            }
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            );
                                        },
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Credit Transfer / Exemption</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <label className="flex items-center gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    disabled={!editable}
                                    checked={form.data.allow_credit_transfer}
                                    onChange={(event) =>
                                        form.setData(
                                            'allow_credit_transfer',
                                            event.target.checked,
                                        )
                                    }
                                />
                                Allow Credit Transfer
                            </label>
                            {form.data.allow_credit_transfer && (
                                <Field
                                    label="Maximum Credit Transfer (%)"
                                    error={
                                        form.errors.maximum_credit_transfer_percent
                                    }
                                >
                                    <Input
                                        className="max-w-sm"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        disabled={!editable}
                                        value={
                                            form.data.maximum_credit_transfer_percent
                                        }
                                        onChange={(event) =>
                                            form.setData(
                                                'maximum_credit_transfer_percent',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                            )}
                            <label className="flex items-center gap-3 text-sm">
                                <input
                                    type="checkbox"
                                    disabled={!editable}
                                    checked={form.data.allow_credit_exemption}
                                    onChange={(event) =>
                                        form.setData(
                                            'allow_credit_exemption',
                                            event.target.checked,
                                        )
                                    }
                                />
                                Allow Credit Exemption
                            </label>
                            <Field label="Notes" error={form.errors.notes}>
                                <textarea
                                    disabled={!editable}
                                    className="min-h-28 w-full rounded-md border bg-background px-3 py-2 text-sm disabled:opacity-60"
                                    value={form.data.notes}
                                    onChange={(event) =>
                                        form.setData('notes', event.target.value)
                                    }
                                />
                            </Field>
                        </CardContent>
                    </Card>

                    {editable && (
                        <div className="flex justify-end">
                            <Button disabled={form.processing}>
                                <Save className="size-4" />
                                {form.processing
                                    ? 'Saving…'
                                    : 'Save Credit / Completion Policy'}
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
            {error && (
                <span className="block text-xs text-destructive">{error}</span>
            )}
        </label>
    );
}
