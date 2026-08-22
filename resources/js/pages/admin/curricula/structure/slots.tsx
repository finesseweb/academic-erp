import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpenCheck,
    CircleX,
    Copy,
    Layers3,
    ListTree,
    Pencil,
    Plus,
    RotateCcw,
    Trash2,
    X,
} from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type MasterOption = {
    id: number;
    name: string;
    code: string;
};

type Slot = {
    id: number;
    course_category_id: number;
    course_category_name: string;
    course_type_id: number | null;
    course_type_name: string;
    credits: string | number | null;
    name: string;
    display_order: number;
    selection_mode: 'MANDATORY' | 'CHOICE';
    min_selection: number | null;
    max_selection: number | null;
    status: 'ACTIVE' | 'INACTIVE';
};

type Term = {
    id: number;
    sequence_no: number;
    name: string;
    status: 'ACTIVE' | 'INACTIVE';
    slots: Slot[];
};

type Curriculum = {
    id: number;
    code: string;
    name: string;
    version: string;
    lifecycle_status: 'DRAFT' | 'ACTIVE' | 'RETIRED';
    program_template: { id: number; name: string; code: string };
    academic_session: { id: number; name: string; code: string };
};

type TermOption = {
    id: number;
    sequence_no: number;
    name: string;
    next_slot_order: number;
};

type CloneTarget = { id: number; code: string; name: string; version: string };

type Props = {
    curriculum: Curriculum;
    term: Term;
    courseCategories: MasterOption[];
    courseTypes: MasterOption[];
    allTerms: TermOption[];
    permissions: { update: boolean };
    structureEditable: boolean;
    cloneTargets: CloneTarget[];
};

