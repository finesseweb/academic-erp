import { Head, router } from '@inertiajs/react';
import {
    BadgeCheck,
    ChevronLeft,
    ChevronRight,
    GraduationCap,
    Search,
    ShieldAlert,
    UserPlus,
} from 'lucide-react';
import { useState } from 'react';
import { useAppDialog } from '@/components/app-dialog-provider';
import { useAppLoading } from '@/components/app-loading-provider';
import { Badge } from '@/components/ui/badge';
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
import { Spinner } from '@/components/ui/spinner';

type Session = { id: number; name: string; code: string; is_current: boolean };
type Offering = {
    id: number;
    name: string | null;
    code: string | null;
    status: string;
};
type Row = {
    admission_id: number;
    admission_no: string | null;
    application_no: string | null;
    candidate_name: string | null;
    programme: string | null;
    discipline: string | null;
    session: string | null;
    fee_clearance_status: 'CLEARED' | 'PENDING' | 'NOT_REQUIRED';
    fee_clearance_outstanding: string;
    currency: string;
    enrollment_status: 'READY' | 'BLOCKED' | 'ENROLLED';
    block_reason: string | null;
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
    students: Page<Row>;
    filters: {
        session_id: number;
        offering_id: number;
        q: string;
        status: string;
        per_page: number;
    };
    can: { enroll: boolean };
};
const money = (value: string | number, currency = 'INR') =>
    new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
    }).format(Number(value || 0));
const enrollmentBadge = (status: Row['enrollment_status']) => (
    <Badge
        variant={
            status === 'READY'
                ? 'default'
                : status === 'BLOCKED'
                  ? 'destructive'
                  : 'secondary'
        }
    >
        {status === 'READY'
            ? 'Ready'
            : status === 'BLOCKED'
              ? 'Blocked'
              : 'Enrolled'}
    </Badge>
);

