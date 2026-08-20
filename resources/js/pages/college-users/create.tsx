import { Form, Head, Link } from '@inertiajs/react';
import { Save, UsersRound } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
export default function Create({
    college,
}: {
    college: { id: number; name: string; code: string };
}) {
    return (
        <>
            <Head title={`Create user · ${college.name}`} />
            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-6">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {college.code} · College Users
                    </p>
                    <h1 className="text-3xl font-semibold">
                        Create College Staff
                    </h1>
                    <p className="text-muted-foreground">
                        This account is securely linked to {college.name}.
                    </p>
                </header>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex gap-2">
                            <UsersRound />
                            Account details
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={`/college/${college.id}/users`}
                            method="post"
                            disableWhileProcessing
                            className="grid gap-4 sm:grid-cols-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {[
                                        ['name', 'Full name', 'text'],
                                        ['email', 'Email address', 'email'],
                                        ['mobile', 'Mobile number', 'text'],
                                        [
                                            'password',
                                            'Initial password',
                                            'password',
                                        ],
                                        [
                                            'password_confirmation',
                                            'Confirm password',
                                            'password',
                                        ],
                                    ].map(([name, label, type]) => (
                                        <div key={name} className="grid gap-2">
                                            <Label htmlFor={name}>
                                                {label}
                                                {name !== 'mobile' ? ' *' : ''}
                                            </Label>
                                            <Input
                                                id={name}
                                                name={name}
                                                type={type}
                                                required={name !== 'mobile'}
                                                aria-invalid={Boolean(
                                                    errors[name],
                                                )}
                                            />
                                            <InputError
                                                message={errors[name]}
                                            />
                                        </div>
                                    ))}
                                    <div className="flex gap-2 sm:col-span-2">
                                        <Button disabled={processing}>
                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <Save />
                                            )}
                                            {processing
                                                ? 'Creating...'
                                                : 'Create College user'}
                                        </Button>
                                        <Button variant="outline" asChild>
                                            <Link
                                                href={`/college/${college.id}/users`}
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
