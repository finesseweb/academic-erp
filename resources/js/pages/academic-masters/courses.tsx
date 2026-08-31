import { AcademicMasterPage } from '@/components/academic-master-page';
import type { MasterRecord } from '@/components/academic-master-page';

type Item = MasterRecord & {
    course_category_id: number;
    course_type_id: number;
    category: { name: string };
    type: { name: string };
};

type Props = {
    courses: Item[];
    categories: { id: number; name: string }[];
    types: { id: number; name: string }[];
    can: { create: boolean; update: boolean; disable: boolean };
};

export default function Courses({ courses, categories, types, can }: Props) {
    return (
        <AcademicMasterPage
            title="Course / Subject Master"
            singular="Course / Subject"
            description="Maintain reusable University course and subject definitions. Program, discipline, specialization, term, credit and L-T-P mapping are handled later in the curriculum."
            endpoint="/admin/courses"
            records={courses}
            can={can}
            fields={[
                {
                    name: 'course_category_id',
                    label: 'Course Category',
                    type: 'select',
                    required: true,
                    options: categories.map((x) => ({
                        value: String(x.id),
                        label: x.name,
                    })),
                },
                {
                    name: 'course_type_id',
                    label: 'Course Type',
                    type: 'select',
                    required: true,
                    options: types.map((x) => ({
                        value: String(x.id),
                        label: x.name,
                    })),
                },
                { name: 'name', label: 'Course / Subject Name', required: true },
                { name: 'code', label: 'Course Code', required: true },
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
                {
                    name: 'description',
                    label: 'Description',
                    type: 'textarea',
                },
            ]}
            meta={(r) =>
                `${(r.category as { name: string }).name} / ${(r.type as { name: string }).name}`
            }
        />
    );
}
