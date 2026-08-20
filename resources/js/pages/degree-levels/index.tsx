import { Form, Head } from '@inertiajs/react';
import { Edit3, GraduationCap, Plus, Power } from 'lucide-react';
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

type Level = {
    id: number;
    name: string;
    code: string;
    description: string | null;
    display_order: number;
    status: 'ACTIVE' | 'INACTIVE';
};
type Props = {
    levels: Level[];
    can: { create: boolean; update: boolean; disable: boolean };
};

function LevelForm({ level }: { level?: Level }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant={level ? 'ghost' : 'default'}
                    size={level ? 'sm' : 'default'}
                >
                    {level ? <Edit3 /> : <Plus />}
                    {level ? 'Edit' : 'Add degree level'}
                </Button>
            </DialogTrigger>
            <DialogContent className="max-w-xl">
                <DialogTitle>
                    {level ? 'Edit degree level' : 'Add degree level'}
                </DialogTitle>
                <DialogDescription>
                    Configure a reusable University classification such as
                    Undergraduate, Postgraduate, or Doctoral.
                </DialogDescription>
                <Form
                    action={
                        level
                            ? `/admin/degree-levels/${level.id}`
                            : '/admin/degree-levels'
                    }
                    method={level ? 'patch' : 'post'}
                    resetOnSuccess={!level}
                    className="grid gap-4 sm:grid-cols-2"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="space-y-2">
                                <Label
                                    htmlFor={`level-name-${level?.id ?? 'new'}`}
                                >
                                    Name
                                </Label>
                                <Input
                                    id={`level-name-${level?.id ?? 'new'}`}
                                    name="name"
                                    defaultValue={level?.name}
                                    aria-invalid={Boolean(errors.name)}
                                    autoFocus
                                />
                                <p className="text-sm text-destructive">
                                    {errors.name}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label
                                    htmlFor={`level-code-${level?.id ?? 'new'}`}
                                >
                                    Code
                                </Label>
                                <Input
                                    id={`level-code-${level?.id ?? 'new'}`}
                                    name="code"
                                    defaultValue={level?.code}
                                    aria-invalid={Boolean(errors.code)}
                                />
                                <p className="text-sm text-destructive">
                                    {errors.code}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label
                                    htmlFor={`level-order-${level?.id ?? 'new'}`}
                                >
                                    Display order
                                </Label>
                                <Input
                                    id={`level-order-${level?.id ?? 'new'}`}
                                    type="number"
                                    min="0"
                                    max="65535"
                                    name="display_order"
                                    defaultValue={level?.display_order ?? 0}
                                    aria-invalid={Boolean(errors.display_order)}
                                />
                                <p className="text-sm text-destructive">
                                    {errors.display_order}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label>Status</Label>
                                <Select
                                    name="status"
                                    defaultValue={level?.status ?? 'ACTIVE'}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ACTIVE">
                                            Active
                                        </SelectItem>
                                        <SelectItem value="INACTIVE">
                                            Inactive
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2 sm:col-span-2">
                                <Label
                                    htmlFor={`level-description-${level?.id ?? 'new'}`}
                                >
                                    Description
                                </Label>
                                <Textarea
                                    id={`level-description-${level?.id ?? 'new'}`}
                                    name="description"
                                    defaultValue={level?.description ?? ''}
                                    aria-invalid={Boolean(errors.description)}
                                />
                                <p className="text-sm text-destructive">
                                    {errors.description}
                                </p>
                            </div>
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
                                        : 'Save degree level'}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function DegreeLevels({ levels, can }: Props) {
    return (
        <>
            <Head title="Degree Levels" />
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-6">
                <header className="flex flex-col gap-4 border-b pb-5 sm:flex-row sm:items-end sm:justify-between">
                    <div className="space-y-2">
                        <p className="flex items-center gap-2 text-sm font-medium text-primary">
                            <GraduationCap className="size-4" />
                            Academic structure
                        </p>
                        <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                            Degree Levels
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Maintain ordered University-wide classifications
                            used by Degrees.
                        </p>
                    </div>
                    {can.create && <LevelForm />}
                </header>
                {levels.length === 0 ? (
                    <Card>
                        <CardContent className="flex min-h-64 flex-col items-center justify-center gap-3 text-center">
                            <GraduationCap className="size-10 text-muted-foreground" />
                            <h2 className="font-semibold">
                                No degree levels yet
                            </h2>
                            <p className="max-w-md text-sm text-muted-foreground">
                                Add Undergraduate, Postgraduate, Doctoral, or
                                another University classification.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="divide-y p-0">
                            {levels.map((level) => (
                                <div
                                    key={level.id}
                                    className="flex flex-col gap-4 p-5 transition-colors hover:bg-muted/40 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="flex items-start gap-4">
                                        <span className="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 font-semibold text-primary">
                                            {level.display_order}
                                        </span>
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <h2 className="font-semibold">
                                                    {level.name}
                                                </h2>
                                                <span className="rounded-full bg-muted px-2 py-1 font-mono text-xs">
                                                    {level.code}
                                                </span>
                                                <span
                                                    className={
                                                        level.status ===
                                                        'ACTIVE'
                                                            ? 'rounded-full bg-primary/10 px-2 py-1 text-xs text-primary'
                                                            : 'rounded-full bg-muted px-2 py-1 text-xs text-muted-foreground'
                                                    }
                                                >
                                                    {level.status}
                                                </span>
                                            </div>
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {level.description ||
                                                    'No description provided.'}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        {can.update && (
                                            <LevelForm level={level} />
                                        )}{' '}
                                        {can.disable && (
                                            <Form
                                                action={`/admin/degree-levels/${level.id}/status`}
                                                method="patch"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="status"
                                                    value={
                                                        level.status ===
                                                        'ACTIVE'
                                                            ? 'INACTIVE'
                                                            : 'ACTIVE'
                                                    }
                                                />
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                >
                                                    <Power />
                                                    {level.status === 'ACTIVE'
                                                        ? 'Disable'
                                                        : 'Enable'}
                                                </Button>
                                            </Form>
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
