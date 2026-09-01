import { Form, Head, useForm } from '@inertiajs/react';
import { Check, ChevronDown, ChevronRight, Edit3, GraduationCap, Plus, Power, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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

type Discipline = { id: number; name: string };
type Specialization = { id: number; parent_id: number; name: string };
type DisciplineMapping = {
    id: number;
    discipline_id: number;
    discipline: Discipline;
    specializations: Specialization[];
    specialization_required: boolean;
};
type Item = {
    id: number;
    degree_id: number;
    degree: { name: string };
    discipline_mappings: DisciplineMapping[];
    name: string;
    code: string;
    term_structure: 'SEMESTER' | 'YEAR' | 'TRIMESTER';
    duration_terms: number;
    description?: string | null;
    display_order: number;
    status: 'ACTIVE' | 'INACTIVE';
};
type Props = {
    templates: Item[];
    degrees: { id: number; name: string }[];
    disciplines: Discipline[];
    specializations: Specialization[];
    can: { create: boolean; update: boolean; disable: boolean };
};
type DisciplineSelection = {
    discipline_id: number;
    specialization_ids: number[];
    specialization_required: boolean;
};
type FormData = {
    degree_id: string;
    disciplines: DisciplineSelection[];
    name: string;
    code: string;
    term_structure: 'SEMESTER' | 'YEAR' | 'TRIMESTER';
    duration_terms: number;
    description: string;
    display_order: number;
    status: 'ACTIVE' | 'INACTIVE';
};

function TemplateEditor({
    record,
    degrees,
    disciplines,
    specializations,
}: {
    record?: Item;
    degrees: Props['degrees'];
    disciplines: Discipline[];
    specializations: Specialization[];
}) {
    const [open, setOpen] = useState(false);
    const [disciplineSearch, setDisciplineSearch] = useState('');
    const [specializationSearch, setSpecializationSearch] = useState('');
    const [disciplineFilter, setDisciplineFilter] = useState<'all' | 'selected'>('all');
    const [activeDisciplineId, setActiveDisciplineId] = useState<number | null>(
        record?.discipline_mappings[0]?.discipline_id ?? disciplines[0]?.id ?? null,
    );

    const initialDisciplines = useMemo<DisciplineSelection[]>(
        () =>
            record?.discipline_mappings.map((mapping) => ({
                discipline_id: mapping.discipline_id,
                specialization_ids: mapping.specializations.map((s) => s.id),
                specialization_required: Boolean(mapping.specialization_required),
            })) ?? [],
        [record],
    );

    const form = useForm<FormData>({
        degree_id: record ? String(record.degree_id) : '',
        disciplines: initialDisciplines,
        name: record?.name ?? '',
        code: record?.code ?? '',
        term_structure: record?.term_structure ?? 'SEMESTER',
        duration_terms: record?.duration_terms ?? 6,
        description: record?.description ?? '',
        display_order: record?.display_order ?? 0,
        status: record?.status ?? 'ACTIVE',
    });

    const selected = (disciplineId: number) =>
        form.data.disciplines.find((x) => x.discipline_id === disciplineId);

    const toggleDiscipline = (disciplineId: number, checked: boolean) => {
        setActiveDisciplineId(disciplineId);
        setSpecializationSearch('');

        if (checked) {
            if (!selected(disciplineId)) {
                form.setData('disciplines', [
                    ...form.data.disciplines,
                    { discipline_id: disciplineId, specialization_ids: [], specialization_required: false },
                ]);
            }
            return;
        }

        const remaining = form.data.disciplines.filter(
            (x) => x.discipline_id !== disciplineId,
        );
        form.setData('disciplines', remaining);

        if (activeDisciplineId === disciplineId) {
            setActiveDisciplineId(
                remaining[0]?.discipline_id ?? disciplines[0]?.id ?? null,
            );
        }
    };

    const toggleSpecialization = (
        disciplineId: number,
        specializationId: number,
        checked: boolean,
    ) => {
        form.setData(
            'disciplines',
            form.data.disciplines.map((row) => {
                if (row.discipline_id !== disciplineId) return row;

                return {
                    ...row,
                    specialization_ids: checked
                        ? [...row.specialization_ids, specializationId]
                        : row.specialization_ids.filter(
                              (id) => id !== specializationId,
                          ),
                };
            }),
        );
    };

    const normalizedDisciplineSearch = disciplineSearch.trim().toLowerCase();
    const visibleDisciplines = disciplines.filter((discipline) => {
        const matchesSearch = discipline.name
            .toLowerCase()
            .includes(normalizedDisciplineSearch);
        const matchesFilter =
            disciplineFilter === 'all' || Boolean(selected(discipline.id));

        return matchesSearch && matchesFilter;
    });

    const activeDiscipline =
        disciplines.find((discipline) => discipline.id === activeDisciplineId) ??
        null;
    const activeSelection = activeDiscipline
        ? selected(activeDiscipline.id)
        : undefined;
    const activeSpecializations = activeDiscipline
        ? specializations.filter(
              (specialization) =>
                  specialization.parent_id === activeDiscipline.id &&
                  specialization.name
                      .toLowerCase()
                      .includes(specializationSearch.trim().toLowerCase()),
          )
        : [];

    const selectedDisciplineRecords = disciplines.filter((discipline) =>
        Boolean(selected(discipline.id)),
    );

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                if (!record) form.reset();
            },
        };

        if (record) {
            form.patch(`/admin/program-templates/${record.id}`, options);
        } else {
            form.post('/admin/program-templates', options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button
                    variant={record ? 'ghost' : 'default'}
                    size={record ? 'sm' : 'default'}
                >
                    {record ? <Edit3 /> : <Plus />}
                    {record ? 'Edit' : 'Add program template'}
                </Button>
            </DialogTrigger>

            <DialogContent className="flex max-h-[94vh] !w-[96vw] !max-w-[1200px] flex-col gap-0 overflow-hidden p-0">
                <div className="border-b px-6 py-5 pr-12">
                    <DialogTitle>
                        {record ? 'Edit program template' : 'Add program template'}
                    </DialogTitle>
                    <DialogDescription className="mt-1">
                        Define the degree and bind one or more disciplines. Each
                        selected discipline can optionally include its own
                        specializations.
                    </DialogDescription>
                </div>

                <form onSubmit={submit} className="flex min-h-0 flex-1 flex-col">
                    <div className="grid min-h-0 flex-1 gap-5 overflow-y-auto px-6 py-5 sm:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="degree_id">Degree *</Label>
                        <Select
                            value={form.data.degree_id}
                            onValueChange={(value) =>
                                form.setData('degree_id', value)
                            }
                        >
                            <SelectTrigger
                                id="degree_id"
                                aria-invalid={Boolean(form.errors.degree_id)}
                            >
                                <SelectValue placeholder="Select degree" />
                            </SelectTrigger>
                            <SelectContent>
                                {degrees.map((degree) => (
                                    <SelectItem
                                        key={degree.id}
                                        value={String(degree.id)}
                                    >
                                        {degree.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <p className="text-sm text-destructive">
                            {form.errors.degree_id}
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="name">Template name *</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) =>
                                form.setData('name', e.target.value)
                            }
                            aria-invalid={Boolean(form.errors.name)}
                        />
                        <p className="text-sm text-destructive">
                            {form.errors.name}
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="code">Code *</Label>
                        <Input
                            id="code"
                            value={form.data.code}
                            onChange={(e) =>
                                form.setData('code', e.target.value)
                            }
                            aria-invalid={Boolean(form.errors.code)}
                        />
                        <p className="text-sm text-destructive">
                            {form.errors.code}
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="term_structure">Term structure *</Label>
                        <Select
                            value={form.data.term_structure}
                            onValueChange={(value) =>
                                form.setData(
                                    'term_structure',
                                    value as FormData['term_structure'],
                                )
                            }
                        >
                            <SelectTrigger id="term_structure">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="SEMESTER">
                                    Semester
                                </SelectItem>
                                <SelectItem value="YEAR">Year</SelectItem>
                                <SelectItem value="TRIMESTER">
                                    Trimester
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="duration_terms">
                            Duration (terms) *
                        </Label>
                        <Input
                            id="duration_terms"
                            type="number"
                            min={1}
                            max={30}
                            value={form.data.duration_terms}
                            onChange={(e) =>
                                form.setData(
                                    'duration_terms',
                                    Number(e.target.value),
                                )
                            }
                        />
                        <p className="text-sm text-destructive">
                            {form.errors.duration_terms}
                        </p>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="display_order">Display order</Label>
                        <Input
                            id="display_order"
                            type="number"
                            min={0}
                            max={65535}
                            value={form.data.display_order}
                            onChange={(e) =>
                                form.setData(
                                    'display_order',
                                    Number(e.target.value),
                                )
                            }
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="status">Status *</Label>
                        <Select
                            value={form.data.status}
                            onValueChange={(value) =>
                                form.setData(
                                    'status',
                                    value as FormData['status'],
                                )
                            }
                        >
                            <SelectTrigger id="status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ACTIVE">Active</SelectItem>
                                <SelectItem value="INACTIVE">
                                    Inactive
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-3 sm:col-span-2">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <Label>Disciplines *</Label>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Select disciplines on the left, then manage the active discipline's optional specializations on the right.
                                </p>
                            </div>
                            <div className="text-xs font-medium text-muted-foreground">
                                {form.data.disciplines.length} selected
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-xl border bg-background">
                            <div className="grid min-h-[390px] lg:grid-cols-[minmax(330px,0.9fr)_minmax(520px,1.4fr)]">
                                <section className="flex min-h-0 flex-col border-b lg:border-r lg:border-b-0">
                                    <div className="space-y-3 border-b p-3">
                                        <div className="relative">
                                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                            <Input
                                                value={disciplineSearch}
                                                onChange={(e) => setDisciplineSearch(e.target.value)}
                                                placeholder="Search disciplines..."
                                                className="pl-9"
                                            />
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant={disciplineFilter === 'all' ? 'default' : 'outline'}
                                                onClick={() => setDisciplineFilter('all')}
                                            >
                                                All ({disciplines.length})
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant={disciplineFilter === 'selected' ? 'default' : 'outline'}
                                                onClick={() => setDisciplineFilter('selected')}
                                            >
                                                Selected ({form.data.disciplines.length})
                                            </Button>
                                        </div>
                                    </div>

                                    <div className="max-h-[320px] min-h-0 flex-1 overflow-y-auto p-2 lg:max-h-[430px]">
                                        {visibleDisciplines.length === 0 ? (
                                            <div className="px-3 py-8 text-center text-sm text-muted-foreground">
                                                No disciplines found.
                                            </div>
                                        ) : (
                                            <div className="space-y-1">
                                                {visibleDisciplines.map((discipline) => {
                                                    const row = selected(discipline.id);
                                                    const specializationCount = specializations.filter(
                                                        (specialization) => specialization.parent_id === discipline.id,
                                                    ).length;
                                                    const isActive = activeDisciplineId === discipline.id;

                                                    return (
                                                        <div
                                                            key={discipline.id}
                                                            className={`flex items-center gap-2 rounded-lg border px-2 py-2 transition-colors ${
                                                                isActive
                                                                    ? 'border-primary/40 bg-primary/5'
                                                                    : 'border-transparent hover:bg-muted/50'
                                                            }`}
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                className="size-4 shrink-0"
                                                                checked={Boolean(row)}
                                                                onChange={(e) =>
                                                                    toggleDiscipline(
                                                                        discipline.id,
                                                                        e.target.checked,
                                                                    )
                                                                }
                                                                aria-label={`Select ${discipline.name}`}
                                                            />
                                                            <button
                                                                type="button"
                                                                className="flex min-w-0 flex-1 items-center gap-2 text-left"
                                                                onClick={() => {
                                                                    setActiveDisciplineId(discipline.id);
                                                                    setSpecializationSearch('');
                                                                }}
                                                            >
                                                                <span className="min-w-0 flex-1 truncate text-sm font-medium">
                                                                    {discipline.name}
                                                                </span>
                                                                {row && row.specialization_ids.length > 0 ? (
                                                                    <span className="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary">
                                                                        {row.specialization_ids.length} spec
                                                                    </span>
                                                                ) : specializationCount > 0 ? (
                                                                    <span className="text-[11px] text-muted-foreground">
                                                                        {specializationCount}
                                                                    </span>
                                                                ) : null}
                                                                <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                                                            </button>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>
                                </section>

                                <section className="flex min-h-0 flex-col bg-muted/10">
                                    {!activeDiscipline ? (
                                        <div className="flex min-h-[320px] items-center justify-center p-6 text-center text-sm text-muted-foreground">
                                            Select a discipline to manage its specializations.
                                        </div>
                                    ) : (
                                        <>
                                            <div className="border-b p-4">
                                                <div className="flex items-start justify-between gap-4">
                                                    <div>
                                                        <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                            Selected discipline
                                                        </p>
                                                        <h3 className="mt-1 font-semibold">
                                                            {activeDiscipline.name}
                                                        </h3>
                                                    </div>
                                                    {activeSelection ? (
                                                        <span className="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">
                                                            <Check className="size-3" /> Included
                                                        </span>
                                                    ) : (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            onClick={() => toggleDiscipline(activeDiscipline.id, true)}
                                                        >
                                                            Add discipline
                                                        </Button>
                                                    )}
                                                </div>
                                            </div>

                                            {!activeSelection ? (
                                                <div className="flex min-h-[280px] flex-1 items-center justify-center p-6 text-center">
                                                    <div className="max-w-sm">
                                                        <p className="font-medium">This discipline is not included yet.</p>
                                                        <p className="mt-1 text-sm text-muted-foreground">
                                                            Add it to this program before choosing any specializations.
                                                        </p>
                                                    </div>
                                                </div>
                                            ) : (
                                                <>
                                                    <div className="border-b p-3">
                                                        <div className="relative">
                                                            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                                            <Input
                                                                value={specializationSearch}
                                                                onChange={(e) => setSpecializationSearch(e.target.value)}
                                                                placeholder={`Search ${activeDiscipline.name} specializations...`}
                                                                className="pl-9"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="max-h-[300px] min-h-0 flex-1 overflow-y-auto p-3 lg:max-h-[355px]">
                                                        {activeSpecializations.length === 0 ? (
                                                            <div className="flex min-h-40 items-center justify-center px-4 text-center text-sm text-muted-foreground">
                                                                {specializations.some(
                                                                    (specialization) => specialization.parent_id === activeDiscipline.id,
                                                                )
                                                                    ? 'No specialization matches your search.'
                                                                    : 'No active specialization under this discipline. It can still be used without specialization.'}
                                                            </div>
                                                        ) : (
                                                            <div className="space-y-2">
                                                                {activeSpecializations.map((specialization) => {
                                                                    const checked = activeSelection.specialization_ids.includes(
                                                                        specialization.id,
                                                                    );

                                                                    return (
                                                                        <label
                                                                            key={specialization.id}
                                                                            className={`flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 transition-colors ${
                                                                                checked
                                                                                    ? 'border-primary/30 bg-primary/5'
                                                                                    : 'hover:bg-muted/50'
                                                                            }`}
                                                                        >
                                                                            <input
                                                                                type="checkbox"
                                                                                className="size-4 shrink-0"
                                                                                checked={checked}
                                                                                onChange={(e) =>
                                                                                    toggleSpecialization(
                                                                                        activeDiscipline.id,
                                                                                        specialization.id,
                                                                                        e.target.checked,
                                                                                    )
                                                                                }
                                                                            />
                                                                            <span className="text-sm">
                                                                                {specialization.name}
                                                                            </span>
                                                                        </label>
                                                                    );
                                                                })}
                                                            </div>
                                                        )}
                                                    </div>

                                                    <div className="border-t px-4 py-3">
                                                        <label className="flex items-start gap-3 rounded-lg border bg-background p-3 text-sm">
                                                            <input
                                                                type="checkbox"
                                                                className="mt-0.5 size-4"
                                                                checked={activeSelection.specialization_required}
                                                                disabled={activeSelection.specialization_ids.length === 0}
                                                                onChange={(e) => form.setData('disciplines', form.data.disciplines.map((row) => row.discipline_id === activeDiscipline.id ? { ...row, specialization_required: e.target.checked } : row))}
                                                            />
                                                            <span><span className="font-medium">Specialization required for applicants</span><span className="mt-0.5 block text-xs text-muted-foreground">Default is optional. Enable only when this program structure requires every applicant in this discipline to select a specialization.</span></span>
                                                        </label>
                                                        <div className="mt-2 text-xs text-muted-foreground">{activeSelection.specialization_ids.length} specialization{activeSelection.specialization_ids.length === 1 ? '' : 's'} available for {activeDiscipline.name}</div>
                                                    </div>
                                                </>
                                            )}
                                        </>
                                    )}
                                </section>
                            </div>
                        </div>

                        {selectedDisciplineRecords.length > 0 ? (
                            <div className="rounded-lg border bg-muted/20 p-3">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Selected disciplines
                                    </p>
                                    <span className="text-xs text-muted-foreground">
                                        {selectedDisciplineRecords.length} total
                                    </span>
                                </div>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {selectedDisciplineRecords.slice(0, 8).map((discipline) => (
                                        <button
                                            type="button"
                                            key={discipline.id}
                                            onClick={() => {
                                                setActiveDisciplineId(discipline.id);
                                                setSpecializationSearch('');
                                            }}
                                            className="inline-flex items-center gap-1 rounded-full border bg-background px-2.5 py-1 text-xs font-medium hover:bg-muted"
                                        >
                                            {discipline.name}
                                            <span className="text-muted-foreground">
                                                ({selected(discipline.id)?.specialization_ids.length ?? 0})
                                            </span>
                                        </button>
                                    ))}
                                    {selectedDisciplineRecords.length > 8 ? (
                                        <span className="inline-flex items-center rounded-full border bg-background px-2.5 py-1 text-xs font-medium text-muted-foreground">
                                            +{selectedDisciplineRecords.length - 8} more
                                        </span>
                                    ) : null}
                                </div>
                            </div>
                        ) : null}

                        <p className="text-sm text-destructive">
                            {form.errors.disciplines}
                        </p>
                    </div>

                    <div className="space-y-2 sm:col-span-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                        />
                    </div>

                    </div>

                    <DialogFooter className="shrink-0 border-t bg-background px-6 py-4">
                        <DialogClose asChild>
                            <Button
                                type="button"
                                variant="outline"
                                disabled={form.processing}
                            >
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            disabled={
                                form.processing ||
                                form.data.disciplines.length === 0
                            }
                        >
                            {form.processing && <Spinner />}
                            {form.processing
                                ? 'Saving...'
                                : 'Save program template'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function StatusAction({ record }: { record: Item }) {
    const active = record.status === 'ACTIVE';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Power />
                    {active ? 'Disable' : 'Enable'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {active ? 'Disable' : 'Enable'} {record.name}?
                </DialogTitle>
                <DialogDescription>
                    Existing references remain intact.
                </DialogDescription>
                <Form
                    action={`/admin/program-templates/${record.id}/status`}
                    method="patch"
                >
                    {({ processing }) => (
                        <>
                            <input
                                type="hidden"
                                name="status"
                                value={active ? 'INACTIVE' : 'ACTIVE'}
                            />
                            <DialogFooter>
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
                                    variant={
                                        active ? 'destructive' : 'default'
                                    }
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    Confirm {active ? 'disable' : 'enable'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}


function ProgramTemplateCard({
    record,
    degrees,
    disciplines,
    specializations,
    can,
}: {
    record: Item;
    degrees: Props['degrees'];
    disciplines: Discipline[];
    specializations: Specialization[];
    can: Props['can'];
}) {
    const [structureOpen, setStructureOpen] = useState(false);
    const specializationCount = record.discipline_mappings.reduce(
        (total, mapping) => total + mapping.specializations.length,
        0,
    );

    return (
        <div className="p-5 transition-colors hover:bg-muted/40">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div className="flex min-w-0 items-start gap-4">
                    <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 font-semibold text-primary">
                        {record.display_order}
                    </span>

                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="font-semibold">{record.name}</h2>
                            <span className="rounded-full bg-muted px-2 py-1 font-mono text-xs">
                                {record.code}
                            </span>
                            <span
                                className={
                                    record.status === 'ACTIVE'
                                        ? 'rounded-full bg-primary/10 px-2 py-1 text-xs text-primary'
                                        : 'rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground'
                                }
                            >
                                {record.status}
                            </span>
                        </div>

                        <p className="mt-1 text-sm font-medium">
                            {record.degree.name} / {record.duration_terms}{' '}
                            {record.term_structure.toLowerCase()} terms
                        </p>

                        <button
                            type="button"
                            onClick={() => setStructureOpen((current) => !current)}
                            className="mt-3 flex w-full max-w-3xl items-center justify-between gap-4 rounded-lg border bg-muted/20 px-3 py-2.5 text-left transition-colors hover:bg-muted/40"
                            aria-expanded={structureOpen}
                        >
                            <span className="flex min-w-0 items-center gap-2">
                                {structureOpen ? (
                                    <ChevronDown className="size-4 shrink-0 text-muted-foreground" />
                                ) : (
                                    <ChevronRight className="size-4 shrink-0 text-muted-foreground" />
                                )}
                                <span className="font-medium">Academic Structure</span>
                            </span>
                            <span className="shrink-0 text-xs text-muted-foreground sm:text-sm">
                                {record.discipline_mappings.length} discipline
                                {record.discipline_mappings.length === 1 ? '' : 's'} •{' '}
                                {specializationCount} specialization
                                {specializationCount === 1 ? '' : 's'}
                            </span>
                        </button>

                        {structureOpen ? (
                            <div className="mt-2 max-w-3xl overflow-hidden rounded-lg border bg-background">
                                <div className="grid grid-cols-[minmax(150px,0.8fr)_minmax(220px,1.6fr)] border-b bg-muted/30 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                    <div>Discipline</div>
                                    <div>Specializations</div>
                                </div>

                                <div className="max-h-72 overflow-y-auto divide-y">
                                    {record.discipline_mappings.length === 0 ? (
                                        <div className="px-4 py-5 text-sm text-muted-foreground">
                                            No disciplines mapped.
                                        </div>
                                    ) : (
                                        record.discipline_mappings.map((mapping) => (
                                            <div
                                                key={mapping.id}
                                                className="grid grid-cols-[minmax(150px,0.8fr)_minmax(220px,1.6fr)] gap-4 px-4 py-3 text-sm"
                                            >
                                                <div className="font-medium">
                                                    {mapping.discipline.name}
                                                </div>
                                                <div className="text-muted-foreground">
                                                    {mapping.specializations.length > 0
                                                        ? mapping.specializations
                                                              .map((item) => item.name)
                                                              .join(', ')
                                                        : '—'}
                                                </div>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        ) : null}

                        <p className="mt-3 text-sm text-muted-foreground">
                            {record.description || 'No description provided.'}
                        </p>
                    </div>
                </div>

                <div className="flex shrink-0 gap-2 sm:pt-8">
                    {can.update && (
                        <TemplateEditor
                            record={record}
                            degrees={degrees}
                            disciplines={disciplines}
                            specializations={specializations}
                        />
                    )}
                    {can.disable && <StatusAction record={record} />}
                </div>
            </div>
        </div>
    );
}

export default function ProgramTemplates({
    templates,
    degrees,
    disciplines,
    specializations,
    can,
}: Props) {
    return (
        <>
            <Head title="Program Templates" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <p className="flex items-center gap-2 text-sm font-medium text-primary">
                            <GraduationCap className="size-4" />
                            Academic structure
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Program Templates
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Define reusable University program blueprints and
                            bind multiple disciplines with optional
                            specialization choices.
                        </p>
                    </div>

                    {can.create && (
                        <TemplateEditor
                            degrees={degrees}
                            disciplines={disciplines}
                            specializations={specializations}
                        />
                    )}
                </header>

                {templates.length === 0 ? (
                    <Card>
                        <CardContent className="flex min-h-64 flex-col items-center justify-center gap-3 text-center">
                            <GraduationCap className="size-10 text-muted-foreground" />
                            <h2 className="font-semibold">
                                No program templates yet
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Add the first program template to establish the
                                University academic master.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="divide-y p-0">
                            {templates.map((record) => (
                                <ProgramTemplateCard
                                    key={record.id}
                                    record={record}
                                    degrees={degrees}
                                    disciplines={disciplines}
                                    specializations={specializations}
                                    can={can}
                                />
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