export default function Index({
    college,
    sessions,
    offerings,
    students,
    filters,
    can,
}: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [sessionId, setSessionId] = useState(
        String(filters.session_id || ''),
    );
    const [offeringId, setOfferingId] = useState(
        filters.offering_id ? String(filters.offering_id) : 'all',
    );
    const [status, setStatus] = useState(filters.status || 'all');
    const [perPage, setPerPage] = useState(String(filters.per_page || 25));
    const { isLoading: loading, setNextLoadingLabel } = useAppLoading();
    const appDialog = useAppDialog();
    const enroll = async (row: Row) => {
        if (row.enrollment_status !== 'READY' || loading || !can.enroll) {
            return;
        }

        const ok = await appDialog.confirm({
            title: 'Enroll Student',
            description: `Enroll ${row.candidate_name ?? 'this student'}?\n\nAdmission: ${row.admission_no ?? '—'}\nProgramme: ${row.programme ?? '—'}\nFee Clearance: ${row.fee_clearance_status}`,
            confirmLabel: 'Enroll Student',
            confirmIcon: <UserPlus className="size-4" />,
        });

        if (!ok) {
            return;
        }

        setNextLoadingLabel('Enrolling student…');
        router.post(
            `/college/${college.id}/student-enrollments/${row.admission_id}`,
            {},
            { preserveScroll: true },
        );
    };
    const visit = (
        params: Record<string, string | number | null | undefined> = {},
        label = 'Loading enrollment eligibility…',
    ) => {
        setNextLoadingLabel(label);
        router.get(
            `/college/${college.id}/student-enrollments`,
            {
                q,
                session_id: sessionId,
                offering_id: offeringId === 'all' ? null : offeringId,
                status: status === 'all' ? null : status,
                per_page: perPage,
                ...params,
            },
            { preserveState: true, preserveScroll: true },
        );
    };
    const paginate = (url: string | null) => {
        if (!url) {
            return;
        }

        setNextLoadingLabel('Loading enrollment eligibility…');
        router.visit(url, { preserveState: true, preserveScroll: true });
    };

    return (
        <>
            <Head title="Student Enrollment" />
            <div className="space-y-5 p-4 md:p-6">
                <div className="flex items-start gap-3">
                    <div className="mt-0.5 rounded-lg border bg-card p-2">
                        <GraduationCap className="size-5" />
                    </div>
                    <div>
                        <h1 className="text-xl font-semibold">
                            Student Enrollment
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Review confirmed admissions, verify Fee Clearance,
                            and create canonical Enrollment. Batch / Section
                            placement is managed from Student Identity.
                        </p>
                    </div>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Enrollment Eligibility Queue</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-[210px_260px_minmax(260px,1fr)_170px_100px_auto]">
                            <Select
                                value={sessionId}
                                onValueChange={(value) => {
                                    setSessionId(value);
                                    setOfferingId('all');
                                    visit(
                                        {
                                            session_id: value,
                                            offering_id: null,
                                            page: 1,
                                        },
                                        'Loading programme offerings…',
                                    );
                                }}
                                disabled={loading}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Academic Session" />
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
                                    visit({
                                        offering_id:
                                            value === 'all' ? null : value,
                                        page: 1,
                                    });
                                }}
                                disabled={loading || !sessionId}
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
                                            {item.name ?? 'Programme'}
                                            {item.code ? ` (${item.code})` : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Input
                                value={q}
                                onChange={(event) => setQ(event.target.value)}
                                onKeyDown={(event) =>
                                    event.key === 'Enter' &&
                                    !loading &&
                                    visit({ page: 1 })
                                }
                                placeholder="Search student / application / admission"
                                disabled={loading}
                            />
                            <Select
                                value={status}
                                onValueChange={setStatus}
                                disabled={loading}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All enrollment states
                                    </SelectItem>
                                    <SelectItem value="READY">Ready</SelectItem>
                                    <SelectItem value="BLOCKED">
                                        Blocked
                                    </SelectItem>
                                    <SelectItem value="ENROLLED">
                                        Enrolled
                                    </SelectItem>
                                </SelectContent>
                            </Select>
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
                                onClick={() => visit({ page: 1 })}
                                disabled={loading}
                            >
                                {loading ? (
                                    <Spinner />
                                ) : (
                                    <Search className="size-4" />
                                )}
                                {loading ? 'Loading…' : 'Search'}
                            </Button>
                        </div>
                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[1050px] text-sm">
                                <thead className="border-b bg-muted/30">
                                    <tr>
                                        {[
                                            'Student',
                                            'Admission',
                                            'Programme',
                                            'Fee Clearance',
                                            'Outstanding',
                                            'Enrollment',
                                            'Reason',
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
                                            key={row.admission_id}
                                            className="border-b last:border-b-0"
                                        >
                                            <td className="px-3 py-3 font-medium">
                                                {row.candidate_name ?? '—'}
                                                <div className="text-xs text-muted-foreground">
                                                    {row.application_no ?? '—'}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3">
                                                {row.admission_no ?? '—'}
                                            </td>
                                            <td className="px-3 py-3">
                                                {row.programme ?? '—'}
                                                <div className="text-xs text-muted-foreground">
                                                    {row.session ?? ''}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3">
                                                <Badge
                                                    variant={
                                                        row.fee_clearance_status ===
                                                        'PENDING'
                                                            ? 'destructive'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {row.fee_clearance_status ===
                                                    'NOT_REQUIRED'
                                                        ? 'Not Required'
                                                        : row.fee_clearance_status}
                                                </Badge>
                                            </td>
                                            <td className="px-3 py-3">
                                                {money(
                                                    row.fee_clearance_outstanding,
                                                    row.currency,
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                {enrollmentBadge(
                                                    row.enrollment_status,
                                                )}
                                            </td>
                                            <td className="px-3 py-3 text-muted-foreground">
                                                {row.enrollment_status ===
                                                'READY' ? (
                                                    <span className="inline-flex items-center gap-1 text-foreground">
                                                        <BadgeCheck className="size-4" />
                                                        Eligible for enrollment
                                                    </span>
                                                ) : row.enrollment_status ===
                                                  'BLOCKED' ? (
                                                    <span className="inline-flex items-center gap-1">
                                                        <ShieldAlert className="size-4" />
                                                        {row.block_reason}
                                                    </span>
                                                ) : (
                                                    'Placement continues in Student Identity'
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                {row.enrollment_status ===
                                                    'READY' && can.enroll ? (
                                                    <Button
                                                        size="sm"
                                                        onClick={() =>
                                                            enroll(row)
                                                        }
                                                        disabled={loading}
                                                    >
                                                        <UserPlus className="size-4" />
                                                        Enroll
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
                                                colSpan={8}
                                                className="px-3 py-10 text-center text-muted-foreground"
                                            >
                                                No confirmed admissions match
                                                the selected enrollment filters.
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
                                    onClick={() =>
                                        paginate(students.prev_page_url)
                                    }
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
                                    onClick={() =>
                                        paginate(students.next_page_url)
                                    }
                                >
                                    Next
                                    <ChevronRight className="size-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
