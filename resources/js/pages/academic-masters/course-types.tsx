import { AcademicMasterPage } from '@/components/academic-master-page';
import type { MasterRecord } from '@/components/academic-master-page';

type Props = {
    courseTypes: MasterRecord[];
    can: { create: boolean; update: boolean; disable: boolean };
};

export default function CourseTypes({ courseTypes, can }: Props) {
    return (
        <AcademicMasterPage
            title="Course Types"
            singular="Course Type"
            description="Define University-wide course delivery or academic activity types for later Course / Subject Master and curriculum use."
            endpoint="/admin/course-types"
            records={courseTypes}
            can={can}
            fields={[
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
                {
                    name: 'description',
                    label: 'Description',
                    type: 'textarea',
                },
            ]}
            meta={() => 'University course type'}
        />
    );
}
