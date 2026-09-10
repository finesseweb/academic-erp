import { Form, Head, router } from '@inertiajs/react';
import { CreditCard, Pencil, Power, Save } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Mapping = {
    id: number;
    fee_head_id: number;
    product_code: string;
    settlement_code?: string;
    status: string;
};

type ProviderDefinition = {
    code: string;
    name: string;
    credential_labels: { merchant_id: string; key_id: string; key_secret: string; webhook_secret: string };
    show: { merchant_id: boolean; webhook_secret: boolean; default_product_code: boolean; enc_key: boolean; dec_key: boolean };
    required_for_activation: string[];
    routing_product_mode: 'OPTIONAL' | 'PROFILE_OR_HEAD_REQUIRED';
    routing_help: string;
};

type Gateway = {
    id: number;
    provider: string;
    display_name: string;
    environment: string;
    merchant_id?: string;
    key_id?: string;
    has_key_secret: boolean;
    has_webhook_secret: boolean;
    has_enc_key: boolean;
    has_dec_key: boolean;
    default_product_code?: string | null;
    status: string;
    mappings: Mapping[];
};

type FeeHead = {
    id: number;
    name: string;
    code: string;
    college_id?: number | null;
};

type RouteDraft = {
    fee_head_id: number;
    college_payment_gateway_id: number | null;
    product_code: string;
    settlement_code: string;
};

