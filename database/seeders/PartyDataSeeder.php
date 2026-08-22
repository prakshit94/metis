<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartyDataSeeder extends Seeder
{
    public function run(): void
    {
        $villId = DB::table('villages')->first()?->id;

        // ─── Customers (Farmers) ───────────────────────────────────────────
        $customers = [
            [
                'firstname'    => 'Ramesh',
                'middlename'   => 'Kumar',
                'lastname'     => 'Patil',
                'phone'        => '9876501001',
                'email'        => 'ramesh.patil@example.com',
                'gst_no'       => null,
                'pan_no'       => 'BZZPA1234C',
                'credit_limit' => 25000.00,
                'credit_days'  => 30,
                'land_area'    => 5.50,
                'land_unit'    => 'acre',
                'crops'        => ['Cotton', 'Wheat'],
                'irrigation_type' => ['Drip', 'Canal'],
                'category'     => 'individual',
                'city'         => 'Akola',
                'state'        => 'Maharashtra',
                'pincode'      => '444001',
                'address'      => 'Plot 45, Near Shiv Mandir, Akola',
            ],
            [
                'firstname'    => 'Suresh',
                'middlename'   => null,
                'lastname'     => 'Sharma',
                'phone'        => '9876501002',
                'email'        => 'suresh.sharma@example.com',
                'gst_no'       => null,
                'pan_no'       => 'CZZPB5678D',
                'credit_limit' => 15000.00,
                'credit_days'  => 15,
                'land_area'    => 3.00,
                'land_unit'    => 'acre',
                'crops'        => ['Rice/Paddy', 'Maize'],
                'irrigation_type' => ['Tube Well', 'Sprinkler'],
                'category'     => 'individual',
                'city'         => 'Nagpur',
                'state'        => 'Maharashtra',
                'pincode'      => '440001',
                'address'      => 'Survey No. 88, Village Kawatha, Nagpur',
            ],
            [
                'firstname'    => 'Pratibha',
                'middlename'   => 'Ashok',
                'lastname'     => 'Deshmukh',
                'phone'        => '9876501003',
                'email'        => 'pratibha.deshmukh@example.com',
                'gst_no'       => null,
                'pan_no'       => 'DZZPC9012E',
                'credit_limit' => 50000.00,
                'credit_days'  => 45,
                'land_area'    => 12.00,
                'land_unit'    => 'acre',
                'crops'        => ['Sugarcane', 'Onion'],
                'irrigation_type' => ['Drip'],
                'category'     => 'individual',
                'city'         => 'Pune',
                'state'        => 'Maharashtra',
                'pincode'      => '411001',
                'address'      => 'Gat No. 102, Village Nhavi, Haveli, Pune',
            ],
            [
                'firstname'    => 'Mohan',
                'middlename'   => null,
                'lastname'     => 'Reddy',
                'phone'        => '9876501004',
                'email'        => 'mohan.reddy@example.com',
                'gst_no'       => null,
                'pan_no'       => 'EZZPD3456F',
                'credit_limit' => 30000.00,
                'credit_days'  => 30,
                'land_area'    => 8.20,
                'land_unit'    => 'acre',
                'crops'        => ['Maize', 'Soybean'],
                'irrigation_type' => ['Rainfed', 'Canal'],
                'category'     => 'individual',
                'city'         => 'Guntur',
                'state'        => 'Andhra Pradesh',
                'pincode'      => '522001',
                'address'      => 'Near Gram Panchayat Office, Village Kakani, Guntur',
            ],
            [
                'firstname'    => 'Balvinder',
                'middlename'   => 'Singh',
                'lastname'     => 'Dhillon',
                'phone'        => '9876501005',
                'email'        => 'balvinder.dhillon@example.com',
                'gst_no'       => null,
                'pan_no'       => 'FZZPE7890G',
                'credit_limit' => 100000.00,
                'credit_days'  => 60,
                'land_area'    => 25.00,
                'land_unit'    => 'acre',
                'crops'        => ['Wheat', 'Rice/Paddy', 'Mustard'],
                'irrigation_type' => ['Tube Well'],
                'category'     => 'individual',
                'city'         => 'Amritsar',
                'state'        => 'Punjab',
                'pincode'      => '143001',
                'address'      => 'Kila No. 12, Village Jandiala Guru, Amritsar',
            ],
        ];

        foreach ($customers as $c) {
            $partyCode = 'CUST-' . strtoupper(Str::random(6));
            $partyId = DB::table('parties')->insertGetId([
                'uuid'             => Str::uuid()->toString(),
                'party_code'       => $partyCode,
                'type'             => 'customer',
                'firstname'        => $c['firstname'],
                'middlename'       => $c['middlename'] ?? null,
                'lastname'         => $c['lastname'],
                'phone'            => $c['phone'],
                'email'            => $c['email'],
                'gst_no'           => $c['gst_no'] ?? null,
                'pan_no'           => $c['pan_no'],
                'credit_limit'     => $c['credit_limit'],
                'credit_days'      => $c['credit_days'],
                'land_area'        => $c['land_area'],
                'land_unit'        => $c['land_unit'],
                'crops'            => json_encode($c['crops']),
                'irrigation_type'  => json_encode($c['irrigation_type']),
                'category'         => $c['category'],
                'company_name'     => null,
                'outstanding_balance' => 0,
                'orders_count'     => 0,
                'is_active'        => true,
                'status'           => 'active',
                'account_type_id'  => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::table('party_addresses')->insert([
                'party_id'       => $partyId,
                'label'          => 'Primary',
                'address_line_1' => $c['address'],
                'address_line_2' => null,
                'village_id'     => $villId,
                'city'           => $c['city'],
                'state'          => $c['state'],
                'pincode'        => $c['pincode'],
                'is_default'     => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        // ─── Suppliers ─────────────────────────────────────────────────────
        $suppliers = [
            [
                'firstname'    => 'Rajendra',
                'lastname'     => 'Agrawal',
                'company_name' => 'UPL Limited - Authorized Distributor',
                'phone'        => '9876502001',
                'email'        => 'rajendra.agrawal@upldistrib.com',
                'gst_no'       => '27AABCU1234A1Z5',
                'pan_no'       => 'AABCU1234F',
                'credit_limit' => 500000.00,
                'credit_days'  => 60,
                'city'         => 'Mumbai',
                'state'        => 'Maharashtra',
                'pincode'      => '400001',
                'address'      => 'UPL House, 610 Maker Chambers V, Nariman Point, Mumbai',
            ],
            [
                'firstname'    => 'Sanjay',
                'lastname'     => 'Mehta',
                'company_name' => 'Syngenta India Limited',
                'phone'        => '9876502002',
                'email'        => 'sanjay.mehta@syngenta-agri.com',
                'gst_no'       => '27AABCS5678A1Z2',
                'pan_no'       => 'AABCS5678G',
                'credit_limit' => 750000.00,
                'credit_days'  => 45,
                'city'         => 'Pune',
                'state'        => 'Maharashtra',
                'pincode'      => '411001',
                'address'      => 'Syngenta India Pvt. Ltd., Survey No. 3203, Aman Court, Baner, Pune',
            ],
        ];

        foreach ($suppliers as $s) {
            $partyCode = 'SUPP-' . strtoupper(Str::random(6));
            DB::table('suppliers')->insert([
                'uuid'             => Str::uuid()->toString(),
                'party_code'       => $partyCode,
                'firstname'        => $s['firstname'],
                'lastname'         => $s['lastname'],
                'phone'            => $s['phone'],
                'email'            => $s['email'],
                'gst_no'           => $s['gst_no'],
                'pan_no'           => $s['pan_no'],
                'company_name'     => $s['company_name'],
                'credit_limit'     => $s['credit_limit'],
                'credit_days'      => $s['credit_days'],
                'is_active'        => true,
                'status'           => 'active',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }
}
