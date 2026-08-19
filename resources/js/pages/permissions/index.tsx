import { Form, Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    CheckCircle2,
    KeyRound,
    Search,
    ShieldCheck,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
type Permission = {
    id: number;
    module: string;
    resource: string;
    action: string;
    code: string;
    description: string;
    is_sensitive: boolean;
    status: 'ACTIVE' | 'INACTIVE';
};
type P = {
    permissions: {
        data: Permission[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    modules: string[];
    filters: {
        search?: string;
        module?: string;
        status?: string;
        sensitive?: string;
    };
    summary: { total: number; active: number; sensitive: number };
};
export default function PermissionsIndex({
    permissions,
    modules,
    filters,
    summary,
}: P) {
    const filtered = Boolean(
        filters.search || filters.module || filters.status || filters.sensitive,
    );

    return (
        <>
            <Head title="Permissions" />
            <div className="space-y-6 p-4 md:p-6">
                <header className="border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        Access & Security
                    </p>
                    <h1 className="text-2xl font-semibold sm:text-3xl">
                        Permission Catalog
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Read-only registry of capabilities implemented and
                        enforced by the Laravel backend.
                    </p>
                </header>
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-6">
                            <KeyRound className="size-8 text-primary" />
                            <div>
                                <p className="text-2xl font-semibold">
                                    {summary.total}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Implemented permissions
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-6">
                            <CheckCircle2 className="size-8 text-emerald-600" />
                            <div>
                                <p className="text-2xl font-semibold">
                                    {summary.active}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Active capabilities
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-3 pt-6">
                            <AlertTriangle className="size-8 text-amber-600" />
                            <div>
                                <p className="text-2xl font-semibold">
                                    {summary.sensitive}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Sensitive actions
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardContent className="pt-6">
                        <Form
                            action="/admin/permissions"
                            method="get"
                            className="grid gap-3 xl:grid-cols-[1fr_220px_170px_170px_auto]"
                        >
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    name="search"
                                    defaultValue={filters.search ?? ''}
                                    placeholder="Search code, resource, or description"
                                    className="pl-9"
                                    aria-label="Search permissions"
                                />
                            </div>
                            <Select
                                name="module"
                                defaultValue={filters.module ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All modules
                                    </SelectItem>
                                    {modules.map((m) => (
                                        <SelectItem key={m} value={m}>
                                            {m}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                name="sensitive"
                                defaultValue={filters.sensitive ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All sensitivity
                                    </SelectItem>
                                    <SelectItem value="yes">
                                        Sensitive
                                    </SelectItem>
                                    <SelectItem value="no">Standard</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select
                                name="status"
                                defaultValue={filters.status ?? 'all'}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All statuses
                                    </SelectItem>
                                    <SelectItem value="ACTIVE">
                                        Active
                                    </SelectItem>
                                    <SelectItem value="INACTIVE">
                                        Inactive
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div className="flex gap-2">
                                <Button type="submit">Filter</Button>
                                <Button variant="outline" asChild>
                                    <Link href="/admin/permissions">Reset</Link>
                                </Button>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        {permissions.data.length ? (
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3">Module</th>
                                        <th className="px-4 py-3">
                                            Permission
                                        </th>
                                        <th className="px-4 py-3">
                                            Resource / action
                                        </th>
                                        <th className="px-4 py-3">
                                            Description
                                        </th>
                                        <th className="px-4 py-3">Metadata</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {permissions.data.map((p) => (
                                        <tr
                                            key={p.id}
                                            className="border-b transition-colors last:border-0 hover:bg-muted/40"
                                        >
                                            <td className="px-4 py-4 font-medium">
                                                {p.module}
                                            </td>
                                            <td className="px-4 py-4">
                                                <code className="rounded bg-muted px-2 py-1 text-xs text-primary">
                                                    {p.code}
                                                </code>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p>{p.resource}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {p.action}
                                                </p>
                                            </td>
                                            <td className="max-w-md px-4 py-4 text-muted-foreground">
                                                {p.description}
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex flex-wrap gap-1">
                                                    <span
                                                        className={`rounded-full px-2 py-1 text-xs ${p.status === 'ACTIVE' ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'bg-muted text-muted-foreground'}`}
                                                    >
                                                        {p.status === 'ACTIVE'
                                                            ? 'Active'
                                                            : 'Inactive'}
                                                    </span>
                                                    {p.is_sensitive ? (
                                                        <span className="rounded-full bg-amber-500/10 px-2 py-1 text-xs text-amber-700 dark:text-amber-300">
                                                            Sensitive
                                                        </span>
                                                    ) : null}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        ) : (
                            <div className="grid place-items-center px-6 py-16 text-center">
                                <ShieldCheck className="size-10 text-muted-foreground" />
                                <h2 className="mt-4 font-semibold">
                                    {filtered
                                        ? 'No matching permissions'
                                        : 'No implemented permissions'}
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {filtered
                                        ? 'Adjust or reset the filters.'
                                        : 'Capabilities appear here when their backend modules are implemented.'}
                                </p>
                            </div>
                        )}
                    </div>
                    {permissions.total ? (
                        <div className="flex flex-col gap-3 border-t px-4 py-3 text-sm text-muted-foreground sm:flex-row sm:justify-between">
                            <p>
                                Showing {permissions.from}–{permissions.to} of{' '}
                                {permissions.total}
                            </p>
                            <nav className="flex gap-1" aria-label="Pagination">
                                {permissions.links.map((x, i) =>
                                    x.url ? (
                                        <Button
                                            key={i}
                                            size="sm"
                                            variant={
                                                x.active ? 'default' : 'outline'
                                            }
                                            asChild
                                        >
                                            <Link
                                                href={x.url}
                                                preserveScroll
                                                dangerouslySetInnerHTML={{
                                                    __html: x.label,
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
                                                __html: x.label,
                                            }}
                                        />
                                    ),
                                )}
                            </nav>
                        </div>
                    ) : null}
                </Card>
            </div>
        </>
    );
}
PermissionsIndex.layout = {
    breadcrumbs: [
        { title: 'Access & Security', href: '/admin/users' },
        { title: 'Permissions', href: '/admin/permissions' },
    ],
};
