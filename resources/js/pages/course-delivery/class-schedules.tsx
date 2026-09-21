import { Form, Head } from '@inertiajs/react';
import { CalendarCheck, Plus } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { SearchableSelect } from '@/components/ui/searchable-select';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
type Entry = {
    id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    effective_from: string;
    effective_until: string | null;
    faculty_allocation: {
        faculty: { name: string };
        section: { name: string } | null;
        course_offering: {
            batch: { name: string };
            curriculum_course_mapping: {
                course: { name: string; code: string };
            };
        };
    };
    room: { code: string; name: string } | null;
};
type Row = {
    id: number;
    timetable_entry_id: number;
    class_date: string;
    start_time: string;
    end_time: string;
    status: string;
    notes: string | null;
    timetable_entry: Entry;
    room: { code: string; name: string } | null;
};
type Props = {
    college: { id: number; name: string; code: string };
    entries: Entry[];
    classes: {
        data: Row[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    can: { manage: boolean; status: boolean };
};
function Add({ collegeId, entries }: { collegeId: number; entries: Entry[] }) {
    const [e, setE] = useState('');
    const [date, setDate] = useState('');

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm">
                    <Plus />
                    Schedule Class
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Schedule Class</DialogTitle>
                <DialogDescription>
                    Create a dated occurrence from an active recurring Timetable
                    entry.
                </DialogDescription>
                <Form
                    method="post"
                    action={`/college/${collegeId}/class-schedules`}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <SearchableSelect
                                name="timetable_entry_id"
                                label="Timetable Entry"
                                value={e}
                                onValueChange={setE}
                                options={entries.map((x) => ({
                                    value: String(x.id),
                                    label: `${x.faculty_allocation.course_offering.curriculum_course_mapping.course.code} · ${x.faculty_allocation.course_offering.curriculum_course_mapping.course.name}`,
                                    description: `${x.faculty_allocation.faculty.name} · ${x.faculty_allocation.course_offering.batch.name} · ${x.start_time.slice(0, 5)}–${x.end_time.slice(0, 5)}`,
                                }))}
                            />
                        <DatePicker
                            id="class-schedule-date"
                            name="class_date"
                                value={date}
                                onValueChange={setDate}
                            />
                            <Textarea
                                name="notes"
                                placeholder="Class note (optional)"
                            />
                            {Object.values(errors).map((x, i) => (
                                <p key={i} className="text-xs text-destructive">
                                    {x}
                                </p>
                            ))}
                            <DialogFooter>
                                <Button disabled={processing || !e || !date}>
                                    {processing && <Spinner />}Schedule
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
export default function Classes({ college, entries, classes, can }: Props) {
    return (
        <>
            <Head title={`${college.name} Class Scheduling`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex justify-between border-b pb-5">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {college.code} · Course Delivery
                        </p>
                        <h1 className="text-3xl font-semibold">
                            Class Scheduling
                        </h1>
                        <p className="text-muted-foreground">
                            Dated class occurrences generated from active
                            Timetable entries.
                        </p>
                    </div>
                    {can.manage && (
                        <Add collegeId={college.id} entries={entries} />
                    )}
                </header>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex gap-2">
                            <CalendarCheck />
                            Scheduled Classes
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr>
                                        {[
                                            'Date / Time',
                                            'Course',
                                            'Faculty / Scope',
                                            'Room',
                                            'Status',
                                            'Update',
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
                                    {classes.data.map((x) => (
                                        <tr key={x.id} className="border-t">
                                            <td className="p-3">
                                                <b>
                                                    {String(x.class_date).slice(
                                                        0,
                                                        10,
                                                    )}
                                                </b>
                                                <div>
                                                    {x.start_time.slice(0, 5)}–
                                                    {x.end_time.slice(0, 5)}
                                                </div>
                                            </td>
                                            <td className="p-3">
                                                {
                                                    x.timetable_entry
                                                        .faculty_allocation
                                                        .course_offering
                                                        .curriculum_course_mapping
                                                        .course.code
                                                }{' '}
                                                ·{' '}
                                                {
                                                    x.timetable_entry
                                                        .faculty_allocation
                                                        .course_offering
                                                        .curriculum_course_mapping
                                                        .course.name
                                                }
                                            </td>
                                            <td className="p-3">
                                                {
                                                    x.timetable_entry
                                                        .faculty_allocation
                                                        .faculty.name
                                                }
                                                <div className="text-xs text-muted-foreground">
                                                    {x.timetable_entry
                                                        .faculty_allocation
                                                        .section?.name ??
                                                        'Entire Batch'}
                                                </div>
                                            </td>
                                            <td className="p-3">
                                                {x.room
                                                    ? `${x.room.code} · ${x.room.name}`
                                                    : 'Unassigned'}
                                            </td>
                                            <td className="p-3">
                                                <Badge
                                                    variant={
                                                        x.status === 'SCHEDULED'
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {x.status}
                                                </Badge>
                                            </td>
                                            <td className="p-3">
                                                {can.status && (
                                                    <Form
                                                        method="patch"
                                                        action={`/college/${college.id}/class-schedules/${x.id}/status`}
                                                    >
                                                        {({ processing }) => (
                                                            <div className="flex gap-2">
                                                                <Select
                                                                    name="status"
                                                                    defaultValue={
                                                                        x.status
                                                                    }
                                                                >
                                                                    <SelectTrigger className="w-36">
                                                                        <SelectValue />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        {[
                                                                            'SCHEDULED',
                                                                            'COMPLETED',
                                                                            'CANCELLED',
                                                                        ].map(
                                                                            (
                                                                                s,
                                                                            ) => (
                                                                                <SelectItem
                                                                                    key={
                                                                                        s
                                                                                    }
                                                                                    value={
                                                                                        s
                                                                                    }
                                                                                >
                                                                                    {
                                                                                        s
                                                                                    }
                                                                                </SelectItem>
                                                                            ),
                                                                        )}
                                                                    </SelectContent>
                                                                </Select>
                                                                <Button
                                                                    size="sm"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                >
                                                                    {processing && (
                                                                        <Spinner />
                                                                    )}
                                                                    Save
                                                                </Button>
                                                            </div>
                                                        )}
                                                    </Form>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                    {!classes.data.length && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="p-10 text-center text-muted-foreground"
                                            >
                                                No classes scheduled.
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
