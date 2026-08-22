import { Head, useForm } from '@inertiajs/react';
import {
    AlertTriangle,
    BookOpenCheck,
    Database,
    Eraser,
    GraduationCap,
    Layers3,
    RotateCcw,
    ShieldAlert,
} from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Ref = { table: string; column: string; count: number };

type Curriculum = {
    id: number;
    code: string;
    name: string;
    version: string;
    lifecycle_status: string;
    approval_status: string;
    terms: number;
    slots: number;
    course_mappings: number;
    approval_requests: number;
    approval_request_stages: number;
    downstream_references: Ref[];
    can_cleanup: boolean;
    can_reset_approval: boolean;
    program_template_name: string | null;
    academic_session_name: string | null;
};

type Entity = {
    id: number;
    code: string;
    name: string;
    status?: string | null;
    kind?: string | null;
    dependencies: Record<string, number>;
    blocked: boolean;
    blocking_references: Ref[];
};

type Props = {
    enabled: boolean;
    environment: string;
    curricula: Curriculum[];
    entities: {
        courses: Entity[];
        course_categories: Entity[];
        course_types: Entity[];
        program_templates: Entity[];
        disciplines: Entity[];
        academic_sessions: Entity[];
    };
};

type TabKey =
    | 'curriculum'
    | 'courses'
    | 'course_categories'
    | 'course_types'
    | 'program_templates'
    | 'disciplines'
    | 'academic_sessions';

type ActionTarget = {
    mode: 'reset_approval' | 'cleanup_curriculum' | 'cleanup_master';
    type?: Exclude<TabKey, 'curriculum'>;
    id: number;
    code: string;
    name: string;
};

const tabs: { key: TabKey; label: string }[] = [
    { key: 'curriculum', label: 'Curriculum' },
    { key: 'courses', label: 'Courses' },
    { key: 'course_categories', label: 'Course Categories' },
    { key: 'course_types', label: 'Course Types' },
    { key: 'program_templates', label: 'Program Templates' },
    { key: 'disciplines', label: 'Disciplines' },
    { key: 'academic_sessions', label: 'Academic Sessions' },
];

