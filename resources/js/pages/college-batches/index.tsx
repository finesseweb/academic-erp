import { Form, Head } from '@inertiajs/react';
import { Layers3, Pencil, Plus, Power, ShieldCheck } from 'lucide-react';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

type NamedOption = { id: number; name: string; code: string };
type Intake = {
    id: number;
    approved_capacity: number;
    status: 'ACTIVE' | 'INACTIVE';
};
type Offering = {
    id: number;
    status: 'ACTIVE' | 'INACTIVE';
    program_template: NamedOption;
    curriculum: NamedOption & { version: string };
    academic_session: NamedOption & { status: string };
    intake: Intake | null;
    activation_ready?: boolean;
};
type Batch = {
    id: number;
    college_program_offering_id: number;
    code: string;
    name: string;
    status: 'ACTIVE' | 'INACTIVE';
    notes: string | null;
    active_sections_count?: number;
    offering: Offering;
};

type Props = {
    college: { id: number; name: string; code: string; status: string };
    batches: Batch[];
    offerings: Offering[];
    summary: {
        total: number;
        active: number;
        inactive: number;
        ready_offerings: number;
    };
    can: {
        create: boolean;
        update: boolean;
        enable: boolean;
        disable: boolean;
    };
};

function BatchForm({
    collegeId,
    offerings,
    batch,
}: {
    collegeId: number;
    offerings: Offering[];
    batch?: Batch;
}) {
    const [offeringId, setOfferingId] = useState(
        String(batch?.college_program_offering_id ?? ''),
    );

    const action = batch
        ? `/college/${collegeId}/batches/${batch.id}`
        : `/college/${collegeId}/batches`;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant={batch ? 'ghost' : 'default'} size={batch ? 'icon' : 'default'}>
                    {batch ? <Pencil /> : <Plus />}
                    {!batch && 'Add Batch'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-xl">
                <DialogTitle>{batch ? 'Edit Batch' : 'Add Batch'}</DialogTitle>
                <DialogDescription>
                    A Batch belongs to one exact College Program Offering. It does not create or recalculate seats; Intake and Reservation remain the seat-capacity authority.
                </DialogDescription>
                <Form action={action} method={batch ? 'patch' : 'post'} className="space-y-4">
                    {({ processing, errors }) => (
                        <>
                            <div className="space-y-2">
                                <Label>Program Offering</Label>
                                <Select
                                    name="college_program_offering_id"
                                    value={offeringId}
                                    onValueChange={setOfferingId}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select active Program Offering" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {offerings.map((offering) => (
                                            <SelectItem key={offering.id} value={String(offering.id)}>
                                                {offering.program_template.name} ({offering.program_template.code}) · {offering.academic_session.name}
                                                {offering.intake
                                                    ? ` · Intake ${offering.intake.approved_capacity} · ${offering.intake.status}`
                                                    : ' · Intake not configured'}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.college_program_offering_id && (
                                    <p className="text-xs text-destructive">{errors.college_program_offering_id}</p>
                                )}
                                {batch?.status === 'ACTIVE' && (
                                    <p className="text-xs text-muted-foreground">
                                        An active Batch cannot be moved to another Program Offering.
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor={`batch-code-${batch?.id ?? 'new'}`}>Batch Code</Label>
                                    <Input
                                        id={`batch-code-${batch?.id ?? 'new'}`}
                                        name="code"
                                        defaultValue={batch?.code ?? ''}
                                        placeholder="BA-2026-30"
                                    />
                                    {errors.code && <p className="text-xs text-destructive">{errors.code}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor={`batch-name-${batch?.id ?? 'new'}`}>Batch Name</Label>
                                    <Input
                                        id={`batch-name-${batch?.id ?? 'new'}`}
                                        name="name"
                                        defaultValue={batch?.name ?? ''}
                                        placeholder="BA 2026-2030 Batch"
                                    />
                                    {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor={`batch-notes-${batch?.id ?? 'new'}`}>Notes (optional)</Label>
                                <Textarea
                                    id={`batch-notes-${batch?.id ?? 'new'}`}
                                    name="notes"
                                    defaultValue={batch?.notes ?? ''}
                                    placeholder="Operational note for this cohort, if required."
                                />
                                {errors.notes && <p className="text-xs text-destructive">{errors.notes}</p>}
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline" disabled={processing}>Cancel</Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing || !offeringId}>
                                    {processing && <Spinner />}
                                    {processing ? 'Saving...' : 'Save Batch'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function StatusAction({
    collegeId,
    batch,
    can,
}: {
    collegeId: number;
    batch: Batch;
    can: Props['can'];
}) {
    const target = batch.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
    const allowed = target === 'ACTIVE' ? can.enable : can.disable;
    const blockedBySections = target === 'INACTIVE' && (batch.active_sections_count ?? 0) > 0;

    if (!allowed) return null;

    return (
        <div title={blockedBySections ? `Cannot deactivate this Batch because ${batch.active_sections_count} ACTIVE Section(s) depend on it. Deactivate the Sections first.` : undefined}>
        <Form action={`/college/${collegeId}/batches/${batch.id}/status`} method="patch">
            {({ processing, errors }) => (
                <div className="space-y-1">
                    <input type="hidden" name="status" value={target} />
                    <Button
                        type="submit"
                        variant={target === 'ACTIVE' ? 'default' : 'outline'}
                        size="sm"
                        disabled={processing || blockedBySections}
                    >
                        {processing ? <Spinner /> : <Power />}
                        {target === 'ACTIVE' ? 'Activate' : 'Deactivate'}
                    </Button>
                    {errors.status && <p className="max-w-56 text-xs text-destructive">{errors.status}</p>}
                </div>
            )}
        </Form>
        </div>
    );
}

export default function CollegeBatches({
    college,
    batches,
    offerings,
    summary,
    can,
}: Props) {
    return (
        <>
            <Head title={`${college.name} Batches`} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {college.code} · College Academic Setup
                        </p>
                        <h1 className="text-3xl font-semibold">Batch Management</h1>
                        <p className="max-w-4xl text-muted-foreground">
                            Create operational student cohorts only from existing College Program Offerings. Batch Management consumes the configured Program Offering and active Intake context; it never redefines seat capacity or reservation.
                        </p>
                    </div>
                    {can.create && college.status === 'ACTIVE' && (
                        <BatchForm collegeId={college.id} offerings={offerings.filter((offering) => offering.status === 'ACTIVE')} />
                    )}
                </header>

                {college.status !== 'ACTIVE' && (
                    <Card>
                        <CardContent className="p-4 text-sm text-muted-foreground">
                            This College is inactive. Existing Batches remain visible but cannot be changed.
                        </CardContent>
                    </Card>
                )}

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {[
                        ['Batches', summary.total],
                        ['Active', summary.active],
                        ['Inactive', summary.inactive],
                        ['Offerings Ready for Batch', summary.ready_offerings],
                    ].map(([label, value]) => (
                        <Card key={String(label)}>
                            <CardContent className="p-4">
                                <p className="text-xs text-muted-foreground">{label}</p>
                                <p className="mt-1 text-2xl font-semibold">{value}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Layers3 className="size-5" /> College Batches
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {batches.length === 0 ? (
                            <div className="grid place-items-center py-16 text-center">
                                <Layers3 className="size-10 text-muted-foreground" />
                                <h2 className="mt-3 font-semibold">No Batches Yet</h2>
                                <p className="mt-1 max-w-xl text-sm text-muted-foreground">
                                    Create the first Batch from an ACTIVE Program Offering. Activation will require that the offering also has an ACTIVE Intake / Seat Capacity.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/60 text-left">
                                        <tr>
                                            <th className="p-4">Batch</th>
                                            <th className="p-4">Program / Session</th>
                                            <th className="p-4">Curriculum</th>
                                            <th className="p-4">Intake Context</th>
                                            <th className="p-4">Status</th>
                                            <th className="p-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {batches.map((batch) => (
                                            <tr key={batch.id}>
                                                <td className="p-4 align-top">
                                                    <div className="font-medium">{batch.name}</div>
                                                    <div className="text-xs text-muted-foreground">{batch.code}</div>
                                                    {batch.notes && (
                                                        <div className="mt-1 max-w-xs text-xs text-muted-foreground">{batch.notes}</div>
                                                    )}
                                                </td>
                                                <td className="p-4 align-top">
                                                    <div className="font-medium">
                                                        {batch.offering.program_template.name} ({batch.offering.program_template.code})
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {batch.offering.academic_session.name} · {batch.offering.academic_session.status}
                                                    </div>
                                                </td>
                                                <td className="p-4 align-top">
                                                    <div>{batch.offering.curriculum.name}</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {batch.offering.curriculum.code} · V{batch.offering.curriculum.version}
                                                    </div>
                                                </td>
                                                <td className="p-4 align-top">
                                                    {batch.offering.intake ? (
                                                        <div>
                                                            <div className="font-medium">Capacity {batch.offering.intake.approved_capacity}</div>
                                                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                                                <ShieldCheck className="size-3" /> Intake {batch.offering.intake.status}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-muted-foreground">Not configured</span>
                                                    )}
                                                </td>
                                                <td className="p-4 align-top">
                                                    <Badge variant={batch.status === 'ACTIVE' ? 'default' : 'secondary'}>
                                                        {batch.status}
                                                    </Badge>
                                                </td>
                                                <td className="p-4 align-top">
                                                    <div className="flex flex-wrap items-start gap-2">
                                                        {can.update && college.status === 'ACTIVE' && (
                                                            <BatchForm collegeId={college.id} offerings={offerings} batch={batch} />
                                                        )}
                                                        {college.status === 'ACTIVE' && (
                                                            <StatusAction collegeId={college.id} batch={batch} can={can} />
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
