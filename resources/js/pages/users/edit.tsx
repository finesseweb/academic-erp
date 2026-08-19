import { Head } from '@inertiajs/react';
import UserForm from './user-form';
import type { UserFormData } from './user-form';
export default function EditUser({
    managedUser,
}: {
    managedUser: UserFormData;
}) {
    return (
        <>
            <Head title={`Edit ${managedUser.name}`} />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <header className="space-y-2 border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        User account
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Edit {managedUser.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Maintain account identity. Current roles:{' '}
                        {managedUser.roles?.map((r) => r.name).join(', ') ||
                            'None assigned'}
                        .
                    </p>
                </header>
                <UserForm mode="edit" managedUser={managedUser} />
            </div>
        </>
    );
}
EditUser.layout = {
    breadcrumbs: [
        { title: 'Users', href: '/admin/users' },
        { title: 'Edit', href: '#' },
    ],
};
