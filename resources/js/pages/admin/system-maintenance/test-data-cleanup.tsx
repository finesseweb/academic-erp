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

type AcademicPolicy = {
    id: number;
    code: string;
    name: string;
    version: string;
    lifecycle_status: string;
    approval_status: string;
    is_current_version: boolean;
    scope_type: string;
    chain_versions: number;
    approval_requests: number;
    approval_request_stages: number;
    credit_completion_rules: number;
    credit_category_requirements: number;
    attendance_rules: number;
    assessment_exam_rules: number;
    grading_rules: number;
    grade_bands: number;
    progression_rule_sets: number;
    progression_rule_terms: number;
    downstream_references: Ref[];
    can_cleanup: boolean;
    can_reset_approval: boolean;
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
    email?: string | null;
    college_name?: string | null;
    program_offering_ids?: number[];
    program_offerings?: { id: number; code?: string | null; name: string }[];
};

type FullReset = {
    confirmation_code: string;
    counts: Record<string, number>;
    preserved: string[];
};

type AccessReset = {
    confirmation_code: string;
    users_total: number;
    users_cleanable: number;
    users_protected_or_blocked: number;
    roles_total: number;
    roles_cleanable: number;
    roles_protected_or_blocked: number;
    preserved: string[];
};

type LegacyUnlinkedRegularApplications = {
    confirmation_code: string;
    total: number;
    cleanable: number;
    blocked: number;
};

type Props = {
    enabled: boolean;
    environment: string;
    curricula: Curriculum[];
    academicPolicies: AcademicPolicy[];
    fullReset: FullReset;
    accessReset: AccessReset;
    legacyUnlinkedRegularApplications: LegacyUnlinkedRegularApplications;
    entities: {
        users: Entity[];
        roles: Entity[];
        applicants: Entity[];
        college_admission_form_templates: Entity[];
        college_application_fee_rules: Entity[];
        fee_demands: Entity[];
        fee_structures: Entity[];
        fee_heads: Entity[];
        fee_categories: Entity[];
        college_admission_document_verifications: Entity[];
        college_admission_seat_allocations: Entity[];
        admissions: Entity[];
        college_admission_scores: Entity[];
        college_admission_merit_rosters: Entity[];
        college_admission_applications: Entity[];
        college_admission_selection_rules: Entity[];
        college_program_reservation_plans: Entity[];
        reservation_categories: Entity[];
        college_program_intakes: Entity[];
        sections: Entity[];
        college_academic_calendars: Entity[];
        batches: Entity[];
        college_program_offerings: Entity[];
        academic_calendars: Entity[];
        approval_workflows: Entity[];
        courses: Entity[];
        course_categories: Entity[];
        course_types: Entity[];
        program_templates: Entity[];
        disciplines: Entity[];
        degrees: Entity[];
        degree_levels: Entity[];
        academic_sessions: Entity[];
    };
};

type TabKey =
    | 'users'
    | 'roles'
    | 'applicants'
    | 'curriculum'
    | 'academic_policies'
    | 'college_admission_form_templates'
    | 'college_application_fee_rules'
    | 'fee_demands'
    | 'fee_structures'
    | 'fee_heads'
    | 'fee_categories'
    | 'college_admission_document_verifications'
    | 'college_admission_seat_allocations'
    | 'admissions'
    | 'college_admission_scores'
    | 'college_admission_merit_rosters'
    | 'college_admission_applications'
    | 'college_admission_selection_rules'
    | 'college_program_reservation_plans'
    | 'reservation_categories'
    | 'college_program_intakes'
    | 'sections'
    | 'college_academic_calendars'
    | 'batches'
    | 'college_program_offerings'
    | 'academic_calendars'
    | 'approval_workflows'
    | 'courses'
    | 'course_categories'
    | 'course_types'
    | 'program_templates'
    | 'disciplines'
    | 'degrees'
    | 'degree_levels'
    | 'academic_sessions';

