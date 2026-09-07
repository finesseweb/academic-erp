import { Head, router } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type BulkContext = {
    key: string;
    purpose: 'ACADEMIC' | 'EXAMINATION' | 'OTHER';
    basis_group: 'TERM' | 'ACADEMIC_YEAR' | 'ONE_TIME';
    period_no: number;
    label: string;
    ready: boolean;
    reason: string;
    cohort_count: number;
};

type ConfirmedAdmission = {
    id: number;
    admission_no: string;
    application_no: string;
    candidate_name: string;
};

type Offering = {
    id: number;
    program: string;
    program_code: string;
    session: string;
    curriculum_id: number | null;
    confirmed_admissions?: ConfirmedAdmission[];
    academic_policy: null | {
        id: number;
        name: string;
        code: string;
        version: string;
        scope_type: string;
    };
    academic_policy_error: string | null;
    bulk_contexts: BulkContext[];
};

type DemandItem = {
    id: number;
    owner_type: string;
    structure_name: string;
    fee_head_name: string;
    fee_head_code: string;
    purpose: string;
    charge_basis: string;
    amount: string;
    is_mandatory: boolean;
    is_enrollment_clearance_required: boolean;
    installment_allowed: boolean;
    is_refundable: boolean;
};

type Demand = {
    id: number;
    demand_no: string;
    admission_no: string;
    application_no: string;
    candidate_name: string;
    billing_period_no: number;
    billing_period_label: string;
    demand_context: string;
    billing_basis_group: string | null;
    bulk_run_key: string | null;
    currency: string;
    total_amount: string;
    mandatory_amount: string;
    enrollment_clearance_amount: string;
    outstanding_amount: string;
    status: string;
    generation_mode: string;
    generated_at: string;
    items: DemandItem[];
};

