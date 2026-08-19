import { Head } from '@inertiajs/react';
import SignatoryForm from './signatory-form';
import type { SignatoryFormData } from './signatory-form';

export default function EditSignatory({
    signatory,
}: {
    signatory: SignatoryFormData;
}) {
    return (
        <>
            <Head title={`Edit ${signatory.full_name}`} />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <header className="space-y-2 border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        Authorized Signatory
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Edit {signatory.full_name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Maintain authority, appointment dates, and operational
                        status.
                    </p>
                </header>
                <SignatoryForm mode="edit" signatory={signatory} />
            </div>
        </>
    );
}
EditSignatory.layout = {
    breadcrumbs: [
        {
            title: 'Authorized Signatories',
            href: '/admin/university/signatories',
        },
        { title: 'Edit', href: '#' },
    ],
};
