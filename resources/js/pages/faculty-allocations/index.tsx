import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Plus, Power, UsersRound } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import { Textarea } from '@/components/ui/textarea';
import { SearchableSelect } from '@/components/ui/searchable-select';
import type { SearchableSelectOption } from '@/components/ui/searchable-select';

type Faculty = {
    id: number;
    name: string;
    email: string;
    roles: { name: string }[];
};
type Section = {
    id: number;
    batch_id: number;
    name: string;
    code: string;
    status: string;
};
type Offering = {
    id: number;
    batch_id: number;
    status: string;
    batch: {
        id: number;
        name: string;
        code: string;
        status: string;
        sections: Section[];
        offering: {
            id: number;
            program_template: { name: string; code: string };
            academic_session: {
                id: number;
                name: string;
                code: string;
                is_current: boolean;
            };
        };
    };
    curriculum_course_mapping: {
        course: { name: string; code: string };
        slot: {
            name: string;
            term: { id: number; name: string; sequence_no: number };
        };
        discipline?: { id: number; name: string; code: string } | null;
        specialization?: { id: number; name: string; code: string } | null;
    };
};
type Allocation = {
    id: number;
    course_offering_id: number;
    section_id: number | null;
    faculty_user_id: number;
    teaching_role: string;
    weekly_load: string | null;
    status: string;
    notes: string | null;
    faculty: Faculty;
    section: Section | null;
};
type Props = {
    college: { id: number; name: string; code: string; status: string };
    courseOfferings: Offering[];
    faculty: Faculty[];
    allocations: Allocation[];
    can: {
        create: boolean;
        update: boolean;
        enable: boolean;
        disable: boolean;
    };
};