export default function Page({
    college,
    offerings,
    demands,
    can,
}: {
    college: { id: number; name: string; code: string };
    offerings: Offering[];
    demands: Demand[];
    can: { generate: boolean; cancel: boolean };
}) {
    const [offeringId, setOfferingId] = useState(offerings[0]?.id ?? 0);
    const [purpose, setPurpose] = useState(offerings[0]?.bulk_contexts.find((row) => row.purpose === 'ACADEMIC')?.purpose ?? offerings[0]?.bulk_contexts[0]?.purpose ?? 'ACADEMIC');
    const [contextKey, setContextKey] = useState('');
    const [generateMode, setGenerateMode] = useState<'BULK' | 'INDIVIDUAL'>('BULK');
    const [individualAdmissionId, setIndividualAdmissionId] = useState(0);
    const [individualSearch, setIndividualSearch] = useState('');
    const [individualOptions, setIndividualOptions] = useState<ConfirmedAdmission[]>([]);
    const [individualSearchLoading, setIndividualSearchLoading] = useState(false);
    const [openAdmission, setOpenAdmission] = useState<string | null>(null);
    const [openDemand, setOpenDemand] = useState<number | null>(null);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [reason, setReason] = useState('');

    const selectedOffering = useMemo(
        () => offerings.find((offering) => offering.id === offeringId),
        [offeringId, offerings],
    );

    const purposeOptions = useMemo(() => {
        const values = new Set(selectedOffering?.bulk_contexts.map((context) => context.purpose) ?? []);
        return ['ACADEMIC', 'EXAMINATION', 'OTHER'].filter((value) => values.has(value as BulkContext['purpose']));
    }, [selectedOffering]);

    const filteredContexts = useMemo(
        () => selectedOffering?.bulk_contexts.filter((context) => context.purpose === purpose) ?? [],
        [selectedOffering, purpose],
    );

    const selectedContext = useMemo(() => {
        const direct = filteredContexts.find((context) => context.key === contextKey);
        return direct ?? filteredContexts[0] ?? null;
    }, [contextKey, filteredContexts]);

    useEffect(() => {
        if (generateMode !== 'INDIVIDUAL' || !selectedContext?.ready || !offeringId) {
            setIndividualOptions([]);
            return;
        }

        const query = individualSearch.trim();
        if (query.length < 2) {
            setIndividualOptions([]);
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setIndividualSearchLoading(true);
            try {
                const response = await fetch(
                    `/college/${college.id}/fee-demands/eligible-admissions?offering_id=${offeringId}&q=${encodeURIComponent(query)}`,
                    { signal: controller.signal, headers: { Accept: 'application/json' } },
                );
                if (!response.ok) throw new Error('Search failed');
                const data = await response.json();
                setIndividualOptions(data.data ?? []);
            } catch (error) {
                if (!(error instanceof DOMException && error.name === 'AbortError')) setIndividualOptions([]);
            } finally {
                if (!controller.signal.aborted) setIndividualSearchLoading(false);
            }
        }, 250);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [college.id, generateMode, individualSearch, offeringId, selectedContext?.ready]);

    const demandGroups = useMemo(() => {
        const grouped = new Map<string, {
            key: string;
            admission_no: string;
            application_no: string;
            candidate_name: string;
            currency: string;
            demands: Demand[];
            total: number;
            mandatory: number;
            clearance: number;
            outstanding: number;
        }>();

        demands.forEach((demand) => {
            const key = demand.admission_no || demand.application_no || String(demand.id);
            const existing = grouped.get(key) ?? {
                key,
                admission_no: demand.admission_no,
                application_no: demand.application_no,
                candidate_name: demand.candidate_name,
                currency: demand.currency,
                demands: [],
                total: 0,
                mandatory: 0,
                clearance: 0,
                outstanding: 0,
            };
            existing.demands.push(demand);
            existing.total += Number(demand.total_amount);
            existing.mandatory += Number(demand.mandatory_amount);
            existing.clearance += Number(demand.enrollment_clearance_amount);
            existing.outstanding += Number(demand.outstanding_amount);
            grouped.set(key, existing);
        });

        return Array.from(grouped.values());
    }, [demands]);

    const filteredDemandGroups = useMemo(() => {
        const query = search.trim().toLowerCase();
        if (!query) return demandGroups;
        return demandGroups.filter((group) => {
            const groupMatch = [group.application_no, group.admission_no, group.candidate_name]
                .some((value) => value?.toLowerCase().includes(query));
            if (groupMatch) return true;
            return group.demands.some((demand) =>
                [demand.demand_no, demand.billing_period_label, demand.demand_context, demand.billing_basis_group === 'TERM' ? 'term-wise' : demand.billing_basis_group === 'ACADEMIC_YEAR' ? 'academic year-wise' : demand.billing_basis_group === 'ONE_TIME' ? 'one-time' : demand.billing_basis_group ?? '']
                    .some((value) => value?.toLowerCase().includes(query)),
            );
        });
    }, [demandGroups, search]);

    const pageSize = 10;
    const totalPages = Math.max(1, Math.ceil(filteredDemandGroups.length / pageSize));
    const currentPage = Math.min(page, totalPages);
    const pagedDemandGroups = filteredDemandGroups.slice((currentPage - 1) * pageSize, currentPage * pageSize);

    const money = (value: string, currency = 'INR') =>
        new Intl.NumberFormat('en-IN', { style: 'currency', currency }).format(Number(value));

    const basisLabel = (value: string | null) => {
        if (value === 'TERM') return 'Term-wise';
        if (value === 'ACADEMIC_YEAR') return 'Academic Year-wise';
        if (value === 'ONE_TIME') return 'One-Time';
        return value ?? '';
    };

    const selectOffering = (value: number) => {
        setOfferingId(value);
        const offering = offerings.find((row) => row.id === value);
        const nextPurpose = offering?.bulk_contexts.find((row) => row.purpose === 'ACADEMIC')?.purpose
            ?? offering?.bulk_contexts[0]?.purpose
            ?? 'ACADEMIC';
        setPurpose(nextPurpose);
        setContextKey('');
        setIndividualAdmissionId(0);
        setIndividualSearch('');
        setIndividualOptions([]);
    };

    return (
        <>
            <Head title="Applicable Fee Demands" />
            <div className="space-y-6 p-4 md:p-6">
                <header className="border-b pb-5">
                    <p className="text-sm font-medium text-primary">{college.code} · Fee Management</p>
                    <h1 className="text-3xl font-semibold">Applicable Fee Demands</h1>
                    <p className="mt-1 max-w-4xl text-muted-foreground">
                        Fee Demand consumes the ACTIVE Fee Setup. Admission-stage charges are generated automatically; period-wise Academic charges can be generated in bulk for the Program Offering when the required eligibility source is available.
                    </p>
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>Demand Workflow</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2">
                        <div className="rounded-md border p-4">
                            <div className="flex items-center justify-between gap-3">
                                <p className="font-medium">Admission Stage</p>
                                <Badge>Automatic</Badge>
                            </div>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Admission Confirmation generates applicable ADMISSION-purpose charges plus only those first-period ACADEMIC charges explicitly marked Enrollment Clearance Required. Admission Fee itself remains optional.
                            </p>
                        </div>
                        <div className="rounded-md border p-4">
                            <div className="flex items-center justify-between gap-3">
                                <p className="font-medium">Program Offering Bulk Demand</p>
                                <Badge variant="outline">Fee Setup driven</Badge>
                            </div>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Purpose, collection basis and billing periods come directly from the effective University + College Fee Setup. Term-wise setup produces Semester/Trimester contexts; Academic Year-wise setup produces Academic Year contexts.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Bulk Demand</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-3">
                            <div>
                                <label className="text-sm font-medium">Program Offering</label>
                                <select
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3"
                                    value={offeringId}
                                    onChange={(event) => selectOffering(Number(event.target.value))}
                                >
                                    <option value="0">Select Program Offering</option>
                                    {offerings.map((offering) => (
                                        <option key={offering.id} value={offering.id}>
                                            {offering.program_code} — {offering.program} — {offering.session}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="text-sm font-medium">Demand Purpose</label>
                                <select
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3"
                                    value={purpose}
                                    onChange={(event) => {
                                        setPurpose(event.target.value);
                                        setContextKey('');
                                        setIndividualAdmissionId(0);
                                    }}
                                    disabled={!selectedOffering || purposeOptions.length === 0}
                                >
                                    {purposeOptions.length === 0 && <option value="ACADEMIC">No active bulk fee purpose</option>}
                                    {purposeOptions.map((value) => <option key={value} value={value}>{value}</option>)}
                                </select>
                            </div>
                            <div>
                                <label className="text-sm font-medium">Billing Period from Fee Setup</label>
                                <select
                                    className="mt-1 h-10 w-full rounded-md border bg-background px-3"
                                    value={selectedContext?.key ?? ''}
                                    onChange={(event) => {
                                        setContextKey(event.target.value);
                                        setIndividualAdmissionId(0);
                                    }}
                                    disabled={!selectedOffering || filteredContexts.length === 0}
                                >
                                    {filteredContexts.length === 0 && <option value="">No applicable billing period</option>}
                                    {filteredContexts.map((context) => (
                                        <option key={context.key} value={context.key}>
                                            {context.label} — {basisLabel(context.basis_group)}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="rounded-md border p-3">
                                <p className="text-xs text-muted-foreground">Resolved University Academic Policy</p>
                                {selectedOffering?.academic_policy ? (
                                    <>
                                        <p className="mt-1 font-medium">{selectedOffering.academic_policy.name}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {selectedOffering.academic_policy.code} · v{selectedOffering.academic_policy.version} · {selectedOffering.academic_policy.scope_type}
                                        </p>
                                    </>
                                ) : selectedOffering?.academic_policy_error ? (
                                    <p className="mt-1 text-sm text-destructive">{selectedOffering.academic_policy_error}</p>
                                ) : (
                                    <p className="mt-1 text-sm text-muted-foreground">No current ACTIVE + APPROVED Academic Policy resolved for this offering.</p>
                                )}
                            </div>

                            <div className="rounded-md border p-3">
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant={selectedContext?.ready ? 'default' : 'secondary'}>
                                        {selectedContext?.ready ? 'Ready to Generate' : 'Blocked'}
                                    </Badge>
                                    {selectedContext && <Badge variant="outline">{selectedContext.label}</Badge>}
                                    {selectedContext && <Badge variant="outline">Cohort: {selectedContext.cohort_count}</Badge>}
                                </div>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {selectedContext?.reason ?? 'Select a Program Offering and a billing context derived from its effective Fee Setup.'}
                                </p>
                            </div>
                        </div>

                        {can.generate && selectedContext && (
                            <div className="space-y-3 rounded-md border p-3">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-medium">Generate For</p>
                                        <p className="text-xs text-muted-foreground">
                                            Bulk uses the full eligible cohort. Individual uses the same Fee Setup, eligibility and duplicate-protection rules for one candidate/student.
                                        </p>
                                    </div>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={generateMode === 'BULK' ? 'default' : 'outline'}
                                            onClick={() => setGenerateMode('BULK')}
                                        >
                                            Bulk Cohort
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={generateMode === 'INDIVIDUAL' ? 'default' : 'outline'}
                                            onClick={() => setGenerateMode('INDIVIDUAL')}
                                        >
                                            Individual
                                        </Button>
                                    </div>
                                </div>

                                {generateMode === 'INDIVIDUAL' && (
                                    <div>
                                        <label className="text-sm font-medium">Candidate / Student</label>
                                        <Input
                                            className="mt-1"
                                            value={individualSearch}
                                            onChange={(event) => {
                                                setIndividualSearch(event.target.value);
                                                setIndividualAdmissionId(0);
                                            }}
                                            placeholder="Search by student name, application no. or admission no."
                                            disabled={!selectedContext.ready}
                                        />
                                        <select
                                            className="mt-2 h-10 w-full rounded-md border bg-background px-3"
                                            value={individualAdmissionId}
                                            onChange={(event) => setIndividualAdmissionId(Number(event.target.value))}
                                            disabled={!selectedContext.ready || individualSearch.trim().length < 2 || individualSearchLoading}
                                        >
                                            <option value="0">
                                                {individualSearchLoading ? 'Searching…' : individualSearch.trim().length < 2 ? 'Type at least 2 characters to search' : individualOptions.length === 0 ? 'No matching eligible candidate / student' : 'Select candidate / student'}
                                            </option>
                                            {individualOptions.map((admission) => (
                                                <option key={admission.id} value={admission.id}>
                                                    {admission.admission_no} — {admission.application_no} — {admission.candidate_name}
                                                </option>
                                            ))}
                                        </select>
                                        <p className="mt-1 text-xs text-muted-foreground">
                                            Current first-period eligibility comes from CONFIRMED admissions. Later periods will use authoritative enrolled/progressed students when Student Academic Progression is available.
                                        </p>
                                    </div>
                                )}

                                <div className="flex justify-end">
                                    {generateMode === 'BULK' ? (
                                        <Button
                                            disabled={!selectedContext.ready}
                                            onClick={() =>
                                                router.post(
                                                    `/college/${college.id}/fee-demands/bulk`,
                                                    {
                                                        offering_id: offeringId,
                                                        purpose: selectedContext.purpose,
                                                        basis_group: selectedContext.basis_group,
                                                        period_no: selectedContext.period_no,
                                                    },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Generate Bulk Demand — {selectedContext.label}
                                        </Button>
                                    ) : (
                                        <Button
                                            disabled={!selectedContext.ready || !individualAdmissionId}
                                            onClick={() =>
                                                router.post(
                                                    `/college/${college.id}/fee-demands/individual`,
                                                    {
                                                        offering_id: offeringId,
                                                        admission_id: individualAdmissionId,
                                                        purpose: selectedContext.purpose,
                                                        basis_group: selectedContext.basis_group,
                                                        period_no: selectedContext.period_no,
                                                    },
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Generate Individual Demand — {selectedContext.label}
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}

                        <p className="text-xs text-muted-foreground">
                            First Academic period uses the CONFIRMED admission cohort because there is no previous academic progression. Bulk and Individual modes both use the same eligibility and duplicate-protection rules. Semester/Year 2 onward stays blocked until Student Enrollment + authoritative Academic Progression results exist.
                        </p>
                    </CardContent>
                </Card>



                <Card>
                    <CardHeader className="pb-3">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <CardTitle>Fee Demands ({filteredDemandGroups.length})</CardTitle>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Demands are grouped by admission/application so the register stays compact. All demand groups and demand details are closed by default.
                                </p>
                            </div>
                            <Input
                                className="w-full md:w-96"
                                placeholder="Search application no., admission no., student name, demand or period"
                                value={search}
                                onChange={(event) => {
                                    setSearch(event.target.value);
                                    setPage(1);
                                    setOpenAdmission(null);
                                    setOpenDemand(null);
                                }}
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {pagedDemandGroups.map((group) => {
                            const groupOpen = openAdmission === group.key;
                            return (
                                <div key={group.key} className="rounded-md border">
                                    <div className="grid gap-3 p-3 md:grid-cols-[1.1fr_1.4fr_0.8fr_0.8fr_0.9fr_0.8fr_auto] md:items-center">
                                        <div>
                                            <p className="text-xs text-muted-foreground">Application / Admission</p>
                                            <p className="font-medium">{group.application_no || group.admission_no}</p>
                                            {group.application_no && group.admission_no && group.application_no !== group.admission_no && (
                                                <p className="text-xs text-muted-foreground">{group.admission_no}</p>
                                            )}
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">Candidate / Student</p>
                                            <p className="font-medium">{group.candidate_name}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">Total</p>
                                            <p className="font-semibold">{money(String(group.total), group.currency)}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">Mandatory</p>
                                            <p className="font-semibold">{money(String(group.mandatory), group.currency)}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">Enrollment Clearance</p>
                                            <p className="font-semibold">{money(String(group.clearance), group.currency)}</p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-muted-foreground">Outstanding</p>
                                            <p className="font-semibold">{money(String(group.outstanding), group.currency)}</p>
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            onClick={() => {
                                                setOpenAdmission(groupOpen ? null : group.key);
                                                setOpenDemand(null);
                                            }}
                                        >
                                            Demands ({group.demands.length}) {groupOpen ? '▲' : '▼'}
                                        </Button>
                                    </div>

                                    {groupOpen && (
                                        <div className="space-y-2 border-t p-3">
                                            {group.demands.map((demand) => {
                                                const demandOpen = openDemand === demand.id;
                                                return (
                                                    <div key={demand.id} className="rounded-md border bg-muted/20">
                                                        <div className="flex flex-wrap items-center justify-between gap-3 p-3">
                                                            <div>
                                                                <p className="font-medium">{demand.billing_period_label}</p>
                                                                <p className="text-xs text-muted-foreground">
                                                                    {demand.demand_no} · {demand.generated_at}
                                                                </p>
                                                            </div>
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <Badge variant="outline">{demand.demand_context.replaceAll('_', ' ')}</Badge>
                                                                {demand.billing_basis_group && <Badge variant="outline">{basisLabel(demand.billing_basis_group)}</Badge>}
                                                                <Badge variant="outline">{demand.generation_mode.replaceAll('_', ' ')}</Badge>
                                                                <Badge variant={demand.status === 'CANCELLED' ? 'secondary' : 'default'}>{demand.status}</Badge>
                                                                <span className="text-sm font-semibold">{money(demand.outstanding_amount, demand.currency)}</span>
                                                                <Button size="sm" variant="outline" onClick={() => setOpenDemand(demandOpen ? null : demand.id)}>
                                                                    {demandOpen ? 'Hide Details' : 'View Details'}
                                                                </Button>
                                                            </div>
                                                        </div>

                                                        {demandOpen && (
                                                            <div className="space-y-3 border-t p-3">
                                                                <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                                                                    {[
                                                                        ['Total', demand.total_amount],
                                                                        ['Mandatory', demand.mandatory_amount],
                                                                        ['Enrollment Clearance', demand.enrollment_clearance_amount],
                                                                        ['Outstanding', demand.outstanding_amount],
                                                                    ].map(([label, value]) => (
                                                                        <div key={label}>
                                                                            <p className="text-xs text-muted-foreground">{label}</p>
                                                                            <p className="font-semibold">{money(value, demand.currency)}</p>
                                                                        </div>
                                                                    ))}
                                                                </div>

                                                                {demand.items.map((item) => (
                                                                    <div key={item.id} className="flex flex-wrap items-center justify-between gap-2 rounded-md border bg-background p-3">
                                                                        <div>
                                                                            <p className="font-medium">
                                                                                {item.fee_head_name} <span className="text-xs text-muted-foreground">({item.fee_head_code})</span>
                                                                            </p>
                                                                            <p className="text-xs text-muted-foreground">
                                                                                {item.owner_type} · {item.structure_name} · {item.purpose} · {item.charge_basis.replaceAll('_', ' ')}
                                                                            </p>
                                                                        </div>
                                                                        <div className="flex flex-wrap gap-2">
                                                                            <Badge variant="outline">{money(item.amount, demand.currency)}</Badge>
                                                                            {item.is_mandatory && <Badge>Mandatory</Badge>}
                                                                            {item.is_enrollment_clearance_required && <Badge variant="destructive">Enrollment Clearance</Badge>}
                                                                            <Badge variant="outline">Installment {item.installment_allowed ? 'Allowed' : 'No'}</Badge>
                                                                            <Badge variant="outline">{item.is_refundable ? 'Refundable' : 'Non-refundable'}</Badge>
                                                                        </div>
                                                                    </div>
                                                                ))}

                                                                {can.cancel && demand.status !== 'CANCELLED' && Number(demand.outstanding_amount) === Number(demand.total_amount) && (
                                                                    <div className="flex gap-2">
                                                                        <Input placeholder="Cancellation reason" value={reason} onChange={(event) => setReason(event.target.value)} />
                                                                        <Button
                                                                            variant="destructive"
                                                                            disabled={!reason.trim()}
                                                                            onClick={() =>
                                                                                router.patch(
                                                                                    `/college/${college.id}/fee-demands/${demand.id}/cancel`,
                                                                                    { reason },
                                                                                    { preserveScroll: true, onSuccess: () => setReason('') },
                                                                                )
                                                                            }
                                                                        >
                                                                            Cancel Demand
                                                                        </Button>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            );
                        })}

                        {!filteredDemandGroups.length && (
                            <div className="py-10 text-center text-sm text-muted-foreground">
                                {demands.length ? 'No fee demands match this search.' : 'No fee demands yet. New Admission Confirmations create admission-stage demand automatically; Academic period charges can be raised from the workflow above.'}
                            </div>
                        )}

                        {filteredDemandGroups.length > pageSize && (
                            <div className="flex flex-wrap items-center justify-between gap-3 border-t pt-3">
                                <p className="text-xs text-muted-foreground">
                                    Showing {(currentPage - 1) * pageSize + 1}–{Math.min(currentPage * pageSize, filteredDemandGroups.length)} of {filteredDemandGroups.length} admissions
                                </p>
                                <div className="flex items-center gap-2">
                                    <Button size="sm" variant="outline" disabled={currentPage <= 1} onClick={() => setPage(currentPage - 1)}>Previous</Button>
                                    <Badge variant="outline">Page {currentPage} of {totalPages}</Badge>
                                    <Button size="sm" variant="outline" disabled={currentPage >= totalPages} onClick={() => setPage(currentPage + 1)}>Next</Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
