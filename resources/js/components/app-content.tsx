import * as React from 'react';
import { AppLoadingOverlay } from '@/components/app-loading-provider';
import { SidebarInset } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = React.ComponentProps<'main'> & {
    variant?: AppVariant;
};

export function AppContent({ variant = 'sidebar', children, className, ...props }: Props) {
    if (variant === 'sidebar') {
        return (
            <SidebarInset {...props} className={`relative ${className ?? ''}`}>
                {children}
                <AppLoadingOverlay />
            </SidebarInset>
        );
    }

    return (
        <main
            className={`relative mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl ${className ?? ''}`}
            {...props}
        >
            {children}
            <AppLoadingOverlay />
        </main>
    );
}
