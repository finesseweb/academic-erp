import { Head, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Eye, Search, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { useAppLoading } from '@/components/app-loading-provider';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

const money = (value: string | number, currency = 'INR') =>
    new Intl.NumberFormat('en-IN', { style: 'currency', currency, minimumFractionDigits: 2 }).format(Number(value || 0));

type Session = { id: number; name: string; code: string; is_current: boolean };
type Offering = { id: number; name: string | null; code: string | null; status: string };
type Item = { fee_demand_item_id: number; demand_no: string; fee_head_name: string; fee_head_code: string; amount: string; outstanding: string; cleared: boolean };
type Row = { admission_id: number; admission_no: string | null; candidate_name: string | null; application_no: string | null; program: string | null; session: string | null; currency: string; required_amount: string; clearance_outstanding: string; required_item_count: number; cleared_item_count: number; status: 'CLEARED' | 'PENDING' | 'NOT_REQUIRED'; is_cleared: boolean; items: Item[] };
type Page<T> = { data: T[]; current_page: number; last_page: number; from: number | null; to: number | null; total: number; prev_page_url: string | null; next_page_url: string | null };
type Props = { college: { id: number; name: string; code: string }; sessions: Session[]; offerings: Offering[]; students: Page<Row>; clearance: Row | null; filters: { session_id: number; offering_id: number | null; q: string; status: string; per_page: number; admission_id: number | null } };

const statusBadge = (status: Row['status']) => (
    <Badge variant={status === 'CLEARED' ? 'default' : status === 'PENDING' ? 'destructive' : 'secondary'}>
        {status === 'NOT_REQUIRED' ? 'Not Required' : status}
    </Badge>
);

export default function Index({ college, sessions, offerings, students, clearance, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [sessionId, setSessionId] = useState(String(filters.session_id || ''));
    const [offeringId, setOfferingId] = useState(filters.offering_id ? String(filters.offering_id) : 'all');
    const [status, setStatus] = useState(filters.status || 'all');
    const [perPage, setPerPage] = useState(String(filters.per_page || 25));
    const { isLoading: loading, setNextLoadingLabel } = useAppLoading();

    const visit = (params: Record<string, string | number | null | undefined> = {}, loadingLabel = 'Loading fee clearance…') => {
        setNextLoadingLabel(loadingLabel);
        router.get(`/college/${college.id}/fee-clearance`, {
            q,
            session_id: sessionId,
            offering_id: offeringId === 'all' ? null : offeringId,
            status: status === 'all' ? null : status,
            per_page: perPage,
            ...params,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const changeSession = (value: string) => {
        setSessionId(value);
        setOfferingId('all');
        visit({ session_id: value, offering_id: null, admission_id: null, page: 1 }, 'Loading programme offerings…');
    };

    const changeOffering = (value: string) => {
        setOfferingId(value);
        visit({ offering_id: value === 'all' ? null : value, admission_id: null, page: 1 }, 'Loading fee clearance…');
    };

    const paginate = (url: string | null) => {
        if (!url) return;
        setNextLoadingLabel('Loading fee clearance…');
        router.visit(url, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return <>
        <Head title="Fee Clearance" />
        <div className="space-y-5 p-4 md:p-6">
            <div className="flex items-start gap-3">
                <div className="mt-0.5 rounded-lg border bg-card p-2"><ShieldCheck className="size-5" /></div>
                <div>
                    <h1 className="text-xl font-semibold">Fee Clearance</h1>
                    <p className="text-sm text-muted-foreground">Authoritative enrollment gate derived from Fee Items explicitly marked Required for Enrollment Clearance and their posted financial history.</p>
                </div>
            </div>

            <Card>
                <CardHeader><CardTitle>Confirmed Admissions</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-[210px_260px_minmax(260px,1fr)_180px_100px_auto]">
                        <Select value={sessionId} onValueChange={changeSession} disabled={loading}>
                            <SelectTrigger><SelectValue placeholder="Academic Session" /></SelectTrigger>
                            <SelectContent>{sessions.map(session => <SelectItem key={session.id} value={String(session.id)}>{session.name}{session.is_current ? ' · Current' : ''}</SelectItem>)}</SelectContent>
                        </Select>
                        <Select value={offeringId} onValueChange={changeOffering} disabled={loading || !sessionId}>
                            <SelectTrigger><SelectValue placeholder="Programme Offering" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Programme Offerings</SelectItem>
                                {offerings.map(offering => <SelectItem key={offering.id} value={String(offering.id)}>{offering.name ?? 'Programme'}{offering.code ? ` (${offering.code})` : ''}</SelectItem>)}
                            </SelectContent>
                        </Select>
                        <Input value={q} onChange={event => setQ(event.target.value)} onKeyDown={event => event.key === 'Enter' && !loading && visit({ admission_id: null, page: 1 })} placeholder="Search student / application / admission" disabled={loading} />
                        <Select value={status} onValueChange={setStatus} disabled={loading}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent><SelectItem value="all">All clearance states</SelectItem><SelectItem value="PENDING">Pending</SelectItem><SelectItem value="CLEARED">Cleared</SelectItem><SelectItem value="NOT_REQUIRED">Not Required</SelectItem></SelectContent>
                        </Select>
                        <Select value={perPage} onValueChange={setPerPage} disabled={loading}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>{[25, 50, 100].map(size => <SelectItem key={size} value={String(size)}>{size}</SelectItem>)}</SelectContent>
                        </Select>
                        <Button variant="outline" onClick={() => visit({ admission_id: null, page: 1 })} disabled={loading}>
                            {loading ? <Spinner /> : <Search className="size-4" />}{loading ? 'Loading…' : 'Search'}
                        </Button>
                    </div>

                    <div className="relative overflow-x-auto rounded-md border">
                        <table className="w-full min-w-[950px] text-sm">
                            <thead className="border-b bg-muted/30"><tr>{['Student', 'Admission', 'Programme', 'Required', 'Cleared Items', 'Outstanding', 'Status', 'Action'].map(label => <th key={label} className="px-3 py-2 text-left font-medium">{label}</th>)}</tr></thead>
                            <tbody>{students.data.map(row => <tr key={row.admission_id} className="border-b last:border-b-0">
                                <td className="px-3 py-3 font-medium">{row.candidate_name ?? '—'}<div className="text-xs text-muted-foreground">{row.application_no ?? '—'}</div></td>
                                <td className="px-3 py-3">{row.admission_no ?? '—'}</td>
                                <td className="px-3 py-3">{row.program ?? '—'}<div className="text-xs text-muted-foreground">{row.session ?? ''}</div></td>
                                <td className="px-3 py-3">{money(row.required_amount, row.currency)}</td>
                                <td className="px-3 py-3">{row.cleared_item_count}/{row.required_item_count}</td>
                                <td className="px-3 py-3 font-semibold">{money(row.clearance_outstanding, row.currency)}</td>
                                <td className="px-3 py-3">{statusBadge(row.status)}</td>
                                <td className="px-3 py-3"><Button size="sm" variant="outline" disabled={loading} onClick={() => visit({ admission_id: row.admission_id })}><Eye className="size-4" />View</Button></td>
                            </tr>)}
                            {students.data.length === 0 && <tr><td colSpan={8} className="px-3 py-10 text-center text-muted-foreground">No confirmed admissions with Fee Demands found for these filters.</td></tr>}
                            </tbody>
                        </table>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3 text-sm">
                        <span>Showing {students.from ?? 0}-{students.to ?? 0} of {students.total}</span>
                        <div className="flex items-center gap-2">
                            <Button size="sm" variant="outline" disabled={loading || !students.prev_page_url} onClick={() => paginate(students.prev_page_url)}><ChevronLeft className="size-4" />Previous</Button>
                            <span>Page {students.current_page} of {students.last_page}</span>
                            <Button size="sm" variant="outline" disabled={loading || !students.next_page_url} onClick={() => paginate(students.next_page_url)}>Next<ChevronRight className="size-4" /></Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {clearance && <Card>
                <CardHeader><CardTitle>{clearance.candidate_name} · Clearance Detail</CardTitle></CardHeader>
                <CardContent className="space-y-4">
                    <div className="flex flex-wrap items-center gap-3">{statusBadge(clearance.status)}<span className="text-sm">Enrollment gate: <strong>{clearance.is_cleared ? 'OPEN' : 'BLOCKED'}</strong></span><span className="text-sm text-muted-foreground">Outstanding {money(clearance.clearance_outstanding, clearance.currency)}</span></div>
                    {clearance.status === 'NOT_REQUIRED' ? <p className="text-sm text-muted-foreground">No active Fee Demand Item in the selected scope is marked Required for Enrollment Clearance. Fee Clearance therefore does not block enrollment.</p> : <div className="overflow-x-auto rounded-md border"><table className="w-full min-w-[700px] text-sm"><thead className="border-b bg-muted/30"><tr>{['Demand', 'Fee Head', 'Original Amount', 'Clearance Outstanding', 'State'].map(label => <th key={label} className="px-3 py-2 text-left font-medium">{label}</th>)}</tr></thead><tbody>{clearance.items.map(item => <tr key={item.fee_demand_item_id} className="border-b last:border-b-0"><td className="px-3 py-3">{item.demand_no}</td><td className="px-3 py-3">{item.fee_head_name}<div className="text-xs text-muted-foreground">{item.fee_head_code}</div></td><td className="px-3 py-3">{money(item.amount, clearance.currency)}</td><td className="px-3 py-3 font-semibold">{money(item.outstanding, clearance.currency)}</td><td className="px-3 py-3"><Badge variant={item.cleared ? 'default' : 'destructive'}>{item.cleared ? 'Cleared' : 'Pending'}</Badge></td></tr>)}</tbody></table></div>}
                    <p className="text-xs text-muted-foreground">This page does not manually approve clearance. Payments, benefits, adjustments, reversals, refunds and applicable late-fine ledger entries automatically change the gate, preventing stale clearance after a financial correction.</p>
                </CardContent>
            </Card>}
        </div>
    </>;
}
