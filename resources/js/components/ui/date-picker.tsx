import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

type DisabledRange = {
    start: string;
    end: string;
};

type Props = {
    id: string;
    name: string;
    defaultValue?: string | null;
    value?: string | null;
    onValueChange?: (value: string) => void;
    disabled?: boolean;
    min?: string;
    max?: string;
    invalid?: boolean;
    disabledRanges?: DisabledRange[];
};

const weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

const monthNames = Array.from({ length: 12 }, (_, month) =>
    new Date(2000, month, 1).toLocaleDateString(undefined, {
        month: 'long',
    }),
);

const toDateOnly = (value?: string | null) => (value ? value.slice(0, 10) : '');

const parseDate = (value: string) => {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
};

const formatValue = (date: Date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(
        date.getDate(),
    ).padStart(2, '0')}`;

export function DatePicker({
    id,
    name,
    defaultValue,
    value: controlledValue,
    onValueChange,
    disabled,
    min = '1800-01-01',
    max,
    invalid,
    disabledRanges = [],
}: Props) {
    const controlled = controlledValue !== undefined;
    const initial = toDateOnly(controlled ? controlledValue : defaultValue);

    const [internalValue, setInternalValue] = useState(initial);
    const value = controlled ? toDateOnly(controlledValue) : internalValue;

    const minDate = parseDate(toDateOnly(min));
    const maxDate = max ? parseDate(toDateOnly(max)) : null;

    const normalizedDisabledRanges = useMemo(
        () =>
            disabledRanges
                .map((range) => ({
                    start: parseDate(toDateOnly(range.start)),
                    end: parseDate(toDateOnly(range.end)),
                }))
                .filter(
                    (range) =>
                        !Number.isNaN(range.start.getTime()) &&
                        !Number.isNaN(range.end.getTime()),
                ),
        [disabledRanges],
    );

    const isDisabledByRange = (date: Date) =>
        normalizedDisabledRanges.some(
            (range) => date >= range.start && date <= range.end,
        );

    const clampViewToAllowedMonth = (date: Date) => {
        const month = new Date(date.getFullYear(), date.getMonth(), 1);
        const minMonth = new Date(
            minDate.getFullYear(),
            minDate.getMonth(),
            1,
        );
        const maxMonth = maxDate
            ? new Date(maxDate.getFullYear(), maxDate.getMonth(), 1)
            : null;

        if (month < minMonth) {
            return minMonth;
        }

        if (maxMonth && month > maxMonth) {
            return maxMonth;
        }

        return month;
    };

    const [view, setView] = useState(() =>
        clampViewToAllowedMonth(initial ? parseDate(initial) : new Date()),
    );

    useEffect(() => {
        if (!controlled) {
            return;
        }

        const next = toDateOnly(controlledValue);

        if (next) {
            setView(clampViewToAllowedMonth(parseDate(next)));
        }
    }, [controlled, controlledValue]);

    useEffect(() => {
        // min/max can change after a parent selection (for example, after
        // choosing a Curriculum). Keep the visible month inside the new
        // allowed range so the Year Select always has a matching option.
        setView((current) => clampViewToAllowedMonth(current));
    }, [min, max]);

    const selected = value ? parseDate(value) : null;
    const firstYear = minDate.getFullYear();
    const lastYear = maxDate?.getFullYear() ?? new Date().getFullYear() + 20;

    const years = useMemo(
        () =>
            Array.from(
                { length: lastYear - firstYear + 1 },
                (_, index) => lastYear - index,
            ),
        [firstYear, lastYear],
    );

    const cells = useMemo(() => {
        const year = view.getFullYear();
        const month = view.getMonth();
        const firstWeekday = new Date(year, month, 1).getDay();
        const days = new Date(year, month + 1, 0).getDate();

        return [
            ...Array(firstWeekday).fill(null),
            ...Array.from(
                { length: days },
                (_, index) => new Date(year, month, index + 1),
            ),
        ];
    }, [view]);

    const setDateValue = (nextValue: string) => {
        if (!controlled) {
            setInternalValue(nextValue);
        }

        onValueChange?.(nextValue);
    };

    return (
        <>
            <input type="hidden" name={name} value={value} />

            <Dialog>
                <DialogTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        disabled={disabled}
                        aria-invalid={invalid}
                        className={cn(
                            'h-10 w-full justify-start bg-background font-normal',
                            !value && 'text-muted-foreground',
                            invalid && 'border-destructive',
                        )}
                    >
                        <CalendarDays className="size-4" />
                        {selected
                            ? selected.toLocaleDateString(undefined, {
                                  day: '2-digit',
                                  month: 'long',
                                  year: 'numeric',
                              })
                            : 'Select date'}
                    </Button>
                </DialogTrigger>

                <DialogContent className="max-w-sm">
                    <DialogTitle>Select date</DialogTitle>
                    <DialogDescription>
                        Choose a month and year directly, then select the date.
                    </DialogDescription>

                    <div className="rounded-xl border bg-card p-3">
                        <div className="mb-3 flex items-center gap-2">
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                aria-label="Previous month"
                                disabled={
                                    view.getFullYear() === minDate.getFullYear() &&
                                    view.getMonth() <= minDate.getMonth()
                                }
                                onClick={() =>
                                    setView(
                                        new Date(
                                            view.getFullYear(),
                                            view.getMonth() - 1,
                                            1,
                                        ),
                                    )
                                }
                            >
                                <ChevronLeft />
                            </Button>

                            <Select
                                value={String(view.getMonth())}
                                onValueChange={(month) =>
                                    setView(
                                        new Date(
                                            view.getFullYear(),
                                            Number(month),
                                            1,
                                        ),
                                    )
                                }
                            >
                                <SelectTrigger
                                    className="h-9 min-w-0 flex-1"
                                    aria-label="Select month"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {monthNames.map((month, index) => (
                                        <SelectItem
                                            key={month}
                                            value={String(index)}
                                        >
                                            {month}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select
                                value={String(view.getFullYear())}
                                onValueChange={(year) =>
                                    setView(
                                        new Date(
                                            Number(year),
                                            view.getMonth(),
                                            1,
                                        ),
                                    )
                                }
                            >
                                <SelectTrigger
                                    className="h-9 w-28"
                                    aria-label="Select year"
                                >
                                    <span className="truncate">
                                        {view.getFullYear()}
                                    </span>
                                </SelectTrigger>
                                <SelectContent className="max-h-72">
                                    {years.map((year) => (
                                        <SelectItem
                                            key={year}
                                            value={String(year)}
                                        >
                                            {year}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                aria-label="Next month"
                                disabled={Boolean(
                                    maxDate &&
                                        view.getFullYear() ===
                                            maxDate.getFullYear() &&
                                        view.getMonth() >= maxDate.getMonth(),
                                )}
                                onClick={() =>
                                    setView(
                                        new Date(
                                            view.getFullYear(),
                                            view.getMonth() + 1,
                                            1,
                                        ),
                                    )
                                }
                            >
                                <ChevronRight />
                            </Button>
                        </div>

                        <div className="grid grid-cols-7 gap-1 text-center">
                            {weekdays.map((day) => (
                                <span
                                    key={day}
                                    className="py-1 text-xs font-medium text-muted-foreground"
                                >
                                    {day}
                                </span>
                            ))}

                            {cells.map((date, index) =>
                                date ? (
                                    <DialogClose
                                        key={formatValue(date)}
                                        asChild
                                    >
                                        <button
                                            type="button"
                                            disabled={Boolean(
                                                date < minDate ||
                                                    (maxDate && date > maxDate) ||
                                                    isDisabledByRange(date),
                                            )}
                                            onClick={() =>
                                                setDateValue(formatValue(date))
                                            }
                                            className={cn(
                                                'grid aspect-square place-items-center rounded-md text-sm transition-colors hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-30',
                                                selected &&
                                                    formatValue(date) ===
                                                        formatValue(selected) &&
                                                    'bg-primary text-primary-foreground hover:bg-primary',
                                            )}
                                        >
                                            {date.getDate()}
                                        </button>
                                    </DialogClose>
                                ) : (
                                    <span key={`blank-${index}`} />
                                ),
                            )}
                        </div>
                    </div>

                    <DialogFooter className="sm:justify-between">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => setDateValue('')}
                        >
                            Clear date
                        </Button>

                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Close
                            </Button>
                        </DialogClose>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