type ActionTarget = {
    mode:
        | 'reset_approval'
        | 'cleanup_curriculum'
        | 'reset_policy_approval'
        | 'cleanup_academic_policy'
        | 'cleanup_master'
        | 'deactivate_admission_form_template'
        | 'cleanup_legacy_unlinked_regular'
        | 'full_access_reset'
        | 'full_reset';
    type?: Exclude<TabKey, 'curriculum' | 'academic_policies'>;
    id: number;
    code: string;
    name: string;
};

const tabs: { key: TabKey; label: string }[] = [
    { key: 'users', label: 'Users' },
    { key: 'roles', label: 'Roles' },
    { key: 'applicants', label: 'Applicants' },
    { key: 'college_admission_form_templates', label: 'Admission Form Templates' },
    { key: 'college_application_fee_rules', label: 'Application Fee Rules' },
    { key: 'fee_demands', label: 'Fee Demands' },
    { key: 'fee_structures', label: 'Fee Structures' },
    { key: 'fee_heads', label: 'Fee Heads' },
    { key: 'fee_categories', label: 'Fee Categories' },
    { key: 'college_admission_document_verifications', label: 'Document Verification' },
    { key: 'college_admission_seat_allocations', label: 'Seat Allocation / Consumption' },
    { key: 'admissions', label: 'Admission Confirmation / Approval' },
    { key: 'college_admission_scores', label: 'Score Capture / Normalization' },
    { key: 'college_admission_merit_rosters', label: 'Generated Merit / Roster' },
    { key: 'college_admission_applications', label: 'Admission Applications' },
    { key: 'college_admission_selection_rules', label: 'Merit / Roster / Selection Rules' },
    { key: 'college_program_reservation_plans', label: 'Reservation / Seat Distribution' },
    { key: 'college_program_intakes', label: 'Intake / Seat Capacity' },
    { key: 'reservation_categories', label: 'Reservation Categories' },
    { key: 'sections', label: 'Sections' },
    { key: 'college_academic_calendars', label: 'College Academic Calendars' },
    { key: 'batches', label: 'Batches' },
    { key: 'college_program_offerings', label: 'Program Offerings' },
    { key: 'academic_calendars', label: 'Academic Calendars' },
    { key: 'academic_policies', label: 'Academic Policies' },
    { key: 'curriculum', label: 'Curriculum' },
    { key: 'approval_workflows', label: 'Approval Workflows' },
    { key: 'courses', label: 'Courses' },
    { key: 'program_templates', label: 'Program Templates' },
    { key: 'disciplines', label: 'Disciplines' },
    { key: 'course_categories', label: 'Course Categories' },
    { key: 'course_types', label: 'Course Types' },
    { key: 'degrees', label: 'Degrees' },
    { key: 'degree_levels', label: 'Degree Levels' },
    { key: 'academic_sessions', label: 'Academic Sessions' },
];

const tabGroups: { label: string; keys: TabKey[] }[] = [
    {
        label: 'Access & Security',
        keys: ['users', 'roles'],
    },
    {
        label: 'Admission Processing',
        keys: [
            'applicants',
            'college_admission_applications',
            'college_admission_scores',
            'college_admission_merit_rosters',
            'college_admission_selection_rules',
            'college_admission_document_verifications',
            'college_admission_seat_allocations',
            'admissions',
        ],
    },
    {
        label: 'Admission Setup',
        keys: [
            'college_admission_form_templates',
            'college_application_fee_rules',
            'college_program_offerings',
            'college_program_intakes',
            'reservation_categories',
            'college_program_reservation_plans',
        ],
    },
    {
        label: 'Fee Management',
        keys: [
            'fee_demands',
            'fee_structures',
            'fee_heads',
            'fee_categories',
        ],
    },
    {
        label: 'Academic Setup',
        keys: [
            'academic_sessions',
            'degree_levels',
            'degrees',
            'disciplines',
            'program_templates',
            'course_types',
            'course_categories',
            'courses',
            'approval_workflows',
            'curriculum',
            'academic_policies',
            'academic_calendars',
            'college_academic_calendars',
            'batches',
            'sections',
        ],
    },
];

