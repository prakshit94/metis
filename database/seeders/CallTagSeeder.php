<?php

namespace Database\Seeders;

use App\Models\CallTag;
use App\Models\CallTagFormField;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CallTagSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CallTagFormField::truncate();
        CallTag::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = Carbon::now();

        $majorCrops = json_encode([
            'Wheat', 'Rice', 'Corn', 'Soybean', 'Cotton', 'Sugarcane',
            'Tomato', 'Onion', 'Potato', 'Chilli', 'Mango', 'Banana',
            'Grapes', 'Apple', 'Pomegranate', 'Groundnut', 'Mustard',
            'Cabbage', 'Cauliflower', 'Brinjal', 'Okra (Ladyfinger)',
        ]);

        $tags = [
            'Sales & New Orders' => [
                'Product Inquiry' => ['Resolved - Sale Made', 'Resolved - No Sale due to Price', 'Resolved - No Sale due to Stock', 'Resolved - Follow Up Required', 'Lead Created'],
                'Store Visit Inquiry' => ['Resolved - Store Sale Made', 'Resolved - Store Visit Only', 'Resolved - Needs Credit'],
                'New Product Inquiry' => ['Details Provided', 'Product Not Available'],
                'Company Information' => ['Company Profile Shared'],
            ],
            'Order Management' => [
                'Order Tracking' => ['Order is On Track', 'Order is Delayed', 'Logistics Issue Escalated'],
                'Order Modification' => ['Order Edited Successfully', 'Cannot Edit - Dispatched'],
                'Order Cancellation' => ['Order Cancelled Successfully', 'Cannot Cancel - Dispatched', 'Cancellation Rejected'],
                'Share Order Details' => ['Sent via SMS/WhatsApp', 'Shared on Call'],
                'General Order Inquiry' => ['Information Provided', 'Ticket Raised'],
            ],
            'Delivery & Fulfillment' => [
                'Delivery Status Update' => ['Delivery Rescheduled', 'Contacted Logistics Partner', 'Customer Refused Delivery'],
                'Delivery Rejection' => ['Order Rejected - On Hold', 'Order Rejected - Return to Origin', 'Partial Rejection'],
                'Delivery Acceptance' => ['Order Accepted Successfully', 'Accepted with Modifications'],
                'LMD (Last Mile) Returns' => ['Return Approved', 'Return Rejected', 'FO/LMD Issue Resolved'],
            ],
            'Payments & Offers' => [
                'UPI Payments' => ['Payment Link Sent', 'Payment Confirmed', 'Payment Failure Escalated'],
                'Reward Points' => ['Points Balance Shared', 'Points Redemption Explained', 'Discrepancy Escalated'],
                'Offers & Coupons' => ['Offer Explained', 'Offer Applied', 'Offer Not Applicable'],
            ],
            'Crop Advisory & Agronomy' => [
                'Pest Identification' => ['Identified Issue - Product Recommended', 'Unable to Identify', 'Escalated to Senior'],
                'Disease Identification' => ['Identified Issue', 'Unable to Identify', 'Escalated to Senior'],
                'Growth Advisory' => ['Provided Growth Advisory', 'Recommended Product', 'Farmer Not Interested'],
                'Farming Practices (PoP)' => ['Sowing Advisory Provided', 'Fertilizer Advisory Provided', 'Pesticide Advisory Provided', 'Irrigation Advisory Provided', 'Harvesting Advisory Provided'],
            ],
            'Referrals & Farmer Network' => [
                'Referral Code Addition' => ['Code Added Successfully', 'Code Invalid/Expired'],
                'Farmer Network Addition' => ['Added Farmer Successfully', 'Failed to Add Farmer'],
                'Referral Points/Commission' => ['Points Balance Shared', 'Commission Details Shared', 'Commission Not Received - Escalated'],
                'Referral Scheme Info (KSY)' => ['Scheme Details Explained'],
                'Referral Gifts' => ['Gift Dispatched', 'Issue Escalated'],
                'Referral Follow Up' => ['Customer Interested', 'Customer Not Interested'],
            ],
            'Account & Profile Help' => [
                'Profile Verification' => ['Verified Successfully', 'Verification Failed', 'Pending Document Upload'],
                'Profile Editing' => ['Profile Edited Successfully', 'Unable to Edit'],
                'App Login/Navigation' => ['App Navigation Explained', 'Helped with Login/OTP'],
                'Other Farmer\'s Profile' => ['Information Provided', 'Directed to Correct Section'],
            ],
            'Complaints & Escalations' => [
                'Product Complaint' => ['Complaint Logged', 'Resolved on Call', 'Escalated for Refund/Replacement'],
                'Delivery Complaint' => ['Complaint Logged', 'Resolved on Call', 'Escalated to Logistics Partner'],
                'Payment Complaint' => ['Complaint Logged', 'Escalated to Finance'],
                'Agronomy Complaint' => ['Complaint Logged', 'Escalated to Senior Agronomist'],
            ],
            'Feedback & Feature Requests' => [
                'App Experience Feedback' => ['Feedback Logged', 'Forwarded to Product Team'],
                'Product Quality Feedback' => ['Feedback Logged', 'Forwarded to Quality Team'],
                'Delivery Service Feedback' => ['Feedback Logged'],
                'Customer Support Feedback' => ['Feedback Logged'],
                'Feature Request' => ['Request Logged', 'Forwarded to Product'],
            ],
            'General & Connectivity' => [
                'Call Connectivity' => ['Customer Not Reachable - Call Back', 'Number Out of Service', 'Wrong Number', 'Call Disconnected'],
                'Call Transfer' => ['Transferred to Agronomy', 'Transferred to Sales', 'Transferred to Escalations'],
                'Spam / Invalid Call' => ['Spam Call', 'Prank Call', 'Silent Call', 'Entered Profile by Mistake'],
                'General Information' => ['News Shared', 'Weather Info Provided'],
            ],
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

                // Dynamic Form Field Logic Map
                if (in_array($l2Name, ['Product Inquiry', 'Store Visit Inquiry'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'search_product', 'label' => 'Search Product', 'type' => 'product_search', 'options' => null, 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'crop', 'label' => 'Crop (Multi-Select)', 'type' => 'multi_select', 'options' => $majorCrops, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if ($l2Name === 'New Product Inquiry') {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'product_name', 'label' => 'Product Name', 'type' => 'text', 'options' => null, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (in_array($l2Name, ['Pest Identification', 'Disease Identification'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'crop', 'label' => 'Affected Crop(s)', 'type' => 'multi_select', 'options' => $majorCrops, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'affected_part', 'label' => 'Affected Plant Part', 'type' => 'select', 'options' => json_encode(['Leaves', 'Stem', 'Roots', 'Fruit/Flower', 'Whole Plant']), 'sort_order' => 2, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'symptoms', 'label' => 'Symptoms', 'type' => 'textarea', 'options' => null, 'sort_order' => 3, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (in_array($l2Name, ['Farming Practices (PoP)', 'Growth Advisory'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'crop', 'label' => 'Crop Discussed', 'type' => 'multi_select', 'options' => $majorCrops, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'advisory_notes', 'label' => 'Advisory Details Provided', 'type' => 'textarea', 'options' => null, 'sort_order' => 2, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if ($l2Name === 'Call Connectivity') {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'callback', 'label' => 'Callback Schedule (If applicable)', 'type' => 'datetime-local', 'options' => null, 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if ($l2Name === 'Call Transfer') {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'transfer_to', 'label' => 'Transfer To Agent (Optional)', 'type' => 'agent_search', 'options' => null, 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (in_array($l1Name, ['Order Management', 'Delivery & Fulfillment'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'order_number', 'label' => 'Order Number', 'type' => 'text', 'options' => null, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'awb_number', 'label' => 'AWB / Tracking Number (If applicable)', 'type' => 'text', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'action_requested', 'label' => 'Notes', 'type' => 'textarea', 'options' => null, 'sort_order' => 3, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (in_array($l1Name, ['Payments & Offers'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'transaction_id', 'label' => 'Transaction/Order ID (If applicable)', 'type' => 'text', 'options' => null, 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (in_array($l1Name, ['Complaints & Escalations'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'order_number', 'label' => 'Related Order Number (If any)', 'type' => 'text', 'options' => null, 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'description', 'label' => 'Complaint Description', 'type' => 'textarea', 'options' => null, 'sort_order' => 2, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (in_array($l1Name, ['Feedback & Feature Requests'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'feedback_rating', 'label' => 'Rating (1-5)', 'type' => 'select', 'options' => json_encode(['1 - Very Poor', '2 - Poor', '3 - Average', '4 - Good', '5 - Excellent']), 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'feedback_details', 'label' => 'Feedback Details', 'type' => 'textarea', 'options' => null, 'sort_order' => 2, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (in_array($l1Name, ['Referrals & Farmer Network'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'farmer_b_mobile', 'label' => 'Referred Farmer Mobile', 'type' => 'text', 'options' => null, 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'referral_code', 'label' => 'Referral Code', 'type' => 'text', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }
            }
        }
    }
}
