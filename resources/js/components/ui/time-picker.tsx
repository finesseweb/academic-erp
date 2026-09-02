import { useMemo, useState } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type TimeState = {
    hour12: string;
    minute: string;
    period: '' | 'AM' | 'PM';
};

const splitTime = (value?: string | null): TimeState => {
    if (!value) return { hour12: '', minute: '', period: '' };
    const [hourRaw = '', minuteRaw = ''] = value.slice(0, 5).split(':');
    const hour24 = Number(hourRaw);
    if (!hourRaw || Number.isNaN(hour24)) return { hour12: '', minute: '', period: '' };
    return {
        hour12: String(hour24 % 12 || 12).padStart(2, '0'),
        minute: minuteRaw.padStart(2, '0'),
        period: hour24 >= 12 ? 'PM' : 'AM',
    };
};

const combineTime = (state: TimeState) => {
    if (!state.hour12 || !state.minute || !state.period) return '';
    const hour24 = Number(state.hour12) % 12 + (state.period === 'PM' ? 12 : 0);
    return `${String(hour24).padStart(2, '0')}:${state.minute}`;
};

type Props = {
    id: string;
    name?: string;
    defaultValue?: string | null;
    value?: string | null;
    onValueChange?: (value: string) => void;
    disabled?: boolean;
    minuteStep?: number;
};

export function TimePicker({
    id,
    name,
    defaultValue,
    value: controlledValue,
    onValueChange,
    disabled,
    minuteStep = 5,
}: Props) {
    const controlled = controlledValue !== undefined;
    const initial = splitTime(controlled ? controlledValue : defaultValue);
    const [internal, setInternal] = useState<TimeState>(initial);
    const state = controlled ? splitTime(controlledValue) : internal;

    const minuteOptions = useMemo(() => {
        const step = Math.max(1, Math.min(30, Math.floor(minuteStep)));
        const values = Array.from({ length: Math.ceil(60 / step) }, (_, index) =>
            String(index * step).padStart(2, '0'),
        ).filter((value) => Number(value) < 60);
        if (state.minute && !values.includes(state.minute)) values.push(state.minute);
        return values.sort((a, b) => Number(a) - Number(b));
    }, [minuteStep, state.minute]);

    const emit = (next: TimeState) => {
        if (!controlled) setInternal(next);
        onValueChange?.(combineTime(next));
    };

    return (
        <div className="grid grid-cols-[1fr_1fr_1fr] gap-2">
            {name ? <input type="hidden" name={name} value={combineTime(state)} /> : null}
            <Select value={state.hour12 || undefined} onValueChange={(hour12) => emit({ ...state, hour12 })} disabled={disabled}>
                <SelectTrigger id={`${id}-hour`} aria-label="Hour" className="w-full"><SelectValue placeholder="Hour" /></SelectTrigger>
                <SelectContent>
                    {Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0')).map((hour) => <SelectItem key={hour} value={hour}>{hour}</SelectItem>)}
                </SelectContent>
            </Select>
            <Select value={state.minute || undefined} onValueChange={(minute) => emit({ ...state, minute })} disabled={disabled}>
                <SelectTrigger aria-label="Minute" className="w-full"><SelectValue placeholder="Min" /></SelectTrigger>
                <SelectContent>
                    {minuteOptions.map((minute) => <SelectItem key={minute} value={minute}>{minute}</SelectItem>)}
                </SelectContent>
            </Select>
            <Select value={state.period || undefined} onValueChange={(period) => emit({ ...state, period: period as 'AM' | 'PM' })} disabled={disabled}>
                <SelectTrigger aria-label="AM or PM" className="w-full"><SelectValue placeholder="AM/PM" /></SelectTrigger>
                <SelectContent><SelectItem value="AM">AM</SelectItem><SelectItem value="PM">PM</SelectItem></SelectContent>
            </Select>
        </div>
    );
}
