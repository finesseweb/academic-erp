import { Head } from '@inertiajs/react';
import RoleForm from './role-form';
import type { RoleData } from './role-form';
export default function EditRole({ role }: { role: RoleData }) {
    return (
        <>
            <Head title={`Edit ${role.name}`} />
            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 md:p-6">
                <header className="space-y-2 border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        {role.is_system_role
                            ? 'Protected system role'
                            : 'Custom role'}
                    </p>
                    <h1 className="text-2xl font-semibold sm:text-3xl">
                        {role.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {role.permissions_count ?? 0} permissions ·{' '}
                        {role.users_count ?? 0} assigned users. Permission
                        configuration becomes available in the Role Permission
                        Matrix milestone.
                    </p>
                </header>
                <RoleForm mode="edit" role={role} />
            </div>
        </>
    );
}
EditRole.layout = {
    breadcrumbs: [
        { title: 'Roles', href: '/admin/roles' },
        { title: 'Edit', href: '#' },
    ],
};
