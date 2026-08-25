import { Form, Head } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCircle2,
    Edit3,
    Plus,
    Power,
    PowerOff,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
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

type Session = {
    id: number;
    name: string;
    code: string;
    starts_on: string;
    ends_on: string;
    status: string;
    is_current: boolean;
};

type CalendarEvent = {
    id: number;
    event_type: string;
    title: string;
    start_date: string;
    end_date: string;
    description: string | null;
    allow_college_override: boolean;
    status: 'ACTIVE' | 'INACTIVE';
    display_order: number;
};

type AcademicCalendar = {
    id: number;
    academic_session_id: number;
    name: string;
    code: string;
    notes: string | null;
    status: 'ACTIVE' | 'INACTIVE';
    academic_session: Session;
    events: CalendarEvent[];
};

type Props = {
    sessions: Session[];
    calendars: AcademicCalendar[];
    can: {
        create: boolean;
        update: boolean;
        disable: boolean;
        eventCreate: boolean;
        eventUpdate: boolean;
        eventDisable: boolean;
    };
};

const eventTypes = [
    ['ACADEMIC', 'Academic'],
    ['REGISTRATION', 'Registration'],
    ['INSTRUCTION', 'Instruction / Teaching'],
    ['EXAMINATION_WINDOW', 'Examination Window'],
    ['HOLIDAY', 'Holiday'],
    ['VACATION', 'Vacation / Break'],
    ['OTHER', 'Other'],
] as const;

const eventTypeLabel = (type: string) =>
    eventTypes.find(([value]) => value === type)?.[1] ?? type;

