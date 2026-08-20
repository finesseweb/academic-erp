import { AcademicMasterPage } from '@/components/academic-master-page';
import type { MasterRecord } from '@/components/academic-master-page';
type Item = MasterRecord & {
    degree_id: number;
    discipline_id: number | null;
    degree: { name: string };
    discipline: { name: string } | null;
    term_structure: string;
    duration_terms: number;
};
type Props = {
    templates: Item[];
    degrees: { id: number; name: string }[];
    disciplines: { id: number; name: string }[];
    can: { create: boolean; update: boolean; disable: boolean };
};
export default function ProgramTemplates({
    templates,
    degrees,
    disciplines,
    can,
}: Props) {
    return (
        <AcademicMasterPage
            title="Program Templates"
            singular="Program Template"
            description="Define reusable University program blueprints for later College offerings and curricula."
            endpoint="/admin/program-templates"
            records={templates}
            can={can}
            fields={[
                {
                    name: 'degree_id',
                    label: 'Degree',
                    type: 'select',
                    required: true,
                    options: degrees.map((x) => ({
                        value: String(x.id),
                        label: x.name,
                    })),
                },
                {
                    name: 'discipline_id',
                    label: 'Primary discipline',
                    type: 'select',
                    options: [
                        { value: 'none', label: 'No primary discipline' },
                        ...disciplines.map((x) => ({
                            value: String(x.id),
                            label: x.name,
                        })),
                    ],
                },
                { name: 'name', label: 'Template name', required: true },
                { name: 'code', label: 'Code', required: true },
                {
                    name: 'term_structure',
                    label: 'Term structure',
                    type: 'select',
                    required: true,
                    defaultValue: 'SEMESTER',
                    options: [
                        { value: 'SEMESTER', label: 'Semester' },
                        { value: 'YEAR', label: 'Year' },
                        { value: 'TRIMESTER', label: 'Trimester' },
                    ],
                },
                {
                    name: 'duration_terms',
                    label: 'Duration (terms)',
                    type: 'number',
                    required: true,
                    min: 1,
                    max: 30,
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
            meta={(r) =>
                `${(r.degree as { name: string }).name}${r.discipline ? ` · ${(r.discipline as { name: string }).name}` : ''} · ${r.duration_terms} ${String(r.term_structure).toLowerCase()} terms`
            }
        />
    );
}
