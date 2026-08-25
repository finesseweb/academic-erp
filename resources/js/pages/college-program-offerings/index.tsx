import { Form, Head } from '@inertiajs/react';
import { BookOpenCheck, GraduationCap, Pencil, Plus, Power } from 'lucide-react';
import { useMemo, useState } from 'react';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type Option = { id: number; name: string; code: string };
type Session = Option & { status: string; is_current: boolean };
type Curriculum = Option & {
    program_template_id: number;
    academic_session_id: number;
    version: string;
};
type Offering = {
    id: number;
    program_template_id: number;
    curriculum_id: number;
    academic_session_id: number;
    status: 'ACTIVE' | 'INACTIVE';
    program_template: Option;
    curriculum: Option & { version: string };
    academic_session: Session & { starts_on: string; ends_on: string };
};

type Props = {
    college: { id: number; name: string; code: string; status: string };
    offerings: Offering[];
    programTemplates: Option[];
    academicSessions: Session[];
    curricula: Curriculum[];
    can: { create: boolean; update: boolean; enable: boolean; disable: boolean };
};

function OfferingForm({
    collegeId,
    offering,
    programTemplates,
    academicSessions,
    curricula,
}: {
    collegeId: number;
    offering?: Offering;
    programTemplates: Option[];
    academicSessions: Session[];
    curricula: Curriculum[];
}) {
    const defaultCurrentSession = academicSessions.find(
        (session) =>
            session.is_current &&
            session.status === 'ACTIVE',
    );

    const [programId, setProgramId] = useState(
        String(offering?.program_template_id ?? ''),
    );
    const [sessionId, setSessionId] = useState(
        String(
            offering?.academic_session_id ??
                defaultCurrentSession?.id ??
                '',
        ),
    );
    const [curriculumId, setCurriculumId] = useState(
        String(offering?.curriculum_id ?? ''),
    );

    const availableCurricula = useMemo(
        () =>
            curricula.filter(
                (curriculum) =>
                    String(curriculum.program_template_id) === programId &&
                    String(curriculum.academic_session_id) === sessionId,
            ),
        [curricula, programId, sessionId],
    );

    const resetCurriculum = () => setCurriculumId('');
    const action = offering
        ? `/college/${collegeId}/program-offerings/${offering.id}`
        : `/college/${collegeId}/program-offerings`;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant={offering ? 'ghost' : 'default'} size={offering ? 'icon' : 'default'}>
                    {offering ? <Pencil /> : <Plus />}
                    {!offering && 'Add offering'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-xl">
                <DialogTitle>{offering ? 'Edit Program Offering' : 'Add Program Offering'}</DialogTitle>
                <DialogDescription>
                    Select an existing University Program Template, its approved Curriculum and the Academic Session. New offerings start inactive and are activated separately.
                </DialogDescription>
                <Form action={action} method={offering ? 'patch' : 'post'} className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="space-y-2">
                                <Label>Academic Session</Label>
                                <Select
                                    name="academic_session_id"
                                    value={sessionId}
                                    onValueChange={(value) => {
                                        setSessionId(value);
                                        resetCurriculum();
                                    }}
                                >
                                    <SelectTrigger><SelectValue placeholder="Select Academic Session" /></SelectTrigger>
                                    <SelectContent>
                                        {academicSessions.map((session) => (
                                            <SelectItem key={session.id} value={String(session.id)}>
                                                {session.name} ({session.code}) · {session.status}
                                                {session.is_current ? ' · CURRENT' : ''}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.academic_session_id && <p className="text-xs text-destructive">{errors.academic_session_id}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label>University Program</Label>
                                <Select
                                    name="program_template_id"
                                    value={programId}
                                    onValueChange={(value) => {
                                        setProgramId(value);
                                        resetCurriculum();
                                    }}
                                >
                                    <SelectTrigger><SelectValue placeholder="Select Program Template" /></SelectTrigger>
                                    <SelectContent>
                                        {programTemplates.map((program) => (
                                            <SelectItem key={program.id} value={String(program.id)}>
                                                {program.name} ({program.code})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.program_template_id && <p className="text-xs text-destructive">{errors.program_template_id}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label>Curriculum</Label>
                                <Select name="curriculum_id" value={curriculumId} onValueChange={setCurriculumId}>
                                    <SelectTrigger><SelectValue placeholder="Select approved Curriculum" /></SelectTrigger>
                                    <SelectContent>
                                        {availableCurricula.map((curriculum) => (
                                            <SelectItem key={curriculum.id} value={String(curriculum.id)}>
                                                {curriculum.name} ({curriculum.code}) · V{curriculum.version}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {programId && sessionId && availableCurricula.length === 0 && (
                                    <p className="text-xs text-muted-foreground">
                                        No current approved ACTIVE Curriculum is available for this Program and Academic Session.
                                    </p>
                                )}
                                {errors.curriculum_id && <p className="text-xs text-destructive">{errors.curriculum_id}</p>}
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline" disabled={processing}>Cancel</Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing || !programId || !sessionId || !curriculumId}>
                                    {processing && <Spinner />}
                                    {processing ? 'Saving...' : 'Save offering'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function CollegeProgramOfferings({
    college,
    offerings,
    programTemplates,
    academicSessions,
    curricula,
    can,
}: Props) {
    return (
        <>
            <Head title={`${college.name} Program Offerings`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {college.code} · College Academic Setup
                        </p>
                        <h1 className="text-3xl font-semibold">Program Offerings</h1>
                        <p className="text-muted-foreground">
                            Select University-defined Programs and approved Curricula that {college.name} will operate for each Academic Session.
                        </p>
                    </div>
                    {can.create && college.status === 'ACTIVE' && (
                        <OfferingForm
                            collegeId={college.id}
                            programTemplates={programTemplates}
                            academicSessions={academicSessions}
                            curricula={curricula}
                        />
                    )}
                </header>

                {college.status !== 'ACTIVE' && (
                    <Card>
                        <CardContent className="p-4 text-sm text-muted-foreground">
                            This College is inactive. Program Offerings remain visible but cannot be changed.
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <GraduationCap className="size-5" /> College Program List
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {offerings.length === 0 ? (
                            <div className="grid place-items-center py-16 text-center">
                                <BookOpenCheck className="size-10 text-muted-foreground" />
                                <h2 className="mt-3 font-semibold">No Program Offerings</h2>
                                <p className="max-w-lg text-sm text-muted-foreground">
                                    Add the first University Program, Curriculum and Academic Session combination for this College.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/60 text-left">
                                        <tr>
                                            <th className="p-4">Program</th>
                                            <th className="p-4">Academic Session</th>
                                            <th className="p-4">Curriculum</th>
                                            <th className="p-4">Status</th>
                                            <th className="p-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {offerings.map((offering) => (
                                            <tr key={offering.id} className="border-t align-top">
                                                <td className="p-4">
                                                    <div className="font-medium">{offering.program_template.name}</div>
                                                    <div className="text-xs text-muted-foreground">{offering.program_template.code}</div>
                                                </td>
                                                <td className="p-4">
                                                    <div>{offering.academic_session.name}</div>
                                                    <div className="text-xs text-muted-foreground">{offering.academic_session.code}</div>
                                                </td>
                                                <td className="p-4">
                                                    <div>{offering.curriculum.name}</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {offering.curriculum.code} · V{offering.curriculum.version}
                                                    </div>
                                                </td>
                                                <td className="p-4">
                                                    <span className="rounded-full bg-muted px-2 py-1 text-xs">{offering.status}</span>
                                                </td>
                                                <td className="p-4">
                                                    <div className="flex justify-end gap-1">
                                                        {can.update && college.status === 'ACTIVE' && (
                                                            <OfferingForm
                                                                collegeId={college.id}
                                                                offering={offering}
                                                                programTemplates={programTemplates}
                                                                academicSessions={academicSessions}
                                                                curricula={curricula}
                                                            />
                                                        )}
                                                        {college.status === 'ACTIVE' && (
                                                            (offering.status === 'ACTIVE' && can.disable) ||
                                                            (offering.status !== 'ACTIVE' && can.enable)
                                                        ) && (
                                                            <StatusDialog collegeId={college.id} offering={offering} />
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
            </div>
        </>
    );
}

function StatusDialog({ collegeId, offering }: { collegeId: number; offering: Offering }) {
    const activating = offering.status !== 'ACTIVE';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="icon" variant="ghost" aria-label={activating ? 'Activate offering' : 'Deactivate offering'}>
                    <Power />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{activating ? 'Activate Program Offering?' : 'Deactivate Program Offering?'}</DialogTitle>
                <DialogDescription>
                    {activating
                        ? 'Activation makes this College Program Offering available to later academic setup modules.'
                        : 'The offering remains in history but will not be available for new downstream setup while inactive.'}
                </DialogDescription>
                <Form action={`/college/${collegeId}/program-offerings/${offering.id}/status`} method="patch">
                    {({ processing }) => (
                        <DialogFooter>
                            <input type="hidden" name="status" value={activating ? 'ACTIVE' : 'INACTIVE'} />
                            <DialogClose asChild><Button type="button" variant="outline">Cancel</Button></DialogClose>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                {processing ? 'Working...' : activating ? 'Activate' : 'Deactivate'}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
