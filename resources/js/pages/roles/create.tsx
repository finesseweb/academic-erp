import { Head } from '@inertiajs/react';
import RoleForm from './role-form';
export default function CreateRole() {
    return (
        <>
            <Head title="Create Role" />
            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <header className="space-y-2 border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        Access & Security
                    </p>
                    <h1 className="text-2xl font-semibold sm:text-3xl">
                        Create Role
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Create role metadata now; configure permissions in the
                        ordered matrix milestone.
                    </p>
                </header>
                <RoleForm mode="create" />
            </div>
        </>
    );
}
CreateRole.layout = {
    breadcrumbs: [
        { title: 'Roles', href: '/admin/roles' },
        { title: 'Create', href: '/admin/roles/create' },
    ],
};
