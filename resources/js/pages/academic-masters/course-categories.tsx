import { AcademicMasterPage } from '@/components/academic-master-page';
import type { MasterRecord } from '@/components/academic-master-page';
type Item = MasterRecord & { category_group: string };
type Props = {
    categories: Item[];
    can: { create: boolean; update: boolean; disable: boolean };
};
export default function CourseCategories({ categories, can }: Props) {
    return (
        <AcademicMasterPage
            title="Course Categories"
            singular="Course Category"
            description="Classify curriculum courses consistently across University programs."
            endpoint="/admin/course-categories"
            records={categories}
            can={can}
            fields={[
                { name: 'name', label: 'Category name', required: true },
                { name: 'code', label: 'Code', required: true },
                {
                    name: 'category_group',
                    label: 'Category group',
                    type: 'select',
                    required: true,
                    defaultValue: 'OTHER',
                    options: [
                        { value: 'CORE', label: 'Core' },
                        { value: 'ELECTIVE', label: 'Elective' },
                        { value: 'REQUIREMENT', label: 'Requirement' },
                        { value: 'OTHER', label: 'Other' },
                    ],
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
                `${String(r.category_group).charAt(0)}${String(r.category_group).slice(1).toLowerCase()} category`
            }
        />
    );
}