function AllocationForm({
    collegeId,
    offerings,
    faculty,
    row,
}: {
    collegeId: number;
    offerings: Offering[];
    faculty: Faculty[];
    row?: Allocation;
}) {
    const [offeringId, setOfferingId] = useState(
        String(row?.course_offering_id ?? ''),
    );
    const offering = offerings.find((x) => String(x.id) === offeringId);
    const [sectionId, setSectionId] = useState(
        row?.section_id ? String(row.section_id) : 'batch',
    );
    const [deliveryScope, setDeliveryScope] = useState<'BATCH' | 'SECTION'>(
        row?.section_id ? 'SECTION' : 'BATCH',
    );
    const [facultyId, setFacultyId] = useState(
        String(row?.faculty_user_id ?? ''),
    );
    const [role, setRole] = useState(row?.teaching_role ?? 'PRIMARY');
    const selectedRow = offerings.find(
        (item) => item.id === row?.course_offering_id,
    );
    const [sessionId, setSessionId] = useState(
        String(
            selectedRow?.batch.offering.academic_session.id ??
                offerings.find(
                    (item) => item.batch.offering.academic_session.is_current,
                )?.batch.offering.academic_session.id ??
                offerings[0]?.batch.offering.academic_session.id ??
                '',
        ),
    );
    const sessionOfferings = offerings.filter(
        (item) => String(item.batch.offering.academic_session.id) === sessionId,
    );
    const [programOfferingId, setProgramOfferingId] = useState(
        String(
            selectedRow?.batch.offering.id ??
                sessionOfferings[0]?.batch.offering.id ??
                '',
        ),
    );
    const programOfferings = sessionOfferings.filter(
        (item) => String(item.batch.offering.id) === programOfferingId,
    );
    const disciplineKey = (item: Offering) =>
        item.curriculum_course_mapping.discipline
            ? String(item.curriculum_course_mapping.discipline.id)
            : 'common';
    const [disciplineId, setDisciplineId] = useState(
        selectedRow ? disciplineKey(selectedRow) : '',
    );
    const disciplineOfferings = programOfferings.filter(
        (item) => disciplineKey(item) === disciplineId,
    );
    const [termId, setTermId] = useState(
        String(selectedRow?.curriculum_course_mapping.slot.term.id ?? ''),
    );
    const termOfferings = disciplineOfferings.filter(
        (item) =>
            String(item.curriculum_course_mapping.slot.term.id) === termId,
    );
    const uniqueOptions = (items: SearchableSelectOption[]) =>
        Array.from(new Map(items.map((item) => [item.value, item])).values());
    const sessionOptions = uniqueOptions(
        offerings.map((item) => ({
            value: String(item.batch.offering.academic_session.id),
            label: `${item.batch.offering.academic_session.name}${item.batch.offering.academic_session.is_current ? ' · Current' : ''}`,
            searchText: item.batch.offering.academic_session.code,
        })),
    );
    const programOptions = uniqueOptions(
        sessionOfferings.map((item) => ({
            value: String(item.batch.offering.id),
            label: `${item.batch.offering.program_template.name} (${item.batch.offering.program_template.code})`,
        })),
    );
    const disciplineOptions = uniqueOptions(
        programOfferings.map((item) => ({
            value: disciplineKey(item),
            label: item.curriculum_course_mapping.discipline
                ? `${item.curriculum_course_mapping.discipline.name} (${item.curriculum_course_mapping.discipline.code})`
                : 'Common / All Disciplines',
        })),
    );
    const termOptions = uniqueOptions(
        [...disciplineOfferings]
            .sort(
                (left, right) =>
                    left.curriculum_course_mapping.slot.term.sequence_no -
                        right.curriculum_course_mapping.slot.term.sequence_no ||
                    left.curriculum_course_mapping.slot.term.name.localeCompare(
                        right.curriculum_course_mapping.slot.term.name,
                        undefined,
                        { numeric: true },
                    ),
            )
            .map((item) => ({
                value: String(item.curriculum_course_mapping.slot.term.id),
                label: item.curriculum_course_mapping.slot.term.name,
                searchText: String(
                    item.curriculum_course_mapping.slot.term.sequence_no,
                ),
            })),
    );
    const courseOptions = termOfferings.map((item) => ({
        value: String(item.id),
        label: `${item.curriculum_course_mapping.course.code} · ${item.curriculum_course_mapping.course.name}`,
        description: `${item.batch.name} (${item.batch.code})${item.curriculum_course_mapping.specialization ? ` · ${item.curriculum_course_mapping.specialization.name}` : ''} · ${item.status}`,
    }));
    const facultyOptions = faculty.map((item) => ({
        value: String(item.id),
        label: item.name,
        description: `${item.email} · ${item.roles.map((roleItem) => roleItem.name).join(', ')}`,
        searchText: item.email,
    }));

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    size={row ? 'icon' : 'sm'}
                    variant={row ? 'ghost' : 'default'}
                >
                    {row ? (
                        <Pencil />
                    ) : (
                        <>
                            <Plus />
                            Allocate Faculty
                        </>
                    )}
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-2xl">
                <DialogTitle>
                    {row ? 'Edit Faculty Allocation' : 'Allocate Faculty'}
                </DialogTitle>
                <DialogDescription>
                    Allocate an active College Faculty user to an existing
                    Course Offering. Section is optional; Batch-wide allocation
                    applies across the offering.
                </DialogDescription>
                <Form
                    method={row ? 'patch' : 'post'}
                    action={
                        row
                            ? `/college/${collegeId}/faculty-allocations/${row.id}`
                            : `/college/${collegeId}/faculty-allocations`
                    }
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="rounded-lg border bg-muted/15 p-4">
                                <p className="mb-3 text-sm font-medium">
                                    Academic delivery context
                                </p>
                                <div className="grid gap-4 md:grid-cols-2">
                                    <SearchableSelect
                                        label="Academic Session"
                                        value={sessionId}
                                        options={sessionOptions}
                                        placeholder="Choose session"
                                        onValueChange={(value) => {
                                            const next = offerings.filter(
                                                (item) =>
                                                    String(
                                                        item.batch.offering
                                                            .academic_session
                                                            .id,
                                                    ) === value,
                                            );
                                            setSessionId(value);
                                            setProgramOfferingId(
                                                String(
                                                    next[0]?.batch.offering
                                                        .id ?? '',
                                                ),
                                            );
                                            setDisciplineId('');
                                            setTermId('');
                                            setOfferingId('');
                                            setSectionId('batch');
                                            setDeliveryScope('BATCH');
                                        }}
                                    />
                                    <SearchableSelect
                                        label="Program Offering"
                                        value={programOfferingId}
                                        options={programOptions}
                                        placeholder="Choose program offering"
                                        disabled={!sessionId}
                                        onValueChange={(value) => {
                                            setProgramOfferingId(value);
                                            setDisciplineId('');
                                            setTermId('');
                                            setOfferingId('');
                                            setSectionId('batch');
                                            setDeliveryScope('BATCH');
                                        }}
                                    />
                                    <SearchableSelect
                                        label="Discipline"
                                        value={disciplineId}
                                        options={disciplineOptions}
                                        placeholder="Choose discipline"
                                        disabled={!programOfferingId}
                                        onValueChange={(value) => {
                                            setDisciplineId(value);
                                            setTermId('');
                                            setOfferingId('');
                                            setSectionId('batch');
                                            setDeliveryScope('BATCH');
                                        }}
                                    />
                                    <SearchableSelect
                                        label="Semester / Term"
                                        value={termId}
                                        options={termOptions}
                                        placeholder="Choose semester"
                                        disabled={!disciplineId}
                                        onValueChange={(value) => {
                                            setTermId(value);
                                            setOfferingId('');
                                            setSectionId('batch');
                                            setDeliveryScope('BATCH');
                                        }}
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <SearchableSelect
                                    name="course_offering_id"
                                    label="Course Offering"
                                    value={offeringId}
                                    options={courseOptions}
                                    placeholder="Search and choose course offering"
                                    searchPlaceholder="Search course code, name, batch or specialization…"
                                    disabled={!termId}
                                    onValueChange={(value) => {
                                        setOfferingId(value);
                                        setSectionId('batch');
                                        setDeliveryScope('BATCH');
                                    }}
                                />
                                {errors.course_offering_id && (
                                    <p className="text-xs text-destructive">
                                        {errors.course_offering_id}
                                    </p>
                                )}
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <SearchableSelect
                                        name="faculty_user_id"
                                        label="Faculty"
                                        value={facultyId}
                                        options={facultyOptions}
                                        placeholder="Search and choose faculty"
                                        searchPlaceholder="Search faculty name, email or role…"
                                        onValueChange={setFacultyId}
                                    />
                                    {errors.faculty_user_id && (
                                        <p className="text-xs text-destructive">
                                            {errors.faculty_user_id}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label>Delivery Scope</Label>
                                    <input
                                        type="hidden"
                                        name="delivery_scope"
                                        value={deliveryScope}
                                    />
                                    <input
                                        type="hidden"
                                        name="section_id"
                                        value={
                                            sectionId === 'batch'
                                                ? ''
                                                : sectionId
                                        }
                                    />
                                    <Select
                                        value={deliveryScope}
                                        onValueChange={(value) => {
                                            const scope = value as
                                                'BATCH' | 'SECTION';
                                            setDeliveryScope(scope);
                                            setSectionId(
                                                scope === 'BATCH'
                                                    ? 'batch'
                                                    : '',
                                            );
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="BATCH">
                                                Entire Batch
                                            </SelectItem>
                                            <SelectItem value="SECTION">
                                                Specific Section
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {deliveryScope === 'SECTION' && (
                                        <Select
                                            value={sectionId}
                                            onValueChange={setSectionId}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select Section" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {offering?.batch.sections
                                                    .filter(
                                                        (section) =>
                                                            section.status ===
                                                            'ACTIVE',
                                                    )
                                                    .map((section) => (
                                                        <SelectItem
                                                            key={section.id}
                                                            value={String(
                                                                section.id,
                                                            )}
                                                        >
                                                            {section.name} (
                                                            {section.code})
                                                        </SelectItem>
                                                    ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                    {errors.section_id && (
                                        <p className="text-xs text-destructive">
                                            {errors.section_id}
                                        </p>
                                    )}
                                </div>
                            </div>
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Teaching Role</Label>
                                    <Select
                                        name="teaching_role"
                                        value={role}
                                        onValueChange={setRole}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="PRIMARY">
                                                Primary Faculty
                                            </SelectItem>
                                            <SelectItem value="CO_FACULTY">
                                                Co-Faculty
                                            </SelectItem>
                                            <SelectItem value="PRACTICAL">
                                                Practical / Lab
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>
                                        Maximum Teaching Hours / Week (optional)
                                    </Label>
                                    <Input
                                        name="weekly_load"
                                        type="number"
                                        min="0.25"
                                        max="168"
                                        step="0.25"
                                        defaultValue={row?.weekly_load ?? ''}
                                    />
                                    {errors.weekly_load && (
                                        <p className="text-xs text-destructive">
                                            {errors.weekly_load}
                                        </p>
                                    )}
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label>Notes (optional)</Label>
                                <Textarea
                                    name="notes"
                                    defaultValue={row?.notes ?? ''}
                                />
                            </div>
                            {errors.allocation && (
                                <p className="text-xs text-destructive">
                                    {errors.allocation}
                                </p>
                            )}
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    disabled={
                                        processing || !offeringId || !facultyId
                                    }
                                >
                                    {processing && <Spinner />}
                                    {processing ? 'Saving…' : 'Save Allocation'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function FacultyAllocations({
    college,
    courseOfferings,
    faculty,
    allocations,
    can,
}: Props) {
    const currentSessionId = String(
        courseOfferings.find(
            (item) => item.batch.offering.academic_session.is_current,
        )?.batch.offering.academic_session.id ??
            courseOfferings[0]?.batch.offering.academic_session.id ??
            'all',
    );
    const [session, setSession] = useState(currentSessionId);
    const [programOffering, setProgramOffering] = useState('all');
    const [discipline, setDiscipline] = useState('all');
    const [term, setTerm] = useState('all');
    const sessionRows = courseOfferings.filter(
        (item) =>
            session === 'all' ||
            String(item.batch.offering.academic_session.id) === session,
    );
    const programRows = sessionRows.filter(
        (item) =>
            programOffering === 'all' ||
            String(item.batch.offering.id) === programOffering,
    );
    const disciplineRows = programRows.filter(
        (item) =>
            discipline === 'all' ||
            String(
                item.curriculum_course_mapping.discipline?.id ?? 'common',
            ) === discipline,
    );
    const listSessionOptions = [
        { value: 'all', label: 'All Academic Sessions' },
        ...Array.from(
            new Map(
                courseOfferings.map((item) => [
                    String(item.batch.offering.academic_session.id),
                    {
                        value: String(item.batch.offering.academic_session.id),
                        label: `${item.batch.offering.academic_session.name}${item.batch.offering.academic_session.is_current ? ' · Current' : ''}`,
                        searchText: item.batch.offering.academic_session.code,
                    },
                ]),
            ).values(),
        ),
    ];
    const listProgramOptions = [
        { value: 'all', label: 'All Program Offerings' },
        ...Array.from(
            new Map(
                sessionRows.map((item) => [
                    String(item.batch.offering.id),
                    {
                        value: String(item.batch.offering.id),
                        label: `${item.batch.offering.program_template.name} (${item.batch.offering.program_template.code})`,
                    },
                ]),
            ).values(),
        ),
    ];
    const listDisciplineOptions = [
        { value: 'all', label: 'All Disciplines' },
        ...Array.from(
            new Map(
                programRows.map((item) => [
                    String(
                        item.curriculum_course_mapping.discipline?.id ??
                            'common',
                    ),
                    {
                        value: String(
                            item.curriculum_course_mapping.discipline?.id ??
                                'common',
                        ),
                        label:
                            item.curriculum_course_mapping.discipline?.name ??
                            'Common / All Disciplines',
                        searchText:
                            item.curriculum_course_mapping.discipline?.code,
                    },
                ]),
            ).values(),
        ),
    ];
    const listTermOptions = [
        { value: 'all', label: 'All Semesters / Terms' },
        ...Array.from(
            new Map(
                [...disciplineRows]
                    .sort(
                        (left, right) =>
                            left.curriculum_course_mapping.slot.term
                                .sequence_no -
                                right.curriculum_course_mapping.slot.term
                                    .sequence_no ||
                            left.curriculum_course_mapping.slot.term.name.localeCompare(
                                right.curriculum_course_mapping.slot.term.name,
                                undefined,
                                { numeric: true },
                            ),
                    )
                    .map((item) => [
                        String(item.curriculum_course_mapping.slot.term.id),
                        {
                            value: String(
                                item.curriculum_course_mapping.slot.term.id,
                            ),
                            label: item.curriculum_course_mapping.slot.term
                                .name,
                            searchText: String(
                                item.curriculum_course_mapping.slot.term
                                    .sequence_no,
                            ),
                        },
                    ]),
            ).values(),
        ),
    ];
    const visible = allocations.filter((a) => {
        const o = courseOfferings.find((x) => x.id === a.course_offering_id);

        return (
            o &&
            (session === 'all' ||
                String(o.batch.offering.academic_session.id) === session) &&
            (programOffering === 'all' ||
                String(o.batch.offering.id) === programOffering) &&
            (discipline === 'all' ||
                String(
                    o.curriculum_course_mapping.discipline?.id ?? 'common',
                ) === discipline) &&
            (term === 'all' ||
                String(o.curriculum_course_mapping.slot.term.id) === term)
        );
    });

    return (
        <>
            <Head title={`${college.name} Faculty Allocation`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {college.code} · Course Delivery
                        </p>
                        <h1 className="text-3xl font-semibold">
                            Faculty Allocation
                        </h1>
                        <p className="max-w-4xl text-muted-foreground">
                            Assign active College Faculty to Course Offerings at
                            Batch or Section scope.
                        </p>
                    </div>
                    {can.create && college.status === 'ACTIVE' && (
                        <AllocationForm
                            collegeId={college.id}
                            offerings={courseOfferings}
                            faculty={faculty}
                        />
                    )}
                </header>
                {faculty.length === 0 && (
                    <Card>
                        <CardContent className="space-y-3 p-4 text-sm">
                            <div>
                                <p className="font-medium">
                                    No eligible Faculty found
                                </p>
                                <p className="text-muted-foreground">
                                    Create the person as College Staff, grant{' '}
                                    <code>
                                        college_faculty_allocation.eligible
                                    </code>{' '}
                                    to a College role such as Faculty, then
                                    assign that role to the user.
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button asChild size="sm" variant="outline">
                                    <Link href={`/college/${college.id}/users`}>
                                        Open College Users
                                    </Link>
                                </Button>
                                <Button asChild size="sm" variant="outline">
                                    <Link href={`/college/${college.id}/roles`}>
                                        Open College Roles
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <UsersRound className="size-5" />
                            Teaching Assignments
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <SearchableSelect
                                label="Academic Session"
                                value={session}
                                options={listSessionOptions}
                                onValueChange={(value) => {
                                    setSession(value);
                                    setProgramOffering('all');
                                    setDiscipline('all');
                                    setTerm('all');
                                }}
                            />
                            <SearchableSelect
                                label="Program Offering"
                                value={programOffering}
                                options={listProgramOptions}
                                onValueChange={(value) => {
                                    setProgramOffering(value);
                                    setDiscipline('all');
                                    setTerm('all');
                                }}
                            />
                            <SearchableSelect
                                label="Discipline"
                                value={discipline}
                                options={listDisciplineOptions}
                                onValueChange={(value) => {
                                    setDiscipline(value);
                                    setTerm('all');
                                }}
                            />
                            <SearchableSelect
                                label="Semester / Term"
                                value={term}
                                options={listTermOptions}
                                onValueChange={setTerm}
                            />
                        </div>
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[980px] text-sm">
                                <thead className="bg-muted/30">
                                    <tr>
                                        {[
                                            'Course / Offering',
                                            'Batch / Section',
                                            'Faculty',
                                            'Role / Load',
                                            'Status',
                                            'Actions',
                                        ].map((x) => (
                                            <th
                                                key={x}
                                                className="p-3 text-left"
                                            >
                                                {x}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {visible.map((a) => {
                                        const o = courseOfferings.find(
                                            (x) =>
                                                x.id === a.course_offering_id,
                                        )!;
                                        const target =
                                            a.status === 'ACTIVE'
                                                ? 'INACTIVE'
                                                : 'ACTIVE';

                                        return (
                                            <tr key={a.id} className="border-t">
                                                <td className="p-3">
                                                    <div className="font-medium">
                                                        {
                                                            o
                                                                .curriculum_course_mapping
                                                                .course.name
                                                        }
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {
                                                            o
                                                                .curriculum_course_mapping
                                                                .course.code
                                                        }{' '}
                                                        ·{' '}
                                                        {
                                                            o
                                                                .curriculum_course_mapping
                                                                .slot.term.name
                                                        }
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    {o.batch.name}
                                                    <div className="text-xs text-muted-foreground">
                                                        {a.section
                                                            ? `${a.section.name} (${a.section.code})`
                                                            : 'Entire Batch'}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    <div className="font-medium">
                                                        {a.faculty.name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {a.faculty.email}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    {a.teaching_role.replace(
                                                        '_',
                                                        ' ',
                                                    )}
                                                    <div className="text-xs text-muted-foreground">
                                                        {a.weekly_load
                                                            ? `${a.weekly_load} hours / week`
                                                            : 'Load not set'}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    <Badge
                                                        variant={
                                                            a.status ===
                                                            'ACTIVE'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {a.status}
                                                    </Badge>
                                                </td>
                                                <td className="p-3">
                                                    <div className="flex gap-2">
                                                        {can.update &&
                                                            a.status ===
                                                                'INACTIVE' && (
                                                                <AllocationForm
                                                                    collegeId={
                                                                        college.id
                                                                    }
                                                                    offerings={
                                                                        courseOfferings
                                                                    }
                                                                    faculty={
                                                                        faculty
                                                                    }
                                                                    row={a}
                                                                />
                                                            )}{' '}
                                                        {((target ===
                                                            'ACTIVE' &&
                                                            can.enable) ||
                                                            (target ===
                                                                'INACTIVE' &&
                                                                can.disable)) && (
                                                            <Form
                                                                method="patch"
                                                                action={`/college/${college.id}/faculty-allocations/${a.id}/status`}
                                                            >
                                                                {({
                                                                    processing,
                                                                    errors,
                                                                }) => (
                                                                    <>
                                                                        <input
                                                                            type="hidden"
                                                                            name="status"
                                                                            value={
                                                                                target
                                                                            }
                                                                        />
                                                                        <Button
                                                                            size="sm"
                                                                            variant={
                                                                                target ===
                                                                                'ACTIVE'
                                                                                    ? 'default'
                                                                                    : 'outline'
                                                                            }
                                                                            disabled={
                                                                                processing
                                                                            }
                                                                        >
                                                                            {processing ? (
                                                                                <Spinner />
                                                                            ) : (
                                                                                <Power />
                                                                            )}
                                                                            {target ===
                                                                            'ACTIVE'
                                                                                ? 'Activate'
                                                                                : 'Deactivate'}
                                                                        </Button>
                                                                        {errors.status && (
                                                                            <p className="text-xs text-destructive">
                                                                                {
                                                                                    errors.status
                                                                                }
                                                                            </p>
                                                                        )}
                                                                    </>
                                                                )}
                                                            </Form>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {visible.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="p-10 text-center text-muted-foreground"
                                            >
                                                No Faculty Allocations found.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
