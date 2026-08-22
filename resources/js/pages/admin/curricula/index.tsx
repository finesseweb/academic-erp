import { Head, router, useForm } from '@inertiajs/react';
import { BookOpenCheck, CircleX, Copy, Pencil, Plus, RotateCcw, Search, Send, Settings2, Trash2, X } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { DatePicker } from '@/components/ui/date-picker';
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

type Option = { id: number; name: string; code: string };
type ApprovalWorkflowOption = { id: number; name: string; code: string };
type Curriculum = {
    id: number;
    code: string;
    name: string;
    version: string;
    effective_from: string | null;
    effective_to: string | null;
    lifecycle_status: 'DRAFT' | 'ACTIVE' | 'RETIRED';
    approval_status:
        | 'NOT_SUBMITTED'
        | 'SUBMITTED'
        | 'UNDER_APPROVAL'
        | 'RETURNED'
        | 'REJECTED'
        | 'APPROVED';
    description: string | null;
    program_template: Option;
    academic_session: Option;
};

type Props = {
    curricula: {
        data: Curriculum[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    filters: { search?: string; status?: string };
    programTemplates: Option[];
    academicSessions: Option[];
    approvalWorkflows: ApprovalWorkflowOption[];
    permissions: {
        create: boolean;
        update: boolean;
        disable: boolean;
        submitApproval: boolean;
    };
};

const emptyForm = {
    program_template_id: '',
    academic_session_id: '',
    code: '',
    name: '',
    version: '1.0',
    effective_from: '',
    effective_to: '',
    lifecycle_status: 'DRAFT',
    description: '',
};

export default function CurriculumIndex({
    curricula,
    filters,
    programTemplates,
    academicSessions,
    approvalWorkflows,
    permissions,
}: Props) {
    const [editing, setEditing] = useState<Curriculum | null>(null);
    const [showForm, setShowForm] = useState(false);
    const [cloning, setCloning] = useState<Curriculum | null>(null);
    const [submittingApproval, setSubmittingApproval] =
        useState<Curriculum | null>(null);
    const approvalForm = useForm({
        approval_workflow_id: '',
    });
    const cloneForm = useForm({
        academic_session_id: '',
        code: '',
        name: '',
        version: '',
        effective_from: '',
        effective_to: '',
        description: '',
    });
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const form = useForm(emptyForm);

    const title = useMemo(() => editing ? 'Edit Curriculum Header' : 'Create Curriculum Header', [editing]);

    const openCreate = () => {
        setEditing(null);
        form.reset();
        form.clearErrors();
        setShowForm(true);
    };

    const openEdit = (item: Curriculum) => {
        setEditing(item);
        form.setData({
            program_template_id: String(item.program_template.id),
            academic_session_id: String(item.academic_session.id),
            code: item.code,
            name: item.name,
            version: item.version,
            effective_from: item.effective_from ?? '',
            effective_to: item.effective_to ?? '',
            lifecycle_status: item.lifecycle_status,
            description: item.description ?? '',
        });
        form.clearErrors();
        setShowForm(true);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setShowForm(false) };
        if (editing) form.patch(`/admin/curricula/${editing.id}`, options);
        else form.post('/admin/curricula', options);
    };

    const openClone = (item: Curriculum) => {
        setCloning(item);
        cloneForm.clearErrors();
        cloneForm.setData({
            academic_session_id: String(item.academic_session.id),
            code: `${item.code}-COPY`,
            name: `${item.name} Copy`,
            version: item.version,
            effective_from: '',
            effective_to: '',
            description: item.description ?? '',
        });
    };

    const submitClone = (event: FormEvent) => {
        event.preventDefault();

        if (!cloning) {
            return;
        }

        cloneForm.post(
            `/admin/curricula/${cloning.id}/clone-structure`,
            {
                preserveScroll: true,
                onSuccess: () => setCloning(null),
            },
        );
    };

    const openSubmitApproval = (item: Curriculum) => {
        setSubmittingApproval(item);
        approvalForm.clearErrors();
        approvalForm.setData({
            approval_workflow_id:
                approvalWorkflows.length === 1
                    ? String(approvalWorkflows[0].id)
                    : '',
        });
    };

    const submitForApproval = (event: FormEvent) => {
        event.preventDefault();

        if (!submittingApproval) {
            return;
        }

        approvalForm.post(
            `/admin/curricula/${submittingApproval.id}/submit-for-approval`,
            {
                preserveScroll: true,
                onSuccess: () => setSubmittingApproval(null),
            },
        );
    };

    const applyFilters = () => {
        router.get('/admin/curricula', { search, status }, { preserveState: true, replace: true });
    };


    const deleteCurriculum = (item: Curriculum) => {
        if (item.lifecycle_status !== 'DRAFT') {
            return;
        }

        const confirmation = window.prompt(
            `This permanently deletes "${item.name}" and its complete Terms, Slots and Course Mappings.\n\nType the Curriculum code ${item.code} to confirm.`,
        );

        if (confirmation !== item.code) {
            return;
        }

        router.delete(`/admin/curricula/${item.id}`, {
            preserveScroll: true,
        });
    };

    const retire = (item: Curriculum) => {
        if (!confirm(`Retire curriculum "${item.name}"? This will make the curriculum read-only.`)) return;

        router.patch(
            `/admin/curricula/${item.id}/retire`,
            {},
            { preserveScroll: true },
        );
    };

    const restore = (item: Curriculum) => {
        if (!confirm(`Restore curriculum "${item.name}" to its status before retirement?`)) return;

        router.patch(
            `/admin/curricula/${item.id}/restore`,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Curriculum" />
            <div className="space-y-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="flex items-center gap-2 text-sm font-medium text-primary">
                            <BookOpenCheck className="size-4" />
                            Academic structure
                        </p>
                        <h1 className="mt-1 text-2xl font-semibold sm:text-3xl">
                            Curriculum
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage versioned Curriculum Headers for Program Templates and Academic Sessions.
                        </p>
                    </div>

                    {permissions.create && (
                        <Button type="button" onClick={openCreate}>
                            <Plus className="size-4" />
                            Add Curriculum
                        </Button>
                    )}
                </header>

                <Card>
                    <CardContent className="pt-6">
                        <div className="grid gap-3 md:grid-cols-[1fr_220px_auto]">
                            <div className="relative">
                                <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    onKeyDown={(event) =>
                                        event.key === 'Enter' && applyFilters()
                                    }
                                    placeholder="Search name, code or version"
                                    className="pl-9"
                                    aria-label="Search curriculum"
                                />
                            </div>

                            <Select
                                value={status || 'all'}
                                onValueChange={(value) =>
                                    setStatus(value === 'all' ? '' : value)
                                }
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All statuses</SelectItem>
                                    <SelectItem value="DRAFT">Draft</SelectItem>
                                    <SelectItem value="ACTIVE">Active</SelectItem>
                                    <SelectItem value="RETIRED">Retired</SelectItem>
                                </SelectContent>
                            </Select>

                            <Button type="button" onClick={applyFilters}>
                                Filter
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">

                    {curricula.data.length === 0 ? (
                        <div className="px-6 py-14 text-center">
                            <BookOpenCheck className="mx-auto size-8 text-muted-foreground" />
                            <h2 className="mt-3 font-medium">No curriculum headers found</h2>
                            <p className="mt-1 text-sm text-muted-foreground">Create the first header before building terms, slots and course mapping.</p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-4">Curriculum</th>
                                        <th className="px-4 py-4">Program Template</th>
                                        <th className="px-4 py-4">Academic Session</th>
                                        <th className="px-4 py-4">Version</th>
                                        <th className="px-4 py-4">Effective</th>
                                        <th className="px-4 py-4">Status</th>
                                        <th className="px-4 py-3">
                                            Approval
                                        </th>
                                        <th className="px-4 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {curricula.data.map(item => (
                                        <tr key={item.id} className="border-b transition-colors last:border-0 hover:bg-muted/40">
                                            <td className="px-4 py-4"><div className="font-medium">{item.name}</div><div className="text-xs text-muted-foreground">{item.code}</div></td>
                                            <td className="px-4 py-4">{item.program_template.name}<div className="text-xs text-muted-foreground">{item.program_template.code}</div></td>
                                            <td className="px-4 py-4">{item.academic_session.name}</td>
                                            <td className="px-4 py-4">{item.version}</td>
                                            <td className="px-4 py-4 text-xs">{item.effective_from || '—'}{item.effective_to ? ` → ${item.effective_to}` : ''}</td>
                                            <td className="px-4 py-4">
                                                <span
                                                    className={`rounded-full px-2 py-1 text-xs ${
                                                        item.lifecycle_status === 'ACTIVE'
                                                            ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                                            : item.lifecycle_status === 'DRAFT'
                                                              ? 'bg-amber-500/10 text-amber-700 dark:text-amber-300'
                                                              : 'bg-muted text-muted-foreground'
                                                    }`}
                                                >
                                                    {item.lifecycle_status === 'ACTIVE'
                                                        ? 'Active'
                                                        : item.lifecycle_status === 'DRAFT'
                                                          ? 'Draft'
                                                          : 'Retired'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4">
                                                <span className="rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground">
                                                    {item.approval_status ===
                                                    'NOT_SUBMITTED'
                                                        ? 'Not Submitted'
                                                        : item.approval_status
                                                              .replaceAll(
                                                                  '_',
                                                                  ' ',
                                                              )
                                                              .toLowerCase()
                                                              .replace(
                                                                  /^./,
                                                                  (value) =>
                                                                      value.toUpperCase(),
                                                              )}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            router.get(
                                                                `/admin/curricula/${item.id}/structure/terms`,
                                                            )
                                                        }
                                                    >
                                                        <Settings2 className="size-4" />
                                                        Structure
                                                    </Button>

                                                    {permissions.create && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                openClone(item)
                                                            }
                                                        >
                                                            <Copy className="size-4" />
                                                            Clone Structure
                                                        </Button>
                                                    )}

                                                    {permissions.update &&
                                                        item.lifecycle_status ===
                                                            'DRAFT' &&
                                                        ![
                                                            'SUBMITTED',
                                                            'UNDER_APPROVAL',
                                                            'APPROVED',
                                                        ].includes(
                                                            item.approval_status,
                                                        ) && (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    openEdit(item)
                                                                }
                                                            >
                                                                <Pencil className="size-4" />
                                                                Edit
                                                            </Button>
                                                        )}


                                                    {permissions.update &&
                                                        item.lifecycle_status ===
                                                            'DRAFT' &&
                                                        ![
                                                            'SUBMITTED',
                                                            'UNDER_APPROVAL',
                                                            'APPROVED',
                                                        ].includes(
                                                            item.approval_status,
                                                        ) && (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    deleteCurriculum(
                                                                        item,
                                                                    )
                                                                }
                                                                className="text-destructive hover:text-destructive"
                                                            >
                                                                <Trash2 className="size-4" />
                                                                Delete
                                                            </Button>
                                                        )}

                                                    {permissions.disable &&
                                                        item.lifecycle_status !==
                                                            'RETIRED' && (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    retire(item)
                                                                }
                                                                className="text-destructive hover:text-destructive"
                                                            >
                                                                <CircleX className="size-4" />
                                                                Retire
                                                            </Button>
                                                        )}

                                                    {permissions.disable &&
                                                        item.lifecycle_status ===
                                                            'RETIRED' && (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                onClick={() =>
                                                                    restore(item)
                                                                }
                                                            >
                                                                <RotateCcw className="size-4" />
                                                                Restore
                                                            </Button>
                                                        )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    </div>

                    {curricula.links.length > 3 && (
                        <div className="flex flex-wrap gap-1 border-t px-4 py-3">
                            {curricula.links.map((link, index) => (
                                <Button
                                    key={index}
                                    size="sm"
                                    variant={link.active ? 'default' : 'outline'}
                                    disabled={!link.url}
                                    onClick={() =>
                                        link.url &&
                                        router.get(
                                            link.url,
                                            {},
                                            { preserveState: true },
                                        )
                                    }
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </Card>
            </div>

            {submittingApproval && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <div className="w-full max-w-lg rounded-xl border border-border bg-card shadow-xl">
                        <div className="border-b p-5">
                            <h2 className="text-lg font-semibold">
                                Submit for Academic Approval
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {submittingApproval.name} (
                                {submittingApproval.code}) will become
                                read-only until the approval request is
                                returned or rejected.
                            </p>
                        </div>

                        <form
                            onSubmit={submitForApproval}
                            className="space-y-4 p-5"
                        >
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Approval Workflow *
                                </label>
                                <Select
                                    value={
                                        approvalForm.data
                                            .approval_workflow_id
                                    }
                                    onValueChange={(value) =>
                                        approvalForm.setData(
                                            'approval_workflow_id',
                                            value,
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select workflow" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {approvalWorkflows.map(
                                            (workflow) => (
                                                <SelectItem
                                                    key={workflow.id}
                                                    value={String(
                                                        workflow.id,
                                                    )}
                                                >
                                                    {workflow.name} (
                                                    {workflow.code})
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                {approvalForm.errors
                                    .approval_workflow_id && (
                                    <p className="text-xs text-destructive">
                                        {
                                            approvalForm.errors
                                                .approval_workflow_id
                                        }
                                    </p>
                                )}
                                {approvalForm.errors.curriculum && (
                                    <p className="text-xs text-destructive">
                                        {
                                            approvalForm.errors
                                                .curriculum
                                        }
                                    </p>
                                )}
                            </div>

                            <div className="rounded-lg border bg-muted/30 p-3 text-sm text-muted-foreground">
                                Validate Structure must pass before
                                submission. Final approval automatically
                                activates the Curriculum.
                            </div>

                            <div className="flex justify-end gap-2 border-t pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() =>
                                        setSubmittingApproval(null)
                                    }
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={approvalForm.processing}
                                >
                                    <Send className="size-4" />
                                    {approvalForm.processing
                                        ? 'Submitting…'
                                        : 'Submit for Approval'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {cloning && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-border bg-card shadow-xl">
                        <div className="flex items-start justify-between border-b p-5">
                            <div>
                                <h2 className="text-lg font-semibold">
                                    Clone Entire Curriculum Structure
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Source: {cloning.name} ({cloning.code}).
                                    Program Template remains the same. The new
                                    Curriculum is always created as Draft.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => setCloning(null)}
                            >
                                <X className="size-4" />
                            </Button>
                        </div>

                        <form onSubmit={submitClone} className="space-y-4 p-5">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Academic Session *
                                    </label>
                                    <Select
                                        value={cloneForm.data.academic_session_id}
                                        onValueChange={(value) =>
                                            cloneForm.setData(
                                                'academic_session_id',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select Academic Session" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {academicSessions.map((session) => (
                                                <SelectItem
                                                    key={session.id}
                                                    value={String(session.id)}
                                                >
                                                    {session.name} ({session.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {cloneForm.errors.academic_session_id && (
                                        <p className="text-xs text-destructive">
                                            {cloneForm.errors.academic_session_id}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Version *
                                    </label>
                                    <Input
                                        value={cloneForm.data.version}
                                        onChange={(event) =>
                                            cloneForm.setData(
                                                'version',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {cloneForm.errors.version && (
                                        <p className="text-xs text-destructive">
                                            {cloneForm.errors.version}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Code *
                                    </label>
                                    <Input
                                        value={cloneForm.data.code}
                                        onChange={(event) =>
                                            cloneForm.setData(
                                                'code',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {cloneForm.errors.code && (
                                        <p className="text-xs text-destructive">
                                            {cloneForm.errors.code}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Name *
                                    </label>
                                    <Input
                                        value={cloneForm.data.name}
                                        onChange={(event) =>
                                            cloneForm.setData(
                                                'name',
                                                event.target.value,
                                            )
                                        }
                                    />
                                    {cloneForm.errors.name && (
                                        <p className="text-xs text-destructive">
                                            {cloneForm.errors.name}
                                        </p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Effective From
                                    </label>
                                    <DatePicker
                                        id="clone_effective_from"
                                        name="clone_effective_from"
                                        defaultValue={
                                            cloneForm.data.effective_from
                                        }
                                    />
                                    <Input
                                        type="date"
                                        value={cloneForm.data.effective_from}
                                        onChange={(event) =>
                                            cloneForm.setData(
                                                'effective_from',
                                                event.target.value,
                                            )
                                        }
                                        className="hidden"
                                    />
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Effective To
                                    </label>
                                    <Input
                                        type="date"
                                        value={cloneForm.data.effective_to}
                                        onChange={(event) =>
                                            cloneForm.setData(
                                                'effective_to',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Description
                                </label>
                                <textarea
                                    value={cloneForm.data.description}
                                    onChange={(event) =>
                                        cloneForm.setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>

                            {cloneForm.errors.source && (
                                <p className="rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm text-destructive">
                                    {cloneForm.errors.source}
                                </p>
                            )}

                            <div className="flex justify-end gap-2 border-t pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setCloning(null)}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="submit"
                                    disabled={cloneForm.processing}
                                >
                                    <Copy className="size-4" />
                                    {cloneForm.processing
                                        ? 'Cloning…'
                                        : 'Clone Entire Structure'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {showForm && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <div className="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl border border-border bg-card shadow-xl">
                        <div className="sticky top-0 flex items-center justify-between border-b border-border bg-card px-5 py-4"><div><h2 className="text-lg font-semibold">{title}</h2><p className="text-sm text-muted-foreground">Header only. Structure mapping is the next curriculum milestone.</p></div><button onClick={() => setShowForm(false)} className="rounded-md p-2 hover:bg-accent"><X className="size-4" /></button></div>
                        <form onSubmit={submit} className="grid gap-4 p-5 md:grid-cols-2">
                            <Field label="Program Template" error={form.errors.program_template_id}><select value={form.data.program_template_id} onChange={e => form.setData('program_template_id', e.target.value)} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none transition-shadow focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"><option value="">Select Program Template</option>{programTemplates.map(o => <option key={o.id} value={o.id}>{o.name} ({o.code})</option>)}</select></Field>
                            <Field label="Academic Session" error={form.errors.academic_session_id}><select value={form.data.academic_session_id} onChange={e => form.setData('academic_session_id', e.target.value)} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none transition-shadow focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"><option value="">Select Academic Session</option>{academicSessions.map(o => <option key={o.id} value={o.id}>{o.name} ({o.code})</option>)}</select></Field>
                            <Field label="Curriculum Code" error={form.errors.code}><input value={form.data.code} onChange={e => form.setData('code', e.target.value.toUpperCase())} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none transition-shadow focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50" maxLength={50} /></Field>
                            <Field label="Curriculum Name" error={form.errors.name}><input value={form.data.name} onChange={e => form.setData('name', e.target.value)} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none transition-shadow focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50" maxLength={160} /></Field>
                            <Field label="Version" error={form.errors.version}><input value={form.data.version} onChange={e => form.setData('version', e.target.value)} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none transition-shadow focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50" maxLength={30} /></Field>
                            <Field label="Lifecycle Status" error={form.errors.lifecycle_status}><select value={form.data.lifecycle_status} onChange={e => form.setData('lifecycle_status', e.target.value)} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none transition-shadow focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"><option value="DRAFT">Draft</option><option value="ACTIVE">Active</option><option value="RETIRED">Retired</option></select></Field>
                            <Field label="Effective From" error={form.errors.effective_from}>
                                <DatePicker
                                    id={`curriculum-effective-from-${editing?.id ?? 'new'}`}
                                    name="effective_from"
                                    value={form.data.effective_from}
                                    onValueChange={(value) => form.setData('effective_from', value)}
                                    invalid={Boolean(form.errors.effective_from)}
                                />
                                <span className="block text-xs text-muted-foreground">
                                    Optional. Exact date from which this curriculum version becomes applicable.
                                </span>
                            </Field>
                            <Field label="Effective To" error={form.errors.effective_to}>
                                <DatePicker
                                    id={`curriculum-effective-to-${editing?.id ?? 'new'}`}
                                    name="effective_to"
                                    value={form.data.effective_to}
                                    onValueChange={(value) => form.setData('effective_to', value)}
                                    min={form.data.effective_from || '1800-01-01'}
                                    invalid={Boolean(form.errors.effective_to)}
                                />
                                <span className="block text-xs text-muted-foreground">
                                    Optional. Leave blank when no end or replacement date is defined.
                                </span>
                            </Field>
                            <div className="md:col-span-2"><Field label="Description" error={form.errors.description}><textarea value={form.data.description} onChange={e => form.setData('description', e.target.value)} rows={3} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground outline-none transition-shadow focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 h-auto py-2" /></Field></div>
                            <div className="flex justify-end gap-2 border-t border-border pt-4 md:col-span-2"><button type="button" onClick={() => setShowForm(false)} className="h-10 rounded-md border border-input bg-background px-4 text-sm font-medium hover:bg-accent">Cancel</button><button disabled={form.processing} className="h-10 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60">{form.processing ? 'Saving…' : editing ? 'Update Curriculum' : 'Create Curriculum'}</button></div>
                        </form>
                    </div>
                </div>
            )}
        </>
    );
}

function Field({ label, error, children }: { label: string; error?: string; children: React.ReactNode }) {
    return <label className="space-y-1.5"><span className="text-sm font-medium">{label}</span>{children}{error && <span className="block text-xs text-destructive">{error}</span>}</label>;
}
