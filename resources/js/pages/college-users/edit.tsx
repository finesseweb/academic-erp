import { Form, Head, Link } from '@inertiajs/react';
import { Save, UserRound } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    college: { id: number; name: string; code: string };
    managedUser: {
        id: number;
        name: string;
        email: string;
        mobile: string | null;
        status: string;
    };
};
export default function Edit({ college, managedUser }: Props) {
    return (
        <>
            <Head title={`Edit ${managedUser.name}`} />
            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-6">
                <header>
                    <p className="text-sm font-medium text-primary">
                        {college.code} · College Users
                    </p>
                    <h1 className="text-3xl font-semibold">
                        Edit {managedUser.name}
                    </h1>
                    <p className="text-muted-foreground">
                        The account remains owned by {college.name}.
                    </p>
                </header>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex gap-2">
                            <UserRound />
                            Account identity
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Form
                            action={`/college/${college.id}/users/${managedUser.id}`}
                            method="patch"
                            disableWhileProcessing
                            className="grid gap-4 sm:grid-cols-2"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {[
                                        [
                                            'name',
                                            'Full name',
                                            managedUser.name,
                                            'text',
                                        ],
                                        [
                                            'email',
                                            'Email address',
                                            managedUser.email,
                                            'email',
                                        ],
                                        [
                                            'mobile',
                                            'Mobile number',
                                            managedUser.mobile ?? '',
                                            'tel',
                                        ],
                                    ].map(([name, label, value, type]) => (
                                        <div key={name} className="grid gap-2">
                                            <Label htmlFor={name}>
                                                {label}
                                                {name !== 'mobile' ? ' *' : ''}
                                            </Label>
                                            <Input
                                                id={name}
                                                name={name}
                                                type={type}
                                                defaultValue={value}
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
                                                ? 'Saving...'
                                                : 'Save changes'}
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
