import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpenCheck,
    CircleCheck,
    CircleX,
    Copy,
    Layers3,
    Pencil,
    Plus,
    RotateCcw,
    Trash2,
    Settings2,
    ShieldCheck,
    X,
} from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Term = {
    id: number;
    sequence_no: number;
    name: string;
    status: 'ACTIVE' | 'INACTIVE';
};

type Curriculum = {
    id: number;
    code: string;
    name: string;
    version: string;
    lifecycle_status: 'DRAFT' | 'ACTIVE' | 'RETIRED';
    program_template: { id: number; name: string; code: string };
    academic_session: { id: number; name: string; code: string };
    terms: Term[];
};

type CreditSummary = {
    curriculum_required_credits: number;
    curriculum_maximum_credits: number;
    missing_credit_slots: number;
    terms: {
        id: number;
        sequence_no: number;
        name: string;
        status: 'ACTIVE' | 'INACTIVE';
        required_credits: number;
        maximum_credits: number;
        slots: {
            id: number;
            name: string;
            credits: number | null;
            selection_mode: 'MANDATORY' | 'CHOICE';
            min_selection: number | null;
            max_selection: number | null;
            required_credits: number;
            maximum_credits: number;
        }[];
    }[];
};

type CloneTarget = { id: number; code: string; name: string; version: string };

type Props = {
    curriculum: Curriculum;
    creditSummary: CreditSummary;
    permissions: { update: boolean };
    structureEditable: boolean;
    cloneTargets: CloneTarget[];
};

const inputClass =
    'h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none transition-shadow focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50';

