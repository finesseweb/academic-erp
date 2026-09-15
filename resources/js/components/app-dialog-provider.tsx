import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    createContext,
    type ReactNode,
    useContext,
    useMemo,
    useRef,
    useState,
} from 'react';

type CommonOptions = {
    title?: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    destructive?: boolean;
    confirmIcon?: ReactNode;
};

type PromptOptions = CommonOptions & {
    defaultValue?: string;
    placeholder?: string;
    multiline?: boolean;
    required?: boolean;
};

type AlertOptions = Omit<CommonOptions, 'cancelLabel'>;

type DialogState =
    | ({ mode: 'confirm' } & CommonOptions)
    | ({ mode: 'prompt'; value: string } & PromptOptions)
    | ({ mode: 'alert' } & AlertOptions)
    | null;

type AppDialogApi = {
    confirm: (options: string | CommonOptions) => Promise<boolean>;
    prompt: (options: string | PromptOptions) => Promise<string | null>;
    alert: (options: string | AlertOptions) => Promise<void>;
};

const AppDialogContext = createContext<AppDialogApi | null>(null);

const normalize = <T extends CommonOptions>(options: string | T): T =>
    (typeof options === 'string'
        ? ({ description: options } as T)
        : options);

export function AppDialogProvider({ children }: { children: ReactNode }) {
    const [state, setState] = useState<DialogState>(null);
    const resolver = useRef<((value: unknown) => void) | null>(null);

    const closeWith = (value: unknown) => {
        resolver.current?.(value);
        resolver.current = null;
        setState(null);
    };

    const api = useMemo<AppDialogApi>(
        () => ({
            confirm: (options) => {
                const config = normalize<CommonOptions>(options);
                return new Promise<boolean>((resolve) => {
                    resolver.current = resolve as (value: unknown) => void;
                    setState({ mode: 'confirm', ...config });
                });
            },
            prompt: (options) => {
                const config = normalize<PromptOptions>(options);
                return new Promise<string | null>((resolve) => {
                    resolver.current = resolve as (value: unknown) => void;
                    setState({
                        mode: 'prompt',
                        value: config.defaultValue ?? '',
                        ...config,
                    });
                });
            },
            alert: (options) => {
                const config = normalize<AlertOptions>(options);
                return new Promise<void>((resolve) => {
                    resolver.current = resolve as (value: unknown) => void;
                    setState({ mode: 'alert', ...config });
                });
            },
        }),
        [],
    );

    const title =
        state?.title ??
        (state?.mode === 'prompt'
            ? 'Enter details'
            : state?.mode === 'alert'
              ? 'Notice'
              : 'Please confirm');

    const confirmLabel =
        state?.confirmLabel ??
        (state?.mode === 'alert' ? 'OK' : state?.mode === 'prompt' ? 'Continue' : 'Confirm');

    const promptValue = state?.mode === 'prompt' ? state.value : '';
    const promptRequired = state?.mode === 'prompt' ? (state.required ?? false) : false;
    const promptInvalid = promptRequired && promptValue.trim().length === 0;

    return (
        <AppDialogContext.Provider value={api}>
            {children}
            <Dialog
                open={state !== null}
                onOpenChange={(open) => {
                    if (!open && state) {
                        closeWith(state.mode === 'confirm' ? false : state.mode === 'prompt' ? null : undefined);
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription className="whitespace-pre-line">
                            {state?.description}
                        </DialogDescription>
                    </DialogHeader>

                    {state?.mode === 'prompt' &&
                        (state.multiline ?? true ? (
                            <Textarea
                                autoFocus
                                value={state.value}
                                placeholder={state.placeholder}
                                className="min-h-24 resize-y"
                                onChange={(event) =>
                                    setState((current) =>
                                        current?.mode === 'prompt'
                                            ? { ...current, value: event.target.value }
                                            : current,
                                    )
                                }
                            />
                        ) : (
                            <Input
                                autoFocus
                                value={state.value}
                                placeholder={state.placeholder}
                                onChange={(event) =>
                                    setState((current) =>
                                        current?.mode === 'prompt'
                                            ? { ...current, value: event.target.value }
                                            : current,
                                    )
                                }
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter' && !promptInvalid) {
                                        closeWith(state.value);
                                    }
                                }}
                            />
                        ))}

                    <DialogFooter>
                        {state?.mode !== 'alert' && (
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => closeWith(state?.mode === 'confirm' ? false : null)}
                            >
                                {state?.cancelLabel ?? 'Cancel'}
                            </Button>
                        )}
                        <Button
                            type="button"
                            variant={state?.destructive ? 'destructive' : 'default'}
                            disabled={state?.mode === 'prompt' && promptInvalid}
                            onClick={() => {
                                if (state?.mode === 'confirm') closeWith(true);
                                else if (state?.mode === 'prompt') closeWith(state.value);
                                else closeWith(undefined);
                            }}
                        >
                            {state?.confirmIcon}
                            {confirmLabel}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppDialogContext.Provider>
    );
}

export function useAppDialog() {
    const context = useContext(AppDialogContext);
    if (!context) {
        throw new Error('useAppDialog must be used within AppDialogProvider.');
    }
    return context;
}
