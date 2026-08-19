import { Head } from '@inertiajs/react';
import UserForm from './user-form';
export default function CreateUser() {
    return (
        <>
            <Head title="Create User" />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <header className="space-y-2 border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        Access & Security
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Create User
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Create a secure ERP login account. Roles and scopes are
                        assigned in their ordered milestones.
                    </p>
                </header>
                <UserForm mode="create" />
            </div>
        </>
    );
}
CreateUser.layout = {
    breadcrumbs: [
        { title: 'Users', href: '/admin/users' },
        { title: 'Create', href: '/admin/users/create' },
    ],
};
