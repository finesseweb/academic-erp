import { Head, Link } from '@inertiajs/react';
import { ClipboardCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Schedule = {
    id: number;
    class_date: string;
    start_time: string;
    end_time: string;
    status: string;
    attendance_register: null | { status: string; revision_no: number };
    timetable_entry: {
        faculty_allocation: {
            faculty: { name: string };
            section: { name: string } | null;
            course_offering: {
                batch: { name: string };
                curriculum_course_mapping: {
                    course: { code: string; name: string };
                };
            };
        };
    };
};
type Props = {
    college: { id: number; name: string; code: string };
    schedules: { data: Schedule[] };
    can: { manage: boolean };
};

export default function AttendanceIndex({ college, schedules }: Props) {
    return (
        <>
            <Head title={`${college.name} Attendance`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        {college.code} · Attendance Operations
                    </p>
                    <h1 className="text-3xl font-semibold">Attendance</h1>
                    <p className="max-w-3xl text-muted-foreground">
                        Enter attendance against dated Class Schedules.
                        Finalized records use the applicable approved Academic
                        Policy.
                    </p>
                </header>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ClipboardCheck className="size-5" />
                            Class Attendance Registers
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[900px] text-sm">
                                <thead className="bg-muted/40 text-left">
                                    <tr>
                                        <th className="p-3">Class</th>
                                        <th className="p-3">Course</th>
                                        <th className="p-3">Faculty / Scope</th>
                                        <th className="p-3">Class Status</th>
                                        <th className="p-3">Attendance</th>
                                        <th className="p-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {schedules.data.map((row) => {
                                        const a =
                                            row.timetable_entry
                                                .faculty_allocation;

                                        return (
                                            <tr key={row.id}>
                                                <td className="p-3 font-medium">
                                                    {String(
                                                        row.class_date,
                                                    ).slice(0, 10)}
                                                    <div className="text-xs font-normal text-muted-foreground">
                                                        {row.start_time.slice(
                                                            0,
                                                            5,
                                                        )}
                                                        –
                                                        {row.end_time.slice(
                                                            0,
                                                            5,
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    {
                                                        a.course_offering
                                                            .curriculum_course_mapping
                                                            .course.code
                                                    }{' '}
                                                    ·{' '}
                                                    {
                                                        a.course_offering
                                                            .curriculum_course_mapping
                                                            .course.name
                                                    }
                                                    <div className="text-xs text-muted-foreground">
                                                        {
                                                            a.course_offering
                                                                .batch.name
                                                        }
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    {a.faculty.name}
                                                    <div className="text-xs text-muted-foreground">
                                                        {a.section?.name ??
                                                            'Entire Batch'}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    <Badge variant="secondary">
                                                        {row.status}
                                                    </Badge>
                                                </td>
                                                <td className="p-3">
                                                    <Badge
                                                        variant={
                                                            row
                                                                .attendance_register
                                                                ?.status ===
                                                            'FINALIZED'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {row.attendance_register
                                                            ?.status ??
                                                            'NOT STARTED'}
                                                    </Badge>
                                                    {row.attendance_register
                                                        ?.revision_no ? (
                                                        <div className="mt-1 text-xs text-muted-foreground">
                                                            Revision{' '}
                                                            {
                                                                row
                                                                    .attendance_register
                                                                    .revision_no
                                                            }
                                                        </div>
                                                    ) : null}
                                                </td>
                                                <td className="p-3">
                                                    <Button
                                                        asChild
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <Link
                                                            href={`/college/${college.id}/attendance/classes/${row.id}`}
                                                        >
                                                            {row.attendance_register
                                                                ? 'Open Register'
                                                                : 'Take Attendance'}
                                                        </Link>
                                                    </Button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {!schedules.data.length && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="p-12 text-center text-muted-foreground"
                                            >
                                                No eligible Class Schedules
                                                found. Create dated classes
                                                before taking attendance.
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
