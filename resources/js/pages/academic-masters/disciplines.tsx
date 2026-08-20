import { AcademicMasterPage } from '@/components/academic-master-page';
import type { MasterRecord } from '@/components/academic-master-page';
type Item = MasterRecord & {
    kind: string;
    parent_id: number | null;
    parent: { name: string } | null;
};
type Props = {
    disciplines: Item[];
    parents: { id: number; name: string }[];
    can: { create: boolean; update: boolean; disable: boolean };
};
export default function Disciplines({ disciplines, parents, can }: Props) {
    return (
        <AcademicMasterPage
            title="Disciplines & Specializations"
            singular="Discipline / Specialization"
            description="Maintain University-wide subject domains and their optional specializations."
            endpoint="/admin/disciplines"
            records={disciplines}
            can={can}
            fields={[
                {
                    name: 'kind',
                    label: 'Type',
                    type: 'select',
                    required: true,
                    defaultValue: 'DISCIPLINE',
                    options: [
                        { value: 'DISCIPLINE', label: 'Discipline' },
                        { value: 'SPECIALIZATION', label: 'Specialization' },
                    ],
                },
                {
                    name: 'parent_id',
                    label: 'Parent discipline',
                    type: 'select',
                    disabledWhen: { field: 'kind', equals: 'DISCIPLINE' },
                    disabledValue: 'none',
                    disabledDisplayValue: 'Not applicable',
                    requiredWhen: { field: 'kind', equals: 'SPECIALIZATION' },
                    helperText:
                        'Required for specializations; disciplines are always top-level.',
                    options: [
                        { value: 'none', label: 'None — top-level discipline' },
                        ...parents.map((x) => ({
                            value: String(x.id),
                            label: x.name,
                        })),
                    ],
                },
                { name: 'name', label: 'Name', required: true },
                { name: 'code', label: 'Code', required: true },
                {
                    name: 'display_order',
                    label: 'Display order',
                    type: 'number',
                    min: 0,
                    max: 65535,
                    defaultValue: 0,
                },
                {
                    name: 'status',
                    label: 'Status',
                    type: 'select',
                    required: true,
                    defaultValue: 'ACTIVE',
                    options: [
                        { value: 'ACTIVE', label: 'Active' },
                        { value: 'INACTIVE', label: 'Inactive' },
                    ],
                },
                { name: 'description', label: 'Description', type: 'textarea' },
            ]}
            meta={(r) =>
                r.kind === 'SPECIALIZATION'
                    ? `Specialization of ${(r.parent as { name: string })?.name}`
                    : 'Discipline'
            }
        />
    );
}
