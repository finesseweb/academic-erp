import { Form, Head } from '@inertiajs/react';
import { ChevronDown, ChevronRight, Layers3, Pencil, Plus, Power } from 'lucide-react';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type NamedOption = { id: number; name: string; code: string };
type Intake = { id: number; approved_capacity: number; status: 'ACTIVE' | 'INACTIVE' };
type Offering = {
    id: number;
    status: 'ACTIVE' | 'INACTIVE';
    program_template: NamedOption;
    curriculum: NamedOption & { version: string };
    academic_session: NamedOption & { status: string };
    intake: Intake | null;
};
type Batch = {
    id: number;
    code: string;
    name: string;
    status: 'ACTIVE' | 'INACTIVE';
    offering: Offering;
    activation_ready?: boolean;
};
type Section = {
    id: number;
    batch_id: number;
    code: string;
    name: string;
    status: 'ACTIVE' | 'INACTIVE';
    notes: string | null;
    batch: Batch;
};
type Props = {
    college: { id: number; name: string; code: string; status: string };
    sections: Section[];
    batches: Batch[];
    summary: { total: number; active: number; inactive: number; active_batches: number };
    can: { create: boolean; update: boolean; enable: boolean; disable: boolean };
};

function SectionForm({
    collegeId,
    batches,
    section,
    parentBatch,
}: {
    collegeId: number;
    batches: Batch[];
    section?: Section;
    parentBatch?: Batch;
}) {
    const [batchId, setBatchId] = useState(String(section?.batch_id ?? parentBatch?.id ?? ''));
    const action = section
        ? `/college/${collegeId}/sections/${section.id}`
        : `/college/${collegeId}/sections`;
    const fixedParent = !section && parentBatch;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant={section ? 'ghost' : 'outline'} size={section ? 'icon' : 'sm'}>
                    {section ? <Pencil /> : <Plus />}
                    {!section && 'Add Section'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-xl">
                <DialogTitle>{section ? 'Edit Section' : `Add Section · ${parentBatch?.name ?? ''}`}</DialogTitle>
                <DialogDescription>
                    A Section belongs to one exact Batch. Program, Session, Curriculum, Intake and reservation context are inherited from that Batch and are never redefined here.
                </DialogDescription>
                <Form action={action} method={section ? 'patch' : 'post'} className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            {fixedParent ? (
                                <div className="rounded-lg border bg-muted/30 p-3">
                                    <input type="hidden" name="batch_id" value={parentBatch.id} />
                                    <p className="text-sm font-medium">{parentBatch.name}</p>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {parentBatch.offering.program_template.name} ({parentBatch.offering.program_template.code}) · {parentBatch.offering.academic_session.name} · {parentBatch.offering.curriculum.name}
                                    </p>
                                    <p className="mt-1 text-xs text-muted-foreground">This parent Batch is fixed for the new Section.</p>
                                    {errors.batch_id && <p className="mt-1 text-xs text-destructive">{errors.batch_id}</p>}
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    <Label>Batch</Label>
                                    <Select name="batch_id" value={batchId} onValueChange={setBatchId}>
                                        <SelectTrigger><SelectValue placeholder="Select active Batch" /></SelectTrigger>
                                        <SelectContent>
                                            {batches.map((batch) => (
                                                <SelectItem key={batch.id} value={String(batch.id)}>
                                                    {batch.name} ({batch.code}) · {batch.offering.program_template.name} · {batch.offering.academic_session.name} · {batch.status}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.batch_id && <p className="text-xs text-destructive">{errors.batch_id}</p>}
                                    {section?.status === 'ACTIVE' && (
                                        <p className="text-xs text-muted-foreground">An ACTIVE Section cannot be moved to another Batch.</p>
                                    )}
                                </div>
                            )}
                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor={`section-code-${section?.id ?? 'new'}`}>Section Code</Label>
                                    <Input id={`section-code-${section?.id ?? 'new'}`} name="code" defaultValue={section?.code ?? ''} placeholder="A" />
                                    {errors.code && <p className="text-xs text-destructive">{errors.code}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor={`section-name-${section?.id ?? 'new'}`}>Section Name</Label>
                                    <Input id={`section-name-${section?.id ?? 'new'}`} name="name" defaultValue={section?.name ?? ''} placeholder="Section A" />
                                    {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor={`section-notes-${section?.id ?? 'new'}`}>Notes (optional)</Label>
                                <Textarea id={`section-notes-${section?.id ?? 'new'}`} name="notes" defaultValue={section?.notes ?? ''} placeholder="Operational note for this Section, if required." />
                                {errors.notes && <p className="text-xs text-destructive">{errors.notes}</p>}
                            </div>
                            <DialogFooter>
                                <DialogClose asChild><Button type="button" variant="outline" disabled={processing}>Cancel</Button></DialogClose>
                                <Button type="submit" disabled={processing || !batchId}>
                                    {processing && <Spinner />}{processing ? 'Saving...' : 'Save Section'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function StatusAction({ collegeId, section, can }: { collegeId: number; section: Section; can: Props['can'] }) {
    const target = section.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
    const allowed = target === 'ACTIVE' ? can.enable : can.disable;
    if (!allowed) return null;

    return (
        <Form action={`/college/${collegeId}/sections/${section.id}/status`} method="patch">
            {({ processing, errors }) => (
                <div className="space-y-1">
                    <input type="hidden" name="status" value={target} />
                    <Button type="submit" variant={target === 'ACTIVE' ? 'default' : 'outline'} size="sm" disabled={processing}>
                        {processing ? <Spinner /> : <Power />}{target === 'ACTIVE' ? 'Activate' : 'Deactivate'}
                    </Button>
                    {errors.status && <p className="max-w-64 text-xs text-destructive">{errors.status}</p>}
                </div>
            )}
        </Form>
    );
}

function BatchSectionCard({
    college,
    batch,
    sections,
    allBatches,
    can,
}: {
    college: Props['college'];
    batch: Batch;
    sections: Section[];
    allBatches: Batch[];
    can: Props['can'];
}) {
    const [expanded, setExpanded] = useState(false);
    const activeCount = sections.filter((section) => section.status === 'ACTIVE').length;
    const inactiveCount = sections.length - activeCount;
    const intake = batch.offering.intake;

    return (
        <Card>
            <CardContent className="p-4 md:p-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="flex items-center gap-2 text-base font-semibold md:text-lg">
                                <Layers3 className="size-5" /> {batch.name}
                            </h2>
                            <Badge variant={batch.status === 'ACTIVE' ? 'default' : 'secondary'}>{batch.status}</Badge>
                        </div>
                        <p className="mt-1 text-xs text-muted-foreground">{batch.code}</p>
                        <p className="mt-2 text-sm font-medium">
                            {batch.offering.program_template.name} ({batch.offering.program_template.code}) / {batch.offering.academic_session.name}
                        </p>
                        <p className="mt-1 text-xs text-muted-foreground">
                            {batch.offering.curriculum.name}
                            {intake ? ` · Approved Intake ${intake.approved_capacity} · Intake ${intake.status}` : ' · Intake not configured'}
                        </p>

                        <button
                            type="button"
                            onClick={() => setExpanded((current) => !current)}
                            className="mt-3 flex w-full max-w-3xl items-center justify-between gap-4 rounded-lg border bg-muted/20 px-3 py-2.5 text-left transition-colors hover:bg-muted/40"
                            aria-expanded={expanded}
                        >
                            <span className="flex min-w-0 items-center gap-2">
                                {expanded ? (
                                    <ChevronDown className="size-4 shrink-0 text-muted-foreground" />
                                ) : (
                                    <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                                )}
                                <span className="font-medium">Sections</span>
                            </span>
                            <span className="shrink-0 text-xs text-muted-foreground sm:text-sm">
                                {sections.length} section{sections.length === 1 ? '' : 's'} · {activeCount} active · {inactiveCount} inactive
                            </span>
                        </button>
                    </div>

                    {can.create && college.status === 'ACTIVE' && batch.activation_ready && (
                        <SectionForm collegeId={college.id} batches={[batch]} parentBatch={batch} />
                    )}
                </div>

                {expanded ? (
                    <div className="mt-2 max-w-3xl overflow-hidden rounded-lg border bg-background">
                        <div className="grid grid-cols-[minmax(180px,1fr)_110px_minmax(170px,auto)] items-center border-b bg-muted/30 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            <div>Section</div>
                            <div>Status</div>
                            <div className="text-right">Actions</div>
                        </div>

                        <div className="max-h-72 divide-y overflow-y-auto">
                            {sections.length === 0 ? (
                                <div className="px-4 py-5 text-sm text-muted-foreground">
                                    {batch.activation_ready
                                        ? 'No Sections mapped yet. Use Add Section to create the first Section under this Batch.'
                                        : 'No Sections mapped. This Batch or its parent academic context is not active.'}
                                </div>
                            ) : (
                                sections.map((section) => (
                                    <div
                                        key={section.id}
                                        className="grid grid-cols-[minmax(180px,1fr)_110px_minmax(170px,auto)] items-center gap-3 px-4 py-3 text-sm"
                                    >
                                        <div className="min-w-0">
                                            <p className="font-medium">{section.name}</p>
                                            <p className="text-xs text-muted-foreground">Code: {section.code}</p>
                                            {section.notes && <p className="mt-1 truncate text-xs text-muted-foreground">{section.notes}</p>}
                                        </div>
                                        <div>
                                            <Badge variant={section.status === 'ACTIVE' ? 'default' : 'secondary'}>{section.status}</Badge>
                                        </div>
                                        <div className="flex justify-end gap-2">
                                            {can.update && college.status === 'ACTIVE' && (
                                                <SectionForm collegeId={college.id} batches={allBatches} section={section} />
                                            )}
                                            {college.status === 'ACTIVE' && <StatusAction collegeId={college.id} section={section} can={can} />}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                ) : null}
            </CardContent>
        </Card>
    );
}

export default function CollegeSections({ college, sections, batches, summary, can }: Props) {
    return (
        <>
            <Head title={`${college.name} Sections`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="border-b pb-5">
                    <div>
                        <p className="text-sm font-medium text-primary">{college.code} · College Academic Setup</p>
                        <h1 className="text-3xl font-semibold">Section Management</h1>
                        <p className="max-w-4xl text-muted-foreground">
                            Manage Sections inside each Batch using the same expandable-row pattern already used across Academic Structure. Shared Program, Session, Curriculum and approved Intake remain Batch-level context; Sections do not own admission capacity.
                        </p>
                    </div>
                </header>

                {college.status !== 'ACTIVE' && (
                    <Card><CardContent className="p-4 text-sm text-muted-foreground">This College is inactive. Existing Sections remain visible but cannot be changed.</CardContent></Card>
                )}

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {[
                        ['Sections', summary.total],
                        ['Active', summary.active],
                        ['Inactive', summary.inactive],
                        ['Active Batches Ready', summary.active_batches],
                    ].map(([label, value]) => (
                        <Card key={String(label)}><CardContent className="p-4"><p className="text-xs text-muted-foreground">{label}</p><p className="mt-1 text-2xl font-semibold">{value}</p></CardContent></Card>
                    ))}
                </div>

                {batches.length === 0 ? (
                    <Card>
                        <CardContent className="grid place-items-center py-16 text-center">
                            <Layers3 className="size-10 text-muted-foreground" />
                            <h2 className="mt-3 font-semibold">No Batches Yet</h2>
                            <p className="mt-1 max-w-xl text-sm text-muted-foreground">Create and activate a Batch first. Sections are always managed under a Batch.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {batches.map((batch) => (
                            <BatchSectionCard
                                key={batch.id}
                                college={college}
                                batch={batch}
                                sections={sections.filter((section) => section.batch_id === batch.id).sort((a, b) => a.code.localeCompare(b.code, undefined, { numeric: true }))}
                                allBatches={batches}
                                can={can}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
