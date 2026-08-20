import { Form, Head } from '@inertiajs/react';
import { Edit3, GraduationCap, Plus, Power } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

export type MasterRecord = {
    id: number;
    name: string;
    code: string;
    description?: string | null;
    display_order: number;
    status: 'ACTIVE' | 'INACTIVE';
    [key: string]: unknown;
};
export type MasterField = {
    name: string;
    label: string;
    type?: 'text' | 'number' | 'textarea' | 'select';
    required?: boolean;
    min?: number;
    max?: number;
    options?: { value: string; label: string }[];
    optionsForValue?: {
        field: string;
        resolve: (value: string) => { value: string; label: string }[];
    };
    defaultValue?: string | number;
    disabledWhen?: { field: string; equals: string };
    disabledWhenRecord?: (record?: MasterRecord) => boolean;
    requiredWhen?: { field: string; equals: string };
    disabledValue?: string;
    disabledDisplayValue?: string;
    helperText?: string;
};
type Props = {
    title: string;
    singular: string;
    description: string;
    endpoint: string;
    records: MasterRecord[];
    fields: MasterField[];
    can: { create: boolean; update: boolean; disable: boolean };
    meta?: (record: MasterRecord) => string;
};

function Editor({
    record,
    ...props
}: Omit<Props, 'records' | 'can' | 'meta'> & { record?: MasterRecord }) {
    const [values, setValues] = useState<Record<string, string>>(() =>
        Object.fromEntries(
            props.fields.map((field) => [
                field.name,
                String(record?.[field.name] ?? field.defaultValue ?? ''),
            ]),
        ),
    );

    const updateValue = (name: string, value: string) => {
        setValues((current) => {
            const next = { ...current, [name]: value };

            for (const dependent of props.fields) {
                if (dependent.disabledWhen?.field === name) {
                    if (value === dependent.disabledWhen.equals) {
                        next[dependent.name] = dependent.disabledValue ?? '';
                    } else if (
                        next[dependent.name] === dependent.disabledValue
                    ) {
                        next[dependent.name] = '';
                    }
                }

                if (dependent.optionsForValue?.field === name) {
                    const optionValues = dependent.optionsForValue
                        .resolve(value)
                        .map((option) => option.value);

                    if (!optionValues.includes(next[dependent.name])) {
                        next[dependent.name] = '';
                    }
                }
            }

            return next;
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant={record ? 'ghost' : 'default'}
                    size={record ? 'sm' : 'default'}
                >
                    {record ? <Edit3 /> : <Plus />}
                    {record ? 'Edit' : `Add ${props.singular.toLowerCase()}`}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogTitle>
                    {record
                        ? `Edit ${props.singular.toLowerCase()}`
                        : `Add ${props.singular.toLowerCase()}`}
                </DialogTitle>
                <DialogDescription>{props.description}</DialogDescription>
                <Form
                    action={
                        record
                            ? `${props.endpoint}/${record.id}`
                            : props.endpoint
                    }
                    method={record ? 'patch' : 'post'}
                    resetOnSuccess={!record}
                    className="grid gap-4 sm:grid-cols-2"
                >
                    {({ processing, errors }) => (
                        <>
                            {props.fields.map((field) => {
                                const disabled = Boolean(
                                    (field.disabledWhen &&
                                        values[field.disabledWhen.field] ===
                                            field.disabledWhen.equals) ||
                                    field.disabledWhenRecord?.(record),
                                );
                                const options = field.optionsForValue
                                    ? field.optionsForValue.resolve(
                                          values[field.optionsForValue.field],
                                      )
                                    : field.options;
                                const disabledValue =
                                    field.disabledValue ?? values[field.name];
                                const disabledDisplayValue =
                                    field.disabledDisplayValue ??
                                    options?.find(
                                        (option) =>
                                            option.value === disabledValue,
                                    )?.label ??
                                    disabledValue;
                                const required = Boolean(
                                    field.required ||
                                    (field.requiredWhen &&
                                        values[field.requiredWhen.field] ===
                                            field.requiredWhen.equals),
                                );

                                return (
                                    <div
                                        key={field.name}
                                        className={
                                            field.type === 'textarea'
                                                ? 'space-y-2 sm:col-span-2'
                                                : 'space-y-2'
                                        }
                                    >
                                        <Label
                                            htmlFor={`${field.name}-${record?.id ?? 'new'}`}
                                        >
                                            {field.label}
                                            {required ? ' *' : ''}
                                        </Label>
                                        {field.type === 'select' && disabled ? (
                                            <>
                                                <Input
                                                    id={`${field.name}-${record?.id ?? 'new'}`}
                                                    value={disabledDisplayValue}
                                                    disabled
                                                    aria-required={required}
                                                />
                                                <input
                                                    type="hidden"
                                                    name={field.name}
                                                    value={disabledValue}
                                                />
                                            </>
                                        ) : field.type === 'select' ? (
                                            <Select
                                                name={field.name}
                                                value={values[field.name]}
                                                onValueChange={(value) =>
                                                    updateValue(
                                                        field.name,
                                                        value,
                                                    )
                                                }
                                                disabled={disabled}
                                                required={required}
                                            >
                                                <SelectTrigger
                                                    id={`${field.name}-${record?.id ?? 'new'}`}
                                                    aria-invalid={Boolean(
                                                        errors[field.name],
                                                    )}
                                                    aria-required={required}
                                                >
                                                    <SelectValue
                                                        placeholder={`Select ${field.label.toLowerCase()}`}
                                                    />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {options?.map((option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {option.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        ) : field.type === 'textarea' ? (
                                            <Textarea
                                                id={`${field.name}-${record?.id ?? 'new'}`}
                                                name={field.name}
                                                defaultValue={String(
                                                    record?.[field.name] ??
                                                        field.defaultValue ??
                                                        '',
                                                )}
                                                aria-invalid={Boolean(
                                                    errors[field.name],
                                                )}
                                            />
                                        ) : (
                                            <Input
                                                id={`${field.name}-${record?.id ?? 'new'}`}
                                                name={field.name}
                                                type={field.type ?? 'text'}
                                                min={field.min}
                                                max={field.max}
                                                defaultValue={String(
                                                    record?.[field.name] ??
                                                        field.defaultValue ??
                                                        '',
                                                )}
                                                aria-invalid={Boolean(
                                                    errors[field.name],
                                                )}
                                                autoFocus={
                                                    field.name ===
                                                    props.fields[0]?.name
                                                }
                                            />
                                        )}
                                        {disabled && field.type !== 'select' ? (
                                            <input
                                                type="hidden"
                                                name={field.name}
                                                value={disabledValue}
                                            />
                                        ) : null}
                                        {field.helperText ? (
                                            <p className="text-xs text-muted-foreground">
                                                {field.helperText}
                                            </p>
                                        ) : null}
                                        <p
                                            className="text-sm text-destructive"
                                            role="alert"
                                        >
                                            {errors[field.name]}
                                        </p>
                                    </div>
                                );
                            })}
                            <DialogFooter className="sm:col-span-2">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Saving...'
                                        : `Save ${props.singular.toLowerCase()}`}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
function StatusAction({
    record,
    endpoint,
    singular,
}: {
    record: MasterRecord;
    endpoint: string;
    singular: string;
}) {
    const active = record.status === 'ACTIVE';

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    <Power />
                    {active ? 'Disable' : 'Enable'}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>
                    {active ? 'Disable' : 'Enable'} {record.name}?
                </DialogTitle>
                <DialogDescription>
                    {active
                        ? `This ${singular.toLowerCase()} will not be available for new configuration. Existing references remain intact.`
                        : `This ${singular.toLowerCase()} will become available.`}
                </DialogDescription>
                <Form action={`${endpoint}/${record.id}/status`} method="patch">
                    {({ processing }) => (
                        <>
                            <input
                                type="hidden"
                                name="status"
                                value={active ? 'INACTIVE' : 'ACTIVE'}
                            />
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant={active ? 'destructive' : 'default'}
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {processing
                                        ? 'Updating...'
                                        : `Confirm ${active ? 'disable' : 'enable'}`}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
export function AcademicMasterPage(props: Props) {
    return (
        <>
            <Head title={props.title} />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <p className="flex items-center gap-2 text-sm font-medium text-primary">
                            <GraduationCap className="size-4" />
                            Academic structure
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            {props.title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {props.description}
                        </p>
                    </div>
                    {props.can.create && <Editor {...props} />}
                </header>
                {props.records.length === 0 ? (
                    <Card>
                        <CardContent className="flex min-h-64 flex-col items-center justify-center gap-3 text-center">
                            <GraduationCap className="size-10 text-muted-foreground" />
                            <h2 className="font-semibold">
                                No {props.title.toLowerCase()} yet
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Add the first {props.singular.toLowerCase()} to
                                establish this University academic master.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="divide-y p-0">
                            {props.records.map((record) => (
                                <div
                                    key={record.id}
                                    className="flex flex-col gap-4 p-5 transition-colors hover:bg-muted/40 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="flex items-start gap-4">
                                        <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 font-semibold text-primary">
                                            {record.display_order}
                                        </span>
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h2 className="font-semibold">
                                                    {record.name}
                                                </h2>
                                                <span className="rounded-full bg-muted px-2 py-1 font-mono text-xs">
                                                    {record.code}
                                                </span>
                                                <span
                                                    className={
                                                        record.status ===
                                                        'ACTIVE'
                                                            ? 'rounded-full bg-primary/10 px-2 py-1 text-xs text-primary'
                                                            : 'rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground'
                                                    }
                                                >
                                                    {record.status}
                                                </span>
                                            </div>
                                            {props.meta && (
                                                <p className="mt-1 text-sm font-medium">
                                                    {props.meta(record)}
                                                </p>
                                            )}
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {record.description ||
                                                    'No description provided.'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        {props.can.update && (
                                            <Editor
                                                {...props}
                                                record={record}
                                            />
                                        )}{' '}
                                        {props.can.disable && (
                                            <StatusAction
                                                record={record}
                                                endpoint={props.endpoint}
                                                singular={props.singular}
                                            />
                                        )}
                                    </div>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}
