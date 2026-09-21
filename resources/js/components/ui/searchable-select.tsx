import { Check, Search } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type SearchableSelectOption = {
    value: string;
    label: string;
    searchText?: string;
    description?: string;
    disabled?: boolean;
};

type Props = {
    label?: string;
    value: string;
    onValueChange: (value: string) => void;
    options: SearchableSelectOption[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    disabled?: boolean;
    name?: string;
};

export function SearchableSelect({
    label,
    value,
    onValueChange,
    options,
    placeholder = 'Select an option',
    searchPlaceholder = 'Search options…',
    emptyText = 'No matching option found.',
    disabled = false,
    name,
}: Props) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const container = useRef<HTMLDivElement>(null);
    const selected = options.find((option) => option.value === value);
    const filtered = useMemo(() => {
        const needle = query.trim().toLocaleLowerCase();

        if (!needle) return options;

        return options.filter((option) =>
            `${option.label} ${option.searchText ?? ''} ${option.description ?? ''}`
                .toLocaleLowerCase()
                .includes(needle),
        );
    }, [options, query]);

    return (
        <div
            ref={container}
            className="relative min-w-0 space-y-2"
            onBlur={(event) => {
                if (!container.current?.contains(event.relatedTarget)) {
                    setOpen(false);
                    setQuery('');
                }
            }}
        >
            {name && <input type="hidden" name={name} value={value} />}
            {label && <Label>{label}</Label>}
            <button
                type="button"
                disabled={disabled}
                aria-expanded={open}
                onClick={() => {
                    setOpen((current) => !current);
                    setQuery('');
                }}
                className="flex min-h-10 w-full min-w-0 items-center justify-between gap-3 rounded-md border bg-background px-3 py-2 text-left text-sm shadow-xs outline-none transition-[color,box-shadow] focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span className={selected ? 'min-w-0 truncate' : 'min-w-0 truncate text-muted-foreground'}>
                    {selected?.label ?? placeholder}
                </span>
                <Search className="size-4 shrink-0 text-muted-foreground" />
            </button>
            {open && (
                <div className="absolute z-50 mt-1 w-full min-w-0 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md">
                    <div className="border-b p-2">
                        <div className="relative">
                            <Search className="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                autoFocus
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder={searchPlaceholder}
                                className="w-full pl-8"
                                onKeyDown={(event) => {
                                    if (event.key === 'Escape') setOpen(false);
                                }}
                            />
                        </div>
                    </div>
                    <div className="max-h-64 overflow-y-auto p-1">
                        {filtered.length === 0 ? (
                            <div className="px-3 py-6 text-center text-sm text-muted-foreground">{emptyText}</div>
                        ) : (
                            filtered.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    disabled={option.disabled}
                                    onMouseDown={(event) => event.preventDefault()}
                                    onClick={() => {
                                        onValueChange(option.value);
                                        setOpen(false);
                                        setQuery('');
                                    }}
                                    className="flex w-full min-w-0 items-start gap-2 rounded-sm px-2 py-2 text-left text-sm hover:bg-accent hover:text-accent-foreground disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    <Check className={`mt-0.5 size-4 shrink-0 ${option.value === value ? 'opacity-100' : 'opacity-0'}`} />
                                    <span className="min-w-0 flex-1">
                                        <span className="block break-words">{option.label}</span>
                                        {option.description && <span className="mt-0.5 block text-xs text-muted-foreground">{option.description}</span>}
                                    </span>
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
