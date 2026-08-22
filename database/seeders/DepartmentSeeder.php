<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Management', 'code' => 'MGT', 'description' => 'Executive Management'],
            ['name' => 'Human Resources', 'code' => 'HR', 'description' => 'HR and Admin'],
            ['name' => 'Sales', 'code' => 'SAL', 'description' => 'Sales and Field Operations'],
            ['name' => 'Procurement', 'code' => 'PRO', 'description' => 'Purchasing and Vendor Relations'],
            ['name' => 'Inventory', 'code' => 'INV', 'description' => 'Warehouse and Stock Management'],
            ['name' => 'Finance', 'code' => 'FIN', 'description' => 'Accounts and Billing'],
            ['name' => 'Information Technology', 'code' => 'IT', 'description' => 'IT Support and Engineering'],
        ];

        $now = now();
        $insertData = array_map(function ($dept) use ($now) {
            return array_merge($dept, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $departments);

        DB::table('departments')->insert($insertData);
    }
}
