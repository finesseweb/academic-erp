import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useMemo, useState } from 'react';
import { ChevronDown, ChevronUp, ReceiptText, RotateCcw, Search, Undo2 } from 'lucide-react';
import { useAppDialog } from '@/components/app-dialog-provider';
import { Button } from '@/components/ui/button';
import { DatePicker } from '@/components/ui/date-picker';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type Session = {
    id: number;
    name: string;
    code: string;
    is_current?: boolean;
};

type Offering = {
    id: number;
    academic_session_id: number;
    program_name?: string;
    program_code?: string;
    status?: string;
};

type Demand = {
    id: number;
    demand_no: string;
    candidate_name?: string;
    admission_no?: string;
    currency: string;
    total_amount: string;
    paid_amount: string;
    adjusted_amount: string;
    outstanding_amount: string;
    items: {
        id: number;
        fee_head_name: string;
        fee_head_code: string;
        amount: string;
        is_refundable: boolean;
    }[];
};

type Receipt = {
    id: number;
    receipt_no: string;
    payment_date: string;
    amount: string;
    currency: string;
    payment_mode: string;
    reference_no?: string | null;
    refundable_balance: string;
};

type PaymentStudent = {
    admission_id: number;
    candidate_name?: string;
    application_no?: string;
    admission_no?: string;
    programme_offering?: {
        id: number;
        program_name?: string;
        program_code?: string;
    } | null;
    currency: string;
    receipt_count: number;
    total_paid: string;
    refundable_balance: string;
    receipts: Receipt[];
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    college: { id: number; name: string; code: string };
    sessions: Session[];
    offerings: Offering[];
    filters: { session_id: number; offering_id: number; q: string; per_page: number };
    demands: Demand[];
    payment_students: Paginated<PaymentStudent>;
    adjustments: any[];
    refunds: any[];
    can: { post: boolean; reverse: boolean; refund: boolean };
};

const money = (value: any) =>
    Number(value || 0).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const niceDate = (value?: string | null) => {
    if (!value) return '—';
    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleDateString('en-IN', {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          });
};