export default function CurriculumTerms({
    curriculum,
    creditSummary,
    permissions,
    structureEditable,
    cloneTargets,
}: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Term | null>(null);
    const [cloningTerm, setCloningTerm] = useState<Term | null>(null);
    const cloneTermForm = useForm({
        target_curriculum_id: '',
        sequence_no: '',
        name: '',
    });
    const [validationOpen, setValidationOpen] = useState(false);
    const [validating, setValidating] = useState(false);
    const [validationResult, setValidationResult] = useState<{
        valid: boolean;
        errors: number;
        warnings: number;
        issues: {
            severity: 'ERROR' | 'WARNING';
            code: string;
            message: string;
        }[];
    } | null>(null);

    const form = useForm({
        sequence_no: '',
        name: '',
    });

    const canChangeStructure = permissions.update && structureEditable;

    const validateStructure = async () => {
        setValidating(true);
        setValidationOpen(true);

        try {
            const response = await fetch(
                `/admin/curricula/${curriculum.id}/structure/validate`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                },
            );

            if (!response.ok) {
                throw new Error('Validation request failed.');
            }

            setValidationResult(await response.json());
        } catch {
            setValidationResult({
                valid: false,
                errors: 1,
                warnings: 0,
                issues: [
                    {
                        severity: 'ERROR',
                        code: 'VALIDATION_REQUEST_FAILED',
                        message:
                            'Unable to validate the Curriculum structure. Please try again.',
                    },
                ],
            });
        } finally {
            setValidating(false);
        }
    };

    const openCloneTerm = (term: Term) => {
        const nextSequence =
            curriculum.terms.length === 0
                ? 1
                : Math.max(
                      ...curriculum.terms.map(
                          (item) => item.sequence_no,
                      ),
                  ) + 1;

        setCloningTerm(term);
        cloneTermForm.clearErrors();
        cloneTermForm.setData({
            target_curriculum_id: String(cloneTargets.find((x) => x.id === curriculum.id)?.id ?? cloneTargets[0]?.id ?? ''),
            sequence_no: String(nextSequence),
            name: `${term.name} Copy`,
        });
    };

    const submitCloneTerm = (event: FormEvent) => {
        event.preventDefault();

        if (!cloningTerm) {
            return;
        }

        cloneTermForm.post(
            `/admin/curricula/${curriculum.id}/structure/terms/${cloningTerm.id}/clone`,
            {
                preserveScroll: true,
                onSuccess: () => setCloningTerm(null),
            },
        );
    };

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();

        const nextSequence =
            curriculum.terms.length === 0
                ? 1
                : Math.max(...curriculum.terms.map((term) => term.sequence_no)) + 1;

        form.setData('sequence_no', String(nextSequence));
        setOpen(true);
    };

    const openEdit = (term: Term) => {
        setEditing(term);
        form.setData({
            sequence_no: String(term.sequence_no),
            name: term.name,
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            form.patch(
                `/admin/curricula/${curriculum.id}/structure/terms/${editing.id}`,
                options,
            );
            return;
        }

        form.post(
            `/admin/curricula/${curriculum.id}/structure/terms`,
            options,
        );
    };

    const deleteTerm = (term: Term) => {
        if (!window.confirm(`Delete ${term.name}? Its Slots and Course Mappings will also be deleted. This cannot be undone.`)) return;
        router.delete(`/admin/curricula/${curriculum.id}/structure/terms/${term.id}`, { preserveScroll: true });
    };

    const toggleStatus = (term: Term) => {
        const status = term.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        router.patch(
            `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/status`,
            { status },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Curriculum Structure - ${curriculum.name}`} />

            <div className="space-y-6 p-4 md:p-6">
                <div className="space-y-3">
                    <Link
                        href="/admin/curricula"
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Curriculum Header
                    </Link>

                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div className="flex items-center gap-2">
                                <BookOpenCheck className="size-5 text-muted-foreground" />
                                <h1 className="text-2xl font-semibold tracking-tight">
                                    Manage Structure
                                </h1>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Curriculum → Terms / Semesters
                            </p>
                        </div>

                        <div className="rounded-lg border border-border bg-card px-4 py-3 text-sm shadow-sm">
                            <div className="font-medium">
                                {curriculum.name}
                            </div>
                            <div className="mt-1 text-muted-foreground">
                                {curriculum.code} · Version {curriculum.version}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid gap-3 md:grid-cols-3">
                    <Summary
                        label="Program Template"
                        value={`${curriculum.program_template.name} (${curriculum.program_template.code})`}
                    />
                    <Summary
                        label="Academic Session"
                        value={`${curriculum.academic_session.name} (${curriculum.academic_session.code})`}
                    />
                    <Summary
                        label="Lifecycle"
                        value={curriculum.lifecycle_status}
                    />
                </div>

                {!structureEditable && (
                    <div className="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm">
                        This curriculum version is{' '}
                        <strong>{curriculum.lifecycle_status}</strong>. Its
                        structure is read-only so historical curriculum versions
                        are preserved.
                    </div>
                )}

                <Card className="overflow-hidden">
                    <div className="border-b p-4">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <h2 className="font-semibold">
                                    Credit Summary
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Derived from active Curriculum Slots.
                                </p>
                            </div>

                            {creditSummary.missing_credit_slots > 0 && (
                                <span className="rounded-full bg-amber-500/10 px-2 py-1 text-xs text-amber-700 dark:text-amber-300">
                                    {creditSummary.missing_credit_slots} Slot(s)
                                    missing Credits
                                </span>
                            )}
                        </div>
                    </div>

                    <div className="grid gap-3 p-4 md:grid-cols-2">
                        <Summary
                            label="Required Credits"
                            value={creditSummary.curriculum_required_credits.toFixed(
                                2,
                            )}
                        />
                        <Summary
                            label="Maximum Credits"
                            value={creditSummary.curriculum_maximum_credits.toFixed(
                                2,
                            )}
                        />
                    </div>

                    <div className="overflow-x-auto border-t">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3">
                                        Term / Semester
                                    </th>
                                    <th className="px-4 py-3">
                                        Required Credits
                                    </th>
                                    <th className="px-4 py-3">
                                        Maximum Credits
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {creditSummary.terms.map((termSummary) => (
                                    <tr
                                        key={termSummary.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="px-4 py-3">
                                            {termSummary.sequence_no}.{' '}
                                            {termSummary.name}
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {termSummary.required_credits.toFixed(
                                                2,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 font-medium">
                                            {termSummary.maximum_credits.toFixed(
                                                2,
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Card className="overflow-hidden">
                    <div className="flex flex-col gap-3 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2">
                                <Layers3 className="size-4 text-muted-foreground" />
                                <h2 className="font-semibold">
                                    Terms / Semesters
                                </h2>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Define the ordered academic periods for this
                                curriculum version.
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={validateStructure}
                                disabled={validating}
                            >
                                <ShieldCheck className="size-4" />
                                {validating
                                    ? 'Validating…'
                                    : 'Validate Structure'}
                            </Button>

                            {canChangeStructure && (
                                <Button type="button" onClick={openCreate}>
                                    <Plus className="size-4" />
                                    Add Term / Semester
                                </Button>
                            )}
                        </div>
                    </div>

                    {curriculum.terms.length === 0 ? (
                        <div className="px-6 py-14 text-center">
                            <Layers3 className="mx-auto size-8 text-muted-foreground" />
                            <h3 className="mt-3 font-medium">
                                No Terms / Semesters defined
                            </h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Add the first ordered academic period for this
                                curriculum.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-4">Sequence</th>
                                        <th className="px-4 py-4">
                                            Term / Semester Name
                                        </th>
                                        <th className="px-4 py-4">Status</th>
                                        <th className="px-4 py-3 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {curriculum.terms.map((term) => (
                                        <tr
                                            key={term.id}
                                            className="border-b transition-colors last:border-0 hover:bg-muted/40"
                                        >
                                            <td className="px-4 py-4 font-medium">
                                                {term.sequence_no}
                                            </td>
                                            <td className="px-4 py-4">
                                                {term.name}
                                            </td>
                                            <td className="px-4 py-4">
                                                <span
                                                    className={`rounded-full px-2 py-1 text-xs ${
                                                        term.status === 'ACTIVE'
                                                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                            : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {term.status === 'ACTIVE'
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            router.get(
                                                                `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots`,
                                                            )
                                                        }
                                                    >
                                                        <Settings2 className="size-4" />
                                                        Slots
                                                    </Button>

                                                    {permissions.update && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                openCloneTerm(term)
                                                            }
                                                        >
                                                            <Copy className="size-4" />
                                                            Clone Semester
                                                        </Button>
                                                    )}

                                                    {canChangeStructure && (
                                                        <>
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    openEdit(term)
                                                                }
                                                            >
                                                                <Pencil className="size-4" />
                                                                Edit
                                                            </Button>


                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() => deleteTerm(term)}
                                                                className="text-destructive hover:text-destructive"
                                                            >
                                                                <Trash2 className="size-4" />
                                                                Delete
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    toggleStatus(term)
                                                                }
                                                                className={
                                                                    term.status ===
                                                                    'ACTIVE'
                                                                        ? 'text-destructive hover:text-destructive'
                                                                        : undefined
                                                                }
                                                            >
                                                                {term.status ===
                                                                'ACTIVE' ? (
                                                                    <CircleX className="size-4" />
                                                                ) : (
                                                                    <RotateCcw className="size-4" />
                                                                )}
                                                                {term.status ===
                                                                'ACTIVE'
                                                                    ? 'Set Inactive'
                                                                    : 'Set Active'}
                                                            </Button>
                                                        </>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>

            {cloningTerm && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-md rounded-xl border border-border bg-card shadow-xl">
                        <div className="flex items-start justify-between border-b p-5">
                            <div>
                                <h2 className="text-lg font-semibold">
                                    Clone Semester
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Copies all Slots, Credits, rules and Course Mappings from {cloningTerm.name} into the selected compatible Draft Curriculum version.
                                </p>
                            </div>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                onClick={() => setCloningTerm(null)}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>

                        <form
                            onSubmit={submitCloneTerm}
                            className="space-y-4 p-5"
                        >
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Target Curriculum Version *</label>
                                <Select value={cloneTermForm.data.target_curriculum_id} onValueChange={(value) => cloneTermForm.setData('target_curriculum_id', value)}>
                                    <SelectTrigger><SelectValue placeholder="Select Draft Curriculum" /></SelectTrigger>
                                    <SelectContent>
                                        {cloneTargets.map((target) => (
                                            <SelectItem key={target.id} value={String(target.id)}>
                                                {target.name} — {target.code} — V{target.version}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {cloneTermForm.errors.target_curriculum_id && <p className="text-xs text-destructive">{cloneTermForm.errors.target_curriculum_id}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Sequence *
                                </label>
                                <Input
                                    type="number"
                                    min="1"
                                    value={cloneTermForm.data.sequence_no}
                                    onChange={(event) =>
                                        cloneTermForm.setData(
                                            'sequence_no',
                                            event.target.value,
                                        )
                                    }
                                />
                                {cloneTermForm.errors.sequence_no && (
                                    <p className="text-xs text-destructive">
                                        {cloneTermForm.errors.sequence_no}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Term / Semester Name *
                                </label>
                                <Input
                                    value={cloneTermForm.data.name}
                                    onChange={(event) =>
                                        cloneTermForm.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                />
                                {cloneTermForm.errors.name && (
                                    <p className="text-xs text-destructive">
                                        {cloneTermForm.errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="flex justify-end gap-2 border-t pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setCloningTerm(null)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={cloneTermForm.processing}
                                >
                                    <Copy className="size-4" />
                                    {cloneTermForm.processing
                                        ? 'Cloning…'
                                        : 'Clone Semester'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {validationOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-border bg-card text-card-foreground shadow-xl">
                        <div className="flex items-start justify-between border-b p-5">
                            <div>
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="size-5 text-muted-foreground" />
                                    <h2 className="text-lg font-semibold">
                                        Curriculum Structure Validation
                                    </h2>
                                </div>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Checks the complete Curriculum structure,
                                    including Terms, Slots, Course Mapping,
                                    Discipline / Specialization and Credits.
                                </p>
                            </div>

                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                onClick={() => setValidationOpen(false)}
                                aria-label="Close validation"
                            >
                                <X className="size-4" />
                            </Button>
                        </div>

                        <div className="space-y-4 p-5">
                            {validating ? (
                                <div className="py-8 text-center text-sm text-muted-foreground">
                                    Validating complete Curriculum structure and Credits…
                                </div>
                            ) : validationResult ? (
                                <>
                                    <div className="rounded-lg border border-border p-4">
                                        <div className="flex items-center gap-2 font-medium">
                                            {validationResult.valid && (
                                                <CircleCheck className="size-5 text-emerald-600" />
                                            )}
                                            <span>
                                                {validationResult.valid
                                                    ? 'Curriculum structure validation passed'
                                                    : 'Curriculum structure needs correction'}
                                            </span>
                                        </div>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {validationResult.errors} error(s)
                                            {' · '}
                                            {validationResult.warnings} warning(s)
                                        </p>
                                    </div>

                                    {validationResult.issues.length > 0 ? (
                                        <div className="space-y-2">
                                            {validationResult.issues.map(
                                                (issue, index) => (
                                                    <div
                                                        key={`${issue.code}-${index}`}
                                                        className="rounded-lg border border-border p-3"
                                                    >
                                                        <div
                                                            className={
                                                                issue.severity ===
                                                                'ERROR'
                                                                    ? 'text-xs font-semibold text-destructive'
                                                                    : 'text-xs font-semibold text-amber-600'
                                                            }
                                                        >
                                                            {issue.severity}
                                                            {' · '}
                                                            {issue.code}
                                                        </div>
                                                        <p className="mt-1 text-sm">
                                                            {issue.message}
                                                        </p>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    ) : (
                                        <div className="rounded-lg border border-border bg-muted/30 p-4 text-sm">
                                            No structural or Credit issues were found.
                                        </div>
                                    )}
                                </>
                            ) : null}
                        </div>

                        <div className="flex justify-end border-t p-5">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setValidationOpen(false)}
                            >
                                Close
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-lg rounded-xl border border-border bg-card shadow-xl">
                        <div className="flex items-center justify-between border-b border-border px-5 py-4">
                            <div>
                                <h2 className="text-lg font-semibold">
                                    {editing
                                        ? 'Edit Term / Semester'
                                        : 'Add Term / Semester'}
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Curriculum structure for {curriculum.name}
                                </p>
                            </div>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                onClick={() => setOpen(false)}
                                aria-label="Close"
                            >
                                <X className="size-4" />
                            </Button>
                        </div>

                        <form
                            onSubmit={submit}
                            className="space-y-4 p-5"
                        >
                            <Field
                                label="Sequence"
                                error={form.errors.sequence_no}
                            >
                                <input
                                    type="number"
                                    min={1}
                                    max={999}
                                    value={form.data.sequence_no}
                                    onChange={(event) =>
                                        form.setData(
                                            'sequence_no',
                                            event.target.value,
                                        )
                                    }
                                    className={inputClass}
                                />
                            </Field>

                            <Field
                                label="Term / Semester Name"
                                error={form.errors.name}
                            >
                                <input
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Example: Semester I"
                                    maxLength={100}
                                    className={inputClass}
                                />
                            </Field>

                            {form.errors.curriculum && (
                                <p className="text-sm text-destructive">
                                    {form.errors.curriculum}
                                </p>
                            )}

                            <div className="flex justify-end gap-2 border-t border-border pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {form.processing
                                        ? 'Saving…'
                                        : editing
                                          ? 'Update'
                                          : 'Add'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

function Summary({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <Card>
            <CardContent className="pt-6">
                <div className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {label}
                </div>
                <div className="mt-1 text-sm font-medium">{value}</div>
            </CardContent>
        </Card>
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
                <span className="block text-xs text-destructive">
                    {error}
                </span>
            )}
        </label>
    );
}
