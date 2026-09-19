import { router } from '@inertiajs/react';
import { createContext, type ReactNode, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { Spinner } from '@/components/ui/spinner';

type AppLoadingApi = {
    isLoading: boolean;
    label: string;
    setNextLoadingLabel: (label?: string) => void;
};

const DEFAULT_LABEL = 'Loading data…';
const AppLoadingContext = createContext<AppLoadingApi>({ isLoading: false, label: DEFAULT_LABEL, setNextLoadingLabel: () => undefined });

/**
 * Project-wide Inertia loading state.
 * The provider owns state only; visual loading belongs inside authenticated app content,
 * so navigation never masks the sidebar and auth/login pages never receive an overlay.
 */
export function AppLoadingProvider({ children }: { children: ReactNode }) {
    const [isLoading, setIsLoading] = useState(false);
    const [label, setLabel] = useState(DEFAULT_LABEL);
    const nextLabel = useRef<string | null>(null);

    const setNextLoadingLabel = useCallback((value?: string) => {
        nextLabel.current = value?.trim() || null;
    }, []);

    useEffect(() => {
        const removeStart = router.on('start', () => {
            setLabel(nextLabel.current ?? DEFAULT_LABEL);
            nextLabel.current = null;
            setIsLoading(true);
        });
        const removeFinish = router.on('finish', () => {
            setIsLoading(false);
            setLabel(DEFAULT_LABEL);
            nextLabel.current = null;
        });
        return () => { removeStart(); removeFinish(); };
    }, []);

    const value = useMemo(() => ({ isLoading, label, setNextLoadingLabel }), [isLoading, label, setNextLoadingLabel]);
    return <AppLoadingContext.Provider value={value}>{children}</AppLoadingContext.Provider>;
}

/** Theme-native content-scoped spinner for normal Inertia fetch/navigation. */
export function AppLoadingOverlay() {
    const { isLoading, label } = useAppLoading();
    if (!isLoading) return null;

    return (
        <div className="pointer-events-auto absolute inset-0 z-[60] flex items-center justify-center bg-background/35 backdrop-blur-[1px]" role="status" aria-live="polite" aria-label={label}>
            <div className="flex items-center gap-2 rounded-lg border bg-card/95 px-4 py-3 text-sm font-medium shadow-lg">
                <Spinner />
                <span>{label}</span>
            </div>
        </div>
    );
}

export function useAppLoading(): AppLoadingApi {
    return useContext(AppLoadingContext);
}
