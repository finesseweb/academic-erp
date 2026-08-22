import { router, useForm } from '@inertiajs/react';
import { CheckCircle2, GitBranch, Plus, ShieldCheck } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Role = { id: number; name: string; code: string };
type Stage = {
    id: number;
    sequence_no: number;
    name: string;
    status: 'ACTIVE' | 'INACTIVE';
    approver_role: Role;
};
type Workflow = {
    id: number;
    name: string;
    code: string;
    applies_to: 'CURRICULUM';
    description?: string | null;
    status: 'ACTIVE' | 'INACTIVE';
    stages: Stage[];
};
type Props = {
    workflows: Workflow[];
    roles: Role[];
    permissions: { create: boolean; update: boolean; disable: boolean };
};

export default function ApprovalWorkflows({ workflows, roles, permissions }: Props) {
    const [stageWorkflow, setStageWorkflow] = useState<Workflow | null>(null);
    const workflowForm = useForm({
        name: '',
        code: '',
        applies_to: 'CURRICULUM',
        description: '',
    });
    const stageForm = useForm({
        sequence_no: '1',
        name: '',
        approver_role_id: '',
        remarks_required_on_reject: true,
        remarks_required_on_return: true,
    });

    const submitWorkflow = (e: FormEvent) => {
        e.preventDefault();
        workflowForm.post('/admin/academic-approval/workflows', {
            preserveScroll: true,
            onSuccess: () => workflowForm.reset(),
        });
    };

    const openStage = (workflow: Workflow) => {
        setStageWorkflow(workflow);
        stageForm.clearErrors();
        stageForm.setData({
            sequence_no: String((workflow.stages.at(-1)?.sequence_no ?? 0) + 1),
            name: '',
            approver_role_id: '',
            remarks_required_on_reject: true,
            remarks_required_on_return: true,
        });
    };

    const submitStage = (e: FormEvent) => {
        e.preventDefault();
        if (!stageWorkflow) return;
        stageForm.post(`/admin/academic-approval/workflows/${stageWorkflow.id}/stages`, {
            preserveScroll: true,
            onSuccess: () => setStageWorkflow(null),
        });
    };

    return (
        <div className="space-y-6 p-6">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">Academic Approval</h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    Configure reusable approval workflows. Approvers come from Roles & Permissions; no officer name is hard-coded.
                </p>
            </div>

            {permissions.create && (
                <Card>
                    <CardHeader><CardTitle className="flex items-center gap-2"><GitBranch className="size-5" />New Workflow</CardTitle></CardHeader>
                    <CardContent>
                        <form onSubmit={submitWorkflow} className="grid gap-4 md:grid-cols-4">
                            <div><label className="text-sm font-medium">Workflow Name *</label><Input value={workflowForm.data.name} onChange={e => workflowForm.setData('name', e.target.value)} placeholder="Curriculum Approval" />{workflowForm.errors.name && <p className="text-xs text-destructive">{workflowForm.errors.name}</p>}</div>
                            <div><label className="text-sm font-medium">Code *</label><Input value={workflowForm.data.code} onChange={e => workflowForm.setData('code', e.target.value)} placeholder="CURRICULUM_APPROVAL" />{workflowForm.errors.code && <p className="text-xs text-destructive">{workflowForm.errors.code}</p>}</div>
                            <div><label className="text-sm font-medium">Applies To *</label><Select value={workflowForm.data.applies_to} onValueChange={v => workflowForm.setData('applies_to', v)}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="CURRICULUM">Curriculum</SelectItem></SelectContent></Select></div>
                            <div className="flex items-end"><Button type="submit" disabled={workflowForm.processing}><Plus className="size-4" />Create Workflow</Button></div>
                            <div className="md:col-span-4"><label className="text-sm font-medium">Description</label><Input value={workflowForm.data.description} onChange={e => workflowForm.setData('description', e.target.value)} placeholder="Optional workflow purpose" /></div>
                        </form>
                    </CardContent>
                </Card>
            )}

            <Card>
                <CardHeader><CardTitle>Workflow Setup</CardTitle></CardHeader>
                <CardContent className="p-0">
                    {workflows.length === 0 ? (
                        <div className="p-8 text-center text-sm text-muted-foreground">No approval workflow configured yet.</div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/50 text-left"><tr><th className="px-4 py-3">Workflow</th><th className="px-4 py-3">Applies To</th><th className="px-4 py-3">Approval Levels</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr></thead>
                                <tbody>
                                    {workflows.map(workflow => (
                                        <tr key={workflow.id} className="border-b last:border-0 align-top">
                                            <td className="px-4 py-4"><div className="font-medium">{workflow.name}</div><div className="text-xs text-muted-foreground">{workflow.code}</div></td>
                                            <td className="px-4 py-4">Curriculum</td>
                                            <td className="px-4 py-4">
                                                {workflow.stages.length === 0 ? <span className="text-muted-foreground">No levels added</span> :
                                                    <div className="space-y-1">{workflow.stages.map(stage => <div key={stage.id} className="flex items-center gap-2"><span className="grid size-6 place-items-center rounded-full border text-xs">{stage.sequence_no}</span><span>{stage.name}</span><span className="text-xs text-muted-foreground">({stage.approver_role.name})</span></div>)}</div>}
                                            </td>
                                            <td className="px-4 py-4"><span className="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-1 text-xs"><CheckCircle2 className="size-3" />{workflow.status === 'ACTIVE' ? 'Active' : 'Inactive'}</span></td>
                                            <td className="px-4 py-4"><div className="flex justify-end gap-2">
                                                {permissions.update && <Button type="button" size="sm" variant="ghost" onClick={() => openStage(workflow)}><Plus className="size-4" />Add Level</Button>}
                                                {permissions.disable && <Button type="button" size="sm" variant="ghost" onClick={() => router.patch(`/admin/academic-approval/workflows/${workflow.id}/status`, {}, { preserveScroll: true })}>{workflow.status === 'ACTIVE' ? 'Set Inactive' : 'Set Active'}</Button>}
                                            </div></td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </CardContent>
            </Card>

            {stageWorkflow && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-background/70 p-4 backdrop-blur-sm">
                    <Card className="w-full max-w-xl">
                        <CardHeader><CardTitle className="flex items-center gap-2"><ShieldCheck className="size-5" />Add Approval Level</CardTitle><p className="text-sm text-muted-foreground">{stageWorkflow.name}</p></CardHeader>
                        <CardContent>
                            <form onSubmit={submitStage} className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div><label className="text-sm font-medium">Level *</label><Input type="number" min="1" value={stageForm.data.sequence_no} onChange={e => stageForm.setData('sequence_no', e.target.value)} /></div>
                                    <div><label className="text-sm font-medium">Level Name *</label><Input value={stageForm.data.name} onChange={e => stageForm.setData('name', e.target.value)} placeholder="Department Review" /></div>
                                </div>
                                <div><label className="text-sm font-medium">Approver Role *</label><Select value={stageForm.data.approver_role_id} onValueChange={v => stageForm.setData('approver_role_id', v)}><SelectTrigger><SelectValue placeholder="Select role" /></SelectTrigger><SelectContent>{roles.map(role => <SelectItem key={role.id} value={String(role.id)}>{role.name} ({role.code})</SelectItem>)}</SelectContent></Select><p className="mt-1 text-xs text-muted-foreground">Dean, Director, Registrar, HoD etc. come from your existing Role Master.</p></div>
                                <div className="flex justify-end gap-2"><Button type="button" variant="outline" onClick={() => setStageWorkflow(null)}>Cancel</Button><Button type="submit" disabled={stageForm.processing}>Add Level</Button></div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            )}
        </div>
    );
}
