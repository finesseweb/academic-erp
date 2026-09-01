export type AdmissionValidationField = {
    id: number;
    label: string;
    field_type: string;
    is_required?: boolean;
    validation_rules?: Record<string, unknown> | null;
    comparison_rule?: { source_field_id: number; source_field_label?: string | null; operator: string } | null;
};

const blank = (value: unknown) => value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0);
const scalar = (value: unknown) => Array.isArray(value) ? value.map(String).join(',') : String(value ?? '');

function completedAge(dateValue: string, referenceValue: string): number | null {
    const date = new Date(`${dateValue}T00:00:00`);
    const reference = new Date(`${referenceValue}T00:00:00`);
    if (Number.isNaN(date.getTime()) || Number.isNaN(reference.getTime())) return null;
    let age = reference.getFullYear() - date.getFullYear();
    const month = reference.getMonth() - date.getMonth();
    if (month < 0 || (month === 0 && reference.getDate() < date.getDate())) age--;
    return age;
}

function todayYmd(): string {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

export function admissionFieldValidationError(field: AdmissionValidationField, value: unknown, values: Record<number, unknown>): string | null {
    if (blank(value)) return field.is_required ? `${field.label} is required.` : null;

    const vr = field.validation_rules ?? {};
    const text = scalar(value);

    if (['TEXT', 'TEXTAREA', 'EMAIL', 'PHONE'].includes(field.field_type)) {
        const length = [...text].length;
        const exact = vr.exact_length !== undefined && vr.exact_length !== null && vr.exact_length !== '' ? Number(vr.exact_length) : null;
        const min = vr.min_length !== undefined && vr.min_length !== null && vr.min_length !== '' ? Number(vr.min_length) : null;
        const max = vr.max_length !== undefined && vr.max_length !== null && vr.max_length !== '' ? Number(vr.max_length) : null;
        if (exact !== null && length !== exact) return `${field.label} must be exactly ${exact} characters.`;
        if (min !== null && length < min) return `${field.label} must be at least ${min} characters.`;
        if (max !== null && length > max) return `${field.label} may not be longer than ${max} characters.`;

        if (['TEXT', 'TEXTAREA'].includes(field.field_type)) {
            const mode = String(vr.text_input_mode ?? 'ANY');
            if (mode === 'LETTERS_ONLY' && !/^[\p{L}\p{M}]+(?:[ '\-][\p{L}\p{M}]+)*$/u.test(text)) return `${field.label} must contain letters only.`;
            if (mode === 'DIGITS_ONLY' && !/^\d+$/u.test(text)) return `${field.label} must contain digits only.`;
            if (mode === 'ALPHANUMERIC' && !/^[\p{L}\p{M}\p{N}]+$/u.test(text)) return `${field.label} must contain letters and numbers only.`;
        }
        if (field.field_type === 'EMAIL' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text)) return `Enter a valid email address for ${field.label}.`;
    }

    if (field.field_type === 'NUMBER') {
        if (text.trim() === '' || Number.isNaN(Number(text))) return `${field.label} must be a valid number.`;
        const number = Number(text);
        if (vr.min_value !== undefined && vr.min_value !== null && vr.min_value !== '' && number < Number(vr.min_value)) return `${field.label} must be at least ${vr.min_value}.`;
        if (vr.max_value !== undefined && vr.max_value !== null && vr.max_value !== '' && number > Number(vr.max_value)) return `${field.label} may not be greater than ${vr.max_value}.`;
        if (Boolean(vr.integer_only) && !Number.isInteger(number)) return `${field.label} must be a whole number.`;
        if (vr.decimal_places !== undefined && vr.decimal_places !== null && vr.decimal_places !== '') {
            const places = (text.split('.')[1] ?? '').replace(/0+$/, '').length;
            if (places > Number(vr.decimal_places)) return `${field.label} may have at most ${vr.decimal_places} decimal places.`;
        }
    }

    if (field.field_type === 'DATE') {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(text) || Number.isNaN(new Date(`${text}T00:00:00`).getTime())) return `Enter a valid date for ${field.label}.`;
        if (vr.min_age_years !== undefined || vr.max_age_years !== undefined) {
            const reference = String(vr.age_reference_mode ?? 'TODAY') === 'CUSTOM' ? String(vr.age_reference_date ?? '') : todayYmd();
            const age = completedAge(text, reference);
            if (age === null) return `The age rule configured for ${field.label} is invalid.`;
            if (age < 0) return `${field.label} cannot be after the age reference date.`;
            if (vr.min_age_years !== undefined && vr.min_age_years !== null && age < Number(vr.min_age_years)) return `Age calculated from ${field.label} must be at least ${vr.min_age_years} years.`;
            if (vr.max_age_years !== undefined && vr.max_age_years !== null && age > Number(vr.max_age_years)) return `Age calculated from ${field.label} may not be more than ${vr.max_age_years} years.`;
        }
    }

    const comparison = field.comparison_rule;
    if (comparison) {
        const other = values[comparison.source_field_id];
        if (!blank(other)) {
            const left = field.field_type === 'DATE' ? new Date(`${scalar(value)}T00:00:00`).getTime() : Number(value);
            const right = field.field_type === 'DATE' ? new Date(`${scalar(other)}T00:00:00`).getTime() : Number(other);
            if (!Number.isNaN(left) && !Number.isNaN(right)) {
                const ok = comparison.operator === 'LT' ? left < right : comparison.operator === 'LTE' ? left <= right : comparison.operator === 'GT' ? left > right : comparison.operator === 'GTE' ? left >= right : comparison.operator === 'EQ' ? left === right : comparison.operator === 'NEQ' ? left !== right : true;
                if (!ok) {
                    const symbol = ({ LT: '<', LTE: '≤', GT: '>', GTE: '≥', EQ: '=', NEQ: '≠' } as Record<string, string>)[comparison.operator] ?? comparison.operator;
                    return `${field.label} must be ${symbol} ${comparison.source_field_label ?? 'the selected field'}.`;
                }
            }
        }
    }

    return null;
}

export function admissionFieldValidationHint(field: AdmissionValidationField): string | null {
    const vr = field.validation_rules ?? {};
    const hints: string[] = [];
    const mode = String(vr.text_input_mode ?? 'ANY');
    if (mode === 'LETTERS_ONLY') hints.push('Letters only');
    if (mode === 'DIGITS_ONLY') hints.push('Digits only');
    if (mode === 'ALPHANUMERIC') hints.push('Letters and numbers only');
    if (vr.exact_length !== undefined) hints.push(`Exactly ${vr.exact_length} characters`);
    else {
        if (vr.min_length !== undefined) hints.push(`Minimum ${vr.min_length} characters`);
        if (vr.max_length !== undefined) hints.push(`Maximum ${vr.max_length} characters`);
    }
    if (vr.min_value !== undefined) hints.push(`Minimum value ${vr.min_value}`);
    if (vr.max_value !== undefined) hints.push(`Maximum value ${vr.max_value}`);
    if (Boolean(vr.integer_only)) hints.push('Whole numbers only');
    if (vr.decimal_places !== undefined) hints.push(`Up to ${vr.decimal_places} decimal places`);
    if (vr.min_age_years !== undefined) hints.push(`Minimum age ${vr.min_age_years}`);
    if (vr.max_age_years !== undefined) hints.push(`Maximum age ${vr.max_age_years}`);
    if ((vr.min_age_years !== undefined || vr.max_age_years !== undefined) && String(vr.age_reference_mode ?? 'TODAY') === 'CUSTOM' && vr.age_reference_date) hints.push(`Age as on ${vr.age_reference_date}`);
    if (field.comparison_rule) {
        const symbol = ({ LT: '<', LTE: '≤', GT: '>', GTE: '≥', EQ: '=', NEQ: '≠' } as Record<string, string>)[field.comparison_rule.operator] ?? field.comparison_rule.operator;
        hints.push(`Must be ${symbol} ${field.comparison_rule.source_field_label ?? 'comparison field'}`);
    }
    return hints.length ? hints.join(' · ') : null;
}
