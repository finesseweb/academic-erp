import { Head, router, useForm } from '@inertiajs/react';
import {
    Check,
    Clock3,
    RotateCcw,
    XCircle,
} from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Pending = {
    id: number;
    submitted_at: string;
    curriculum_id: number;
    curriculum_name: string;
    curriculum_code: string;
    curriculum_version: string;
    workflow_name: string;
    stage_name: string;
    approver_role_name: string;
};

type StageHistory = {
    sequence_no: number;
    name: string;
    status: string;
    remarks: string | null;
    decided_at: string | null;
    approver_role_name: string;
    decided_by_name: string | null;
};

type History = {
    id: number;
    status: string;
    submitted_at: string;
    completed_at: string | null;
    curriculum_id: number;
    curriculum_name: string;
    curriculum_code: string;
    curriculum_version: string;
    workflow_name: string;
    submitted_by_name: string | null;
    stages: StageHistory[];
};

type Props = {
    pending: Pending[];
    history: History[];
    permissions: { decide: boolean };
};

export default function AcademicApprovalInbox({
    pending,
    history,
    permissions,
}: Props) {
    const [decisionRequest, setDecisionRequest] =
        useState<Pending | null>(null);

    const decisionForm = useForm({
        decision: 'APPROVE',
        remarks: '',
    });

    const openDecision = (
        request: Pending,
        decision: 'APPROVE' | 'REJECT' | 'RETURN',
    ) => {
        setDecisionRequest(request);
        decisionForm.clearErrors();
        decisionForm.setData({
            decision,
            remarks: '',
        });
    };

    const submitDecision = (event: FormEvent) => {
        event.preventDefault();

        if (!decisionRequest) {
            return;
        }

        decisionForm.post(
            `/admin/academic-approval/requests/${decisionRequest.id}/decision`,
            {
                preserveScroll: true,
                onSuccess: () => setDecisionRequest(null),
            },
        );
    };

    return (
        <>
            <Head title="Academic Approval" />

            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Academic Approval
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Review Curriculum requests assigned to your
                        configured approval roles.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Pending Approvals</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        {pending.length === 0 ? (
                            <div className="p-8 text-center text-sm text-muted-foreground">
                                No approval request is currently waiting
                                for your role.
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b bg-muted/50 text-left">
                                        <tr>
                                            <th className="px-4 py-3">
                                                Curriculum
                                            </th>
                                            <th className="px-4 py-3">
                                                Workflow
                                            </th>
                                            <th className="px-4 py-3">
                                                Current Level
                                            </th>
                                            <th className="px-4 py-3 text-right">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {pending.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-4">
                                                    <div className="font-medium">
                                                        {
                                                            item.curriculum_name
                                                        }
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {
                                                            item.curriculum_code
                                                        }{' '}
                                                        · V
                                                        {
                                                            item.curriculum_version
                                                        }
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4">
                                                    {item.workflow_name}
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div>
                                                        {item.stage_name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {
                                                            item.approver_role_name
                                                        }
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4">
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                router.get(
                                                                    `/admin/curricula/${item.curriculum_id}/structure/terms`,
                                                                )
                                                            }
                                                        >
                                                            Review
                                                        </Button>

                                                        {permissions.decide && (
                                                            <>
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    onClick={() =>
                                                                        openDecision(
                                                                            item,
                                                                            'APPROVE',
                                                                        )
                                                                    }
                                                                >
                                                                    <Check className="size-4" />
                                                                    Approve
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    onClick={() =>
                                                                        openDecision(
                                                                            item,
                                                                            'RETURN',
                                                                        )
                                                                    }
                                                                >
                                                                    <RotateCcw className="size-4" />
                                                                    Return
                                                                </Button>
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    variant="ghost"
                                                                    className="text-destructive hover:text-destructive"
                                                                    onClick={() =>
                                                                        openDecision(
                                                                            item,
                                                                            'REJECT',
                                                                        )
                                                                    }
                                                                >
                                                                    <XCircle className="size-4" />
                                                                    Reject
                                                                </Button>
                                                            </>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Approval History</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {history.length === 0 ? (
                            <div className="py-6 text-center text-sm text-muted-foreground">
                                No approval history yet.
                            </div>
                        ) : (
                            history.map((item) => (
                                <div
                                    key={item.id}
                                    className="rounded-lg border p-4"
                                >
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <div className="font-medium">
                                                {item.curriculum_name}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {item.curriculum_code} · V
                                                {item.curriculum_version} ·{' '}
                                                {item.workflow_name}
                                            </div>
                                        </div>
                                        <span className="rounded-full bg-muted px-2 py-1 text-xs">
                                            {item.status}
                                        </span>
                                    </div>

                                    <div className="mt-4 space-y-2">
                                        {item.stages.map((stage) => (
                                            <div
                                                key={stage.sequence_no}
                                                className="flex gap-3 rounded-md bg-muted/30 p-3 text-sm"
                                            >
                                                <span className="grid size-6 shrink-0 place-items-center rounded-full border text-xs">
                                                    {stage.sequence_no}
                                                </span>
                                                <div>
                                                    <div className="font-medium">
                                                        {stage.name}{' '}
                                                        <span className="font-normal text-muted-foreground">
                                                            (
                                                            {
                                                                stage.approver_role_name
                                                            }
                                                            )
                                                        </span>
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {stage.status}
                                                        {stage.decided_by_name
                                                            ? ` · ${stage.decided_by_name}`
                                                            : ''}
                                                    </div>
                                                    {stage.remarks && (
                                                        <div className="mt-1 text-sm">
                                                            {
                                                                stage.remarks
                                                            }
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>

            {decisionRequest && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <Card className="w-full max-w-lg">
                        <CardHeader>
                            <CardTitle>
                                {decisionForm.data.decision ===
                                'APPROVE'
                                    ? 'Approve Curriculum'
                                    : decisionForm.data.decision ===
                                        'RETURN'
                                      ? 'Return for Correction'
                                      : 'Reject Curriculum'}
                            </CardTitle>
                        </CardHeader>

                        <CardContent>
                            <form
                                onSubmit={submitDecision}
                                className="space-y-4"
                            >
                                <div className="rounded-lg border bg-muted/30 p-3 text-sm">
                                    {decisionRequest.curriculum_name} ·{' '}
                                    {decisionRequest.stage_name}
                                </div>

                                <div className="space-y-2">
                                    <label className="text-sm font-medium">
                                        Remarks
                                    </label>
                                    <textarea
                                        value={
                                            decisionForm.data.remarks
                                        }
                                        onChange={(event) =>
                                            decisionForm.setData(
                                                'remarks',
                                                event.target.value,
                                            )
                                        }
                                        className="min-h-28 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                        placeholder={
                                            decisionForm.data.decision ===
                                            'APPROVE'
                                                ? 'Optional approval remarks'
                                                : 'Explain the correction or reason'
                                        }
                                    />
                                    {decisionForm.errors.remarks && (
                                        <p className="text-xs text-destructive">
                                            {
                                                decisionForm.errors
                                                    .remarks
                                            }
                                        </p>
                                    )}
                                </div>

                                <div className="flex justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            setDecisionRequest(null)
                                        }
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={
                                            decisionForm.processing
                                        }
                                        variant={
                                            decisionForm.data
                                                .decision === 'REJECT'
                                                ? 'destructive'
                                                : 'default'
                                        }
                                    >
                                        {decisionForm.processing
                                            ? 'Saving…'
                                            : decisionForm.data
                                                    .decision ===
                                                'APPROVE'
                                              ? 'Approve'
                                              : decisionForm.data
                                                      .decision ===
                                                  'RETURN'
                                                ? 'Return'
                                                : 'Reject'}
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
