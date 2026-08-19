import { useEffect } from 'react';

export function FocusFirstError({
    errors,
}: {
    errors: Record<string, string>;
}) {
    useEffect(() => {
        if (Object.keys(errors).length === 0) {
            return;
        }

        requestAnimationFrame(() =>
            document
                .querySelector<HTMLElement>('[aria-invalid="true"]')
                ?.focus(),
        );
    }, [errors]);

    return null;
}
