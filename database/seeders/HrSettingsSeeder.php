<?php

namespace Database\Seeders;

use App\Modules\Users\Models\Department;
use App\Modules\Users\Models\Designation;
use App\Modules\Users\Models\EmploymentType;
use Illuminate\Database\Seeder;

class HrSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employmentTypes = [
            'Full-time',
            'Part-time',
            'Contract',
            'Intern',
            'Freelance',
            'Temporary',
            'Probation',
        ];

        foreach ($employmentTypes as $type) {
            EmploymentType::firstOrCreate(
                ['name' => $type],
                ['is_active' => true]
            );
        }

        $designations = [
            'Chief Executive Officer (CEO)',
            'Chief Operating Officer (COO)',
            'Chief Technology Officer (CTO)',
            'Chief Financial Officer (CFO)',
            'General Manager',
            'Human Resources Manager',
            'IT Manager',
            'Marketing Manager',
            'Sales Manager',
            'Operations Manager',
            'Finance Manager',
            'Project Manager',
            'Senior Software Engineer',
            'Software Engineer',
            'Junior Software Engineer',
            'Frontend Developer',
            'Backend Developer',
            'Full Stack Developer',
            'QA Engineer',
            'UI/UX Designer',
            'Graphic Designer',
            'System Administrator',
            'Network Engineer',
            'Database Administrator',
            'Business Analyst',
            'Data Analyst',
            'Sales Executive',
            'Marketing Executive',
            'Customer Support Representative',
            'Accountant',
            'Administrative Assistant',
        ];

        foreach ($designations as $designation) {
            Designation::firstOrCreate(
                ['name' => $designation],
                ['is_active' => true]
            );
        }

        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'Human Resources Department', 'children' => []],
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'Information Technology Department', 'children' => [
                ['name' => 'IT Support', 'code' => 'ITS', 'description' => 'IT Support & Infrastructure'],
                ['name' => 'Security', 'code' => 'SEC', 'description' => 'Information Security'],
            ]],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Finance Department', 'children' => [
                ['name' => 'Accounting', 'code' => 'ACC', 'description' => 'Accounting Department'],
            ]],
            ['name' => 'Sales', 'code' => 'SAL', 'description' => 'Sales Department', 'children' => [
                ['name' => 'Domestic Sales', 'code' => 'SAL-DOM', 'description' => 'Domestic Sales'],
                ['name' => 'International Sales', 'code' => 'SAL-INT', 'description' => 'International Sales'],
            ]],
            ['name' => 'Marketing', 'code' => 'MKT', 'description' => 'Marketing Department', 'children' => [
                ['name' => 'Digital Marketing', 'code' => 'MKT-DIG', 'description' => 'Digital & Social Media Marketing'],
            ]],
            ['name' => 'Operations', 'code' => 'OPS', 'description' => 'Operations Department', 'children' => [
                ['name' => 'Logistics', 'code' => 'LOG', 'description' => 'Logistics & Distribution'],
                ['name' => 'Procurement', 'code' => 'PROC', 'description' => 'Procurement & Sourcing'],
            ]],
            ['name' => 'Customer Support', 'code' => 'CS', 'description' => 'Customer Support Department', 'children' => []],
            ['name' => 'Engineering', 'code' => 'ENG', 'description' => 'Engineering Department', 'children' => [
                ['name' => 'Frontend Development', 'code' => 'ENG-FE', 'description' => 'Frontend Engineering Team'],
                ['name' => 'Backend Development', 'code' => 'ENG-BE', 'description' => 'Backend Engineering Team'],
                ['name' => 'Quality Assurance', 'code' => 'ENG-QA', 'description' => 'QA & Testing Team'],
            ]],
            ['name' => 'Legal', 'code' => 'LEG', 'description' => 'Legal Department', 'children' => []],
        ];

        foreach ($departments as $deptData) {
            $parent = Department::firstOrCreate(
                ['name' => $deptData['name']],
                [
                    'code' => $deptData['code'],
                    'description' => $deptData['description'],
                    'is_active' => true,
                ]
            );

            if (isset($deptData['children'])) {
                foreach ($deptData['children'] as $childData) {
                    Department::firstOrCreate(
                        ['name' => $childData['name']],
                        [
                            'code' => $childData['code'],
                            'description' => $childData['description'],
                            'parent_id' => $parent->id,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
