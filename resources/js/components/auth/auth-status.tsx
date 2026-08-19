import { CircleAlert, CircleCheck, Info } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

export function AuthStatus({
    message,
    type = 'success',
}: {
    message?: string;
    type?: 'success' | 'error' | 'info';
}) {
    if (!message) {
        return null;
    }

    const Icon =
        type === 'success'
            ? CircleCheck
            : type === 'error'
              ? CircleAlert
              : Info;

    return (
        <Alert
            variant={type === 'error' ? 'destructive' : 'default'}
            className={
                type === 'success'
                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200'
                    : ''
            }
        >
            <Icon className="size-4" />
            <AlertDescription>{message}</AlertDescription>
        </Alert>
    );
}