const localToday = () => {
    const date = new Date();
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(
        date.getDate(),
    ).padStart(2, '0')}`;
};

export default function Index({
    college,
    sessions,
    offerings,
    filters,
    demands,
    payment_students,
    adjustments,
    refunds,
    can,
}: Props) {
    const appDialog = useAppDialog();
    const [q, setQ] = useState(filters.q || '');
    const [sessionId, setSessionId] = useState(String(filters.session_id || ''));
    const [offeringId, setOfferingId] = useState(String(filters.offering_id || '0'));
    const [perPage, setPerPage] = useState(String(filters.per_page || 25));
    const [expanded, setExpanded] = useState<Record<number, boolean>>({});
    const [demandId, setDemandId] = useState<number>(demands[0]?.id || 0);

    const selected = useMemo(
        () => demands.find((demand) => demand.id === demandId),
        [demands, demandId],
    );

    const adj = useForm({
        demand_id: demandId,
        fee_demand_item_id: selected?.items[0]?.id || 0,
        adjustment_date: localToday(),
        direction: 'CREDIT',
        amount: '',
        reason_code: 'CORRECTION',
        reason: '',
    });

    const [refundPayment, setRefundPayment] = useState<Receipt | null>(null);
    const refund = useForm({
        refund_date: localToday(),
        amount: '',
        refund_mode: 'BANK_TRANSFER',
        reference_no: '',
        reason: '',
    });

    const submitAdj = (event: FormEvent) => {
        event.preventDefault();
        adj.setData('demand_id', demandId);
        adj.post(`/college/${college.id}/fee-adjustments`);
    };

    const filter = (overrides: Partial<{ session_id: string; offering_id: string; q: string; per_page: string }> = {}) => {
        const nextSession = overrides.session_id ?? sessionId;
        const nextOffering = overrides.offering_id ?? offeringId;
        const nextQ = overrides.q ?? q;
        const nextPerPage = overrides.per_page ?? perPage;

        router.get(
            `/college/${college.id}/fee-adjustments`,
            {
                session_id: nextSession,
                offering_id: nextOffering === '0' ? 0 : nextOffering,
                q: nextQ.trim(),
                per_page: nextPerPage,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const changeSession = (value: string) => {
        setSessionId(value);
        setOfferingId('0');
        setExpanded({});
        filter({ session_id: value, offering_id: '0' });
    };

    const changeOffering = (value: string) => {
        setOfferingId(value);
        setExpanded({});
        filter({ offering_id: value });
    };

    const changeDemand = (value: string) => {
        const id = Number(value);
        setDemandId(id);
        const demand = demands.find((item) => item.id === id);
        adj.setData('fee_demand_item_id', demand?.items[0]?.id || 0);
    };

    const reverseAdj = async (id: number) => {
        const reason = await appDialog.prompt({
            title: 'Reverse adjustment',
            description: 'Enter the reason for reversing this adjustment.',
            placeholder: 'Reason for reversal',
            confirmLabel: 'Reverse adjustment',
            confirmIcon: <RotateCcw className="size-4" aria-hidden="true" />,
            destructive: true,
            required: true,
        });
        if (reason && reason.trim().length >= 5) {
            router.post(`/college/${college.id}/fee-adjustments/${id}/reverse`, {
                reason: reason.trim(),
            });
        }
    };

    const reversePay = async (id: number) => {
        const reason = await appDialog.prompt({
            title: 'Reverse payment receipt',
            description: 'Enter the reason for reversing this entire receipt. All payment allocations will be restored.',
            placeholder: 'Reason for reversal',
            confirmLabel: 'Continue',
            confirmIcon: <RotateCcw className="size-4" aria-hidden="true" />,
            destructive: true,
            required: true,
        });
        if (!reason || reason.trim().length < 5) return;

        const confirmed = await appDialog.confirm({
            title: 'Reverse complete receipt?',
            description: 'This will reverse the complete payment receipt and restore all of its allocations.',
            confirmLabel: 'Reverse receipt',
            confirmIcon: <RotateCcw className="size-4" aria-hidden="true" />,
            destructive: true,
        });
        if (confirmed) {
            router.post(`/college/${college.id}/fee-payments/${id}/reverse`, {
                reason: reason.trim(),
            });
        }
    };

    const submitRefund = (event: FormEvent) => {
        event.preventDefault();
        if (refundPayment) {
            refund.post(
                `/college/${college.id}/fee-payments/${refundPayment.id}/refund`,
                { onSuccess: () => setRefundPayment(null) },
            );
        }
    };

    return (
        <>
            <Head title="Fee Adjustments / Refunds" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Fee Adjustments / Reversal / Refund
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Controlled post-payment corrections. Gross Fee Demand remains immutable and every action is auditable.
                    </p>
                </div>

                <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-[240px_300px_minmax(320px,1fr)_auto]">
                    <div className="space-y-1.5">
                        <Label>Academic Session</Label>
                        <Select value={sessionId} onValueChange={changeSession}>
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Select academic session" />
                            </SelectTrigger>
                            <SelectContent>
                                {sessions.map((session) => (
                                    <SelectItem key={session.id} value={String(session.id)}>
                                        {session.name} ({session.code})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-1.5">
                        <Label>Programme Offering</Label>
                        <Select value={offeringId} onValueChange={changeOffering}>
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="All Programme Offerings" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="0">All Programme Offerings</SelectItem>
                                {offerings.map((offering) => (
                                    <SelectItem key={offering.id} value={String(offering.id)}>
                                        {offering.program_name || 'Programme'}
                                        {offering.program_code ? ` (${offering.program_code})` : ''}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-1.5">
                        <Label>Search</Label>
                        <Input
                            value={q}
                            onChange={(event) => setQ(event.target.value)}
                            onKeyDown={(event) => event.key === 'Enter' && filter()}
                            placeholder="Student / application / admission / demand / receipt"
                        />
                    </div>

                    <div className="flex items-end">
                        <Button type="button" variant="outline" onClick={() => filter()}>
                            <Search className="size-4" aria-hidden="true" />
                            Search
                        </Button>
                    </div>
                </div>

                {can.post && (
                    <form onSubmit={submitAdj} className="space-y-4 rounded-lg border p-4">
                        <h2 className="flex items-center gap-2 font-semibold"><ReceiptText className="size-4" aria-hidden="true" />Post Manual Adjustment</h2>
                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="min-w-0 space-y-1.5">
                                <Label>Student / Fee Demand</Label>
                                <Select value={demandId ? String(demandId) : ''} onValueChange={changeDemand}>
                                    <SelectTrigger className="w-full min-w-0">
                                        <SelectValue placeholder="Select student / demand" />
                                    </SelectTrigger>
                                    <SelectContent className="max-w-[calc(100vw-2rem)]">
                                        {demands.map((demand) => (
                                            <SelectItem key={demand.id} value={String(demand.id)}>
                                                {demand.candidate_name} · {demand.demand_no} · Outstanding {money(demand.outstanding_amount)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="min-w-0 space-y-1.5">
                                <Label>Fee Head</Label>
                                <Select
                                    value={adj.data.fee_demand_item_id ? String(adj.data.fee_demand_item_id) : ''}
                                    onValueChange={(value) => adj.setData('fee_demand_item_id', Number(value))}
                                    disabled={!selected}
                                >
                                    <SelectTrigger className="w-full min-w-0">
                                        <SelectValue placeholder="Select fee head" />
                                    </SelectTrigger>
                                    <SelectContent className="max-w-[calc(100vw-2rem)]">
                                        {selected?.items.map((item) => (
                                            <SelectItem key={item.id} value={String(item.id)}>
                                                {item.fee_head_name} ({item.fee_head_code}) · {money(item.amount)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="min-w-0 space-y-1.5">
                                <Label>Adjustment Date</Label>
                                <DatePicker
                                    id="fee-adjustment-date"
                                    name="adjustment_date"
                                    value={adj.data.adjustment_date}
                                    onValueChange={(value) => adj.setData('adjustment_date', value)}
                                    max={localToday()}
                                    invalid={Boolean(adj.errors.adjustment_date)}
                                />
                            </div>

                            <div className="min-w-0 space-y-1.5">
                                <Label>Direction</Label>
                                <Select value={adj.data.direction} onValueChange={(value) => adj.setData('direction', value)}>
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="CREDIT">Credit — reduce liability</SelectItem>
                                        <SelectItem value="DEBIT">Debit — increase liability</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="min-w-0 space-y-1.5">
                                <Label>Amount</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    placeholder="Amount"
                                    value={adj.data.amount}
                                    onChange={(event) => adj.setData('amount', event.target.value)}
                                    aria-invalid={Boolean(adj.errors.amount)}
                                />
                            </div>

                            <div className="min-w-0 space-y-1.5">
                                <Label>Reason Type</Label>
                                <Select value={adj.data.reason_code} onValueChange={(value) => adj.setData('reason_code', value)}>
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="CORRECTION">Correction</SelectItem>
                                        <SelectItem value="ROUNDING">Rounding</SelectItem>
                                        <SelectItem value="APPROVED_RELIEF">Approved Relief</SelectItem>
                                        <SelectItem value="OTHER">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label>Reason</Label>
                            <Textarea
                                placeholder="Mandatory reason"
                                value={adj.data.reason}
                                onChange={(event) => adj.setData('reason', event.target.value)}
                                aria-invalid={Boolean(adj.errors.reason)}
                            />
                        </div>

                        <Button type="submit" disabled={adj.processing || !selected}>
                            <ReceiptText className="size-4" aria-hidden="true" />
                            {adj.processing ? 'Posting…' : 'Post Adjustment'}
                        </Button>
                        {Object.values(adj.errors).map((error, index) => (
                            <div key={index} className="text-sm text-destructive">
                                {error}
                            </div>
                        ))}
                    </form>
                )}

                <section className="space-y-3">
                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 className="flex items-center gap-2 font-semibold"><RotateCcw className="size-4" aria-hidden="true" />Posted Payments — Reversal / Refund</h2>
                            <p className="text-sm text-muted-foreground">
                                One row per student. Expand a student to work with individual receipts.
                            </p>
                        </div>
                        <div className="w-28 space-y-1.5">
                            <Label>Per page</Label>
                            <Select
                                value={perPage}
                                onValueChange={(value) => {
                                    setPerPage(value);
                                    setExpanded({});
                                    filter({ per_page: value });
                                }}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="25">25</SelectItem>
                                    <SelectItem value="50">50</SelectItem>
                                    <SelectItem value="100">100</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full min-w-[1050px] table-fixed text-sm">
                            <thead className="border-b bg-muted/30">
                                <tr>
                                    <th className="w-[24%] px-3 py-2 text-left font-medium">Student</th>
                                    <th className="w-[18%] px-3 py-2 text-left font-medium">Admission</th>
                                    <th className="w-[22%] px-3 py-2 text-left font-medium">Programme Offering</th>
                                    <th className="w-[13%] px-3 py-2 text-right font-medium">Total Paid</th>
                                    <th className="w-[13%] px-3 py-2 text-right font-medium">Refundable</th>
                                    <th className="w-[10%] px-3 py-2 text-left font-medium">Receipts</th>
                                </tr>
                            </thead>
                            <tbody>
                                {payment_students.data.map((student) => {
                                    const isOpen = Boolean(expanded[student.admission_id]);
                                    return (
                                        <FragmentRows
                                            key={student.admission_id}
                                            student={student}
                                            open={isOpen}
                                            onToggle={() =>
                                                setExpanded((current) => ({
                                                    ...current,
                                                    [student.admission_id]: !current[student.admission_id],
                                                }))
                                            }
                                            canRefund={can.refund}
                                            canReverse={can.reverse}
                                            onRefund={(payment) => {
                                                setRefundPayment(payment);
                                                refund.setData('amount', payment.refundable_balance);
                                            }}
                                            onReverse={reversePay}
                                        />
                                    );
                                })}
                                {payment_students.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-3 py-8 text-center text-muted-foreground">
                                            No posted payments found for the selected filters.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
                        <span className="text-muted-foreground">
                            {payment_students.total === 0
                                ? 'No students'
                                : `Showing ${payment_students.from ?? 0}–${payment_students.to ?? 0} of ${payment_students.total} students`}
                        </span>
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                disabled={!payment_students.prev_page_url}
                                onClick={() =>
                                    payment_students.prev_page_url &&
                                    router.visit(payment_students.prev_page_url, {
                                        preserveState: true,
                                        preserveScroll: true,
                                    })
                                }
                            >
                                Previous
                            </Button>
                            <span>
                                Page {payment_students.current_page} of {payment_students.last_page}
                            </span>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                disabled={!payment_students.next_page_url}
                                onClick={() =>
                                    payment_students.next_page_url &&
                                    router.visit(payment_students.next_page_url, {
                                        preserveState: true,
                                        preserveScroll: true,
                                    })
                                }
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </section>

                {refundPayment && (
                    <form onSubmit={submitRefund} className="space-y-4 rounded-lg border p-4">
                        <div>
                            <h2 className="flex items-center gap-2 font-semibold"><Undo2 className="size-4" aria-hidden="true" />Refund {refundPayment.receipt_no}</h2>
                            <p className="text-sm text-muted-foreground">
                                Maximum refundable paid balance: {money(refundPayment.refundable_balance)}. Non-refundable Fee Heads are automatically excluded.
                            </p>
                        </div>

                        <div className="grid gap-4 md:grid-cols-4">
                            <div className="min-w-0 space-y-1.5">
                                <Label>Refund Date</Label>
                                <DatePicker
                                    id={`fee-refund-date-${refundPayment.id}`}
                                    name="refund_date"
                                    value={refund.data.refund_date}
                                    onValueChange={(value) => refund.setData('refund_date', value)}
                                    invalid={Boolean(refund.errors.refund_date)}
                                />
                            </div>

                            <div className="min-w-0 space-y-1.5">
                                <Label>Refund Amount</Label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    max={refundPayment.refundable_balance}
                                    value={refund.data.amount}
                                    onChange={(event) => refund.setData('amount', event.target.value)}
                                    aria-invalid={Boolean(refund.errors.amount)}
                                />
                            </div>

                            <div className="min-w-0 space-y-1.5">
                                <Label>Refund Mode</Label>
                                <Select value={refund.data.refund_mode} onValueChange={(value) => refund.setData('refund_mode', value)}>
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {['BANK_TRANSFER', 'UPI', 'CASH', 'CARD', 'CHEQUE', 'GATEWAY', 'OTHER'].map((mode) => (
                                            <SelectItem key={mode} value={mode}>
                                                {mode.replaceAll('_', ' ')}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="min-w-0 space-y-1.5">
                                <Label>Reference No. (optional)</Label>
                                <Input
                                    placeholder="Reference no."
                                    value={refund.data.reference_no}
                                    onChange={(event) => refund.setData('reference_no', event.target.value)}
                                />
                            </div>
                        </div>

                        <div className="space-y-1.5">
                            <Label>Refund Reason</Label>
                            <Textarea
                                placeholder="Mandatory refund reason"
                                value={refund.data.reason}
                                onChange={(event) => refund.setData('reason', event.target.value)}
                                aria-invalid={Boolean(refund.errors.reason)}
                            />
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button type="submit" disabled={refund.processing}>
                                <Undo2 className="size-4" aria-hidden="true" />
                                {refund.processing ? 'Posting…' : 'Post Refund'}
                            </Button>
                            <Button type="button" variant="outline" onClick={() => setRefundPayment(null)}>
                                Cancel
                            </Button>
                        </div>
                        {Object.values(refund.errors).map((error, index) => (
                            <div key={index} className="text-sm text-destructive">
                                {error}
                            </div>
                        ))}
                    </form>
                )}

                <section className="space-y-2">
                    <h2 className="font-semibold">Adjustment Register</h2>
                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full min-w-[850px] text-sm">
                            <thead className="border-b bg-muted/30">
                                <tr>
                                    <th className="px-3 py-2 text-left font-medium">Adjustment</th>
                                    <th className="px-3 py-2 text-left font-medium">Date</th>
                                    <th className="px-3 py-2 text-left font-medium">Direction</th>
                                    <th className="px-3 py-2 text-right font-medium">Amount</th>
                                    <th className="px-3 py-2 text-left font-medium">Status</th>
                                    <th className="px-3 py-2 text-left font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {adjustments.map((adjustment) => (
                                    <tr className="border-b last:border-0" key={adjustment.id}>
                                        <td className="px-3 py-3">
                                            {adjustment.adjustment_no}
                                            <div className="text-xs text-muted-foreground">{adjustment.reason}</div>
                                        </td>
                                        <td className="px-3 py-3">{String(adjustment.adjustment_date).slice(0, 10)}</td>
                                        <td className="px-3 py-3">{adjustment.direction}</td>
                                        <td className="px-3 py-3 text-right">{money(adjustment.amount)}</td>
                                        <td className="px-3 py-3">{adjustment.status}</td>
                                        <td className="px-3 py-3">
                                            {can.reverse && adjustment.status === 'POSTED' && (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => reverseAdj(adjustment.id)}
                                                >
                                                    <RotateCcw className="size-4" aria-hidden="true" />
                                                    Reverse
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="space-y-2">
                    <h2 className="flex items-center gap-2 font-semibold"><Undo2 className="size-4" aria-hidden="true" />Refund Register</h2>
                    <div className="overflow-x-auto rounded-md border">
                        <table className="w-full min-w-[760px] text-sm">
                            <thead className="border-b bg-muted/30">
                                <tr>
                                    <th className="px-3 py-2 text-left font-medium">Refund</th>
                                    <th className="px-3 py-2 text-left font-medium">Receipt</th>
                                    <th className="px-3 py-2 text-left font-medium">Date</th>
                                    <th className="px-3 py-2 text-right font-medium">Amount</th>
                                    <th className="px-3 py-2 text-left font-medium">Mode</th>
                                </tr>
                            </thead>
                            <tbody>
                                {refunds.map((item) => (
                                    <tr className="border-b last:border-0" key={item.id}>
                                        <td className="px-3 py-3">{item.refund_no}</td>
                                        <td className="px-3 py-3">{item.receipt_no}</td>
                                        <td className="px-3 py-3">{String(item.refund_date).slice(0, 10)}</td>
                                        <td className="px-3 py-3 text-right">{money(item.amount)}</td>
                                        <td className="px-3 py-3">{String(item.refund_mode).replaceAll('_', ' ')}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </>
    );
}

function FragmentRows({
    student,
    open,
    onToggle,
    canRefund,
    canReverse,
    onRefund,
    onReverse,
}: {
    student: PaymentStudent;
    open: boolean;
    onToggle: () => void;
    canRefund: boolean;
    canReverse: boolean;
    onRefund: (payment: Receipt) => void;
    onReverse: (id: number) => void | Promise<void>;
}) {
    return (
        <>
            <tr className="border-b align-top last:border-0">
                <td className="px-3 py-3 font-medium">
                    {student.candidate_name || '—'}
                    <div className="text-xs font-normal text-muted-foreground">
                        {student.application_no || '—'}
                    </div>
                </td>
                <td className="px-3 py-3">{student.admission_no || '—'}</td>
                <td className="px-3 py-3">
                    {student.programme_offering?.program_name || '—'}
                    {student.programme_offering?.program_code && (
                        <div className="text-xs text-muted-foreground">
                            {student.programme_offering.program_code}
                        </div>
                    )}
                </td>
                <td className="px-3 py-3 text-right font-medium">
                    {money(student.total_paid)}
                </td>
                <td className="px-3 py-3 text-right">
                    {money(student.refundable_balance)}
                </td>
                <td className="px-3 py-3">
                    <Button type="button" size="sm" variant="outline" onClick={onToggle}>
                        {open ? <ChevronUp className="size-4" aria-hidden="true" /> : <ChevronDown className="size-4" aria-hidden="true" />}
                        {open ? 'Hide' : 'View'} {student.receipt_count}
                    </Button>
                </td>
            </tr>
            {open && (
                <tr className="border-b bg-muted/10">
                    <td colSpan={6} className="p-3 md:p-4">
                        <div className="overflow-x-auto rounded-md border bg-background">
                            <table className="w-full min-w-[900px] table-fixed text-sm">
                                <thead className="border-b bg-muted/30">
                                    <tr>
                                        <th className="w-[28%] px-3 py-2 text-left font-medium">Receipt</th>
                                        <th className="w-[20%] px-3 py-2 text-left font-medium">Date / Mode</th>
                                        <th className="w-[14%] px-3 py-2 text-right font-medium">Paid</th>
                                        <th className="w-[14%] px-3 py-2 text-right font-medium">Refundable</th>
                                        <th className="w-[24%] px-3 py-2 text-left font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {student.receipts.map((payment) => (
                                        <tr className="border-b last:border-0" key={payment.id}>
                                            <td className="px-3 py-3 font-medium">
                                                {payment.receipt_no}
                                                {payment.reference_no && (
                                                    <div className="text-xs font-normal text-muted-foreground">
                                                        Ref: {payment.reference_no}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-3 py-3">
                                                {niceDate(payment.payment_date)}
                                                <div className="text-xs text-muted-foreground">
                                                    {payment.payment_mode.replaceAll('_', ' ')}
                                                </div>
                                            </td>
                                            <td className="px-3 py-3 text-right font-medium">
                                                {money(payment.amount)}
                                            </td>
                                            <td className="px-3 py-3 text-right">
                                                {money(payment.refundable_balance)}
                                            </td>
                                            <td className="px-3 py-3">
                                                <div className="flex flex-wrap gap-2">
                                                    {canRefund && Number(payment.refundable_balance) > 0 && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => onRefund(payment)}
                                                        >
                                                            <Undo2 className="size-4" aria-hidden="true" />
                                                            Refund
                                                        </Button>
                                                    )}
                                                    {canReverse && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() => onReverse(payment.id)}
                                                        >
                                                            <RotateCcw className="size-4" aria-hidden="true" />
                                                            Reverse receipt
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            )}
        </>
    );
}