function Summary({ label, value }: { label: string; value: string }) {
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

export default function CurriculumSlots({
    curriculum,
    term,
    courseCategories,
    courseTypes,
    allTerms,
    permissions,
    structureEditable,
    cloneTargets,
}: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Slot | null>(null);
    const [cloningSlot, setCloningSlot] = useState<Slot | null>(null);
    const [cloneTargetTerms, setCloneTargetTerms] = useState<TermOption[]>(allTerms);
    const cloneSlotForm = useForm({
        target_curriculum_id: '',
        target_term_id: '',
        name: '',
        display_order: '',
    });

    const form = useForm({
        course_category_id: '',
        course_type_id: '',
        credits: '',
        name: '',
        display_order: '',
        selection_mode: 'MANDATORY',
        min_selection: '',
        max_selection: '',
    });

    const canChangeStructure = permissions.update && structureEditable;
    const isChoice = form.data.selection_mode === 'CHOICE';

    const loadCloneTargetTerms = async (targetCurriculumId: string) => {
        if (!targetCurriculumId) { setCloneTargetTerms([]); return; }
        const response = await fetch(`/admin/curricula/${curriculum.id}/clone-targets/${targetCurriculumId}/terms`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok) { setCloneTargetTerms([]); return; }
        const payload = await response.json();
        setCloneTargetTerms(payload.terms ?? []);
    };

    const openCloneSlot = (slot: Slot) => {
        const currentTerm = allTerms.find(
            (item) => item.id === term.id,
        );

        setCloningSlot(slot);
        setCloneTargetTerms(allTerms);
        cloneSlotForm.clearErrors();
        cloneSlotForm.setData({
            target_curriculum_id: String(curriculum.id),
            target_term_id: String(term.id),
            name: `${slot.name} Copy`,
            display_order: String(
                currentTerm?.next_slot_order ?? 1,
            ),
        });
    };

    const submitCloneSlot = (event: FormEvent) => {
        event.preventDefault();

        if (!cloningSlot) {
            return;
        }

        cloneSlotForm.post(
            `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots/${cloningSlot.id}/clone`,
            {
                preserveScroll: true,
                onSuccess: () => setCloningSlot(null),
            },
        );
    };

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();

        const nextOrder =
            term.slots.length === 0
                ? 1
                : Math.max(...term.slots.map((slot) => slot.display_order)) + 1;

        form.setData({
            course_category_id: '',
            course_type_id: '',
            credits: '',
            name: '',
            display_order: String(nextOrder),
            selection_mode: 'MANDATORY',
            min_selection: '',
            max_selection: '',
        });

        setOpen(true);
    };

    const openEdit = (slot: Slot) => {
        setEditing(slot);
        form.setData({
            course_category_id: String(slot.course_category_id),
            course_type_id: slot.course_type_id
                ? String(slot.course_type_id)
                : '',
            credits:
                slot.credits !== null
                    ? String(slot.credits)
                    : '',
            name: slot.name,
            display_order: String(slot.display_order),
            selection_mode: slot.selection_mode,
            min_selection:
                slot.min_selection !== null ? String(slot.min_selection) : '',
            max_selection:
                slot.max_selection !== null ? String(slot.max_selection) : '',
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
                `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots/${editing.id}`,
                options,
            );
            return;
        }

        form.post(
            `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots`,
            options,
        );
    };

    const deleteSlot = (slot: Slot) => {
        if (!window.confirm(`Delete ${slot.name}? Its Course Mappings will also be deleted. This cannot be undone.`)) return;
        router.delete(`/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots/${slot.id}`, { preserveScroll: true });
    };

    const toggleStatus = (slot: Slot) => {
        const status = slot.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        router.patch(
            `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots/${slot.id}/status`,
            { status },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Curriculum Slots - ${term.name}`} />

            <div className="space-y-6 p-4 md:p-6">
                <div className="space-y-3">
                    <Link
                        href={`/admin/curricula/${curriculum.id}/structure/terms`}
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Terms / Semesters
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
                                Curriculum → Terms / Semesters → Curriculum Slots
                            </p>
                        </div>

                        <div className="rounded-lg border border-border bg-card px-4 py-3 text-sm shadow-sm">
                            <div className="font-medium">{curriculum.name}</div>
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
                        label="Term / Semester"
                        value={`${term.sequence_no}. ${term.name}`}
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
                    <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2">
                                <Layers3 className="size-4 text-muted-foreground" />
                                <h2 className="font-semibold">Curriculum Slots</h2>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Define category, type, credits, ordering and
                                selection behavior for this Term / Semester.
                            </p>
                        </div>

                        {canChangeStructure && (
                            <Button
                                type="button"
                                onClick={openCreate}
                                disabled={
                                    courseCategories.length === 0 ||
                                    courseTypes.length === 0
                                }
                            >
                                <Plus className="size-4" />
                                Add Slot
                            </Button>
                        )}
                    </div>

                    {(courseCategories.length === 0 ||
                        courseTypes.length === 0) &&
                        canChangeStructure && (
                            <div className="border-b bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                                An active Course Category and Course Type are
                                required before adding Curriculum Slots.
                            </div>
                        )}

                    {term.slots.length === 0 ? (
                        <div className="px-6 py-14 text-center">
                            <Layers3 className="mx-auto size-8 text-muted-foreground" />
                            <h3 className="mt-3 font-medium">
                                No Curriculum Slots defined
                            </h3>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Add the first slot for {term.name}.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3">Order</th>
                                        <th className="px-4 py-3">
                                            Course Category
                                        </th>
                                        <th className="px-4 py-3">Slot Name</th>
                                        <th className="px-4 py-3">
                                            Course Type
                                        </th>
                                        <th className="px-4 py-3">
                                            Credits
                                        </th>
                                        <th className="px-4 py-3">
                                            Selection
                                        </th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {term.slots.map((slot) => (
                                        <tr
                                            key={slot.id}
                                            className="border-b transition-colors last:border-0 hover:bg-muted/40"
                                        >
                                            <td className="px-4 py-4 font-medium">
                                                {slot.display_order}
                                            </td>
                                            <td className="px-4 py-4">
                                                {slot.course_category_name}
                                            </td>
                                            <td className="px-4 py-4">
                                                {slot.name}
                                            </td>
                                            <td className="px-4 py-4">
                                                {slot.course_type_name}
                                            </td>
                                            <td className="px-4 py-4 font-medium">
                                                {slot.credits !== null
                                                    ? Number(slot.credits).toFixed(2)
                                                    : '—'}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="font-medium">
                                                    {slot.selection_mode ===
                                                    'MANDATORY'
                                                        ? 'Mandatory'
                                                        : 'Choice'}
                                                </div>
                                                {slot.selection_mode ===
                                                    'CHOICE' && (
                                                    <div className="text-xs text-muted-foreground">
                                                        Select {slot.min_selection}
                                                        {slot.max_selection !==
                                                        slot.min_selection
                                                            ? `–${slot.max_selection}`
                                                            : ''}{' '}
                                                        course(s)
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                <span
                                                    className={`rounded-full px-2 py-1 text-xs ${
                                                        slot.status === 'ACTIVE'
                                                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                            : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {slot.status === 'ACTIVE'
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
                                                                `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots/${slot.id}/course-mappings`,
                                                            )
                                                        }
                                                    >
                                                        <ListTree className="size-4" />
                                                        Courses
                                                    </Button>

                                                    {permissions.update && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                openCloneSlot(slot)
                                                            }
                                                        >
                                                            <Copy className="size-4" />
                                                            Clone Slot
                                                        </Button>
                                                    )}

                                                    {canChangeStructure && (
                                                        <>
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    openEdit(slot)
                                                                }
                                                            >
                                                                <Pencil className="size-4" />
                                                                Edit
                                                            </Button>


                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() => deleteSlot(slot)}
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
                                                                    toggleStatus(slot)
                                                                }
                                                                className={
                                                                    slot.status ===
                                                                    'ACTIVE'
                                                                        ? 'text-destructive hover:text-destructive'
                                                                        : undefined
                                                                }
                                                            >
                                                                {slot.status ===
                                                                'ACTIVE' ? (
                                                                    <CircleX className="size-4" />
                                                                ) : (
                                                                    <RotateCcw className="size-4" />
                                                                )}
                                                                {slot.status ===
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

            {cloningSlot && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-md rounded-xl border border-border bg-card shadow-xl">
                        <div className="flex items-start justify-between border-b p-5">
                            <div>
                                <h2 className="text-lg font-semibold">
                                    Clone Slot
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Copies Credits, selection rules and all Course / Paper Mappings into the selected compatible Draft Curriculum version.
                                </p>
                            </div>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                onClick={() => setCloningSlot(null)}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>

                        <form
                            onSubmit={submitCloneSlot}
                            className="space-y-4 p-5"
                        >
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Target Curriculum Version *</label>
                                <Select
                                    value={cloneSlotForm.data.target_curriculum_id}
                                    onValueChange={async (value) => {
                                        cloneSlotForm.setData('target_curriculum_id', value);
                                        cloneSlotForm.setData('target_term_id', '');
                                        cloneSlotForm.setData('display_order', '');
                                        await loadCloneTargetTerms(value);
                                    }}
                                >
                                    <SelectTrigger><SelectValue placeholder="Select Draft Curriculum" /></SelectTrigger>
                                    <SelectContent>
                                        {cloneTargets.map((target) => (
                                            <SelectItem key={target.id} value={String(target.id)}>
                                                {target.name} — {target.code} — V{target.version}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {cloneSlotForm.errors.target_curriculum_id && <p className="text-xs text-destructive">{cloneSlotForm.errors.target_curriculum_id}</p>}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Target Term / Semester *
                                </label>
                                <Select
                                    value={
                                        cloneSlotForm.data.target_term_id
                                    }
                                    onValueChange={(value) => {
                                        cloneSlotForm.setData(
                                            'target_term_id',
                                            value,
                                        );

                                        const target = cloneTargetTerms.find(
                                            (item) =>
                                                String(item.id) === value,
                                        );

                                        if (target) {
                                            cloneSlotForm.setData(
                                                'display_order',
                                                String(
                                                    target.next_slot_order,
                                                ),
                                            );
                                        }
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {cloneTargetTerms.map((targetTerm) => (
                                            <SelectItem
                                                key={targetTerm.id}
                                                value={String(
                                                    targetTerm.id,
                                                )}
                                            >
                                                {targetTerm.sequence_no}.{' '}
                                                {targetTerm.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {cloneSlotForm.errors.target_term_id && (
                                    <p className="text-xs text-destructive">
                                        {cloneSlotForm.errors.target_term_id}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Slot Name *
                                </label>
                                <Input
                                    value={cloneSlotForm.data.name}
                                    onChange={(event) =>
                                        cloneSlotForm.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Display Order *
                                </label>
                                <Input
                                    type="number"
                                    min="1"
                                    value={
                                        cloneSlotForm.data.display_order
                                    }
                                    onChange={(event) =>
                                        cloneSlotForm.setData(
                                            'display_order',
                                            event.target.value,
                                        )
                                    }
                                />
                                {cloneSlotForm.errors.display_order && (
                                    <p className="text-xs text-destructive">
                                        {cloneSlotForm.errors.display_order}
                                    </p>
                                )}
                            </div>

                            <div className="flex justify-end gap-2 border-t pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setCloningSlot(null)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={cloneSlotForm.processing}
                                >
                                    <Copy className="size-4" />
                                    {cloneSlotForm.processing
                                        ? 'Cloning…'
                                        : 'Clone Slot'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-border bg-card text-card-foreground shadow-xl">
                        <div className="sticky top-0 flex items-start justify-between border-b bg-card p-5">
                            <div>
                                <h2 className="text-lg font-semibold">
                                    {editing
                                        ? 'Edit Curriculum Slot'
                                        : 'Add Curriculum Slot'}
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {term.name}
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
                            className="grid gap-5 p-5 sm:grid-cols-2"
                        >
                            <div className="space-y-2">
                                <label
                                    htmlFor="course_category_id"
                                    className="text-sm font-medium"
                                >
                                    Course Category *
                                </label>
                                <Select
                                    value={form.data.course_category_id}
                                    onValueChange={(value) =>
                                        form.setData(
                                            'course_category_id',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id="course_category_id"
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            form.errors.course_category_id,
                                        )}
                                    >
                                        <SelectValue placeholder="Select Course Category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {courseCategories.map((category) => (
                                            <SelectItem
                                                key={category.id}
                                                value={String(category.id)}
                                            >
                                                {category.name} ({category.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.course_category_id && (
                                    <p className="text-xs text-destructive">
                                        {form.errors.course_category_id}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label
                                    htmlFor="course_type_id"
                                    className="text-sm font-medium"
                                >
                                    Course Type *
                                </label>
                                <Select
                                    value={form.data.course_type_id}
                                    onValueChange={(value) =>
                                        form.setData('course_type_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="course_type_id"
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            form.errors.course_type_id,
                                        )}
                                    >
                                        <SelectValue placeholder="Select Course Type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {courseTypes.map((courseType) => (
                                            <SelectItem
                                                key={courseType.id}
                                                value={String(courseType.id)}
                                            >
                                                {courseType.name} ({courseType.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.course_type_id && (
                                    <p className="text-xs text-destructive">
                                        {form.errors.course_type_id}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label
                                    htmlFor="credits"
                                    className="text-sm font-medium"
                                >
                                    Credits *
                                </label>
                                <Input
                                    id="credits"
                                    type="number"
                                    min="0"
                                    max="99.99"
                                    step="0.01"
                                    value={form.data.credits}
                                    onChange={(event) =>
                                        form.setData(
                                            'credits',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="e.g. 4.00"
                                    aria-invalid={Boolean(
                                        form.errors.credits,
                                    )}
                                />
                                {form.errors.credits && (
                                    <p className="text-xs text-destructive">
                                        {form.errors.credits}
                                    </p>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Curriculum-specific credit value for this
                                    Slot. Credit Summary is implemented in the
                                    next milestone.
                                </p>
                            </div>

                            <div className="space-y-2">
                                <label
                                    htmlFor="slot_name"
                                    className="text-sm font-medium"
                                >
                                    Slot Name *
                                </label>
                                <Input
                                    id="slot_name"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    placeholder="e.g. Core Paper 1"
                                    aria-invalid={Boolean(form.errors.name)}
                                />
                                {form.errors.name && (
                                    <p className="text-xs text-destructive">
                                        {form.errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label
                                    htmlFor="display_order"
                                    className="text-sm font-medium"
                                >
                                    Display Order *
                                </label>
                                <Input
                                    id="display_order"
                                    type="number"
                                    min="1"
                                    value={form.data.display_order}
                                    onChange={(event) =>
                                        form.setData(
                                            'display_order',
                                            event.target.value,
                                        )
                                    }
                                    aria-invalid={Boolean(
                                        form.errors.display_order,
                                    )}
                                />
                                {form.errors.display_order && (
                                    <p className="text-xs text-destructive">
                                        {form.errors.display_order}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2 sm:col-span-2">
                                <label
                                    htmlFor="selection_mode"
                                    className="text-sm font-medium"
                                >
                                    Selection Rule *
                                </label>
                                <Select
                                    value={form.data.selection_mode}
                                    onValueChange={(value) =>
                                        form.setData(
                                            'selection_mode',
                                            value as 'MANDATORY' | 'CHOICE',
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id="selection_mode"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="MANDATORY">
                                            Mandatory
                                        </SelectItem>
                                        <SelectItem value="CHOICE">
                                            Choice
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">
                                    Mandatory means every mapped course in this
                                    slot is required. Choice means the student
                                    selects from the courses mapped later.
                                </p>
                            </div>

                            {isChoice && (
                                <>
                                    <div className="space-y-2">
                                        <label
                                            htmlFor="min_selection"
                                            className="text-sm font-medium"
                                        >
                                            Minimum Selection *
                                        </label>
                                        <Input
                                            id="min_selection"
                                            type="number"
                                            min="1"
                                            value={form.data.min_selection}
                                            onChange={(event) =>
                                                form.setData(
                                                    'min_selection',
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={Boolean(
                                                form.errors.min_selection,
                                            )}
                                        />
                                        {form.errors.min_selection && (
                                            <p className="text-xs text-destructive">
                                                {form.errors.min_selection}
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <label
                                            htmlFor="max_selection"
                                            className="text-sm font-medium"
                                        >
                                            Maximum Selection *
                                        </label>
                                        <Input
                                            id="max_selection"
                                            type="number"
                                            min="1"
                                            value={form.data.max_selection}
                                            onChange={(event) =>
                                                form.setData(
                                                    'max_selection',
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={Boolean(
                                                form.errors.max_selection,
                                            )}
                                        />
                                        {form.errors.max_selection && (
                                            <p className="text-xs text-destructive">
                                                {form.errors.max_selection}
                                            </p>
                                        )}
                                    </div>
                                </>
                            )}

                            {form.errors.curriculum && (
                                <p className="text-sm text-destructive sm:col-span-2">
                                    {form.errors.curriculum}
                                </p>
                            )}

                            <div className="flex justify-end gap-2 border-t pt-4 sm:col-span-2">
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
