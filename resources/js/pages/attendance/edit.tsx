import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    ClipboardCheck,
    RotateCcw,
    Save,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type Roster = {
    id: number;
    class_roll_no: string | null;
    student: {
        full_name: string;
        student_uid: string | null;
        university_roll_no: string | null;
    };
    attendance_status: string;
    remarks: string | null;
};
type Props = {
    college: { id: number; name: string; code: string };
    classSchedule: any;
    register: any | null;
    policy: {
        id: number;
        name: string;
        code: string;
        version: number;
        attendance_rule: {
            minimum_attendance_percent: string;
            calculation_level: string;
            rounding_rule: string;
            allow_condonation: boolean;
            attendance_required_for_exam: boolean;
        };
    };
    roster: Roster[];
    summary: Record<
        number,
        { held: number; attended: number; percent: number }
    >;
    can: { manage: boolean; finalize: boolean; correct: boolean };
};

export default function AttendanceEdit({
    college,
    classSchedule,
    register,
    policy,
    roster,
    summary,
    can,
}: Props) {
    const locked = register?.status === 'FINALIZED';
    const form = useForm({
        action: 'SAVE_DRAFT',
        records: roster.map((r) => ({
            student_enrollment_id: r.id,
            attendance_status: r.attendance_status,
            remarks: r.remarks ?? '',
        })),
    });
    const [reason, setReason] = useState('');
    const reopen = useForm({ reason: '' });
    const allocation = classSchedule.timetable_entry.faculty_allocation;
    const setStatus = (i: number, value: string) =>
        form.setData(
            'records',
            form.data.records.map((r, n) =>
                n === i ? { ...r, attendance_status: value } : r,
            ),
        );
    const submit = (action: 'SAVE_DRAFT' | 'FINALIZE') => {
        form.setData('action', action);
        form.transform((data) => ({ ...data, action }));
        form.put(
            `/college/${college.id}/attendance/classes/${classSchedule.id}`,
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`${college.name} Attendance Register`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5">
                    <div>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={`/college/${college.id}/attendance`}>
                                <ArrowLeft />
                                Attendance
                            </Link>
                        </Button>
                        <p className="mt-2 text-sm font-medium text-primary">
                            {college.code} ·{' '}
                            {String(classSchedule.class_date).slice(0, 10)} ·{' '}
                            {classSchedule.start_time.slice(0, 5)}–
                            {classSchedule.end_time.slice(0, 5)}
                        </p>
                        <h1 className="text-3xl font-semibold">
                            {
                                allocation.course_offering
                                    .curriculum_course_mapping.course.code
                            }{' '}
                            ·{' '}
                            {
                                allocation.course_offering
                                    .curriculum_course_mapping.course.name
                            }
                        </h1>
                        <p className="text-muted-foreground">
                            {allocation.faculty.name} ·{' '}
                            {allocation.section?.name ?? 'Entire Batch'} ·{' '}
                            {allocation.course_offering.batch.name}
                        </p>
                    </div>
                    <Badge variant={locked ? 'default' : 'secondary'}>
                        {register?.status ?? 'NOT STARTED'}
                    </Badge>
                </header>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ClipboardCheck className="size-5" />
                            Applicable Attendance Policy
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <span className="text-muted-foreground">
                                Policy
                            </span>
                            <p className="font-medium">
                                {policy.code} · v{policy.version}
                            </p>
                        </div>
                        <div>
                            <span className="text-muted-foreground">
                                Minimum
                            </span>
                            <p className="font-medium">
                                {
                                    policy.attendance_rule
                                        .minimum_attendance_percent
                                }
                                %
                            </p>
                        </div>
                        <div>
                            <span className="text-muted-foreground">
                                Calculation
                            </span>
                            <p className="font-medium">
                                {policy.attendance_rule.calculation_level}
                            </p>
                        </div>
                        <div>
                            <span className="text-muted-foreground">
                                Rounding
                            </span>
                            <p className="font-medium">
                                {policy.attendance_rule.rounding_rule}
                            </p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Student Roster</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[950px] text-sm">
                                <thead className="bg-muted/40 text-left">
                                    <tr>
                                        <th className="p-3">Roll / Student</th>
                                        <th className="p-3">Status</th>
                                        <th className="p-3">Remarks</th>
                                        <th className="p-3">
                                            Finalized Course Attendance
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {roster.map((row, i) => {
                                        const s = summary[row.id];
                                        const short =
                                            s &&
                                            s.percent <
                                                Number(
                                                    policy.attendance_rule
                                                        .minimum_attendance_percent,
                                                );

                                        return (
                                            <tr key={row.id}>
                                                <td className="p-3">
                                                    <p className="font-medium">
                                                        {row.class_roll_no ??
                                                            row.student
                                                                .university_roll_no ??
                                                            row.student
                                                                .student_uid ??
                                                            'Pending ID'}{' '}
                                                        ·{' '}
                                                        {row.student.full_name}
                                                    </p>
                                                </td>
                                                <td className="p-3">
                                                    <Select
                                                        value={
                                                            form.data.records[i]
                                                                .attendance_status
                                                        }
                                                        onValueChange={(v) =>
                                                            setStatus(i, v)
                                                        }
                                                        disabled={
                                                            locked ||
                                                            !can.manage
                                                        }
                                                    >
                                                        <SelectTrigger className="w-40">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {[
                                                                'PRESENT',
                                                                'ABSENT',
                                                                'LATE',
                                                                'EXCUSED',
                                                            ].map((v) => (
                                                                <SelectItem
                                                                    key={v}
                                                                    value={v}
                                                                >
                                                                    {v}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </td>
                                                <td className="p-3">
                                                    <Input
                                                        value={
                                                            form.data.records[i]
                                                                .remarks
                                                        }
                                                        onChange={(e) =>
                                                            form.setData(
                                                                'records',
                                                                form.data.records.map(
                                                                    (r, n) =>
                                                                        n === i
                                                                            ? {
                                                                                  ...r,
                                                                                  remarks:
                                                                                      e
                                                                                          .target
                                                                                          .value,
                                                                              }
                                                                            : r,
                                                                ),
                                                            )
                                                        }
                                                        disabled={
                                                            locked ||
                                                            !can.manage
                                                        }
                                                        placeholder="Optional note"
                                                    />
                                                </td>
                                                <td className="p-3">
                                                    {s ? (
                                                        <span
                                                            className={
                                                                short
                                                                    ? 'text-destructive'
                                                                    : 'text-foreground'
                                                            }
                                                        >
                                                            {s.attended}/
                                                            {s.held} ·{' '}
                                                            {s.percent}%{' '}
                                                            {short
                                                                ? '· SHORT'
                                                                : ''}
                                                        </span>
                                                    ) : (
                                                        <span className="text-muted-foreground">
                                                            No finalized classes
                                                            yet
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {!roster.length && (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="p-12 text-center text-muted-foreground"
                                            >
                                                No enrolled students are mapped
                                                to this Course Offering and
                                                delivery scope.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {Object.values(form.errors).map((e, i) => (
                            <p
                                key={i}
                                className="mt-2 text-sm text-destructive"
                            >
                                {e}
                            </p>
                        ))}
                        {!locked && roster.length > 0 && (
                            <div className="mt-5 flex flex-wrap justify-end gap-2">
                                {can.manage && (
                                    <Button
                                        variant="outline"
                                        disabled={form.processing}
                                        onClick={() => submit('SAVE_DRAFT')}
                                    >
                                        {form.processing ? (
                                            <Spinner />
                                        ) : (
                                            <Save />
                                        )}
                                        Save Draft
                                    </Button>
                                )}
                                {can.finalize && (
                                    <Button
                                        disabled={form.processing}
                                        onClick={() => submit('FINALIZE')}
                                    >
                                        {form.processing ? (
                                            <Spinner />
                                        ) : (
                                            <CheckCircle2 />
                                        )}
                                        Finalize Attendance
                                    </Button>
                                )}
                            </div>
                        )}
                        {locked && can.correct && (
                            <div className="mt-5 flex justify-end">
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button variant="outline">
                                            <RotateCcw />
                                            Correct Attendance
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogTitle>
                                            Reopen Finalized Attendance
                                        </DialogTitle>
                                        <DialogDescription>
                                            This audited action unlocks the
                                            register for correction. Raw history
                                            remains in the audit log.
                                        </DialogDescription>
                                        <Textarea
                                            value={reason}
                                            onChange={(e) =>
                                                setReason(e.target.value)
                                            }
                                            placeholder="Correction reason (minimum 10 characters)"
                                        />
                                        <DialogFooter>
                                            <Button
                                                disabled={
                                                    reopen.processing ||
                                                    reason.trim().length < 10
                                                }
                                                onClick={() => {
                                                    reopen.setData(
                                                        'reason',
                                                        reason,
                                                    );
                                                    reopen.transform(() => ({
                                                        reason,
                                                    }));
                                                    reopen.post(
                                                        `/college/${college.id}/attendance/registers/${register.id}/reopen`,
                                                    );
                                                }}
                                            >
                                                {reopen.processing && (
                                                    <Spinner />
                                                )}
                                                Reopen Register
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
