import { Form, Head, router } from '@inertiajs/react';
import {
    BadgeCheck,
    ChevronLeft,
    ChevronRight,
    Fingerprint,
    MapPin,
    Search,
    Settings2,
    TicketCheck,
    X,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { useAppDialog } from '@/components/app-dialog-provider';
import { useAppLoading } from '@/components/app-loading-provider';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

type Session = { id: number; name: string; code: string; is_current: boolean };
type Section = { id: number; code: string; name: string };
type Batch = { id: number; code: string; name: string; sections: Section[] };
type Offering = {
    id: number;
    name: string | null;
    code: string | null;
    batches: Batch[];
};
type Discipline = { id: number; name: string; code: string | null };
type Row = {
    id: number;
    student_id: number;
    name: string | null;
    student_uid: string | null;
    university_roll_no: string | null;
    class_roll_no: string | null;
    programme: string | null;
    discipline: string | null;
    discipline_code: string | null;
    session: string | null;
    offering_id: number;
    batch_id: number | null;
    section_id: number | null;
    placement_complete: boolean;
    complete: boolean;
};
type Page<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
type Props = {
    college: { id: number; name: string; code: string };
    sessions: Session[];
    offerings: Offering[];
    disciplines: Discipline[];
    students: Page<Row>;
    settings: {
        student_uid_format: string;
        university_roll_format: string;
        class_roll_format: string;
        class_roll_scope: 'PROGRAMME_OFFERING' | 'DISCIPLINE';
    };
    filters: {
        session_id: number;
        offering_id: number;
        discipline_id: number;
        q: string;
        per_page: number;
    };
    can: { manage: boolean };
};

export default function Index({
    college,
    sessions,
    offerings,
    disciplines,
    students,
    settings,
    filters,
    can,
}: Props) {
    const [selected, setSelected] = useState<number[]>([]);
    const [placementOpen, setPlacementOpen] = useState(false);
    const [placementBatch, setPlacementBatch] = useState('');
    const [placementSection, setPlacementSection] = useState('');
    const [q, setQ] = useState(filters.q ?? '');
    const [sessionId, setSessionId] = useState(
        String(filters.session_id || ''),
    );
    const [offeringId, setOfferingId] = useState(
        filters.offering_id ? String(filters.offering_id) : 'all',
    );
    const [disciplineId, setDisciplineId] = useState(
        filters.discipline_id ? String(filters.discipline_id) : 'all',
    );
    const [perPage, setPerPage] = useState(String(filters.per_page || 25));
    const { isLoading: loading, setNextLoadingLabel } = useAppLoading();
    const dialog = useAppDialog();
    const selectedRows = useMemo(
        () => students.data.filter((row) => selected.includes(row.id)),
        [selected, students.data],
    );
    const selectedOfferingIds = useMemo(
        () => Array.from(new Set(selectedRows.map((row) => row.offering_id))),
        [selectedRows],
    );
    const placementOffering =
        selectedOfferingIds.length === 1
            ? offerings.find((item) => item.id === selectedOfferingIds[0])
            : null;
    const placementSections =
        placementOffering?.batches.find(
            (batch) => String(batch.id) === placementBatch,
        )?.sections ?? [];
    const allVisibleSelected =
        students.data.length > 0 &&
        students.data.every((row) => selected.includes(row.id));
    const visit = (
        params: Record<string, string | number | null> = {},
        label = 'Loading student identities…',
    ) => {
        setSelected([]);
        setNextLoadingLabel(label);
        router.get(
            `/college/${college.id}/student-identities`,
            {
                q,
                session_id: sessionId,
                offering_id: offeringId === 'all' ? null : offeringId,
                discipline_id: disciplineId === 'all' ? null : disciplineId,
                per_page: perPage,
                ...params,
            },
            { preserveState: true, preserveScroll: true },
        );
    };
    const assign = async (row: Row) => {
        if (row.complete || loading || !can.manage) {
            return;
        }

        const ok = await dialog.confirm({
            title: 'Assign Student Identities',
            description: `Assign configured identifiers to ${row.name ?? 'this student'}? Already assigned identifiers will never be replaced.`,
            confirmLabel: 'Assign Identities',
            confirmIcon: <Fingerprint className="size-4" />,
        });

        if (!ok) {
            return;
        }

        setNextLoadingLabel('Assigning student identities…');
        router.post(
            `/college/${college.id}/student-identities/${row.id}/assign`,
            {},
            { preserveScroll: true },
        );
    };
    const openPlacement = () => {
        if (!selected.length || selectedOfferingIds.length !== 1) {
            return;
        }

        setPlacementBatch('');
        setPlacementSection('');
        setPlacementOpen(true);
    };
    const savePlacement = () => {
        if (!placementBatch || !placementSection || loading) {
            return;
        }

        setNextLoadingLabel('Assigning academic placement…');
        router.post(
            `/college/${college.id}/student-identities/placement/bulk`,
            {
                enrollment_ids: selected,
                batch_id: Number(placementBatch),
                section_id: Number(placementSection),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setPlacementOpen(false);
                    setSelected([]);
                },
            },
        );
    };
    const page = (url: string | null) => {
        if (!url) {
            return;
        }

        setSelected([]);
        setNextLoadingLabel('Loading student identities…');
        router.visit(url, { preserveState: true, preserveScroll: true });
    };

    return (
        <>
            <Head title="Student Identity" />
            <div className="space-y-5 p-4 md:p-6">
                <div className="flex items-start gap-3">
                    <div className="mt-0.5 rounded-lg border bg-card p-2">
                        <Fingerprint className="size-5" />
                    </div>
                    <div>
                        <h1 className="text-xl font-semibold">
                            Student Identity
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Assign institutional identifiers and canonical Batch
                            / Section placement for Admission- and Import-origin
                            students.
                        </p>
                    </div>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Settings2 className="size-4" />
                            Identity Numbering Rules
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={`/college/${college.id}/student-identities/settings`}
                            method="patch"
                        >
                            {({ processing, errors }) => (
                                <div className="space-y-4">
                                    <div className="grid gap-4 lg:grid-cols-4">
                                        <div>
                                            <Label>Student UID Format</Label>
                                            <Input
                                                name="student_uid_format"
                                                className="mt-2 font-mono"
                                                defaultValue={
                                                    settings.student_uid_format
                                                }
                                                disabled={!can.manage}
                                            />
                                            <p className="mt-1 text-xs text-destructive">
                                                {errors.student_uid_format}
                                            </p>
                                        </div>
                                        <div>
                                            <Label>
                                                University Roll No. Format
                                            </Label>
                                            <Input
                                                name="university_roll_format"
                                                className="mt-2 font-mono"
                                                defaultValue={
                                                    settings.university_roll_format
                                                }
                                                disabled={!can.manage}
                                            />
                                            <p className="mt-1 text-xs text-destructive">
                                                {errors.university_roll_format}
                                            </p>
                                        </div>
                                        <div>
                                            <Label>Class Roll No. Format</Label>
                                            <Input
                                                name="class_roll_format"
                                                className="mt-2 font-mono"
                                                defaultValue={
                                                    settings.class_roll_format
                                                }
                                                disabled={!can.manage}
                                            />
                                            <p className="mt-1 text-xs text-destructive">
                                                {errors.class_roll_format}
                                            </p>
                                        </div>
                                        <div>
                                            <Label>Class Roll Scope</Label>
                                            <Select
                                                name="class_roll_scope"
                                                defaultValue={
                                                    settings.class_roll_scope ??
                                                    'PROGRAMME_OFFERING'
                                                }
                                                disabled={
                                                    !can.manage || processing
                                                }
                                            >
                                                <SelectTrigger className="mt-2">
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="PROGRAMME_OFFERING">
                                                        Programme Offering
                                                    </SelectItem>
                                                    <SelectItem value="DISCIPLINE">
                                                        Discipline
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p className="mt-1 text-xs text-destructive">
                                                {errors.class_roll_scope}
                                            </p>
                                        </div>
                                    </div>
                                    <p className="text-xs leading-5 text-muted-foreground">
                                        Supported tokens include College,
                                        Session, Programme, Year and Sequence
                                        values. Existing identifiers are never
                                        renumbered.
                                    </p>
                                    {can.manage && (
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}Save
                                            Identity Rules
                                        </Button>
                                    )}
                                </div>
                            )}
                        </Form>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <CardTitle>Enrolled Students</CardTitle>
                            {can.manage && (
                                <Button
                                    onClick={openPlacement}
                                    disabled={
                                        loading ||
                                        !selected.length ||
                                        selectedOfferingIds.length !== 1
                                    }
                                >
                                    <MapPin className="size-4" />
                                    Assign Placement ({selected.length})
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-[210px_260px_240px_minmax(260px,1fr)_100px_auto]">
                            <Select
                                value={sessionId}
                                onValueChange={(value) => {
                                    setSessionId(value);
                                    setOfferingId('all');
                                    setDisciplineId('all');
                                    visit(
                                        {
                                            session_id: value,
                                            offering_id: null,
                                            discipline_id: null,
                                        },
                                        'Loading programme offerings…',
                                    );
                                }}
                                disabled={loading}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {sessions.map((item) => (
                                        <SelectItem
                                            key={item.id}
                                            value={String(item.id)}
                                        >
                                            {item.name}
                                            {item.is_current
                                                ? ' · Current'
                                                : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={offeringId}
                                onValueChange={(value) => {
                                    setOfferingId(value);
                                    setDisciplineId('all');
                                    visit(
                                        {
                                            offering_id:
                                                value === 'all' ? null : value,
                                            discipline_id: null,
                                        },
                                        'Loading disciplines…',
                                    );
                                }}
                                disabled={loading}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Programme Offering" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All Programme Offerings
                                    </SelectItem>
                                    {offerings.map((item) => (
                                        <SelectItem
                                            key={item.id}
                                            value={String(item.id)}
                                        >
                                            {item.name}
                                            {item.code ? ` (${item.code})` : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={disciplineId}
                                onValueChange={(value) => {
                                    setDisciplineId(value);
                                    visit({
                                        discipline_id:
                                            value === 'all' ? null : value,
                                    });
                                }}
                                disabled={loading}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Discipline" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All Disciplines
                                    </SelectItem>
                                    {disciplines.map((item) => (
                                        <SelectItem
                                            key={item.id}
                                            value={String(item.id)}
                                        >
                                            {item.name}
                                            {item.code ? ` (${item.code})` : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Input
                                value={q}
                                onChange={(event) => setQ(event.target.value)}
                                onKeyDown={(event) =>
                                    event.key === 'Enter' && visit()
                                }
                                placeholder="Search name / Student UID / University Roll"
                                disabled={loading}
                            />
                            <Select
                                value={perPage}
                                onValueChange={setPerPage}
                                disabled={loading}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {[25, 50, 100].map((value) => (
                                        <SelectItem
                                            key={value}
                                            value={String(value)}
                                        >
                                            {value}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Button
                                variant="outline"
                                onClick={() => visit()}
                                disabled={loading}
                            >
                                {loading ? (
                                    <Spinner />
                                ) : (
                                    <Search className="size-4" />
                                )}
                                Search
                            </Button>
                        </div>
                        {selected.length > 0 &&
                            selectedOfferingIds.length !== 1 && (
                                <p className="rounded-md border border-destructive/40 bg-destructive/5 p-3 text-sm text-destructive">
                                    Selected students belong to different
                                    Programme Offerings. Select students from
                                    one Programme Offering for bulk placement.
                                </p>
                            )}
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[1100px] text-sm">
                                <thead className="border-b bg-muted/30">
                                    <tr>
                                        <th className="px-3 py-2">
                                            <Checkbox
                                                checked={allVisibleSelected}
                                                onCheckedChange={(checked) =>
                                                    setSelected(
                                                        checked
                                                            ? students.data.map(
                                                                  (row) =>
                                                                      row.id,
                                                              )
                                                            : [],
                                                    )
                                                }
                                            />
                                        </th>
                                        {[
                                            'Student',
                                            'Programme',
                                            'Discipline',
                                            'Student UID',
                                            'University Roll No.',
                                            'Class Roll No.',
                                            'Placement',
                                            'Identity',
                                            'Action',
                                        ].map((label) => (
                                            <th
                                                key={label}
                                                className="px-3 py-2 text-left font-medium"
                                            >
                                                {label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {students.data.map((row) => (
                                        <tr
                                            key={row.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-3 py-3">
                                                <Checkbox
                                                    checked={selected.includes(
                                                        row.id,
                                                    )}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        setSelected(
                                                            (current) =>
                                                                checked
                                                                    ? [
                                                                          ...new Set(
                                                                              [
                                                                                  ...current,
                                                                                  row.id,
                                                                              ],
                                                                          ),
                                                                      ]
                                                                    : current.filter(
                                                                          (
                                                                              id,
                                                                          ) =>
                                                                              id !==
                                                                              row.id,
                                                                      ),
                                                        )
                                                    }
                                                />
                                            </td>
                                            <td className="px-3 py-3 font-medium">
                                                {row.name ?? '—'}
                                            </td>
                                            <td className="px-3 py-3">
                                                {row.programme ?? '—'}
                                                <div className="text-xs text-muted-foreground">
                                                    {row.session ?? ''}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3">
                                                {row.discipline ?? '—'}
                                                {row.discipline_code && (
                                                    <div className="text-xs text-muted-foreground">
                                                        {row.discipline_code}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-3 py-3 font-mono">
                                                {row.student_uid ?? '—'}
                                            </td>
                                            <td className="px-3 py-3 font-mono">
                                                {row.university_roll_no ?? '—'}
                                            </td>
                                            <td className="px-3 py-3 font-mono">
                                                {row.class_roll_no ?? '—'}
                                            </td>
                                            <td className="px-3 py-3">
                                                {row.placement_complete ? (
                                                    <Badge variant="secondary">
                                                        Assigned
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Pending
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                {row.complete ? (
                                                    <Badge variant="secondary">
                                                        <BadgeCheck className="mr-1 size-3" />
                                                        Assigned
                                                    </Badge>
                                                ) : (
                                                    <Badge variant="outline">
                                                        Pending
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                {!row.complete && can.manage ? (
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            assign(row)
                                                        }
                                                        disabled={loading}
                                                    >
                                                        <TicketCheck className="size-4" />
                                                        Assign ID
                                                    </Button>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        —
                                                    </span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {!students.data.length && (
                                        <tr>
                                            <td
                                                colSpan={10}
                                                className="px-3 py-10 text-center text-muted-foreground"
                                            >
                                                No enrolled students match the
                                                selected filters.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
                            <span>
                                Showing {students.from ?? 0}-{students.to ?? 0}{' '}
                                of {students.total}
                            </span>
                            <div className="flex items-center gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    disabled={
                                        loading || !students.prev_page_url
                                    }
                                    onClick={() => page(students.prev_page_url)}
                                >
                                    <ChevronLeft className="size-4" />
                                    Previous
                                </Button>
                                <span>
                                    Page {students.current_page} of{' '}
                                    {students.last_page}
                                </span>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    disabled={
                                        loading || !students.next_page_url
                                    }
                                    onClick={() => page(students.next_page_url)}
                                >
                                    Next
                                    <ChevronRight className="size-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                {placementOpen && placementOffering && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                        <div className="w-full max-w-lg rounded-xl border bg-card shadow-xl">
                            <div className="flex items-start justify-between border-b p-5">
                                <div>
                                    <h2 className="text-lg font-semibold">
                                        Assign Placement
                                    </h2>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {selected.length} student(s) ·{' '}
                                        {placementOffering.name}
                                    </p>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => setPlacementOpen(false)}
                                >
                                    <X className="size-4" />
                                </Button>
                            </div>
                            <div className="space-y-4 p-5">
                                <div>
                                    <Label>Batch</Label>
                                    <Select
                                        value={placementBatch}
                                        onValueChange={(value) => {
                                            setPlacementBatch(value);
                                            setPlacementSection('');
                                        }}
                                        disabled={loading}
                                    >
                                        <SelectTrigger className="mt-2">
                                            <SelectValue placeholder="Select Batch" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {placementOffering.batches.map(
                                                (batch) => (
                                                    <SelectItem
                                                        key={batch.id}
                                                        value={String(batch.id)}
                                                    >
                                                        {batch.code} ·{' '}
                                                        {batch.name}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Section</Label>
                                    <Select
                                        value={placementSection}
                                        onValueChange={setPlacementSection}
                                        disabled={loading || !placementBatch}
                                    >
                                        <SelectTrigger className="mt-2">
                                            <SelectValue placeholder="Select Section" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {placementSections.map(
                                                (section) => (
                                                    <SelectItem
                                                        key={section.id}
                                                        value={String(
                                                            section.id,
                                                        )}
                                                    >
                                                        {section.code} ·{' '}
                                                        {section.name}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Existing placement is changed only after the
                                    server confirms every selected Enrollment
                                    belongs to this Programme Offering.
                                    Attendance consumes this placement
                                    automatically.
                                </p>
                                <div className="flex justify-end gap-2">
                                    <Button
                                        variant="outline"
                                        onClick={() => setPlacementOpen(false)}
                                        disabled={loading}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        onClick={savePlacement}
                                        disabled={
                                            loading ||
                                            !placementBatch ||
                                            !placementSection
                                        }
                                    >
                                        {loading ? (
                                            <Spinner />
                                        ) : (
                                            <MapPin className="size-4" />
                                        )}
                                        Assign Placement
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