function CalendarForm({
    sessions,
    calendar,
}: {
    sessions: Session[];
    calendar?: AcademicCalendar;
}) {
    const action = calendar
        ? `/admin/academic-calendars/${calendar.id}`
        : '/admin/academic-calendars';
    const currentAcademicSession = sessions.find(
        (session) => session.is_current && session.status === 'ACTIVE',
    );

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant={calendar ? 'ghost' : 'default'}
                    size={calendar ? 'sm' : 'default'}
                >
                    {calendar ? <Edit3 /> : <Plus />}
                    {calendar ? 'Edit' : 'Add Calendar'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogTitle>
                    {calendar ? 'Edit Academic Calendar' : 'Add Academic Calendar'}
                </DialogTitle>
                <DialogDescription>
                    Define the official University calendar for one Academic Session.
                    Only one University Academic Calendar is allowed per session.
                </DialogDescription>
                <Form
                    action={action}
                    method={calendar ? 'patch' : 'post'}
                    resetOnSuccess={!calendar}
                    className="grid gap-4 sm:grid-cols-2"
                >
                    {({ processing, errors }) => (
                        <>
                            {!calendar && (
                                <div className="space-y-2 sm:col-span-2">
                                    <Label>Academic Session</Label>
                                    <Select
                                        name="academic_session_id"
                                        defaultValue={
                                            currentAcademicSession
                                                ? String(currentAcademicSession.id)
                                                : undefined
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select session" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {sessions.map((session) => (
                                                <SelectItem
                                                    key={session.id}
                                                    value={String(session.id)}
                                                >
                                                    {session.name} ({session.code})
                                                    {session.is_current ? ' · Current' : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.academic_session_id && (
                                        <p className="text-sm text-destructive">
                                            {errors.academic_session_id}
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label>Calendar Name</Label>
                                <Input
                                    name="name"
                                    defaultValue={calendar?.name}
                                    placeholder="Academic Calendar 2026-27"
                                    aria-invalid={Boolean(errors.name)}
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">{errors.name}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Calendar Code</Label>
                                <Input
                                    name="code"
                                    defaultValue={calendar?.code}
                                    placeholder="AC-2026-27"
                                    aria-invalid={Boolean(errors.code)}
                                />
                                {errors.code && (
                                    <p className="text-sm text-destructive">{errors.code}</p>
                                )}
                            </div>

                            {!calendar && (
                                <div className="space-y-2 sm:col-span-2">
                                    <Label>Status</Label>
                                    <Select name="status" defaultValue="ACTIVE">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="ACTIVE">Active</SelectItem>
                                            <SelectItem value="INACTIVE">Inactive</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div className="space-y-2 sm:col-span-2">
                                <Label>Notes</Label>
                                <textarea
                                    name="notes"
                                    defaultValue={calendar?.notes ?? ''}
                                    className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Optional University calendar note"
                                />
                                {errors.notes && (
                                    <p className="text-sm text-destructive">{errors.notes}</p>
                                )}
                            </div>

                            <DialogFooter className="sm:col-span-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="outline" disabled={processing}>
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {processing ? 'Saving...' : 'Save Calendar'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function EventForm({
    calendar,
    event,
}: {
    calendar: AcademicCalendar;
    event?: CalendarEvent;
}) {
    const action = event
        ? `/admin/academic-calendars/${calendar.id}/events/${event.id}`
        : `/admin/academic-calendars/${calendar.id}/events`;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    size="sm"
                    variant={event ? 'ghost' : 'outline'}
                    disabled={calendar.status !== 'ACTIVE'}
                >
                    {event ? <Edit3 /> : <Plus />}
                    {event ? 'Edit' : 'Add Event'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogTitle>{event ? 'Edit Calendar Event' : 'Add Calendar Event'}</DialogTitle>
                <DialogDescription>
                    Event dates must remain inside {calendar.academic_session.name} (
                    {new Date(calendar.academic_session.starts_on).toLocaleDateString()} -{' '}
                    {new Date(calendar.academic_session.ends_on).toLocaleDateString()}).
                </DialogDescription>
                <Form
                    action={action}
                    method={event ? 'patch' : 'post'}
                    resetOnSuccess={!event}
                    className="grid gap-4 sm:grid-cols-2"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="space-y-2">
                                <Label>Event Type</Label>
                                <Select name="event_type" defaultValue={event?.event_type ?? 'ACADEMIC'}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {eventTypes.map(([value, label]) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.event_type && (
                                    <p className="text-sm text-destructive">{errors.event_type}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Title</Label>
                                <Input
                                    name="title"
                                    defaultValue={event?.title}
                                    placeholder="Commencement of classes"
                                    aria-invalid={Boolean(errors.title)}
                                />
                                {errors.title && (
                                    <p className="text-sm text-destructive">{errors.title}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Start Date</Label>
                                <DatePicker
                                    id={`event-start-${event?.id ?? 'new'}`}
                                    name="start_date"
                                    defaultValue={event?.start_date}
                                    invalid={Boolean(errors.start_date)}
                                />
                                {errors.start_date && (
                                    <p className="text-sm text-destructive">{errors.start_date}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>End Date</Label>
                                <DatePicker
                                    id={`event-end-${event?.id ?? 'new'}`}
                                    name="end_date"
                                    defaultValue={event?.end_date}
                                    invalid={Boolean(errors.end_date)}
                                />
                                {errors.end_date && (
                                    <p className="text-sm text-destructive">{errors.end_date}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Display Order</Label>
                                <Input
                                    type="number"
                                    min="0"
                                    max="9999"
                                    name="display_order"
                                    defaultValue={event?.display_order ?? 0}
                                />
                            </div>

                            <label className="flex items-center gap-3 self-end rounded-md border p-3 text-sm">
                                <input
                                    type="hidden"
                                    name="allow_college_override"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    name="allow_college_override"
                                    value="1"
                                    defaultChecked={event?.allow_college_override ?? false}
                                    className="size-4"
                                />
                                <span>
                                    <span className="font-medium">Allow College override</span>
                                    <span className="block text-xs text-muted-foreground">
                                        Future College Academic Calendar may override this event when permitted.
                                    </span>
                                </span>
                            </label>

                            <div className="space-y-2 sm:col-span-2">
                                <Label>Description</Label>
                                <textarea
                                    name="description"
                                    defaultValue={event?.description ?? ''}
                                    className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Optional event details"
                                />
                            </div>

                            <DialogFooter className="sm:col-span-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="outline" disabled={processing}>
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {processing ? 'Saving...' : 'Save Event'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function AcademicCalendars({ sessions, calendars, can }: Props) {
    const availableSessions = sessions.filter(
        (session) => !calendars.some((calendar) => calendar.academic_session_id === session.id),
    );

    return (
        <>
            <Head title="Academic Calendar" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <p className="flex items-center gap-2 text-sm font-medium text-primary">
                            <CalendarDays className="size-4" />
                            Academic structure
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Academic Calendar
                        </h1>
                        <p className="max-w-3xl text-sm text-muted-foreground">
                            Define the official University calendar for each Academic Session and
                            the University events that future College calendars must follow unless
                            an event explicitly allows an override.
                        </p>
                    </div>
                    {can.create && availableSessions.length > 0 && (
                        <CalendarForm sessions={availableSessions} />
                    )}
                </header>

                {calendars.length === 0 ? (
                    <Card>
                        <CardContent className="flex min-h-64 flex-col items-center justify-center gap-3 text-center">
                            <CalendarDays className="size-10 text-muted-foreground" />
                            <h2 className="font-semibold">No Academic Calendar yet</h2>
                            <p className="max-w-md text-sm text-muted-foreground">
                                Create the University calendar for an Academic Session, then add
                                academic events, holidays, registration periods and other key dates.
                            </p>
                            {can.create && availableSessions.length > 0 && (
                                <CalendarForm sessions={availableSessions} />
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    calendars.map((calendar) => (
                        <Card key={calendar.id} className={calendar.academic_session.is_current ? 'border-primary' : ''}>
                            <CardHeader className="border-b">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <CardTitle>{calendar.name}</CardTitle>
                                            {calendar.academic_session.is_current && (
                                                <span className="rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">
                                                    Current Session
                                                </span>
                                            )}
                                            <span className="rounded-full bg-muted px-2 py-1 text-xs">
                                                {calendar.status}
                                            </span>
                                        </div>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {calendar.code} · {calendar.academic_session.name} ·{' '}
                                            {new Date(calendar.academic_session.starts_on).toLocaleDateString()} -{' '}
                                            {new Date(calendar.academic_session.ends_on).toLocaleDateString()}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        {can.update && <CalendarForm sessions={sessions} calendar={calendar} />}
                                        {can.eventCreate && <EventForm calendar={calendar} />}
                                        {can.disable && (
                                            <Form
                                                action={`/admin/academic-calendars/${calendar.id}/status`}
                                                method="patch"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value={calendar.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE'}
                                                />
                                                <Button size="sm" variant="outline">
                                                    {calendar.status === 'ACTIVE' ? <PowerOff /> : <Power />}
                                                    {calendar.status === 'ACTIVE' ? 'Set Inactive' : 'Set Active'}
                                                </Button>
                                            </Form>
                                        )}
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent className="p-0">
                                {calendar.events.length === 0 ? (
                                    <div className="p-8 text-center text-sm text-muted-foreground">
                                        No calendar events have been added for this session.
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead className="border-b bg-muted/50 text-left">
                                                <tr>
                                                    <th className="px-4 py-3">Event</th>
                                                    <th className="px-4 py-3">Type</th>
                                                    <th className="px-4 py-3">Date</th>
                                                    <th className="px-4 py-3">College Override</th>
                                                    <th className="px-4 py-3">Status</th>
                                                    <th className="px-4 py-3 text-right">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {calendar.events.map((event) => (
                                                    <tr key={event.id} className="border-b last:border-0">
                                                        <td className="px-4 py-4">
                                                            <div className="font-medium">{event.title}</div>
                                                            {event.description && (
                                                                <div className="mt-1 max-w-xl text-xs text-muted-foreground">
                                                                    {event.description}
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-4">{eventTypeLabel(event.event_type)}</td>
                                                        <td className="px-4 py-4 whitespace-nowrap">
                                                            {new Date(event.start_date).toLocaleDateString()}
                                                            {event.end_date !== event.start_date && (
                                                                <> - {new Date(event.end_date).toLocaleDateString()}</>
                                                            )}
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            {event.allow_college_override ? 'Allowed' : 'Not allowed'}
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-1 text-xs">
                                                                {event.status === 'ACTIVE' && <CheckCircle2 className="size-3" />}
                                                                {event.status}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-4">
                                                            <div className="flex justify-end gap-1">
                                                                {can.eventUpdate && <EventForm calendar={calendar} event={event} />}
                                                                {can.eventDisable && (
                                                                    <Form
                                                                        action={`/admin/academic-calendars/${calendar.id}/events/${event.id}/status`}
                                                                        method="patch"
                                                                    >
                                                                        <input
                                                                            type="hidden"
                                                                            name="status"
                                                                            value={event.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE'}
                                                                        />
                                                                        <Button type="submit" size="sm" variant="ghost">
                                                                            {event.status === 'ACTIVE' ? 'Disable' : 'Enable'}
                                                                        </Button>
                                                                    </Form>
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))
                )}

                {can.create && availableSessions.length === 0 && sessions.length > 0 && (
                    <p className="text-sm text-muted-foreground">
                        Every existing Academic Session already has a University Academic Calendar.
                    </p>
                )}
            </div>
        </>
    );
}
