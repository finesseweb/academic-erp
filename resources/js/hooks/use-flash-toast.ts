import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

type FlashPageProps = {
    flash?: {
        toast?: FlashToast | null;
    };
    errors?: Record<string, string | string[]>;
};

/**
 * Render server-side mutation feedback from the current Inertia page props.
 *
 * IMPORTANT: this hook requires Inertia page context. It must be mounted from
 * AppLayout (or another Inertia layout/page), never from the global <Toaster />
 * that is mounted by createInertiaApp.withApp().
 */
export function useFlashToast(): void {
    const page = usePage();
    const props = page.props as FlashPageProps;
    const flashToast = props.flash?.toast;
    const errors = props.errors;

    useEffect(() => {
        if (!flashToast?.message) {
            return;
        }

        switch (flashToast.type) {
            case 'success':
                toast.success(flashToast.message);
                break;
            case 'error':
                toast.error(flashToast.message);
                break;
            case 'warning':
                toast.warning(flashToast.message);
                break;
            case 'info':
            default:
                toast.info(flashToast.message);
                break;
        }
    }, [flashToast]);

    useEffect(() => {
        if (flashToast?.message || !errors) {
            return;
        }

        const firstError = Object.values(errors)
            .flatMap((value) => (Array.isArray(value) ? value : [value]))
            .find((value) => typeof value === 'string' && value.trim() !== '');

        if (firstError) {
            toast.error(firstError);
        }
    }, [errors, flashToast]);
}
