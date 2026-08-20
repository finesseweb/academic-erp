import { Form, Head, Link } from '@inertiajs/react';
import { Save, Shield } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
type Props = {
    college: { id: number; name: string; code: string };
    role: {
        id: number;
        name: string;
        code: string;
        description: string | null;
        status: string;
    };
};
export default function Edit({ college, role }: Props) {
    return (
        <>
            <Head title={`Edit ${role.name}`} />
            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-6">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {college.code} · College Roles
                    </p>
                    <h1 className="text-3xl font-semibold">Edit {role.name}</h1>
                    <p className="text-muted-foreground">
                        This role remains owned by {college.name}.
                    </p>
                </header>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex gap-2">
                            <Shield />
                            Role identity
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={`/college/${college.id}/roles/${role.id}`}
                            method="patch"
                            disableWhileProcessing
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {[
                                        ['name', 'Role name', role.name],
                                        ['code', 'Stable role code', role.code],
                                    ].map(([name, label, value]) => (
                                        <div key={name} className="grid gap-2">
                                            <Label htmlFor={name}>
                                                {label} *
                                            </Label>
                                            <Input
                                                id={name}
                                                name={name}
                                                defaultValue={value}
                                                required
                                                aria-invalid={Boolean(
                                                    errors[name],
                                                )}
                                            />
                                            <InputError
                                                message={errors[name]}
                                            />
                                        </div>
                                    ))}
                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            Description
                                        </Label>
                                        <textarea
                                            id="description"
                                            name="description"
                                            defaultValue={
                                                role.description ?? ''
                                            }
                                            rows={4}
                                            className="rounded-md border border-input bg-background p-3 text-sm focus-visible:ring-2 focus-visible:ring-ring"
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>
                                    <div className="flex gap-2">
                                        <Button disabled={processing}>
                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <Save />
                                            )}
                                            {processing
                                                ? 'Saving...'
                                                : 'Save changes'}
                                        </Button>
                                        <Button variant="outline" asChild>
                                            <Link
                                                href={`/college/${college.id}/roles`}
                                            >
                                                Cancel
                                            </Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
