<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CallTag;
use App\Models\CallTagFormField;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CallTagSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing to avoid duplicates
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CallTagFormField::truncate();
        CallTag::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = Carbon::now();

        $tags = [
            'Sales Enquiry' => [
                'Product Enquiry' => [
                    'Sale Made_Default', 'Sale Made_With Cross Sell', 'Sale Made_With GS', 
                    'No Sale_High Pricing', 'No Sale_No Inventory', 'No Sale_No Money', 
                    'No Sale_High TAT', 'No Sale_Already Bought', 'No Sale_Disconnected', 
                    'No Sale_Offer Not Available', 'No Sale_No Requirement', 'No Sale_Unsure', 
                    'No Sale_Shipping Charges', 'No Sale_Follow Up', 'No Sale_SKU Disabled', 
                    'No Sale_Lead Created', 'No Sale_Lower Order Value', 'No Sale_Order Via India Post', 
                    'No Sale_Single Variety Demanded', 'No Sale_Non Serviceable Area', 
                    'No Sale_Alternate Number Not Available', 'No Sale_New Product', 
                    'Store_Sales_Made_Default', 'Store_No Sale_No Requirement', 
                    'Store_No Sale_Product Not Available', 'Store_No Sale_Credit Required', 
                    'Store_No Sale_High Price', 'Store_No Sale_Only Agronomy Info', 
                    'Store_No Sale_Only Inquiry', 'Store_No Sale_Store Locator And Benefits', 
                    'Store_No Sale_Due To AgroStar Point Redemption'
                ],
                'Growth Related Enquiry' => [],
                'Pest & Diseases Enquiry' => [],
                'New Product Enquiry' => []
            ],
            'Service' => [
                'Not Reachable / Busy / Call Back' => [],
                'Call Transfer' => [],
                'Order Related' => [],
                'Order Tracking' => [],
                'Order Edit' => [],
                'Order Cancel' => [],
                'Share Order Details' => [],
                'Delivery Status' => [],
                'Other Inquiry' => [],
                'Random Call' => [],
                'AgroStar Point Related' => [],
                'FO/LMD Related' => [],
                'About Company' => [],
                'App Related Help' => [],
                'Repeat Call' => [],
                'News/Weather' => [],
                'UPI Payment' => [],
                'Offer Related' => [],
                'Profile Verification' => [],
                'Other Farmers Profile' => [],
                'Enter Profile By Mistake' => [],
                'Profile Editing' => [],
                'Farmer Profile Verification' => [],
                'LMD Returns' => [],
                'Reject & Hold' => [],
                'Reject & Deliver' => [],
                'Accept' => [],
                'Pending For Action' => []
            ],
            'Referral' => [
                'Farmer B Related' => [],
                'Referral Code Addition' => [],
                'Referral / KSY Related' => [],
                'Referral Points Inquiry' => [],
                'Referral Scheme Inquiry' => [],
                'Farmer B Addition' => [],
                'What\'s My Referral Code' => [],
                'Did Not Receive Referral Gift' => [],
                'Asking About Farmer B Order' => [],
                'Referral Order Placed' => [],
                'Commission Inquiry' => [],
                'Commission Not Received' => [],
                'Referral Follow Up Call' => []
            ],
            'Complaint Related' => [
                'Product Level Complaint' => [],
                'Delivery Related Complaint' => [],
                'LP Related Complaint' => [],
                'UPI Related Complaint' => [],
                'Agronomy Complaint' => []
            ],
            'PoP Advisory' => []
        ];

        $l1Sort = 1;
        foreach ($tags as $l1Name => $l2Tags) {
            $l1 = CallTag::create(['name' => $l1Name, 'level' => 1, 'sort_order' => $l1Sort++]);
            
            $l2Sort = 1;
            foreach ($l2Tags as $l2Name => $l3Tags) {
                $l2 = CallTag::create(['name' => $l2Name, 'parent_id' => $l1->id, 'level' => 2, 'sort_order' => $l2Sort++]);
                
                $l3Sort = 1;
                foreach ($l3Tags as $l3Name) {
                    CallTag::create(['name' => $l3Name, 'parent_id' => $l2->id, 'level' => 3, 'sort_order' => $l3Sort++]);
                }

                // Add dynamic form fields for specific L2s
                if ($l2Name === 'Product Enquiry') {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'search_product', 'label' => 'Search Product', 'type' => 'product_search', 'options' => null, 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'crop', 'label' => 'Crop', 'type' => 'select', 'options' => json_encode(['Wheat', 'Rice', 'Corn', 'Soybean']), 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'multiple_products', 'label' => 'Multiple Products?', 'type' => 'select', 'options' => json_encode(['Yes', 'No']), 'sort_order' => 3, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }
                
                if ($l2Name === 'New Product Enquiry') {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'product_name', 'label' => 'Product Name', 'type' => 'text', 'options' => null, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if ($l2Name === 'Call Transfer') {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'transfer_to', 'label' => 'Transfer To Department/Agent', 'type' => 'agent_search', 'options' => null, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'comments', 'label' => 'Comments', 'type' => 'textarea', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if ($l2Name === 'Not Reachable / Busy / Call Back') {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'reason', 'label' => 'Reason', 'type' => 'select', 'options' => json_encode(['Switched Off', 'Out of Network', 'Ringing but no answer']), 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'callback', 'label' => 'Callback Time', 'type' => 'text', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'comments', 'label' => 'Comments', 'type' => 'textarea', 'options' => null, 'sort_order' => 3, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }
            }
        }
    }
}
