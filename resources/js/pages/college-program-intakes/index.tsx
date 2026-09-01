import { Form, Head, router } from '@inertiajs/react';
import {
    GraduationCap,
    Pencil,
    Plus,
    Power,
    Trash2,
    UsersRound,
} from 'lucide-react';
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

type Option = { id: number; name: string; code: string };
type AcademicSession = Option & {
    is_current: boolean;
    status: string;
};
type Offering = {
    id: number;
    status: string;
    program_template_id: number;
    program_template: Option;
    curriculum: Option & { version: string };
    academic_session: AcademicSession;
};
type Specialization = Option & { parent_id: number };
type Discipline = Option & {
    mapping_id: number;
    program_template_id: number;
    college_program_offering_id: number;
    specialization_required?: boolean;
    specializations: Specialization[];
};
type Allocation = {
    id: number;
    discipline_id: number;
    parent_allocation_id: number | null;
    seat_scope_type: 'DISCIPLINE' | 'ADMISSION_SPECIALIZATION';
    specialization_id: number | null;
    seat_capacity: number;
    display_order: number;
    status: string;
    discipline: Option;
    specialization: Option | null;
};
type Intake = {
    id: number;
    college_program_offering_id: number;
    approved_capacity: number;
    allocation_mode: 'PROGRAM' | 'DISCIPLINE';
    status: 'ACTIVE' | 'INACTIVE';
    notes: string | null;
    offering: Offering;
    allocations: Allocation[];
};
type Props = {
    college: { id: number; name: string; code: string; status: string };
    intakes: Intake[];
    availableOfferings: Offering[];
    disciplines: Discipline[];
    can: {
        create: boolean;
        update: boolean;
        enable: boolean;
        disable: boolean;
    };
};

