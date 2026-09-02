import { useState } from 'react';
import { DatePicker } from '@/components/ui/date-picker';
import { TimePicker } from '@/components/ui/time-picker';

const splitDateTime = (value?: string | null) => {
    if (!value) return { date: '', time: '10:00' };
    const normalized = value.replace(' ', 'T');
    const [date = '', time = '10:00'] = normalized.split('T');
    return { date, time: time.slice(0, 5) };
};

type Props = {
    id: string;
    name: string;
    defaultValue?: string | null;
    value?: string | null;
    onValueChange?: (value: string) => void;
    disabled?: boolean;
    invalid?: boolean;
    minDate?: string;
    maxDate?: string;
    minuteStep?: number;
};

export function DateTimePicker({
    id,
    name,
    defaultValue,
    value: controlledValue,
    onValueChange,
    disabled,
    invalid,
    minDate,
    maxDate,
    minuteStep = 5,
}: Props) {
    const controlled = controlledValue !== undefined;
    const initial = splitDateTime(controlled ? controlledValue : defaultValue);
    const [internal, setInternal] = useState(initial);
    const state = controlled ? splitDateTime(controlledValue) : internal;

    const emit = (next: typeof state) => {
        if (!controlled) setInternal(next);
        onValueChange?.(next.date ? `${next.date}T${next.time}` : '');
    };

    return (
        <div className="space-y-2">
            <input type="hidden" name={name} value={state.date ? `${state.date}T${state.time}` : ''} />
            <DatePicker
                id={`${id}-date`}
                name={`${name}_date_ui`}
                value={state.date}
                onValueChange={(date) => emit({ ...state, date })}
                disabled={disabled}
                invalid={invalid}
                min={minDate}
                max={maxDate}
            />
            <TimePicker
                id={`${id}-time`}
                value={state.time}
                onValueChange={(time) => emit({ ...state, time })}
                disabled={disabled}
                minuteStep={minuteStep}
            />
        </div>
    );
}
