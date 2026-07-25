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

        // Advanced Indian Crop List
        $majorCrops = json_encode([
            'Wheat', 'Rice', 'Corn', 'Soybean', 'Cotton', 'Sugarcane', 
            'Tomato', 'Onion', 'Potato', 'Chilli', 'Mango', 'Banana', 
            'Grapes', 'Apple', 'Pomegranate', 'Groundnut', 'Mustard', 
            'Cabbage', 'Cauliflower', 'Brinjal', 'Okra (Ladyfinger)'
        ]);

        $tags = [
            'Sales Enquiry' => [
                'Product Enquiry' => [
                    'Sale Made - Default', 'Sale Made - With Cross Sell', 'Sale Made - With GS', 
                    'No Sale - High Pricing', 'No Sale - No Inventory', 'No Sale - No Money', 
                    'No Sale - High TAT', 'No Sale - Already Bought', 'No Sale - Disconnected', 
                    'No Sale - Offer Not Available', 'No Sale - No Requirement', 'No Sale - Unsure', 
                    'No Sale - Shipping Charges', 'No Sale - Follow Up', 'No Sale - SKU Disabled', 
                    'No Sale - Lead Created', 'No Sale - Lower Order Value', 'No Sale - Order Via India Post', 
                    'No Sale - Single Variety Demanded', 'No Sale - Non Serviceable Area', 
                    'No Sale - Alternate Number Not Available', 'No Sale - New Product', 
                    'Store Sale Made - Default', 'Store No Sale - No Requirement', 
                    'Store No Sale - Product Not Available', 'Store No Sale - Credit Required', 
                    'Store No Sale - High Price', 'Store No Sale - Only Agronomy Info', 
                    'Store No Sale - Only Inquiry', 'Store No Sale - Store Locator And Benefits', 
                    'Store No Sale - Due To Points Redemption'
                ],
                'Growth Related Enquiry' => [
                    'Provided Advisory', 'Recommended Product', 'No Relevant Product Available', 'Farmer Not Interested'
                ],
                'Pest & Diseases Enquiry' => [
                    'Identified Issue - Product Recommended', 'Identified Issue - No Product Available', 
                    'Unable to Identify Issue', 'Escalated to Senior Agronomist'
                ],
                'New Product Enquiry' => [
                    'Details Provided - Sale Made', 'Details Provided - No Sale', 'Product Not Yet Launched'
                ]
            ],
            'Service' => [
                'Not Reachable / Busy / Call Back' => [
                    'Will Call Back Later', 'Number Out of Service', 'Wrong Number', 'Call Disconnected'
                ],
                'Call Transfer' => [
                    'Transferred to Agronomy Team', 'Transferred to Sales Team', 'Transferred to Escalations'
                ],
                'Order Related' => [
                    'Information Provided', 'Issue Resolved', 'Ticket Raised'
                ],
                'Order Tracking' => [
                    'Status Provided - On Track', 'Status Provided - Delayed', 'Logistics Issue Escalated'
                ],
                'Order Edit' => [
                    'Order Edited Successfully', 'Cannot Edit - Order Already Dispatched', 'Customer Changed Mind'
                ],
                'Order Cancel' => [
                    'Order Cancelled Successfully', 'Cannot Cancel - Order Already Dispatched', 'Cancellation Rejected'
                ],
                'Share Order Details' => [
                    'Details Sent via SMS/WhatsApp', 'Details Read on Call'
                ],
                'Delivery Status' => [
                    'Delivery Rescheduled', 'Contacted Logistics Partner', 'Customer Refused Delivery'
                ],
                'Other Inquiry' => [
                    'Information Provided', 'Follow Up Required', 'Issue Escalated to Relevant Team'
                ],
                'Random Call' => [
                    'Spam / Prank Call', 'Wrong Department', 'Silent Call', 'Test Call'
                ],
                'Reward Point Related' => [
                    'Points Balance Shared', 'Points Redemption Explained', 'Points Discrepancy Escalated'
                ],
                'FO/LMD Related' => [
                    'LMD Issue Resolved', 'LMD Escalation Logged'
                ],
                'About Company' => [
                    'Company Profile Shared'
                ],
                'App Related Help' => [
                    'App Navigation Explained', 'Bug / Issue Logged', 'Helped with Login/OTP'
                ],
                'Repeat Call' => [
                    'Follow Up on Open Ticket', 'Customer Impatient'
                ],
                'News/Weather' => [
                    'Weather Info Provided', 'News Shared'
                ],
                'UPI Payment' => [
                    'Payment Link Sent', 'Payment Confirmed', 'Payment Failure Escalated'
                ],
                'Offer Related' => [
                    'Offer Explained', 'Offer Applied to Order', 'Offer Not Applicable'
                ],
                'Profile Verification' => [
                    'Verified Successfully', 'Verification Failed', 'Pending Document Upload'
                ],
                'Other Farmers Profile' => [
                    'Information Provided', 'Directed to Correct App Section'
                ],
                'Enter Profile By Mistake' => [
                    'Corrected Profile Selection', 'Call Ignored / Dropped'
                ],
                'Profile Editing' => [
                    'Profile Edited Successfully', 'Unable to Edit'
                ],
                'Farmer Profile Verification' => [
                    'Farmer Verified', 'Farmer Verification Failed'
                ],
                'LMD Returns' => [
                    'Return Approved', 'Return Rejected'
                ],
                'Reject & Hold' => [
                    'Order Rejected - Placed on Hold', 'Customer Requested Temporary Hold'
                ],
                'Reject & Deliver' => [
                    'Order Rejected - Delivery Attempted', 'Partial Rejection by Customer'
                ],
                'Accept' => [
                    'Order Accepted Successfully', 'Accepted with Modifications'
                ],
                'Pending For Action' => [
                    'Waiting on Customer Input', 'Waiting on Backend Processing', 'Pending Manager Approval'
                ]
            ],
            'Referral' => [
                'Farmer B Related' => [
                    'Query Resolved', 'Ticket Raised'
                ],
                'Referral Code Addition' => [
                    'Code Added Successfully', 'Code Invalid/Expired'
                ],
                'Referral / KSY Related' => [
                    'Information Provided'
                ],
                'Referral Points Inquiry' => [
                    'Points Balance Shared', 'Discrepancy Escalated'
                ],
                'Referral Scheme Inquiry' => [
                    'Scheme Details Explained'
                ],
                'Farmer B Addition' => [
                    'Added Successfully', 'Failed to Add'
                ],
                'What\'s My Referral Code' => [
                    'Code Sent via SMS', 'Code Shared on Call'
                ],
                'Did Not Receive Referral Gift' => [
                    'Gift Dispatched', 'Issue Escalated'
                ],
                'Asking About Farmer B Order' => [
                    'Status Shared'
                ],
                'Referral Order Placed' => [
                    'Confirmed Order'
                ],
                'Commission Inquiry' => [
                    'Commission Details Shared'
                ],
                'Commission Not Received' => [
                    'Escalated to Finance', 'Resolved'
                ],
                'Referral Follow Up Call' => [
                    'Customer Interested', 'Customer Not Interested'
                ]
            ],
            'Complaint Related' => [
                'Product Level Complaint' => [
                    'Complaint Logged', 'Resolved on Call', 'Invalid Complaint', 'Escalated for Refund/Replacement'
                ],
                'Delivery Related Complaint' => [
                    'Complaint Logged', 'Resolved on Call', 'Invalid Complaint', 'Escalated to LP'
                ],
                'LP Related Complaint' => [
                    'Complaint Logged', 'Escalated to Logistics Team'
                ],
                'UPI Related Complaint' => [
                    'Complaint Logged', 'Escalated to Finance'
                ],
                'Agronomy Complaint' => [
                    'Complaint Logged', 'Escalated to Senior Agronomist'
                ]
            ],
            'PoP Advisory' => [
                'Sowing Information' => [
                    'Advisory Provided Successfully', 'Follow Up Required'
                ],
                'Fertilizer Management' => [
                    'Advisory Provided Successfully', 'Follow Up Required'
                ],
                'Pesticide Application' => [
                    'Advisory Provided Successfully', 'Follow Up Required'
                ],
                'Irrigation Schedule' => [
                    'Advisory Provided Successfully', 'Follow Up Required'
                ],
                'Weather Advisory' => [
                    'Advisory Provided Successfully', 'Follow Up Required'
                ],
                'Harvesting Guidance' => [
                    'Advisory Provided Successfully', 'Follow Up Required'
                ]
            ],
            'Feedback & Suggestions' => [
                'App Experience Feedback' => [
                    'Feedback Logged', 'Forwarded to Product Team'
                ],
                'Product Quality Feedback' => [
                    'Feedback Logged', 'Forwarded to Quality Team'
                ],
                'Delivery Service Feedback' => [
                    'Feedback Logged', 'Forwarded to Logistics Team'
                ],
                'Customer Support Feedback' => [
                    'Feedback Logged', 'Forwarded to QA Team'
                ],
                'Feature Request' => [
                    'Feature Request Logged', 'Forwarded to Product Team'
                ]
            ]
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
                        ['call_tag_id' => $l2->id, 'name' => 'crop', 'label' => 'Crop (Multi-Select)', 'type' => 'multi_select', 'options' => $majorCrops, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }
                
                if ($l2Name === 'New Product Enquiry') {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'product_name', 'label' => 'Product Name', 'type' => 'text', 'options' => null, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'remarks', 'label' => 'Remarks', 'type' => 'textarea', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if ($l2Name === 'Pest & Diseases Enquiry') {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'crop', 'label' => 'Affected Crop(s)', 'type' => 'multi_select', 'options' => $majorCrops, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'affected_part', 'label' => 'Affected Plant Part', 'type' => 'select', 'options' => json_encode(['Leaves', 'Stem', 'Roots', 'Fruit/Flower', 'Whole Plant']), 'sort_order' => 2, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'symptoms', 'label' => 'Disease Symptoms', 'type' => 'textarea', 'options' => null, 'sort_order' => 3, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
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
                        ['call_tag_id' => $l2->id, 'name' => 'callback', 'label' => 'Callback Schedule', 'type' => 'datetime-local', 'options' => null, 'sort_order' => 2, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'comments', 'label' => 'Comments', 'type' => 'textarea', 'options' => null, 'sort_order' => 3, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (in_array($l2Name, ['Order Related', 'Order Tracking', 'Order Edit', 'Order Cancel', 'Share Order Details'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'order_number', 'label' => 'Order Number', 'type' => 'text', 'options' => null, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'action_requested', 'label' => 'Action Requested', 'type' => 'textarea', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (in_array($l1Name, ['PoP Advisory'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'crop', 'label' => 'Crop Discussed', 'type' => 'multi_select', 'options' => $majorCrops, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'advisory_notes', 'label' => 'Advisory Details Provided', 'type' => 'textarea', 'options' => null, 'sort_order' => 2, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }
                
                if (in_array($l1Name, ['Feedback & Suggestions'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'feedback_rating', 'label' => 'Rating (1-5)', 'type' => 'select', 'options' => json_encode(['1 - Very Poor', '2 - Poor', '3 - Average', '4 - Good', '5 - Excellent']), 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'feedback_details', 'label' => 'Feedback Details', 'type' => 'textarea', 'options' => null, 'sort_order' => 2, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (in_array($l2Name, ['Product Level Complaint', 'Delivery Related Complaint', 'LP Related Complaint', 'UPI Related Complaint', 'Agronomy Complaint'])) {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'complaint_category', 'label' => 'Complaint Category', 'type' => 'select', 'options' => json_encode(['Quality Issue', 'Missing Item', 'Damaged Item', 'Delay in Delivery', 'Wrong Item', 'Payment Failure', 'Other']), 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'order_number', 'label' => 'Related Order Number', 'type' => 'text', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'description', 'label' => 'Complaint Description', 'type' => 'textarea', 'options' => null, 'sort_order' => 3, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if ($l2Name === 'Delivery Status') {
                    CallTagFormField::insert([
                        ['call_tag_id' => $l2->id, 'name' => 'order_number', 'label' => 'Order Number', 'type' => 'text', 'options' => null, 'sort_order' => 1, 'is_required' => true, 'created_at' => $now, 'updated_at' => $now],
                        ['call_tag_id' => $l2->id, 'name' => 'awb_number', 'label' => 'AWB / Tracking Number', 'type' => 'text', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                    ]);
                }

                if (str_contains($l1Name, 'Referral') || str_contains($l2Name, 'Referral')) {
                    if (!in_array($l2Name, ['Commission Inquiry', 'Commission Not Received'])) { // Example optimization
                        CallTagFormField::insert([
                            ['call_tag_id' => $l2->id, 'name' => 'farmer_b_mobile', 'label' => 'Referred Farmer Mobile', 'type' => 'text', 'options' => null, 'sort_order' => 1, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                            ['call_tag_id' => $l2->id, 'name' => 'referral_code', 'label' => 'Referral Code', 'type' => 'text', 'options' => null, 'sort_order' => 2, 'is_required' => false, 'created_at' => $now, 'updated_at' => $now],
                        ]);
                    }
                }
            }
        }
    }
}
