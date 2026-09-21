import { Form, Head } from '@inertiajs/react';
import { CalendarDays, Plus, Power } from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SearchableSelect } from '@/components/ui/searchable-select';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
type Allocation = {
    id: number;
    status: string;
    faculty: { name: string };
    section: { name: string; code: string } | null;
    course_offering: {
        batch: {
            name: string;
            offering: {
                program_template: { name: string };
                academic_session: { name: string };
            };
        };
        curriculum_course_mapping: {
            course: { name: string; code: string };
            slot: { term: { name: string } };
            discipline: { name: string } | null;
        };
    };
};
type Room = { id: number; code: string; name: string; status: string };
type Entry = {
    id: number;
    faculty_allocation_id: number;
    room_id: number | null;
    day_of_week: number;
    start_time: string;
    end_time: string;
    effective_from: string;
    effective_until: string | null;
    status: string;
    faculty_allocation: {
        faculty: { name: string };
        section: { name: string } | null;
    };
    room: Room | null;
    class_schedules: { id: number; status: string }[];
};
type Props = {
    college: { id: number; name: string; code: string };
    allocations: Allocation[];
    rooms: Room[];
    entries: Entry[];
    can: { manage: boolean; enable: boolean; disable: boolean };
};
const days = [
    '',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday',
    'Sunday',
];
function Add({
    collegeId,
    allocations,
    rooms,
}: {
    collegeId: number;
    allocations: Allocation[];
    rooms: Room[];
}) {
    const [a, setA] = useState('');
    const [room, setRoom] = useState('none');
    const [day, setDay] = useState('1');
    const [from, setFrom] = useState('');
    const [until, setUntil] = useState('');

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm">
                    <Plus />
                    Add Timetable Entry
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-2xl">
                <DialogTitle>Add Timetable Entry</DialogTitle>
                <DialogDescription>
                    Recurring weekly slot linked to an active Faculty
                    Allocation.
                </DialogDescription>
                <Form
                    method="post"
                    action={`/college/${collegeId}/timetables`}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <SearchableSelect
                                name="faculty_allocation_id"
                                label="Faculty Allocation"
                                value={a}
                                onValueChange={setA}
                                options={allocations.map((x) => ({
                                    value: String(x.id),
                                    label: `${x.course_offering.curriculum_course_mapping.course.code} · ${x.course_offering.curriculum_course_mapping.course.name}`,
                                    description: `${x.faculty.name} · ${x.course_offering.batch.name}${x.section ? ` · ${x.section.name}` : ' · Entire Batch'} · ${x.status}`,
                                }))}
                            />
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label>Weekday</Label>
                                    <Select
                                        name="day_of_week"
                                        value={day}
                                        onValueChange={setDay}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {days.slice(1).map((x, i) => (
                                                <SelectItem
                                                    key={x}
                                                    value={String(i + 1)}
                                                >
                                                    {x}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <SearchableSelect
                                    name="room_id"
                                    label="Room"
                                    value={room === 'none' ? '' : room}
                                    onValueChange={setRoom}
                                    options={rooms
                                        .filter((x) => x.status === 'ACTIVE')
                                        .map((x) => ({
                                            value: String(x.id),
                                            label: `${x.code} · ${x.name}`,
                                        }))}
                                    placeholder="Select room (optional)"
                                />
                                <div>
                                    <Label>Start Time</Label>
                                    <Input name="start_time" type="time" />
                                </div>
                                <div>
                                    <Label>End Time</Label>
                                    <Input name="end_time" type="time" />
                                </div>
                                <div>
                                    <Label>Effective From</Label>
                                    <DatePicker
                                        id="timetable-effective-from"
                                        name="effective_from"
                                        value={from}
                                        onValueChange={setFrom}
                                    />
                                </div>
                                <div>
                                    <Label>Effective Until (optional)</Label>
                                    <DatePicker
                                        id="timetable-effective-until"
                                        name="effective_until"
                                        value={until}
                                        onValueChange={setUntil}
                                    />
                                </div>
                            </div>
                            {Object.values(errors).map((e, i) => (
                                <p key={i} className="text-xs text-destructive">
                                    {e}
                                </p>
                            ))}
                            <DialogFooter>
                                <Button disabled={processing || !a || !from}>
                                    {processing && <Spinner />}Save Entry
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
export default function Timetables({
    college,
    allocations,
    rooms,
    entries,
    can,
}: Props) {
    return (
        <>
            <Head title={`${college.name} Timetable`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex justify-between border-b pb-5">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {college.code} · Course Delivery
                        </p>
                        <h1 className="text-3xl font-semibold">Timetable</h1>
                        <p className="text-muted-foreground">
                            Recurring weekly teaching plan linked to Faculty
                            Allocation and Rooms.
                        </p>
                    </div>
                    {can.manage && (
                        <Add
                            collegeId={college.id}
                            allocations={allocations}
                            rooms={rooms}
                        />
                    )}
                </header>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex gap-2">
                            <CalendarDays />
                            Weekly Entries
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr>
                                        {[
                                            'Day / Time',
                                            'Faculty / Scope',
                                            'Room',
                                            'Effective Period',
                                            'Classes',
                                            'Status',
                                            'Action',
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
                                    {entries.map((e) => {
                                        const target =
                                            e.status === 'ACTIVE'
                                                ? 'INACTIVE'
                                                : 'ACTIVE';

                                        return (
                                            <tr className="border-t" key={e.id}>
                                                <td className="p-3">
                                                    <b>{days[e.day_of_week]}</b>
                                                    <div>
                                                        {e.start_time.slice(
                                                            0,
                                                            5,
                                                        )}
                                                        –
                                                        {e.end_time.slice(0, 5)}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    {
                                                        e.faculty_allocation
                                                            .faculty.name
                                                    }
                                                    <div className="text-xs text-muted-foreground">
                                                        {e.faculty_allocation
                                                            .section?.name ??
                                                            'Entire Batch'}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    {e.room
                                                        ? `${e.room.code} · ${e.room.name}`
                                                        : 'Unassigned'}
                                                </td>
                                                <td className="p-3">
                                                    {String(
                                                        e.effective_from,
                                                    ).slice(0, 10)}{' '}
                                                    –{' '}
                                                    {e.effective_until
                                                        ? String(
                                                              e.effective_until,
                                                          ).slice(0, 10)
                                                        : 'Ongoing'}
                                                </td>
                                                <td className="p-3">
                                                    {e.class_schedules.length}
                                                </td>
                                                <td className="p-3">
                                                    <Badge
                                                        variant={
                                                            e.status ===
                                                            'ACTIVE'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {e.status}
                                                    </Badge>
                                                </td>
                                                <td className="p-3">
                                                    {((target === 'ACTIVE' &&
                                                        can.enable) ||
                                                        (target ===
                                                            'INACTIVE' &&
                                                            can.disable)) && (
                                                        <Form
                                                            method="patch"
                                                            action={`/college/${college.id}/timetables/${e.id}/status`}
                                                        >
                                                            {({
                                                                processing,
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
                                                                        variant="outline"
                                                                        disabled={
                                                                            processing
                                                                        }
                                                                    >
                                                                        <Power />
                                                                        {target ===
                                                                        'ACTIVE'
                                                                            ? 'Activate'
                                                                            : 'Deactivate'}
                                                                    </Button>
                                                                </>
                                                            )}
                                                        </Form>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    {!entries.length && (
                                        <tr>
                                            <td
                                                colSpan={7}
                                                className="p-10 text-center text-muted-foreground"
                                            >
                                                No Timetable entries configured.
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