export default function TestDataCleanup({
    enabled,
    environment,
    curricula,
    entities,
}: Props) {
    const [tab, setTab] = useState<TabKey>('curriculum');
    const [target, setTarget] = useState<ActionTarget | null>(null);

    const form = useForm({
        confirmation_code: '',
    });

    const currentEntities = useMemo(
        () => (tab === 'curriculum' ? [] : entities[tab]),
        [tab, entities],
    );

    const openAction = (value: ActionTarget) => {
        setTarget(value);
        form.clearErrors();
        form.setData('confirmation_code', '');
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!target) return;

        if (target.mode === 'reset_approval') {
            form.post(
                `/admin/system-maintenance/test-data-cleanup/curricula/${target.id}/reset-approval`,
                {
                    preserveScroll: true,
                    onSuccess: () => setTarget(null),
                },
            );
            return;
        }

        if (target.mode === 'cleanup_curriculum') {
            form.delete(
                `/admin/system-maintenance/test-data-cleanup/curricula/${target.id}`,
                {
                    preserveScroll: true,
                    onSuccess: () => setTarget(null),
                },
            );
            return;
        }

        form.delete(
            `/admin/system-maintenance/test-data-cleanup/${target.type}/${target.id}`,
            {
                preserveScroll: true,
                onSuccess: () => setTarget(null),
            },
        );
    };

    return (
        <>
            <Head title="Test Data Cleanup" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <p className="flex items-center gap-2 text-sm font-medium text-destructive">
                        <ShieldAlert className="size-4" />
                        Sensitive maintenance tool
                    </p>
                    <h1 className="mt-1 text-2xl font-semibold">
                        Test Data Cleanup Center
                    </h1>
                    <p className="mt-1 max-w-3xl text-sm text-muted-foreground">
                        Menu-wise cleanup for development/testing data.
                        This is dependency-aware ERP cleanup, not a raw
                        database console.
                    </p>
                </div>

                <div className="rounded-lg border bg-muted/30 p-4 text-sm">
                    <div className="flex items-start gap-3">
                        <Database className="mt-0.5 size-5" />
                        <div>
                            <div className="font-medium">
                                Environment: {environment}
                            </div>
                            <div className="text-muted-foreground">
                                {enabled
                                    ? 'Test cleanup execution is enabled.'
                                    : 'Cleanup execution is disabled for this environment.'}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2 rounded-lg border bg-card p-2">
                    {tabs.map((item) => (
                        <Button
                            key={item.key}
                            type="button"
                            size="sm"
                            variant={
                                tab === item.key
                                    ? 'default'
                                    : 'ghost'
                            }
                            onClick={() => setTab(item.key)}
                        >
                            {item.label}
                        </Button>
                    ))}
                </div>

                {tab === 'curriculum' ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BookOpenCheck className="size-5" />
                                Curriculum Test Data
                            </CardTitle>
                        </CardHeader>

                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b bg-muted/50 text-left">
                                        <tr>
                                            <th className="px-4 py-3">
                                                Curriculum
                                            </th>
                                            <th className="px-4 py-3">
                                                Status
                                            </th>
                                            <th className="px-4 py-3">
                                                Dependencies
                                            </th>
                                            <th className="px-4 py-3 text-right">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {curricula.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-4">
                                                    <div className="font-medium">
                                                        {item.name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.code} · V
                                                        {item.version}
                                                    </div>
                                                    <div className="mt-1 text-xs text-muted-foreground">
                                                        {
                                                            item.program_template_name
                                                        }{' '}
                                                        ·{' '}
                                                        {
                                                            item.academic_session_name
                                                        }
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div>
                                                        {
                                                            item.lifecycle_status
                                                        }
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {
                                                            item.approval_status
                                                        }
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4 text-xs">
                                                    Terms {item.terms} ·
                                                    Slots {item.slots} ·
                                                    Mappings{' '}
                                                    {
                                                        item.course_mappings
                                                    }{' '}
                                                    · Approvals{' '}
                                                    {
                                                        item.approval_requests
                                                    }
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            disabled={
                                                                !enabled ||
                                                                !item.can_reset_approval
                                                            }
                                                            onClick={() =>
                                                                openAction({
                                                                    mode:
                                                                        'reset_approval',
                                                                    id:
                                                                        item.id,
                                                                    code:
                                                                        item.code,
                                                                    name:
                                                                        item.name,
                                                                })
                                                            }
                                                        >
                                                            <RotateCcw className="size-4" />
                                                            Reset Approval
                                                        </Button>

                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            className="text-destructive hover:text-destructive"
                                                            disabled={
                                                                !enabled ||
                                                                !item.can_cleanup
                                                            }
                                                            onClick={() =>
                                                                openAction({
                                                                    mode:
                                                                        'cleanup_curriculum',
                                                                    id:
                                                                        item.id,
                                                                    code:
                                                                        item.code,
                                                                    name:
                                                                        item.name,
                                                                })
                                                            }
                                                        >
                                                            <Eraser className="size-4" />
                                                            Clean
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                {tab === 'courses' ? (
                                    <GraduationCap className="size-5" />
                                ) : (
                                    <Layers3 className="size-5" />
                                )}
                                {
                                    tabs.find(
                                        (item) => item.key === tab,
                                    )?.label
                                }
                            </CardTitle>
                        </CardHeader>

                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b bg-muted/50 text-left">
                                        <tr>
                                            <th className="px-4 py-3">
                                                Record
                                            </th>
                                            <th className="px-4 py-3">
                                                Dependencies
                                            </th>
                                            <th className="px-4 py-3">
                                                Safety
                                            </th>
                                            <th className="px-4 py-3 text-right">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {currentEntities.map(
                                            (item) => (
                                                <tr
                                                    key={item.id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="px-4 py-4">
                                                        <div className="font-medium">
                                                            {
                                                                item.name
                                                            }
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {
                                                                item.code
                                                            }
                                                            {item.kind
                                                                ? ` · ${item.kind}`
                                                                : ''}
                                                        </div>
                                                    </td>

                                                    <td className="px-4 py-4">
                                                        <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs">
                                                            {Object.entries(
                                                                item.dependencies,
                                                            ).map(
                                                                ([
                                                                    key,
                                                                    count,
                                                                ]) => (
                                                                    <span
                                                                        key={
                                                                            key
                                                                        }
                                                                    >
                                                                        {key.replaceAll(
                                                                            '_',
                                                                            ' ',
                                                                        )}
                                                                        :{' '}
                                                                        {
                                                                            count
                                                                        }
                                                                    </span>
                                                                ),
                                                            )}
                                                        </div>
                                                    </td>

                                                    <td className="px-4 py-4 text-xs">
                                                        {item.blocked
                                                            ? 'Clean dependencies first'
                                                            : 'Ready for test cleanup'}
                                                    </td>

                                                    <td className="px-4 py-4">
                                                        <div className="flex justify-end">
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                variant="ghost"
                                                                className="text-destructive hover:text-destructive"
                                                                disabled={
                                                                    !enabled ||
                                                                    item.blocked
                                                                }
                                                                onClick={() =>
                                                                    openAction(
                                                                        {
                                                                            mode:
                                                                                'cleanup_master',
                                                                            type:
                                                                                tab as Exclude<
                                                                                    TabKey,
                                                                                    'curriculum'
                                                                                >,
                                                                            id:
                                                                                item.id,
                                                                            code:
                                                                                item.code,
                                                                            name:
                                                                                item.name,
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                <Eraser className="size-4" />
                                                                Clean
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            {target && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <Card className="w-full max-w-lg">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <AlertTriangle className="size-5 text-destructive" />
                                {target.mode === 'reset_approval'
                                    ? 'Reset Test Approval'
                                    : 'Clean Test Data'}
                            </CardTitle>
                        </CardHeader>

                        <CardContent>
                            <form
                                onSubmit={submit}
                                className="space-y-4"
                            >
                                <div className="rounded-lg border bg-muted/30 p-3 text-sm">
                                    <div className="font-medium">
                                        {target.name}
                                    </div>
                                    <div className="text-muted-foreground">
                                        {target.code}
                                    </div>
                                </div>

                                <p className="text-sm text-muted-foreground">
                                    {target.mode === 'reset_approval'
                                        ? 'Approval requests/history for this test Curriculum will be removed and the Curriculum will return to DRAFT / NOT_SUBMITTED. Structure is preserved.'
                                        : 'The selected test record will be permanently removed. Dependency checks are enforced by Laravel.'}
                                </p>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Type exact Code{' '}
                                        <span className="text-destructive">
                                            {target.code}
                                        </span>
                                    </label>
                                    <Input
                                        value={
                                            form.data
                                                .confirmation_code
                                        }
                                        onChange={(event) =>
                                            form.setData(
                                                'confirmation_code',
                                                event.target.value,
                                            )
                                        }
                                        autoComplete="off"
                                    />
                                    {form.errors
                                        .confirmation_code && (
                                        <p className="text-xs text-destructive">
                                            {
                                                form.errors
                                                    .confirmation_code
                                            }
                                        </p>
                                    )}
                                    {form.errors.record && (
                                        <p className="text-xs text-destructive">
                                            {
                                                form.errors.record
                                            }
                                        </p>
                                    )}
                                    {form.errors.curriculum && (
                                        <p className="text-xs text-destructive">
                                            {
                                                form.errors
                                                    .curriculum
                                            }
                                        </p>
                                    )}
                                </div>

                                <div className="flex justify-end gap-2 border-t pt-4">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            setTarget(null)
                                        }
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        variant={
                                            target.mode ===
                                            'reset_approval'
                                                ? 'default'
                                                : 'destructive'
                                        }
                                        disabled={
                                            form.processing ||
                                            form.data
                                                .confirmation_code !==
                                                target.code
                                        }
                                    >
                                        {target.mode ===
                                        'reset_approval'
                                            ? 'Reset Approval'
                                            : 'Permanently Clean'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            )}
        </>
    );
}