const tabLabel = (key: TabKey) =>
    tabs.find((item) => item.key === key)?.label ?? key;

export default function TestDataCleanup({
    enabled,
    environment,
    curricula,
    academicPolicies,
    entities,
    fullReset,
    accessReset,
    legacyUnlinkedRegularApplications,
}: Props) {
    const [tab, setTab] = useState<TabKey>('college_admission_applications');
    const [applicantOfferingFilter, setApplicantOfferingFilter] = useState('all');
    const [target, setTarget] = useState<ActionTarget | null>(null);

    const form = useForm({
        confirmation_code: '',
    });

    const applicantOfferingOptions = useMemo(() => {
        const offerings = new Map<number, string>();

        entities.applicants.forEach((applicant) => {
            applicant.program_offerings?.forEach((offering) => {
                offerings.set(
                    offering.id,
                    `${offering.name}${offering.code ? ` · ${offering.code}` : ''}`,
                );
            });
        });

        return Array.from(offerings.entries())
            .map(([id, label]) => ({ id, label }))
            .sort((a, b) => a.label.localeCompare(b.label));
    }, [entities.applicants]);

    const currentEntities = useMemo(() => {
        if (tab === 'curriculum' || tab === 'academic_policies') {
            return [];
        }

        const rows = entities[tab];

        if (tab !== 'applicants' || applicantOfferingFilter === 'all') {
            return rows;
        }

        if (applicantOfferingFilter === 'unlinked') {
            return rows.filter(
                (item) => !item.program_offering_ids?.length,
            );
        }

        const offeringId = Number(applicantOfferingFilter);
        return rows.filter((item) =>
            item.program_offering_ids?.includes(offeringId),
        );
    }, [tab, entities, applicantOfferingFilter]);

    const openAction = (value: ActionTarget) => {
        setTarget(value);
        form.clearErrors();
        form.setData('confirmation_code', '');
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!target) return;

        if (target.mode === 'full_reset') {
            form.delete(
                '/admin/system-maintenance/test-data-cleanup/full-reset',
                {
                    preserveScroll: true,
                    onSuccess: () => setTarget(null),
                },
            );
            return;
        }

        if (target.mode === 'full_access_reset') {
            form.delete(
                '/admin/system-maintenance/test-data-cleanup/access-reset',
                {
                    preserveScroll: true,
                    onSuccess: () => setTarget(null),
                },
            );
            return;
        }

        if (target.mode === 'cleanup_legacy_unlinked_regular') {
            form.delete(
                '/admin/system-maintenance/test-data-cleanup/legacy-unlinked-regular-applications',
                {
                    preserveScroll: true,
                    onSuccess: () => setTarget(null),
                },
            );
            return;
        }

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

        if (target.mode === 'reset_policy_approval') {
            form.post(
                `/admin/system-maintenance/test-data-cleanup/academic-policies/${target.id}/reset-approval`,
                {
                    preserveScroll: true,
                    onSuccess: () => setTarget(null),
                },
            );
            return;
        }

        if (target.mode === 'cleanup_academic_policy') {
            form.delete(
                `/admin/system-maintenance/test-data-cleanup/academic-policies/${target.id}`,
                {
                    preserveScroll: true,
                    onSuccess: () => setTarget(null),
                },
            );
            return;
        }

        if (target.mode === 'deactivate_admission_form_template') {
            form.post(
                `/admin/system-maintenance/test-data-cleanup/admission-form-templates/${target.id}/deactivate`,
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

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2">
                            <ShieldAlert className="size-5" />
                            Quick Test Resets
                        </CardTitle>
                        <p className="text-sm text-muted-foreground">
                            High-level reset actions stay compact here. Open details only when you need the table-wise breakdown.
                        </p>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3 lg:grid-cols-2">
                            <div className="rounded-lg border border-amber-500/40 p-4">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0">
                                        <div className="font-medium">Full User & Role Test Reset</div>
                                        <div className="mt-1 text-xs text-muted-foreground">
                                            Cleans test staff users and custom roles while preserving protected identities and referenced records.
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        className="shrink-0"
                                        disabled={!enabled || (accessReset.users_cleanable === 0 && accessReset.roles_cleanable === 0)}
                                        onClick={() =>
                                            openAction({
                                                mode: 'full_access_reset',
                                                id: 0,
                                                code: accessReset.confirmation_code,
                                                name: `Clean ${accessReset.users_cleanable} user(s) + ${accessReset.roles_cleanable} custom role(s)`,
                                            })
                                        }
                                    >
                                        <Eraser className="size-4" />
                                        Clean Users & Roles
                                    </Button>
                                </div>

                                <div className="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm sm:grid-cols-4">
                                    <div><span className="text-muted-foreground">Users:</span> <span className="font-semibold">{accessReset.users_total}</span></div>
                                    <div><span className="text-muted-foreground">Cleanable:</span> <span className="font-semibold">{accessReset.users_cleanable}</span></div>
                                    <div><span className="text-muted-foreground">Roles:</span> <span className="font-semibold">{accessReset.roles_total}</span></div>
                                    <div><span className="text-muted-foreground">Cleanable:</span> <span className="font-semibold">{accessReset.roles_cleanable}</span></div>
                                </div>

                                <details className="mt-3 rounded-md bg-muted/30 px-3 py-2 text-xs">
                                    <summary className="cursor-pointer font-medium">View preservation details</summary>
                                    <div className="mt-2 text-muted-foreground">
                                        Users preserved/blocked: {accessReset.users_protected_or_blocked} · Roles preserved/blocked: {accessReset.roles_protected_or_blocked}
                                    </div>
                                    <div className="mt-1 text-muted-foreground">
                                        {accessReset.preserved.join(' · ')}
                                    </div>
                                </details>
                            </div>

                            <div className="rounded-lg border border-destructive/40 p-4">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0">
                                        <div className="font-medium text-destructive">Full Academic Test Reset</div>
                                        <div className="mt-1 text-xs text-muted-foreground">
                                            Deletes implemented academic/test data in child-first dependency order without disabling foreign keys.
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        className="shrink-0"
                                        disabled={!enabled}
                                        onClick={() =>
                                            openAction({
                                                mode: 'full_reset',
                                                id: 0,
                                                code: fullReset.confirmation_code,
                                                name: 'Full Academic Test Data Reset',
                                            })
                                        }
                                    >
                                        <Eraser className="size-4" />
                                        Reset Academic Data
                                    </Button>
                                </div>

                                <div className="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                                    <div>
                                        <span className="text-muted-foreground">Records:</span>{' '}
                                        <span className="font-semibold">
                                            {Object.values(fullReset.counts).reduce((sum, count) => sum + count, 0)}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Tables with data:</span>{' '}
                                        <span className="font-semibold">
                                            {Object.values(fullReset.counts).filter((count) => count > 0).length}
                                        </span>
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Tracked tables:</span>{' '}
                                        <span className="font-semibold">{Object.keys(fullReset.counts).length}</span>
                                    </div>
                                </div>

                                <details className="mt-3 rounded-md bg-muted/30 px-3 py-2 text-xs">
                                    <summary className="cursor-pointer font-medium">
                                        View table-wise counts ({Object.keys(fullReset.counts).length})
                                    </summary>
                                    <div className="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                        {Object.entries(fullReset.counts).map(([name, count]) => (
                                            <div key={name} className="flex items-center justify-between gap-3 rounded-md border bg-background px-3 py-2">
                                                <span className="min-w-0 truncate text-muted-foreground" title={name.replaceAll('_', ' ')}>
                                                    {name.replaceAll('_', ' ')}
                                                </span>
                                                <span className="shrink-0 font-semibold text-foreground">{count}</span>
                                            </div>
                                        ))}
                                    </div>
                                    <div className="mt-3 border-t pt-2 text-muted-foreground">
                                        <span className="font-medium text-foreground">Preserved:</span>{' '}
                                        {fullReset.preserved.join(' · ')}
                                    </div>
                                </details>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex flex-col gap-3 rounded-lg border bg-card p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0">
                        <div className="text-sm font-medium">Cleanup section</div>
                        <div className="mt-0.5 truncate text-xs text-muted-foreground">
                            Current: {tabLabel(tab)}
                        </div>
                    </div>

                    <div className="w-full sm:w-auto">
                        <label className="sr-only" htmlFor="cleanup-section">
                            Select cleanup section
                        </label>
                        <select
                            id="cleanup-section"
                            value={tab}
                            onChange={(event) => setTab(event.target.value as TabKey)}
                            className="h-9 w-full rounded-md border border-input bg-background px-3 pr-8 text-sm shadow-xs outline-none transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 sm:min-w-[320px]"
                        >
                            {tabGroups.map((group) => (
                                <optgroup key={group.label} label={group.label}>
                                    {group.keys.map((key) => (
                                        <option key={key} value={key}>
                                            {tabLabel(key)}
                                        </option>
                                    ))}
                                </optgroup>
                            ))}
                        </select>
                    </div>
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
                ) : tab === 'academic_policies' ? (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <BookOpenCheck className="size-5" />
                                Academic Policy Test Data
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Cleanup is version-chain aware. A clean action removes the complete test policy version chain only when no downstream operational reference exists.
                            </p>
                        </CardHeader>

                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b bg-muted/50 text-left">
                                        <tr>
                                            <th className="px-4 py-3">Academic Policy</th>
                                            <th className="px-4 py-3">Status</th>
                                            <th className="px-4 py-3">Dependencies</th>
                                            <th className="px-4 py-3">Safety</th>
                                            <th className="px-4 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {academicPolicies.map((item) => (
                                            <tr key={item.id} className="border-b last:border-0">
                                                <td className="px-4 py-4">
                                                    <div className="font-medium">{item.name}</div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {item.code} · V{item.version} · {item.scope_type.replaceAll('_', ' ')}
                                                    </div>
                                                    <div className="mt-1 text-xs text-muted-foreground">
                                                        Version chain: {item.chain_versions}
                                                        {item.is_current_version ? ' · Current' : ''}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div>{item.lifecycle_status}</div>
                                                    <div className="text-xs text-muted-foreground">{item.approval_status}</div>
                                                </td>
                                                <td className="px-4 py-4 text-xs">
                                                    Approvals {item.approval_requests} · Credit {item.credit_completion_rules + item.credit_category_requirements} · Attendance {item.attendance_rules} · Assessment {item.assessment_exam_rules} · Grading {item.grading_rules + item.grade_bands} · Progression {item.progression_rule_sets}
                                                </td>
                                                <td className="px-4 py-4 text-xs">
                                                    {item.downstream_references.length > 0
                                                        ? 'Blocked: operational references exist'
                                                        : item.chain_versions > 1
                                                          ? 'Ready: complete version chain cleanup'
                                                          : 'Ready for test cleanup'}
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            disabled={!enabled || !item.can_reset_approval}
                                                            onClick={() =>
                                                                openAction({
                                                                    mode: 'reset_policy_approval',
                                                                    id: item.id,
                                                                    code: item.code,
                                                                    name: `${item.name} · V${item.version}`,
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
                                                            disabled={!enabled || !item.can_cleanup}
                                                            onClick={() =>
                                                                openAction({
                                                                    mode: 'cleanup_academic_policy',
                                                                    id: item.id,
                                                                    code: item.code,
                                                                    name: `${item.name} · V${item.version}`,
                                                                })
                                                            }
                                                        >
                                                            <Eraser className="size-4" />
                                                            {item.chain_versions > 1 ? 'Clean Chain' : 'Clean'}
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                        {!academicPolicies.length && (
                                            <tr>
                                                <td colSpan={5} className="px-6 py-14 text-center text-muted-foreground">
                                                    No Academic Policy test data found.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader className="space-y-3">
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
                            {tab === 'applicants' && (
                                <div className="flex flex-col gap-2 rounded-lg border bg-muted/20 p-3 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <div className="text-sm font-medium">Program Offering filter</div>
                                        <div className="text-xs text-muted-foreground">
                                            Filter applicant accounts by the Program Offering linked through their Admission Application. Cleanup remains one applicant at a time.
                                        </div>
                                    </div>
                                    <div className="w-full sm:w-[360px]">
                                        <label className="sr-only" htmlFor="applicant-program-offering">
                                            Filter applicants by Program Offering
                                        </label>
                                        <select
                                            id="applicant-program-offering"
                                            value={applicantOfferingFilter}
                                            onChange={(event) => setApplicantOfferingFilter(event.target.value)}
                                            className="h-9 w-full rounded-md border border-input bg-background px-3 pr-8 text-sm shadow-xs outline-none transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            <option value="all">All Program Offerings</option>
                                            {applicantOfferingOptions.map((offering) => (
                                                <option key={offering.id} value={offering.id}>
                                                    {offering.label}
                                                </option>
                                            ))}
                                            <option value="unlinked">No Program Offering linked yet</option>
                                        </select>
                                    </div>
                                </div>
                            )}
                            {tab === 'college_admission_applications' &&
                                legacyUnlinkedRegularApplications.total > 0 && (
                                    <div className="flex flex-col gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-900 dark:bg-amber-950/30 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <div className="font-medium">Legacy unlinked Regular submissions detected</div>
                                            <div className="text-muted-foreground">
                                                {legacyUnlinkedRegularApplications.total} old PUBLIC + REGULAR + SUBMITTED application(s) have no processing choice link. {legacyUnlinkedRegularApplications.cleanable} can be cleaned now
                                                {legacyUnlinkedRegularApplications.blocked > 0
                                                    ? `; ${legacyUnlinkedRegularApplications.blocked} are protected by downstream references.`
                                                    : '.'}
                                                {' '}Applicant login identities and applicant profiles are preserved.
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="destructive"
                                            disabled={!enabled || legacyUnlinkedRegularApplications.cleanable === 0}
                                            onClick={() =>
                                                openAction({
                                                    mode: 'cleanup_legacy_unlinked_regular',
                                                    id: 0,
                                                    code: legacyUnlinkedRegularApplications.confirmation_code,
                                                    name: `Clean ${legacyUnlinkedRegularApplications.cleanable} legacy unlinked Regular application(s)`,
                                                })
                                            }
                                        >
                                            <Eraser className="size-4" />
                                            Clean Legacy Unlinked
                                        </Button>
                                    </div>
                                )}
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
                                                        {tab === 'applicants' && (
                                                            <div className="mt-1 space-y-1 text-xs text-muted-foreground">
                                                                {item.email && <div>{item.email}</div>}
                                                                <div>
                                                                    Program Offering:{' '}
                                                                    {item.program_offerings?.length
                                                                        ? item.program_offerings
                                                                              .map((offering) =>
                                                                                  `${offering.name}${offering.code ? ` (${offering.code})` : ''}`,
                                                                              )
                                                                              .join(' · ')
                                                                        : 'Not linked yet'}
                                                                </div>
                                                            </div>
                                                        )}
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
                                                            ? tab === 'roles' && item.kind?.startsWith('SYSTEM')
                                                                ? 'Protected system role'
                                                                : tab === 'users'
                                                                  ? 'Protected or operational references exist'
                                                                  : tab === 'applicants'
                                                                    ? 'Protected: already linked to Admission / Student lifecycle'
                                                                    : 'Clean dependencies first'
                                                            : tab === 'applicants'
                                                              ? 'Ready: applicant account + linked test admission data'
                                                              : 'Ready for test cleanup'}
                                                    </td>

                                                    <td className="px-4 py-4">
                                                        <div className="flex flex-wrap justify-end gap-2">
                                                            {tab === 'college_admission_form_templates' &&
                                                                item.status === 'ACTIVE' && (
                                                                    <Button
                                                                        type="button"
                                                                        size="sm"
                                                                        variant="outline"
                                                                        disabled={!enabled}
                                                                        onClick={() =>
                                                                            openAction({
                                                                                mode: 'deactivate_admission_form_template',
                                                                                id: item.id,
                                                                                code: item.code,
                                                                                name: item.name,
                                                                            })
                                                                        }
                                                                    >
                                                                        <RotateCcw className="size-4" />
                                                                        Deactivate for Testing
                                                                    </Button>
                                                                )}
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
                                                                                    'curriculum' | 'academic_policies'
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
                                {target.mode === 'full_reset'
                                    ? 'Full Academic Test Reset'
                                    : target.mode === 'full_access_reset'
                                      ? 'Full User & Role Test Reset'
                                    : target.mode === 'cleanup_legacy_unlinked_regular'
                                      ? 'Clean Legacy Unlinked Applications'
                                    : target.mode === 'reset_approval' ||
                                        target.mode === 'reset_policy_approval'
                                      ? 'Reset Test Approval'
                                      : target.mode === 'deactivate_admission_form_template'
                                        ? 'Deactivate Admission Form for Testing'
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
                                    {target.mode === 'full_reset'
                                        ? 'All currently implemented academic/test records will be permanently deleted in dependency-safe order. University Profile, Colleges, Users, protected Roles, Permissions, access assignments, Audit Logs, migrations, and system tables are preserved.'
                                        : target.mode === 'full_access_reset'
                                          ? 'All cleanable internal University/College staff test users and custom roles will be permanently removed. The current logged-in user, SUPER_ADMIN identities, applicants, system roles, permissions, audit logs and operationally referenced users/roles are preserved.'
                                        : target.mode === 'cleanup_legacy_unlinked_regular'
                                          ? 'Only old PUBLIC + REGULAR + SUBMITTED applications with no eligibility-processing choice link will be removed. Dynamic answers, academic preferences and course choices for those applications are cleaned child-first. Applicant users/login identities, applicant profiles and registration numbers are preserved. Any record with downstream Score, Interview, Merit, Seat, Admission or Student references is skipped.'
                                        : target.mode === 'reset_approval'
                                          ? 'Approval requests/history for this test Curriculum will be removed and the Curriculum will return to DRAFT / NOT_SUBMITTED. Structure is preserved.'
                                        : target.mode === 'reset_policy_approval'
                                          ? 'Approval requests/history for this standalone test Academic Policy will be removed and the Policy will return to DRAFT / NOT_SUBMITTED. Configured policy rules are preserved.'
                                          : target.mode === 'cleanup_academic_policy'
                                            ? 'The complete Academic Policy test version chain and its policy-rule children will be permanently removed. Cleanup is blocked if operational references exist.'
                                            : target.mode === 'deactivate_admission_form_template'
                                              ? 'Testing-only recovery action: the ACTIVE Admission Form Template will return to DRAFT so its setup can be corrected. No template structure or submitted application is deleted. Any enabled public applicant mapping for this template is switched off automatically. Normal Admission Form Setup still does not allow ACTIVE → DRAFT.'
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
                                    {form.errors.academic_policy && (
                                        <p className="text-xs text-destructive">
                                            {form.errors.academic_policy}
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
                                            target.mode === 'reset_approval' ||
                                            target.mode === 'reset_policy_approval' ||
                                            target.mode === 'deactivate_admission_form_template'
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
                                        {target.mode === 'full_reset'
                                            ? 'Reset All Academic Test Data'
                                            : target.mode === 'full_access_reset'
                                              ? 'Clean All Users & Roles'
                                            : target.mode === 'reset_approval' ||
                                                target.mode === 'reset_policy_approval'
                                              ? 'Reset Approval'
                                              : target.mode === 'deactivate_admission_form_template'
                                                ? 'Return Template to Draft'
                                                : target.mode === 'cleanup_academic_policy'
                                                  ? 'Permanently Clean Policy Chain'
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
