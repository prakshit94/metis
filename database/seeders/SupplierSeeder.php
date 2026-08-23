<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('en_IN');
        $suppliers = [];

        for ($i = 0; $i < 15; $i++) {
            $suppliers[] = [
                'uuid' => (string) Str::uuid(),
                'party_code' => 'SUP-' . str_pad((string)($i + 1), 4, '0', STR_PAD_LEFT),
                'firstname' => $faker->firstName,
                'lastname' => $faker->lastName,
                'email' => $faker->unique()->companyEmail,
                'phone' => '9' . $faker->numerify('#########'),
                'company_name' => $faker->company,
                'gst_no' => '27' . strtoupper(Str::random(10)) . '1Z' . strtoupper(Str::random(1)),
                'pan_no' => strtoupper(Str::random(10)),
                'credit_limit' => $faker->randomElement([50000, 100000, 250000, 500000]),
                'credit_days' => $faker->randomElement([15, 30, 45, 60]),
                'status' => 'active',
                'is_active' => true,
                'internal_notes' => 'Seeded test supplier',
                'address_line_1' => $faker->streetAddress,
                'address_line_2' => $faker->streetName,
                'village_id' => 1,
                'village_name' => $faker->citySuffix,
                'post_office' => $faker->city,
                'taluka' => $faker->city,
                'district' => $faker->city,
                'city' => $faker->city,
                'state' => 'Maharashtra',
                'pincode' => $faker->postcode,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('suppliers')->insert($suppliers);
    }
}
