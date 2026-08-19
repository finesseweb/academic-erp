import { Head } from '@inertiajs/react';
import SignatoryForm from './signatory-form';

export default function CreateSignatory() {
    return (
        <>
            <Head title="Create Authorized Signatory" />
            <div className="mx-auto w-full max-w-5xl space-y-6 p-4 md:p-6">
                <header className="space-y-2 border-b pb-5">
                    <p className="text-sm font-medium text-primary">
                        University administration
                    </p>
                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Create Authorized Signatory
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Register a governed signing appointment without creating
                        a login account.
                    </p>
                </header>
                <SignatoryForm mode="create" />
            </div>
        </>
    );
}
CreateSignatory.layout = {
    breadcrumbs: [
        {
            title: 'Authorized Signatories',
            href: '/admin/university/signatories',
        },
        { title: 'Create', href: '/admin/university/signatories/create' },
    ],
};
