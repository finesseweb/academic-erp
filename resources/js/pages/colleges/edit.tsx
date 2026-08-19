import { Head } from '@inertiajs/react';
import CollegeForm from './college-form';
import type { CollegeFormData } from './college-form';

export default function EditCollege({ college }: { college: CollegeFormData }) {
    return (
        <>
            <Head title={`Edit ${college.name}`} />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <header className="space-y-2 border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        Affiliated College
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Edit {college.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Maintain the College identity and University-governed
                        administrative details.
                    </p>
                </header>
                <CollegeForm mode="edit" college={college} />
            </div>
        </>
    );
}
EditCollege.layout = {
    breadcrumbs: [
        { title: 'Affiliated Colleges', href: '/admin/colleges' },
        { title: 'Edit', href: '#' },
    ],
};