function IntakeForm({
    collegeId,
    availableOfferings,
    intake,
}: {
    collegeId: number;
    availableOfferings: Offering[];
    intake?: Intake;
}) {
    const currentOffering = availableOfferings.find(
        (offering) => offering.academic_session.is_current,
    );

    const action = intake
        ? `/college/${collegeId}/intakes/${intake.id}`
        : `/college/${collegeId}/intakes`;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant={intake ? 'ghost' : 'default'}
                    size={intake ? 'icon' : 'default'}
                >
                    {intake ? <Pencil /> : <Plus />}
                    {!intake && 'Add Intake'}
                </Button>
            </DialogTrigger>
            <DialogContent className="w-[calc(100vw-2rem)] max-w-xl overflow-hidden sm:w-full">
                <DialogTitle>
                    {intake
                        ? 'Edit Intake / Seat Capacity'
                        : 'Add Intake / Seat Capacity'}
                </DialogTitle>
                <DialogDescription>
                    Define the approved Program capacity. Choose Program-level capacity or Discipline-wise capacity. In Discipline-wise mode, Specialization capacity is optional and works as a child bucket inside the Discipline.
                </DialogDescription>

                <Form
                    action={action}
                    method={intake ? 'patch' : 'post'}
                    className="min-w-0 space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            {!intake && (
                                <div className="space-y-2">
                                    <Label>Active Program Offering</Label>
                                    <Select
                                        name="college_program_offering_id"
                                        defaultValue={
                                            currentOffering
                                                ? String(currentOffering.id)
                                                : undefined
                                        }
                                    >
                                        <SelectTrigger className="w-full min-w-0">
                                            <SelectValue className="min-w-0 truncate" placeholder="Select Program Offering" />
                                        </SelectTrigger>
                                        <SelectContent className="max-w-[calc(100vw-2rem)]">
                                            {availableOfferings.map(
                                                (offering) => (
                                                    <SelectItem
                                                        key={offering.id}
                                                        value={String(offering.id)}
                                                    >
                                                        {
                                                            offering
                                                                .program_template
                                                                .name
                                                        }{' '}
                                                        (
                                                        {
                                                            offering
                                                                .program_template
                                                                .code
                                                        }
                                                        ) ·{' '}
                                                        {
                                                            offering
                                                                .academic_session
                                                                .name
                                                        }
                                                        {offering
                                                            .academic_session
                                                            .is_current
                                                            ? ' · CURRENT'
                                                            : ''}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    {errors.college_program_offering_id && (
                                        <p className="text-xs text-destructive">
                                            {
                                                errors.college_program_offering_id
                                            }
                                        </p>
                                    )}
                                </div>
                            )}

                            {intake && (
                                <div className="min-w-0 break-words rounded-md border bg-muted/30 p-3 text-sm">
                                    <strong>
                                        {intake.offering.program_template.name}
                                    </strong>{' '}
                                    · {intake.offering.academic_session.name} ·{' '}
                                    {intake.offering.curriculum.name} V
                                    {intake.offering.curriculum.version}
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label>Approved Capacity</Label>
                                <Input
                                    className="w-full min-w-0"
                                    type="number"
                                    min={1}
                                    name="approved_capacity"
                                    defaultValue={intake?.approved_capacity}
                                    placeholder="e.g. 120"
                                />
                                {errors.approved_capacity && (
                                    <p className="text-xs text-destructive">
                                        {errors.approved_capacity}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Allocation Mode</Label>
                                <Select
                                    name="allocation_mode"
                                    defaultValue={
                                        intake?.allocation_mode ??
                                        'PROGRAM'
                                    }
                                >
                                    <SelectTrigger className="w-full min-w-0">
                                        <SelectValue className="min-w-0 truncate" />
                                    </SelectTrigger>
                                    <SelectContent className="max-w-[calc(100vw-2rem)]">
                                        <SelectItem value="PROGRAM" className="whitespace-normal">
                                            Program level — one sanctioned capacity
                                        </SelectItem>
                                        <SelectItem value="DISCIPLINE" className="whitespace-normal">
                                            Discipline-wise — optional Specialization capacity inside each Discipline
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.allocation_mode && (
                                    <p className="text-xs text-destructive">
                                        {errors.allocation_mode}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label>Notes</Label>
                                <textarea
                                    name="notes"
                                    defaultValue={intake?.notes ?? ''}
                                    className="min-h-24 w-full min-w-0 resize-y rounded-md border bg-background px-3 py-2 text-sm"
                                    placeholder="Optional approval/order/reference notes"
                                />
                            </div>

                            <DialogFooter className="flex-wrap">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Saving...'
                                        : 'Save Intake'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function AllocationForm({
    collegeId,
    intake,
    disciplines,
    allocation,
    parentDisciplineAllocation,
}: {
    collegeId: number;
    intake: Intake;
    disciplines: Discipline[];
    allocation?: Allocation;
    parentDisciplineAllocation?: Allocation;
}) {
    const eligible = useMemo(
        () =>
            disciplines.filter(
                (discipline) =>
                    discipline.college_program_offering_id ===
                    intake.offering.id,
            ),
        [disciplines, intake.offering.id],
    );

    const isSpecialization =
        Boolean(parentDisciplineAllocation) ||
        allocation?.seat_scope_type === 'ADMISSION_SPECIALIZATION';

    const lockedDisciplineId = parentDisciplineAllocation?.discipline_id;
    const [disciplineId, setDisciplineId] = useState(
        String(
            lockedDisciplineId ??
                allocation?.discipline_id ??
                '',
        ),
    );

    const selectedDiscipline = eligible.find(
        (discipline) =>
            String(discipline.id) === disciplineId,
    );

    const [specializationId, setSpecializationId] = useState(
        allocation?.specialization_id
            ? String(allocation.specialization_id)
            : '',
    );

    const action = allocation
        ? `/college/${collegeId}/intakes/${intake.id}/allocations/${allocation.id}`
        : `/college/${collegeId}/intakes/${intake.id}/allocations`;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant={allocation ? 'ghost' : 'outline'}
                    size={allocation ? 'icon' : 'sm'}
                >
                    {allocation ? <Pencil /> : <Plus />}
                    {!allocation &&
                        (isSpecialization
                            ? 'Add Specialization Capacity'
                            : 'Add Discipline Capacity')}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-lg">
                <DialogTitle>
                    {allocation
                        ? 'Edit Seat Capacity'
                        : isSpecialization
                          ? 'Add Specialization Capacity'
                          : 'Add Discipline Capacity'}
                </DialogTitle>
                <DialogDescription>
                    {isSpecialization
                        ? 'Specialization capacity is optional and is carved out of the parent Discipline capacity. Remaining Discipline seats stay available for students without a specialization.'
                        : 'Define the sanctioned capacity for this Discipline. Optional Specialization capacities can be added beneath it later.'}
                </DialogDescription>

                <Form
                    action={action}
                    method={allocation ? 'patch' : 'post'}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="seat_scope_type"
                                value={
                                    isSpecialization
                                        ? 'ADMISSION_SPECIALIZATION'
                                        : 'DISCIPLINE'
                                }
                            />
                            <input
                                type="hidden"
                                name="parent_allocation_id"
                                value={
                                    parentDisciplineAllocation?.id ??
                                    allocation?.parent_allocation_id ??
                                    ''
                                }
                            />

                            <div className="space-y-2">
                                <Label>Discipline</Label>
                                {isSpecialization ? (
                                    <>
                                        <input
                                            type="hidden"
                                            name="discipline_id"
                                            value={disciplineId}
                                        />
                                        <div className="rounded-md border bg-muted/30 px-3 py-2 text-sm">
                                            {
                                                selectedDiscipline?.name
                                            }{' '}
                                            (
                                            {
                                                selectedDiscipline?.code
                                            }
                                            )
                                        </div>
                                    </>
                                ) : (
                                    <Select
                                        name="discipline_id"
                                        value={disciplineId}
                                        onValueChange={(value) => {
                                            setDisciplineId(value);
                                            setSpecializationId('');
                                        }}
                                    >
                                        <SelectTrigger className="w-full min-w-0">
                                            <SelectValue className="min-w-0 truncate" placeholder="Select Discipline" />
                                        </SelectTrigger>
                                        <SelectContent className="max-w-[calc(100vw-2rem)]">
                                            {eligible.map(
                                                (discipline) => (
                                                    <SelectItem
                                                        key={
                                                            discipline.id
                                                        }
                                                        value={String(
                                                            discipline.id,
                                                        )}
                                                    >
                                                        {
                                                            discipline.name
                                                        }{' '}
                                                        (
                                                        {
                                                            discipline.code
                                                        }
                                                        )
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                )}
                                {errors.discipline_id && (
                                    <p className="text-xs text-destructive">
                                        {errors.discipline_id}
                                    </p>
                                )}
                            </div>

                            {isSpecialization && (
                                <div className="space-y-2">
                                    <Label>Specialization</Label>
                                    <Select
                                        name="specialization_id"
                                        value={specializationId}
                                        onValueChange={
                                            setSpecializationId
                                        }
                                    >
                                        <SelectTrigger className="w-full min-w-0">
                                            <SelectValue className="min-w-0 truncate" placeholder="Select Specialization" />
                                        </SelectTrigger>
                                        <SelectContent className="max-w-[calc(100vw-2rem)]">
                                            {selectedDiscipline?.specializations.map(
                                                (specialization) => (
                                                    <SelectItem
                                                        key={
                                                            specialization.id
                                                        }
                                                        value={String(
                                                            specialization.id,
                                                        )}
                                                    >
                                                        {
                                                            specialization.name
                                                        }{' '}
                                                        (
                                                        {
                                                            specialization.code
                                                        }
                                                        )
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    {errors.specialization_id && (
                                        <p className="text-xs text-destructive">
                                            {errors.specialization_id}
                                        </p>
                                    )}
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label>
                                    {isSpecialization
                                        ? 'Specialization Capacity'
                                        : 'Discipline Capacity'}
                                </Label>
                                <Input
                                    className="w-full min-w-0"
                                    type="number"
                                    min={1}
                                    name="seat_capacity"
                                    defaultValue={
                                        allocation?.seat_capacity
                                    }
                                />
                                {errors.seat_capacity && (
                                    <p className="text-xs text-destructive">
                                        {errors.seat_capacity}
                                    </p>
                                )}
                            </div>

                            <DialogFooter className="flex-wrap">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        !disciplineId ||
                                        (isSpecialization &&
                                            !specializationId)
                                    }
                                >
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Saving...'
                                        : 'Save Capacity'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}


function IntakeStatusDialog({
    collegeId,
    intake,
    readyToActivate,
    remainingForActivation,
}: {
    collegeId: number;
    intake: Intake;
    readyToActivate: boolean;
    remainingForActivation: number;
}) {
    const activating = intake.status !== 'ACTIVE';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    size="icon"
                    variant="ghost"
                    aria-label={
                        activating
                            ? 'Activate Intake / Seat Capacity'
                            : 'Deactivate Intake / Seat Capacity'
                    }
                    disabled={
                        activating && !readyToActivate
                    }
                    title={
                        activating && !readyToActivate
                            ? `Allocate the remaining ${remainingForActivation} Discipline seats before activation.`
                            : activating
                              ? 'Activate Intake / Seat Capacity'
                              : 'Deactivate Intake / Seat Capacity'
                    }
                >
                    <Power />
                </Button>
            </DialogTrigger>

            <DialogContent className="w-[calc(100vw-2rem)] max-w-md sm:w-full">
                <DialogTitle>
                    {activating
                        ? 'Activate Intake / Seat Capacity?'
                        : 'Deactivate Intake / Seat Capacity?'}
                </DialogTitle>

                <DialogDescription>
                    {activating
                        ? 'Activation freezes the current approved Intake structure for downstream Reservation / Seat Distribution and later Admission setup.'
                        : 'The Intake remains in history, but it will not be available for new downstream Reservation / Admission setup while inactive.'}
                </DialogDescription>

                <Form
                    action={`/college/${collegeId}/intakes/${intake.id}/status`}
                    method="patch"
                >
                    {({ processing }) => (
                        <DialogFooter>
                            <input
                                type="hidden"
                                name="status"
                                value={
                                    activating
                                        ? 'ACTIVE'
                                        : 'INACTIVE'
                                }
                            />

                            <DialogClose asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                >
                                    Cancel
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                disabled={
                                    processing ||
                                    (activating &&
                                        !readyToActivate)
                                }
                            >
                                {processing && <Spinner />}
                                {processing
                                    ? 'Working...'
                                    : activating
                                      ? 'Activate'
                                      : 'Deactivate'}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function CollegeProgramIntakes({
    college,
    intakes,
    availableOfferings,
    disciplines,
    can,
}: Props) {
    const deleteAllocation = (
        intake: Intake,
        allocation: Allocation,
    ) => {
        if (
            !window.confirm(
                'Remove this test/setup seat allocation? This is allowed only while the Intake is inactive.',
            )
        ) {
            return;
        }

        router.delete(
            `/college/${college.id}/intakes/${intake.id}/allocations/${allocation.id}`,
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`${college.name} Intake / Seat Capacity`} />

            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-wrap items-end justify-between gap-3 border-b pb-5">
                    <div>
                        <p className="text-sm font-medium text-primary">
                            {college.code} · College Academic Setup
                        </p>
                        <h1 className="text-3xl font-semibold">
                            Intake / Seat Capacity
                        </h1>
                        <p className="text-muted-foreground">
                            Define approved Program seats and optional
                            Admission seat allocations.
                        </p>
                    </div>

                    {can.create &&
                        college.status === 'ACTIVE' &&
                        availableOfferings.length > 0 && (
                            <IntakeForm
                                collegeId={college.id}
                                availableOfferings={availableOfferings}
                            />
                        )}
                </header>

                {college.status !== 'ACTIVE' && (
                    <Card>
                        <CardContent className="p-4 text-sm text-muted-foreground">
                            This College is inactive. Intake records remain
                            visible but cannot be changed.
                        </CardContent>
                    </Card>
                )}

                {intakes.length === 0 ? (
                    <Card>
                        <CardContent className="grid place-items-center py-16 text-center">
                            <UsersRound className="size-10 text-muted-foreground" />
                            <h2 className="mt-3 font-semibold">
                                No Intake / Seat Capacity configured
                            </h2>
                            <p className="mt-1 max-w-xl text-sm text-muted-foreground">
                                First activate a College Program Offering,
                                then define its approved intake.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {intakes.map((intake) => {
                            const allocated = intake.allocations
                                .filter(
                                    (allocation) =>
                                        allocation.status === 'ACTIVE' &&
                                        allocation.seat_scope_type ===
                                            'DISCIPLINE',
                                )
                                .reduce(
                                    (total, allocation) =>
                                        total +
                                        Number(allocation.seat_capacity),
                                    0,
                                );

                            const readyToActivate =
                                intake.allocation_mode === 'PROGRAM'
                                    ? Number(intake.approved_capacity) > 0
                                    : allocated ===
                                      Number(intake.approved_capacity);

                            const remainingForActivation =
                                Math.max(
                                    Number(intake.approved_capacity) -
                                        allocated,
                                    0,
                                );

                            return (
                                <Card key={intake.id}>
                                    <CardHeader className="gap-3">
                                        <div className="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <CardTitle className="flex items-center gap-2">
                                                    <GraduationCap className="size-5" />
                                                    {
                                                        intake.offering
                                                            .program_template
                                                            .name
                                                    }
                                                </CardTitle>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    {
                                                        intake.offering
                                                            .academic_session
                                                            .name
                                                    }{' '}
                                                    ·{' '}
                                                    {
                                                        intake.offering
                                                            .curriculum.name
                                                    }{' '}
                                                    V
                                                    {
                                                        intake.offering
                                                            .curriculum.version
                                                    }
                                                </p>
                                            </div>

                                            <div className="flex gap-2">
                                                {can.update &&
                                                    intake.status ===
                                                        'INACTIVE' && (
                                                        <IntakeForm
                                                            collegeId={
                                                                college.id
                                                            }
                                                            availableOfferings={
                                                                availableOfferings
                                                            }
                                                            intake={intake}
                                                        />
                                                    )}
                                                {college.status === 'ACTIVE' &&
                                                    ((intake.status === 'ACTIVE' &&
                                                        can.disable) ||
                                                        (intake.status !== 'ACTIVE' &&
                                                            can.enable)) && (
                                                        <IntakeStatusDialog
                                                            collegeId={
                                                                college.id
                                                            }
                                                            intake={
                                                                intake
                                                            }
                                                            readyToActivate={
                                                                readyToActivate
                                                            }
                                                            remainingForActivation={
                                                                remainingForActivation
                                                            }
                                                        />
                                                    )}
                                            </div>
                                        </div>

                                        {intake.status === 'INACTIVE' &&
                                            intake.allocation_mode ===
                                                'DISCIPLINE' &&
                                            !readyToActivate && (
                                                <div className="rounded-md border border-dashed px-3 py-2 text-xs text-muted-foreground">
                                                    Activation pending:{' '}
                                                    {allocated} of{' '}
                                                    {intake.approved_capacity}{' '}
                                                    Discipline seats allocated.
                                                    Add the remaining{' '}
                                                    {remainingForActivation}{' '}
                                                    seats to enable the Intake.
                                                </div>
                                            )}

                                        <div className="grid gap-2 sm:grid-cols-3">
                                            <div className="rounded-md border p-3">
                                                <div className="text-xs text-muted-foreground">
                                                    Approved Capacity
                                                </div>
                                                <div className="text-xl font-semibold">
                                                    {
                                                        intake.approved_capacity
                                                    }
                                                </div>
                                            </div>
                                            <div className="rounded-md border p-3">
                                                <div className="text-xs text-muted-foreground">
                                                    Allocation Mode
                                                </div>
                                                <div className="font-medium">
                                                    {intake.allocation_mode === 'PROGRAM'
                                                        ? 'Program level'
                                                        : 'Discipline-wise + optional Specialization'}
                                                </div>
                                            </div>
                                            <div className="rounded-md border p-3">
                                                <div className="text-xs text-muted-foreground">
                                                    Status
                                                </div>
                                                <div className="font-medium">
                                                    {intake.status}
                                                </div>
                                            </div>
                                        </div>
                                    </CardHeader>

                                    {intake.allocation_mode !== 'PROGRAM' && (
                                        <CardContent className="space-y-3">
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <div>
                                                    <h3 className="font-medium">
                                                        Seat Allocations
                                                    </h3>
                                                    <p className="text-xs text-muted-foreground">
                                                        {allocated} of{' '}
                                                        {
                                                            intake.approved_capacity
                                                        }{' '}
                                                        seats allocated.
                                                        Activation requires an
                                                        exact total.
                                                    </p>
                                                </div>

                                                {can.update &&
                                                    intake.status ===
                                                        'INACTIVE' && (
                                                        <AllocationForm
                                                            collegeId={
                                                                college.id
                                                            }
                                                            intake={intake}
                                                            disciplines={
                                                                disciplines
                                                            }
                                                        />
                                                    )}
                                            </div>

                                            {intake.allocations.filter(
                                                (allocation) =>
                                                    allocation.seat_scope_type ===
                                                    'DISCIPLINE',
                                            ).length === 0 ? (
                                                <div className="rounded-md border border-dashed p-5 text-sm text-muted-foreground">
                                                    No Discipline capacity added yet.
                                                </div>
                                            ) : (
                                                <div className="space-y-3">
                                                    {intake.allocations
                                                        .filter(
                                                            (allocation) =>
                                                                allocation.seat_scope_type ===
                                                                'DISCIPLINE',
                                                        )
                                                        .map(
                                                            (
                                                                disciplineAllocation,
                                                            ) => {
                                                                const specializationRows =
                                                                    intake.allocations.filter(
                                                                        (
                                                                            allocation,
                                                                        ) =>
                                                                            allocation.parent_allocation_id ===
                                                                                disciplineAllocation.id &&
                                                                            allocation.seat_scope_type ===
                                                                                'ADMISSION_SPECIALIZATION',
                                                                    );
                                                                const specializationTotal =
                                                                    specializationRows.reduce(
                                                                        (
                                                                            total,
                                                                            row,
                                                                        ) =>
                                                                            total +
                                                                            Number(
                                                                                row.seat_capacity,
                                                                            ),
                                                                        0,
                                                                    );
                                                                const generalRemaining =
                                                                    Number(
                                                                        disciplineAllocation.seat_capacity,
                                                                    ) -
                                                                    specializationTotal;

                                                                return (
                                                                    <div
                                                                        key={
                                                                            disciplineAllocation.id
                                                                        }
                                                                        className="rounded-md border p-4"
                                                                    >
                                                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                                                            <div>
                                                                                <div className="font-medium">
                                                                                    {
                                                                                        disciplineAllocation
                                                                                            .discipline
                                                                                            .name
                                                                                    }{' '}
                                                                                    —{' '}
                                                                                    {
                                                                                        disciplineAllocation.seat_capacity
                                                                                    }{' '}
                                                                                    seats
                                                                                </div>
                                                                                <div className="text-xs text-muted-foreground">
                                                                                    Specialization allocated:{' '}
                                                                                    {
                                                                                        specializationTotal
                                                                                    }{' '}
                                                                                    · General Discipline remaining:{' '}
                                                                                    {
                                                                                        generalRemaining
                                                                                    }
                                                                                </div>
                                                                            </div>

                                                                            {can.update &&
                                                                                intake.status ===
                                                                                    'INACTIVE' && (
                                                                                    <div className="flex gap-1">
                                                                                        <AllocationForm
                                                                                            collegeId={
                                                                                                college.id
                                                                                            }
                                                                                            intake={
                                                                                                intake
                                                                                            }
                                                                                            disciplines={
                                                                                                disciplines
                                                                                            }
                                                                                            allocation={
                                                                                                disciplineAllocation
                                                                                            }
                                                                                        />
                                                                                        <AllocationForm
                                                                                            collegeId={
                                                                                                college.id
                                                                                            }
                                                                                            intake={
                                                                                                intake
                                                                                            }
                                                                                            disciplines={
                                                                                                disciplines
                                                                                            }
                                                                                            parentDisciplineAllocation={
                                                                                                disciplineAllocation
                                                                                            }
                                                                                        />
                                                                                        <Button
                                                                                            type="button"
                                                                                            variant="ghost"
                                                                                            size="icon"
                                                                                            onClick={() =>
                                                                                                deleteAllocation(
                                                                                                    intake,
                                                                                                    disciplineAllocation,
                                                                                                )
                                                                                            }
                                                                                        >
                                                                                            <Trash2 className="size-4" />
                                                                                        </Button>
                                                                                    </div>
                                                                                )}
                                                                        </div>

                                                                        {specializationRows.length >
                                                                            0 && (
                                                                            <div className="mt-3 overflow-x-auto rounded-md border">
                                                                                <table className="w-full text-sm">
                                                                                    <thead className="bg-muted/50 text-left">
                                                                                        <tr>
                                                                                            <th className="p-3">
                                                                                                Specialization
                                                                                            </th>
                                                                                            <th className="p-3 text-right">
                                                                                                Capacity
                                                                                            </th>
                                                                                            <th className="p-3 text-right">
                                                                                                Actions
                                                                                            </th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        {specializationRows.map(
                                                                                            (
                                                                                                specializationAllocation,
                                                                                            ) => (
                                                                                                <tr
                                                                                                    key={
                                                                                                        specializationAllocation.id
                                                                                                    }
                                                                                                    className="border-t"
                                                                                                >
                                                                                                    <td className="p-3">
                                                                                                        {
                                                                                                            specializationAllocation
                                                                                                                .specialization
                                                                                                                ?.name
                                                                                                        }
                                                                                                    </td>
                                                                                                    <td className="p-3 text-right font-medium">
                                                                                                        {
                                                                                                            specializationAllocation.seat_capacity
                                                                                                        }
                                                                                                    </td>
                                                                                                    <td className="p-3">
                                                                                                        <div className="flex justify-end gap-1">
                                                                                                            {can.update &&
                                                                                                                intake.status ===
                                                                                                                    'INACTIVE' && (
                                                                                                                    <>
                                                                                                                        <AllocationForm
                                                                                                                            collegeId={
                                                                                                                                college.id
                                                                                                                            }
                                                                                                                            intake={
                                                                                                                                intake
                                                                                                                            }
                                                                                                                            disciplines={
                                                                                                                                disciplines
                                                                                                                            }
                                                                                                                            allocation={
                                                                                                                                specializationAllocation
                                                                                                                            }
                                                                                                                            parentDisciplineAllocation={
                                                                                                                                disciplineAllocation
                                                                                                                            }
                                                                                                                        />
                                                                                                                        <Button
                                                                                                                            type="button"
                                                                                                                            variant="ghost"
                                                                                                                            size="icon"
                                                                                                                            onClick={() =>
                                                                                                                                deleteAllocation(
                                                                                                                                    intake,
                                                                                                                                    specializationAllocation,
                                                                                                                                )
                                                                                                                            }
                                                                                                                        >
                                                                                                                            <Trash2 className="size-4" />
                                                                                                                        </Button>
                                                                                                                    </>
                                                                                                                )}
                                                                                                        </div>
                                                                                                    </td>
                                                                                                </tr>
                                                                                            ),
                                                                                        )}
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                );
                                                            },
                                                        )}
                                                </div>
                                            )}
                                        </CardContent>
                                    )}
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
}
