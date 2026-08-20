import { AcademicMasterPage } from '@/components/academic-master-page';
import type { MasterRecord } from '@/components/academic-master-page';
type Degree = MasterRecord & {
    degree_level_id: number;
    degree_level: { name: string };
    typical_duration_years: number | null;
};
type Props = {
    degrees: Degree[];
    levels: { id: number; name: string }[];
    can: { create: boolean; update: boolean; disable: boolean };
};
export default function Degrees({ degrees, levels, can }: Props) {
    return (
        <AcademicMasterPage
            title="Degrees"
            singular="Degree"
            description="Manage University awards and their degree-level classification."
            endpoint="/admin/degrees"
            records={degrees}
            can={can}
            fields={[
                {
                    name: 'degree_level_id',
                    label: 'Degree level',
                    type: 'select',
                    required: true,
                    options: levels.map((x) => ({
                        value: String(x.id),
                        label: x.name,
                    })),
                },
                { name: 'name', label: 'Degree name', required: true },
                { name: 'code', label: 'Code', required: true },
                {
                    name: 'typical_duration_years',
                    label: 'Typical duration (years)',
                    type: 'number',
                    min: 1,
                    max: 15,
                },
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
            meta={(record) =>
                `${(record.degree_level as { name: string }).name}${record.typical_duration_years ? ` · ${record.typical_duration_years} years` : ''}`
            }
        />
    );
}