function GatewayCard({
    collegeId,
    gateway,
    canManage,
    providerDefinition,
}: {
    collegeId: number;
    gateway: Gateway;
    canManage: boolean;
    providerDefinition: ProviderDefinition;
}) {
    const [edit, setEdit] = useState(false);

    return (
        <Card>
            <CardHeader className="flex-row items-center justify-between">
                <div>
                    <CardTitle>
                        {gateway.display_name}{' '}
                        <span className="text-sm font-normal text-muted-foreground">
                            · {gateway.provider} · {gateway.environment} · {gateway.status}
                        </span>
                    </CardTitle>
                </div>
                {canManage && (
                    <div className="flex gap-2">
                        <Button
                            size="icon"
                            variant="outline"
                            title="Edit"
                            onClick={() => setEdit((value) => !value)}
                        >
                            <Pencil className="size-4" />
                        </Button>
                        <Form
                            action={`/college/${collegeId}/payment-gateways/${gateway.id}/status`}
                            method="patch"
                        >
                            <input
                                type="hidden"
                                name="status"
                                value={gateway.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE'}
                            />
                            <Button
                                size="icon"
                                variant="outline"
                                title={gateway.status === 'ACTIVE' ? 'Deactivate' : 'Activate'}
                            >
                                <Power className="size-4" />
                            </Button>
                        </Form>
                    </div>
                )}
            </CardHeader>

            {edit && canManage && (
                <CardContent>
                    <Form
                        action={`/college/${collegeId}/payment-gateways/${gateway.id}`}
                        method="patch"
                        className="grid gap-3 md:grid-cols-3"
                    >
                        {({ processing }) => (
                            <>
                                <div>
                                    <Label>Profile Name</Label>
                                    <Input name="display_name" defaultValue={gateway.display_name} />
                                </div>
                                <div>
                                    <Label>Environment</Label>
                                    <select
                                        name="environment"
                                        defaultValue={gateway.environment}
                                        className="h-9 w-full rounded-md border bg-background px-3"
                                    >
                                        <option>TEST</option>
                                        <option>LIVE</option>
                                    </select>
                                </div>
                                {providerDefinition.show.merchant_id && (
                                    <div>
                                        <Label>{providerDefinition.credential_labels.merchant_id}</Label>
                                        <Input
                                            name="merchant_id"
                                            defaultValue={gateway.merchant_id}
                                            placeholder={gateway.provider === 'NTTDATA_ATOM' ? 'Provided MID' : 'Only if provider gives one'}
                                        />
                                    </div>
                                )}
                                <div>
                                    <Label>{providerDefinition.credential_labels.key_id}</Label>
                                    <Input name="key_id" defaultValue={gateway.key_id} />
                                </div>
                                <div>
                                    <Label>{providerDefinition.credential_labels.key_secret}</Label>
                                    <Input
                                        name="key_secret"
                                        type="password"
                                        placeholder={gateway.has_key_secret ? 'Saved — leave blank to keep' : 'Enter secret'}
                                    />
                                </div>
                                {providerDefinition.show.webhook_secret && (
                                    <div>
                                        <Label>{providerDefinition.credential_labels.webhook_secret}</Label>
                                        <Input
                                            name="webhook_secret"
                                            type="password"
                                            placeholder={gateway.has_webhook_secret ? 'Saved — leave blank to keep' : 'Optional for now'}
                                        />
                                    </div>
                                )}
                                {providerDefinition.show.default_product_code && (
                                    <div>
                                        <Label>Default Product ID</Label>
                                        <Input
                                            name="default_product_code"
                                            defaultValue={gateway.default_product_code ?? ''}
                                            placeholder="Optional when every Fee Head has its own Product ID"
                                        />
                                    </div>
                                )}
                                {providerDefinition.show.enc_key && (
                                    <div>
                                        <Label>Encryption Key</Label>
                                        <Input
                                            name="enc_key"
                                            type="password"
                                            placeholder={gateway.has_enc_key ? 'Saved — leave blank to keep' : 'Enter if supplied by provider'}
                                        />
                                    </div>
                                )}
                                {providerDefinition.show.dec_key && (
                                    <div>
                                        <Label>Decryption Key</Label>
                                        <Input
                                            name="dec_key"
                                            type="password"
                                            placeholder={gateway.has_dec_key ? 'Saved — leave blank to keep' : 'Enter if supplied by provider'}
                                        />
                                    </div>
                                )}
                                <div className="md:col-span-3 text-xs text-muted-foreground">
                                    {providerDefinition.routing_help}
                                </div>
                                <div>
                                    <Button disabled={processing}>
                                        <Save className="size-4" />
                                        Save Configuration
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </CardContent>
            )}
        </Card>
    );
}

function FeeHeadRouting({
    collegeId,
    gateways,
    feeHeads,
    canManage,
    providerDefinitions,
}: {
    collegeId: number;
    gateways: Gateway[];
    feeHeads: FeeHead[];
    canManage: boolean;
    providerDefinitions: ProviderDefinition[];
}) {
    const environments = ['TEST', 'LIVE'] as const;
    const [environment, setEnvironment] = useState<(typeof environments)[number]>('TEST');
    const profiles = useMemo(
        () => gateways.filter((gateway) => gateway.environment === environment),
        [gateways, environment],
    );
    const definitionByCode = useMemo(
        () => Object.fromEntries(providerDefinitions.map((definition) => [definition.code, definition])),
        [providerDefinitions],
    );

    const initialDrafts = useMemo<Record<number, RouteDraft>>(() => {
        const result: Record<number, RouteDraft> = {};

        feeHeads.forEach((head) => {
            let selectedGateway: Gateway | undefined;
            let selectedMapping: Mapping | undefined;

            profiles.some((gateway) => {
                const mapping = gateway.mappings.find(
                    (item) => item.fee_head_id === head.id && item.status === 'ACTIVE',
                );
                if (!mapping) return false;
                selectedGateway = gateway;
                selectedMapping = mapping;
                return true;
            });

            result[head.id] = {
                fee_head_id: head.id,
                college_payment_gateway_id: selectedGateway?.id ?? null,
                product_code: selectedMapping?.product_code ?? '',
                settlement_code: selectedMapping?.settlement_code ?? '',
            };
        });

        return result;
    }, [feeHeads, profiles]);

    const [draftsByEnvironment, setDraftsByEnvironment] = useState<Record<string, Record<number, RouteDraft>>>({});
    const drafts = draftsByEnvironment[environment] ?? initialDrafts;
    const [saving, setSaving] = useState(false);

    const updateDraft = (headId: number, patch: Partial<RouteDraft>) => {
        setDraftsByEnvironment((current) => {
            const envDrafts = current[environment] ?? initialDrafts;
            return {
                ...current,
                [environment]: {
                    ...envDrafts,
                    [headId]: { ...envDrafts[headId], ...patch },
                },
            };
        });
    };

    const chooseProfile = (headId: number, rawValue: string) => {
        const gatewayId = rawValue ? Number(rawValue) : null;
        const selectedGateway = profiles.find((gateway) => gateway.id === gatewayId);
        const existing = selectedGateway?.mappings.find((item) => item.fee_head_id === headId);

        updateDraft(headId, {
            college_payment_gateway_id: gatewayId,
            product_code: existing?.product_code ?? '',
            settlement_code: existing?.settlement_code ?? '',
        });
    };

    const saveAll = () => {
        const mappings = feeHeads.map((head) => drafts[head.id]);

        setSaving(true);
        router.post(
            `/college/${collegeId}/payment-gateways/routing/bulk`,
            { environment, mappings },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <Card>
            <CardHeader className="space-y-3">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <CardTitle>Fee Head Payment Routing</CardTitle>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Each Fee Head is independent. It can use a different credential profile, or remain unassigned.
                        </p>
                    </div>
                    {canManage && (
                        <Button type="button" onClick={saveAll} disabled={saving || profiles.length === 0}>
                            <Save className="size-4" />
                            Save All Routing
                        </Button>
                    )}
                </div>
                <div className="flex items-center gap-2">
                    <Label>Routing Environment</Label>
                    <select
                        value={environment}
                        onChange={(event) => setEnvironment(event.target.value as 'TEST' | 'LIVE')}
                        className="h-9 rounded-md border bg-background px-3"
                    >
                        {environments.map((item) => (
                            <option key={item} value={item}>{item}</option>
                        ))}
                    </select>
                </div>
            </CardHeader>
            <CardContent>
                {profiles.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No {environment} credential profile exists yet. Add one above first.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/60 text-left">
                                <tr>
                                    <th className="p-2">Fee Head</th>
                                    <th className="p-2">Credential Profile</th>
                                    <th className="p-2">Product Code</th>
                                    <th className="p-2">Settlement Code</th>
                                </tr>
                            </thead>
                            <tbody>
                                {feeHeads.map((head) => {
                                    const draft = drafts[head.id];
                                    return (
                                        <tr key={head.id} className="border-t">
                                            <td className="p-2">
                                                {head.name}
                                                <div className="text-xs text-muted-foreground">
                                                    {head.code}{head.college_id ? ' · College' : ' · University'}
                                                </div>
                                            </td>
                                            <td className="p-2">
                                                {canManage ? (
                                                    <select
                                                        value={draft.college_payment_gateway_id ?? ''}
                                                        onChange={(event) => chooseProfile(head.id, event.target.value)}
                                                        className="h-9 min-w-56 w-full rounded-md border bg-background px-2"
                                                    >
                                                        <option value="">Not assigned</option>
                                                        {profiles.map((gateway) => (
                                                            <option key={gateway.id} value={gateway.id}>
                                                                {gateway.display_name} · {gateway.provider} · {gateway.status}
                                                            </option>
                                                        ))}
                                                    </select>
                                                ) : (
                                                    profiles.find((gateway) => gateway.id === draft.college_payment_gateway_id)?.display_name ?? 'Not assigned'
                                                )}
                                            </td>
                                            <td className="p-2">
                                                {canManage ? (
                                                    <>
                                                        <Input
                                                            value={draft.product_code}
                                                            disabled={!draft.college_payment_gateway_id}
                                                            placeholder={(() => {
                                                                const selected = profiles.find((gateway) => gateway.id === draft.college_payment_gateway_id);
                                                                const definition = selected ? definitionByCode[selected.provider] : undefined;
                                                                if (!selected) return 'Select a profile first';
                                                                if (definition?.routing_product_mode === 'PROFILE_OR_HEAD_REQUIRED') {
                                                                    return selected.default_product_code ? `Optional override · default ${selected.default_product_code}` : 'Required Product ID';
                                                                }
                                                                return 'Optional';
                                                            })()}
                                                            onChange={(event) => updateDraft(head.id, { product_code: event.target.value })}
                                                        />
                                                        {draft.college_payment_gateway_id && (
                                                            <div className="mt-1 text-xs text-muted-foreground">
                                                                {(() => {
                                                                    const selected = profiles.find((gateway) => gateway.id === draft.college_payment_gateway_id);
                                                                    return selected ? definitionByCode[selected.provider]?.routing_help : '';
                                                                })()}
                                                            </div>
                                                        )}
                                                    </>
                                                ) : draft.product_code || '—'}
                                            </td>
                                            <td className="p-2">
                                                {canManage ? (
                                                    <Input
                                                        value={draft.settlement_code}
                                                        disabled={!draft.college_payment_gateway_id}
                                                        placeholder="Optional"
                                                        onChange={(event) => updateDraft(head.id, { settlement_code: event.target.value })}
                                                    />
                                                ) : draft.settlement_code || '—'}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function Index({
    college,
    providers,
    gateways,
    feeHeads,
    can,
}: {
    college: { id: number; name: string; code: string };
    providers: ProviderDefinition[];
    gateways: Gateway[];
    feeHeads: FeeHead[];
    can: { manage: boolean };
}) {
    return (
        <>
            <Head title="Payment Gateway Configuration" />
            <div className="space-y-6 p-4 md:p-6">
                <header className="border-b pb-5">
                    <p className="text-sm font-medium text-primary">{college.code} · Fee Management</p>
                    <h1 className="text-3xl font-semibold">Payment Gateway Configuration</h1>
                    <p className="text-muted-foreground">
                        Add provider-specific credential profiles, then independently route each Fee Head to the correct profile. Product Code is only required when that gateway actually needs one.
                    </p>
                </header>

                {can.manage && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Add Gateway Credential Profile</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Form
                                action={`/college/${college.id}/payment-gateways`}
                                method="post"
                                className="grid gap-4 md:grid-cols-4"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div>
                                            <Label>Gateway</Label>
                                            <select name="provider" className="h-9 w-full rounded-md border bg-background px-3">
                                                {providers.map((provider) => (
                                                    <option key={provider.code} value={provider.code}>{provider.name}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <Label>Profile Name</Label>
                                            <Input name="display_name" placeholder="e.g. Principal / Alumni Fund" required />
                                            <p className="text-xs text-destructive">{errors.display_name}</p>
                                        </div>
                                        <div>
                                            <Label>Environment</Label>
                                            <select name="environment" className="h-9 w-full rounded-md border bg-background px-3">
                                                <option value="TEST">TEST</option>
                                                <option value="LIVE">LIVE</option>
                                            </select>
                                        </div>
                                        <div className="flex items-end">
                                            <Button disabled={processing}>
                                                <CreditCard className="size-4" />
                                                Add Profile
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}

                {gateways.map((gateway) => {
                    const providerDefinition = providers.find((provider) => provider.code === gateway.provider)!;
                    return (
                        <GatewayCard
                            key={gateway.id}
                            collegeId={college.id}
                            gateway={gateway}
                            canManage={can.manage}
                            providerDefinition={providerDefinition}
                        />
                    );
                })}

                {!!gateways.length && (
                    <FeeHeadRouting
                        collegeId={college.id}
                        gateways={gateways}
                        feeHeads={feeHeads}
                        canManage={can.manage}
                        providerDefinitions={providers}
                    />
                )}

                {!gateways.length && (
                    <Card>
                        <CardContent className="py-10 text-center text-muted-foreground">
                            No payment gateway credential profile configured yet.
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
