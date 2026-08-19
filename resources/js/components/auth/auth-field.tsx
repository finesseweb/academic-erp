import type { ComponentProps, ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export function AuthField({
    label,
    error,
    icon,
    className,
    ...props
}: ComponentProps<typeof Input> & {
    label: string;
    error?: string;
    icon?: ReactNode;
}) {
    const errorId = `${props.id}-error`;

    return (
        <div className="grid gap-2">
            <Label htmlFor={props.id}>{label}</Label>
            <div className="relative">
                {icon ? (
                    <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted-foreground">
                        {icon}
                    </span>
                ) : null}
                <Input
                    {...props}
                    aria-invalid={Boolean(error)}
                    aria-describedby={error ? errorId : undefined}
                    className={cn(
                        'h-11 bg-background/70 transition-shadow',
                        icon && 'pl-10',
                        error &&
                            'border-destructive focus-visible:ring-destructive/30',
                        className,
                    )}
                />
            </div>
            <InputError id={errorId} message={error} />
        </div>
    );
}
