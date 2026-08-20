import { Form, Head } from '@inertiajs/react';
import { CalendarRange, CheckCircle2, Edit3, Plus, Star } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
type Props = {
    sessions: Session[];
    can: {
        create: boolean;
        update: boolean;
        close: boolean;
        setCurrent: boolean;
    };
};

function SessionForm({ session }: { session?: Session }) {
    const action = session
        ? `/admin/academic-sessions/${session.id}`
        : '/admin/academic-sessions';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant={session ? 'ghost' : 'default'}
                    size={session ? 'sm' : 'default'}
                >
                    {session ? <Edit3 /> : <Plus />}
                    {session ? 'Edit' : 'Add session'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-xl">
                <DialogTitle>
                    {session ? 'Edit academic session' : 'Add academic session'}
                </DialogTitle>
                <DialogDescription>
                    Define the official University academic period. Dates are
                    stored exactly as selected.
                </DialogDescription>
                <Form
                    action={action}
                    method={session ? 'patch' : 'post'}
                    resetOnSuccess={!session}
                    className="grid gap-4 sm:grid-cols-2"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor={`name-${session?.id ?? 'new'}`}>
                                    Session name
                                </Label>
                                <Input
                                    id={`name-${session?.id ?? 'new'}`}
                                    name="name"
                                    defaultValue={session?.name}
                                    aria-invalid={Boolean(errors.name)}
                                    autoFocus
                                />
                                <p className="text-sm text-destructive">
                                    {errors.name}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor={`code-${session?.id ?? 'new'}`}>
                                    Code
                                </Label>
                                <Input
                                    id={`code-${session?.id ?? 'new'}`}
                                    name="code"
                                    defaultValue={session?.code}
                                    aria-invalid={Boolean(errors.code)}
                                />
                                <p className="text-sm text-destructive">
                                    {errors.code}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label
                                    htmlFor={`start-${session?.id ?? 'new'}`}
                                >
                                    Start date
                                </Label>
                                <DatePicker
                                    id={`start-${session?.id ?? 'new'}`}
                                    name="starts_on"
                                    defaultValue={session?.starts_on}
                                    invalid={Boolean(errors.starts_on)}
                                />
                                <p className="text-sm text-destructive">
                                    {errors.starts_on}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor={`end-${session?.id ?? 'new'}`}>
                                    End date
                                </Label>
                                <DatePicker
                                    id={`end-${session?.id ?? 'new'}`}
                                    name="ends_on"
                                    defaultValue={session?.ends_on}
                                    invalid={Boolean(errors.ends_on)}
                                />
                                <p className="text-sm text-destructive">
                                    {errors.ends_on}
                                </p>
                            </div>
                            <div className="space-y-2 sm:col-span-2">
                                <Label>Status</Label>
                                <Select
                                    name="status"
                                    defaultValue={session?.status ?? 'PLANNED'}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {[
                                            'PLANNED',
                                            'ACTIVE',
                                            'CLOSED',
                                            'ARCHIVED',
                                        ].map((status) => (
                                            <SelectItem
                                                key={status}
                                                value={status}
                                            >
                                                {status}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <DialogFooter className="sm:col-span-2">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {processing ? 'Saving...' : 'Save session'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function AcademicSessions({ sessions, can }: Props) {
    return (
        <>
            <Head title="Academic Sessions" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <p className="flex items-center gap-2 text-sm font-medium text-primary">
                            <CalendarRange className="size-4" />
                            Academic structure
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Academic Sessions
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Manage University academic periods and the single
                            current session.
                        </p>
                    </div>
                    {can.create && <SessionForm />}
                </header>
                {sessions.length === 0 ? (
                    <Card>
                        <CardContent className="flex min-h-64 flex-col items-center justify-center gap-3 text-center">
                            <CalendarRange className="size-10 text-muted-foreground" />
                            <h2 className="font-semibold">
                                No academic sessions yet
                            </h2>
                            <p className="max-w-md text-sm text-muted-foreground">
                                Add the first session to establish the academic
                                calendar.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 lg:grid-cols-2">
                        {sessions.map((session) => (
                            <Card
                                key={session.id}
                                className={
                                    session.is_current ? 'border-primary' : ''
                                }
                            >
                                <CardContent className="space-y-4 p-5">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h2 className="font-semibold">
                                                    {session.name}
                                                </h2>
                                                {session.is_current && (
                                                    <span className="rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">
                                                        Current
                                                    </span>
                                                )}
                                                <span className="rounded-full bg-muted px-2 py-1 text-xs">
                                                    {session.status}
                                                </span>
                                            </div>
                                            <p className="mt-1 font-mono text-xs text-muted-foreground">
                                                {session.code}
                                            </p>
                                        </div>
                                        {can.update && (
                                            <SessionForm session={session} />
                                        )}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {new Date(
                                            session.starts_on,
                                        ).toLocaleDateString()}{' '}
                                        -{' '}
                                        {new Date(
                                            session.ends_on,
                                        ).toLocaleDateString()}
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {can.setCurrent &&
                                            !session.is_current &&
                                            !['CLOSED', 'ARCHIVED'].includes(
                                                session.status,
                                            ) && (
                                                <Form
                                                    action={`/admin/academic-sessions/${session.id}/current`}
                                                    method="patch"
                                                >
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <Star />
                                                        Set current
                                                    </Button>
                                                </Form>
                                            )}
                                        {can.close &&
                                            session.status !== 'CLOSED' && (
                                                <Form
                                                    action={`/admin/academic-sessions/${session.id}/status`}
                                                    method="patch"
                                                >
                                                    <input
                                                        type="hidden"
                                                        name="status"
                                                        value="CLOSED"
                                                    />
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                    >
                                                        <CheckCircle2 />
                                                        Close
                                                    </Button>
                                                </Form>
                                            )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
