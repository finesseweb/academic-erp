import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpenCheck,
    CircleX,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type CourseOption = {
    id: number;
    name: string;
    code: string;
};

type Mapping = {
    id: number;
    course_id: number;
    discipline_id: number | null;
    specialization_id: number | null;
    discipline_name: string | null;
    specialization_name: string | null;
    course_code: string;
    course_name: string;
    display_order: number | null;
    status: 'ACTIVE' | 'INACTIVE';
};

type DisciplineOption = {
    id: number;
    name: string;
    code: string;
    specializations: {
        id: number;
        name: string;
        code: string;
    }[];
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

type Term = {
    id: number;
    sequence_no: number;
    name: string;
};

type Slot = {
    id: number;
    name: string;
    course_category_id: number;
    course_category_name: string;
    course_type_id: number;
    course_type_name: string;
    credits: string | number | null;
    selection_mode: 'MANDATORY' | 'CHOICE';
    min_selection: number | null;
    max_selection: number | null;
    status: 'ACTIVE' | 'INACTIVE';
};

type Props = {
    curriculum: Curriculum;
    term: Term;
    slot: Slot;
    mappings: Mapping[];
    availableCourses: CourseOption[];
    disciplineOptions: DisciplineOption[];
    permissions: { update: boolean };
    structureEditable: boolean;
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

export default function CurriculumCourseMappings({
    curriculum,
    term,
    slot,
    mappings,
    availableCourses,
    disciplineOptions,
    permissions,
    structureEditable,
}: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<Mapping | null>(null);
    const form = useForm({
        discipline_id: '',
        specialization_id: '',
        course_id: '',
    });

    const selectedDiscipline = disciplineOptions.find(
        (discipline) =>
            String(discipline.id) === form.data.discipline_id,
    );

    const specializationOptions =
        selectedDiscipline?.specializations ?? [];

    const selectedSpecializationId = form.data.specialization_id
        ? Number(form.data.specialization_id)
        : null;

    const mappingCourseOptions = availableCourses.filter((course) => {
        if (!form.data.discipline_id) {
            return true;
        }

        const duplicate = mappings.some((mapping) => {
            if (editing && mapping.id === editing.id) {
                return false;
            }

            return (
                mapping.course_id === course.id &&
                mapping.discipline_id === Number(form.data.discipline_id) &&
                mapping.specialization_id === selectedSpecializationId
            );
        });

        return !duplicate;
    });

    const canChangeStructure = permissions.update && structureEditable;

    const openCreate = () => {
        setEditing(null);
        form.clearErrors();
        form.setData({
            discipline_id: '',
            specialization_id: '',
            course_id: '',
        });
        setOpen(true);
    };

    const openEdit = (mapping: Mapping) => {
        setEditing(mapping);
        form.clearErrors();
        form.setData({
            discipline_id: mapping.discipline_id ? String(mapping.discipline_id) : '',
            specialization_id: mapping.specialization_id
                ? String(mapping.specialization_id)
                : '',
            course_id: String(mapping.course_id),
        });
        setOpen(true);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                form.setData({
                    discipline_id: '',
                    specialization_id: '',
                    course_id: '',
                });
                setEditing(null);
                setOpen(false);
            },
        };

        if (editing) {
            form.patch(
                `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots/${slot.id}/course-mappings/${editing.id}`,
                options,
            );
            return;
        }

        form.post(
            `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots/${slot.id}/course-mappings`,
            options,
        );
    };

    const updateOrder = (mapping: Mapping, displayOrder: number) => {
        router.patch(
            `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots/${slot.id}/course-mappings/${mapping.id}/order`,
            { display_order: displayOrder },
            { preserveScroll: true },
        );
    };

    const deleteMapping = (mapping: Mapping) => {
        if (!window.confirm(`Delete ${mapping.course_name}? This mapping will be permanently removed.`)) return;
        router.delete(`/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots/${slot.id}/course-mappings/${mapping.id}`, { preserveScroll: true });
    };

    const toggleStatus = (mapping: Mapping) => {
        const status =
            mapping.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        router.patch(
            `/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots/${slot.id}/course-mappings/${mapping.id}/status`,
            { status },
            { preserveScroll: true },
        );
    };

    const selectionText =
        slot.selection_mode === 'MANDATORY'
            ? 'Mandatory'
            : `Choice · Select ${slot.min_selection}${
                  slot.max_selection !== slot.min_selection
                      ? `–${slot.max_selection}`
                      : ''
              }`;

    return (
        <>
            <Head title={`Course / Paper Mapping - ${slot.name}`} />

            <div className="space-y-6 p-4 md:p-6">
                <div className="space-y-3">
                    <Link
                        href={`/admin/curricula/${curriculum.id}/structure/terms/${term.id}/slots`}
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft className="size-4" />
                        Curriculum Slots
                    </Link>

                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p className="flex items-center gap-2 text-sm font-medium text-primary">
                                <BookOpenCheck className="size-4" />
                                Academic structure
                            </p>
                            <h1 className="mt-1 text-2xl font-semibold sm:text-3xl">
                                Course / Paper Mapping
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Map existing Course / Subject Master records to
                                the selected Curriculum Slot.
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

                <div className="grid gap-3 md:grid-cols-5">
                    <Summary
                        label="Term / Semester"
                        value={`${term.sequence_no}. ${term.name}`}
                    />
                    <Summary label="Slot" value={slot.name} />
                    <Summary
                        label="Category / Type"
                        value={`${slot.course_category_name} / ${slot.course_type_name}`}
                    />
                    <Summary
                        label="Credits"
                        value={
                            slot.credits !== null
                                ? Number(slot.credits).toFixed(2)
                                : 'Not set'
                        }
                    />
                    <Summary label="Selection" value={selectionText} />
                </div>

                {!structureEditable && (
                    <div className="rounded-lg border border-border bg-muted/40 px-4 py-3 text-sm">
                        This curriculum version is{' '}
                        <strong>{curriculum.lifecycle_status}</strong>. Course /
                        Paper Mapping is read-only so historical curriculum
                        versions are preserved.
                    </div>
                )}

                <Card className="overflow-hidden">
                    <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div className="flex items-center gap-2">
                                <ListTree className="size-4 text-muted-foreground" />
                                <h2 className="font-semibold">
                                    Mapped Courses / Papers
                                </h2>
                            </div>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Only active courses matching this Slot's Course
                                Category and Course Type are available. Display
                                Order controls how mapped courses appear within
                                this Slot.
                            </p>
                        </div>

                        {canChangeStructure && (
                            <Button
                                type="button"
                                onClick={openCreate}
                                disabled={availableCourses.length === 0}
                            >
                                <Plus className="size-4" />
                                Map Course / Paper
                            </Button>
                        )}
                    </div>

                    {canChangeStructure && availableCourses.length === 0 && (
                        <div className="border-b bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                            No compatible active Course / Subject exists for this Slot's Course Category and Course Type.
                        </div>
                    )}

                    {mappings.length === 0 ? (
                        <div className="grid place-items-center px-6 py-16 text-center">
                            <ListTree className="size-10 text-muted-foreground" />
                            <h3 className="mt-4 font-semibold">
                                No Course / Paper mapped
                            </h3>
                            <p className="mt-1 max-w-md text-sm text-muted-foreground">
                                Map an existing compatible Course / Subject to
                                this Curriculum Slot.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3">
                                            Order
                                        </th>
                                        <th className="px-4 py-3">
                                            Discipline
                                        </th>
                                        <th className="px-4 py-3">
                                            Specialization
                                        </th>
                                        <th className="px-4 py-3">
                                            Course / Paper
                                        </th>
                                        <th className="px-4 py-3">
                                            Course Code
                                        </th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {mappings.map((mapping) => (
                                        <tr
                                            key={mapping.id}
                                            className="border-b transition-colors last:border-0 hover:bg-muted/40"
                                        >
                                            <td className="px-4 py-4">
                                                {canChangeStructure ? (
                                                    <select
                                                        value={mapping.display_order ?? 1}
                                                        onChange={(event) =>
                                                            updateOrder(
                                                                mapping,
                                                                Number(
                                                                    event.target.value,
                                                                ),
                                                            )
                                                        }
                                                        className="h-8 rounded-md border border-input bg-background px-2 text-sm"
                                                        aria-label={`Display order for ${mapping.course_name}`}
                                                    >
                                                        {mappings.map((_, index) => (
                                                            <option
                                                                key={index + 1}
                                                                value={index + 1}
                                                            >
                                                                {index + 1}
                                                            </option>
                                                        ))}
                                                    </select>
                                                ) : (
                                                    mapping.display_order ?? '—'
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                {mapping.discipline_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-4 text-muted-foreground">
                                                {mapping.specialization_name ?? '—'}
                                            </td>
                                            <td className="px-4 py-4 font-medium">
                                                {mapping.course_name}
                                            </td>
                                            <td className="px-4 py-4">
                                                <code className="rounded bg-muted px-2 py-1 text-xs text-primary">
                                                    {mapping.course_code}
                                                </code>
                                            </td>
                                            <td className="px-4 py-4">
                                                <span
                                                    className={`rounded-full px-2 py-1 text-xs ${
                                                        mapping.status ===
                                                        'ACTIVE'
                                                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                            : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {mapping.status === 'ACTIVE'
                                                        ? 'Active'
                                                        : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex justify-end gap-1">
                                                    {canChangeStructure && (
                                                        <>
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    openEdit(mapping)
                                                                }
                                                            >
                                                                <Pencil className="size-4" />
                                                                Edit
                                                            </Button>

                                                            
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() => deleteMapping(mapping)}
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
                                                                    toggleStatus(
                                                                        mapping,
                                                                    )
                                                                }
                                                            className={
                                                                mapping.status ===
                                                                'ACTIVE'
                                                                    ? 'text-destructive hover:text-destructive'
                                                                    : undefined
                                                            }
                                                        >
                                                            {mapping.status ===
                                                            'ACTIVE' ? (
                                                                <CircleX className="size-4" />
                                                            ) : (
                                                                <RotateCcw className="size-4" />
                                                            )}
                                                            {mapping.status ===
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

            {open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-lg rounded-xl border border-border bg-card text-card-foreground shadow-xl">
                        <div className="flex items-start justify-between border-b p-5">
                            <div>
                                <h2 className="text-lg font-semibold">
                                    {editing
                                        ? 'Edit Course / Paper Mapping'
                                        : 'Map Course / Paper'}
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {slot.name}
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

                        <form onSubmit={submit} className="space-y-5 p-5">
                            <div className="space-y-2">
                                <label htmlFor="discipline_id" className="text-sm font-medium">
                                    Discipline *
                                </label>
                                <Select
                                    value={form.data.discipline_id}
                                    onValueChange={(value) => {
                                        form.setData('discipline_id', value);
                                        form.setData('specialization_id', '');
                                        form.setData('course_id', '');
                                    }}
                                >
                                    <SelectTrigger id="discipline_id" className="w-full">
                                        <SelectValue placeholder="Select Discipline" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {disciplineOptions.map((discipline) => (
                                            <SelectItem key={discipline.id} value={String(discipline.id)}>
                                                {discipline.name} ({discipline.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.discipline_id && (
                                    <p className="text-xs text-destructive">
                                        {form.errors.discipline_id}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label htmlFor="specialization_id" className="text-sm font-medium">
                                    Specialization
                                </label>
                                <Select
                                    value={form.data.specialization_id || 'none'}
                                    onValueChange={(value) => {
                                        form.setData(
                                            'specialization_id',
                                            value === 'none' ? '' : value,
                                        );
                                        form.setData('course_id', '');
                                    }}
                                    disabled={!form.data.discipline_id || specializationOptions.length === 0}
                                >
                                    <SelectTrigger id="specialization_id" className="w-full">
                                        <SelectValue placeholder="No specialization" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No specialization</SelectItem>
                                        {specializationOptions.map((specialization) => (
                                            <SelectItem key={specialization.id} value={String(specialization.id)}>
                                                {specialization.name} ({specialization.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {form.errors.specialization_id && (
                                    <p className="text-xs text-destructive">
                                        {form.errors.specialization_id}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label
                                    htmlFor="course_id"
                                    className="text-sm font-medium"
                                >
                                    Course / Subject *
                                </label>

                                <Select
                                    value={form.data.course_id}
                                    onValueChange={(value) =>
                                        form.setData('course_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="course_id"
                                        className="w-full"
                                        aria-invalid={Boolean(
                                            form.errors.course_id,
                                        )}
                                    >
                                        <SelectValue placeholder="Select Course / Subject" />
                                    </SelectTrigger>

                                    <SelectContent>
                                        {mappingCourseOptions.map((course) => (
                                            <SelectItem
                                                key={course.id}
                                                value={String(course.id)}
                                            >
                                                {course.name} ({course.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                {form.errors.course_id && (
                                    <p className="text-xs text-destructive">
                                        {form.errors.course_id}
                                    </p>
                                )}

                                {form.data.discipline_id &&
                                    mappingCourseOptions.length === 0 && (
                                        <p className="text-xs text-muted-foreground">
                                            All compatible Course / Subject records are already mapped to this exact Discipline / Specialization for this Slot. Choose another Discipline / Specialization or create another compatible Course Master record.
                                        </p>
                                    )}

                                <p className="text-xs text-muted-foreground">
                                    Course Master stays reusable. Discipline
                                    and optional Specialization are assigned by
                                    this Curriculum mapping; Course Category and
                                    Course Type must still match the Slot.
                                </p>
                            </div>

                            {form.errors.curriculum && (
                                <p className="text-sm text-destructive">
                                    {form.errors.curriculum}
                                </p>
                            )}

                            <div className="flex justify-end gap-2 border-t pt-4">
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
                                          ? 'Update Mapping'
                                          : 'Map Course / Paper'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}
