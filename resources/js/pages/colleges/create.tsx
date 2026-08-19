import { Head } from '@inertiajs/react';
import CollegeForm from './college-form';

export default function CreateCollege() {
    return (
        <>
            <Head title="Create Affiliated College" />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <header className="space-y-2 border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        University administration
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Create Affiliated College
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Establish the College identity under the University.
                        Academic, finance, and access configuration remain in
                        their ordered milestones.
                    </p>
                </header>
                <CollegeForm mode="create" />
            </div>
        </>
    );
}
CreateCollege.layout = {
    breadcrumbs: [
        { title: 'Affiliated Colleges', href: '/admin/colleges' },
        { title: 'Create', href: '/admin/colleges/create' },
    ],
};
