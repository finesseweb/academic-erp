import { Form, Head, Link } from '@inertiajs/react';
import { Eye, Filter, ScrollText, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DatePicker } from '@/components/ui/date-picker';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Log = {
    id: number;
    event: string;
    resource_type: string;
    resource_id: number | null;
    ip_address: string | null;
    before: Record<string, unknown> | null;
    after: Record<string, unknown> | null;
    created_at: string;
    actor: { id: number; name: string; email: string } | null;
};
type Props = {
    logs: {
        data: Log[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    filters: Record<string, string | undefined>;
    actors: { id: number; name: string }[];
    events: string[];
    resources: string[];
    context?: {
        title: string;
        eyebrow: string;
        description: string;
        action: string;
    };
};

const pretty = (value: Record<string, unknown> | null) =>
    value ? JSON.stringify(value, null, 2) : 'No value recorded';

export default function AuditLogs({
    logs,
    filters,
    actors,
    events,
    resources,
    context,
}: Props) {
    return (
        <>
            <Head title={context?.title ?? 'Audit Logs'} />
            <div className="space-y-6 p-4 md:p-6">
                <header className="border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        {context?.eyebrow ?? 'Audit & Security'}
                    </p>
                    <h1 className="text-2xl font-semibold sm:text-3xl">
                        {context?.title ?? 'Audit Logs'}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {context?.description ??
                            'Immutable history of security and high-impact administration events.'}
                    </p>
                </header>
                <Card>
                    <CardContent className="pt-6">
                        <Form
                            action={context?.action ?? '/admin/audit-logs'}
                            method="get"
                            className="grid gap-3 lg:grid-cols-4"
                        >
                            <div className="relative lg:col-span-2">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search ?? ''}
                                    placeholder="Search event or resource"
                                    className="pl-9"
                                    aria-label="Search audit logs"
                                />
                            </div>
                            <Select
                                name="actor_id"
                                defaultValue={filters.actor_id ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="All actors" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All actors
                                    </SelectItem>
                                    {actors.map((a) => (
                                        <SelectItem
                                            key={a.id}
                                            value={String(a.id)}
                                        >
                                            {a.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                name="event"
                                defaultValue={filters.event ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="All events" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All events
                                    </SelectItem>
                                    {events.map((e) => (
                                        <SelectItem key={e} value={e}>
                                            {e.replaceAll('_', ' ')}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                name="resource_type"
                                defaultValue={filters.resource_type ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="All resources" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All resources
                                    </SelectItem>
                                    {resources.map((r) => (
                                        <SelectItem key={r} value={r}>
                                            {r}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Input
                                name="ip_address"
                                defaultValue={filters.ip_address ?? ''}
                                placeholder="Exact IP address"
                                aria-label="IP address"
                            />
                            <DatePicker
                                id="audit-from"
                                name="from"
                                defaultValue={filters.from}
                                min="2020-01-01"
                                max="2100-12-31"
                            />
                            <DatePicker
                                id="audit-until"
                                name="until"
                                defaultValue={filters.until}
                                min="2020-01-01"
                                max="2100-12-31"
                            />
                            <div className="flex gap-2 lg:col-span-4">
                                <Button type="submit">
                                    <Filter />
                                    Apply filters
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={
                                            context?.action ??
                                            '/admin/audit-logs'
                                        }
                                    >
                                        Reset
                                    </Link>
                                </Button>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-0">
                        {logs.data.length ? (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/60 text-left">
                                        <tr>
                                            <th className="p-4">Timestamp</th>
                                            <th className="p-4">Actor</th>
                                            <th className="p-4">Event</th>
                                            <th className="p-4">Resource</th>
                                            <th className="p-4">IP</th>
                                            <th className="p-4">
                                                <span className="sr-only">
                                                    Details
                                                </span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {logs.data.map((log) => (
                                            <tr
                                                key={log.id}
                                                className="border-t transition-colors hover:bg-muted/30"
                                            >
                                                <td className="p-4 whitespace-nowrap">
                                                    {new Date(
                                                        log.created_at,
                                                    ).toLocaleString()}
                                                </td>
                                                <td className="p-4">
                                                    {log.actor?.name ??
                                                        'System'}
                                                    <span className="block text-xs text-muted-foreground">
                                                        {log.actor?.email}
                                                    </span>
                                                </td>
                                                <td className="p-4 font-medium">
                                                    {log.event.replaceAll(
                                                        '_',
                                                        ' ',
                                                    )}
                                                </td>
                                                <td className="p-4">
                                                    {log.resource_type}
                                                    {log.resource_id
                                                        ? ` #${log.resource_id}`
                                                        : ''}
                                                </td>
                                                <td className="p-4 font-mono text-xs">
                                                    {log.ip_address ?? '—'}
                                                </td>
                                                <td className="p-4">
                                                    <Dialog>
                                                        <DialogTrigger asChild>
                                                            <Button
                                                                size="icon"
                                                                variant="ghost"
                                                                aria-label={`View ${log.event} details`}
                                                            >
                                                                <Eye />
                                                            </Button>
                                                        </DialogTrigger>
                                                        <DialogContent className="max-w-2xl">
                                                            <DialogTitle>
                                                                Audit event #
                                                                {log.id}
                                                            </DialogTitle>
                                                            <DialogDescription>
                                                                {log.event} ·{' '}
                                                                {new Date(
                                                                    log.created_at,
                                                                ).toLocaleString()}
                                                            </DialogDescription>
                                                            <div className="grid gap-4 md:grid-cols-2">
                                                                <div>
                                                                    <h3 className="mb-2 font-medium">
                                                                        Before
                                                                    </h3>
                                                                    <pre className="max-h-72 overflow-auto rounded-lg bg-muted p-3 text-xs">
                                                                        {pretty(
                                                                            log.before,
                                                                        )}
                                                                    </pre>
                                                                </div>
                                                                <div>
                                                                    <h3 className="mb-2 font-medium">
                                                                        After
                                                                    </h3>
                                                                    <pre className="max-h-72 overflow-auto rounded-lg bg-muted p-3 text-xs">
                                                                        {pretty(
                                                                            log.after,
                                                                        )}
                                                                    </pre>
                                                                </div>
                                                            </div>
                                                        </DialogContent>
                                                    </Dialog>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="grid place-items-center py-16 text-center">
                                <ScrollText className="size-10 text-muted-foreground" />
                                <h2 className="mt-3 font-semibold">
                                    No audit events found
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Adjust the filters or reset the search.
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
                {logs.links.length > 3 ? (
                    <nav
                        className="flex flex-wrap justify-center gap-1"
                        aria-label="Audit log pagination"
                    >
                        {logs.links.map((link, i) =>
                            link.url ? (
                                <Button
                                    key={i}
                                    size="sm"
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
                                    asChild
                                >
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                </Button>
                            ) : (
                                <Button
                                    key={i}
                                    size="sm"
                                    variant="outline"
                                    disabled
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ),
                        )}
                    </nav>
                ) : null}
            </div>
        </>
    );
}
AuditLogs.layout = {
    breadcrumbs: [{ title: 'Audit Logs', href: '/admin/audit-logs' }],
};
