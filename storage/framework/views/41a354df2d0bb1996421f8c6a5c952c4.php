<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <title>Delivery Challan - <?php echo e($invoice->invoice_no); ?></title>
<style>
@page {
   size: A4 portrait;
   margin: 8mm 8mm;
}

body {
   font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
   font-size: 8.7px;
   color: #334155;
   line-height: 1.15;
   background: #ffffff;
}

table {
   width: 100%;
   border-collapse: collapse;
   page-break-inside: auto;
   border: 1px solid #cbd5e1;
}

tr {
   page-break-inside: avoid;
}

th,
td {
   border: 1px solid #cbd5e1;
   padding: 3px;
   vertical-align: top;
}

.text-right { text-align: right; }
.text-center { text-align: center; }
.bold { font-weight: bold; }

/* 🔥 TITLE (MATCH BULK) */
.title {
   text-align: center;
   font-size: 13px;
   font-weight: bold;
   padding: 4px;
   border: 2px solid #1e40af;
   background: #eff6ff;
   color: #1e40af;
   margin-bottom: 5px;
   letter-spacing: 1px;
   border-radius: 4px;
}

/* 🔥 COMPANY */
.company-name {
   font-size: 12px;
   font-weight: bold;
   color: #1e40af;
}

.muted {
   color: #475569;
}

/* 🔥 HEADER TABLE LOOK */
.header-table td {
   border: 1px solid #cbd5e1;
   background: #f8fafc;
}

/* 🔥 ADDRESS LABELS */
.label {
   width: 38%;
   font-weight: bold;
   color: #1e293b;
   white-space: nowrap;
}

/* 🔥 REMOVE EXTRA SPACE */
.no-border td {
   border: none;
   padding: 1px 2px;
}

/* 🔥 ITEMS HEADER (BLUE BAR) */
.items thead th {
   background: #1e40af;
   color: #ffffff;
   text-align: center;
   border-color: #1e40af;
}

/* 🔥 ITEMS ROWS */
.items td {
   padding: 3px 2px;
}

/* 🔥 TOTAL SECTION */
.totals td {
   padding: 3px;
   background: #f8fafc;
}

/* 🔥 GRAND TOTAL HIGHLIGHT */
.grand-total {
   font-size: 10px;
   font-weight: bold;
   background: #e2e8f0;
   color: #0f172a;
}

/* 🔥 TERMS BOX */
.terms td {
   background: #f8fafc;
}

.terms {
   font-size: 8.5px;
   line-height: 1.2;
}

/* 🔥 SMALL UI IMPROVEMENT */
br {
   line-height: 0.8;
}
</style>
   </head>
   <body>
      <div class="title">DELIVERY CHALLAN</div>
      <!-- HEADER -->
      <table class="header-table">
         <tr>
            <td width="60%">
               <div class="company-name"><?php echo e($invoice->order?->warehouse?->company_name ?: 'Krushify Agro Pvt. Ltd.'); ?></div>
               <div class="muted">
                  <strong>Mobile:</strong> <?php echo e($invoice->order?->warehouse?->phone ?: '9199125925'); ?><br>
                  <strong>Address:</strong>
                  <?php if($invoice->order?->warehouse && $invoice->order->warehouse->address_line_1): ?>
                     <?php echo e($invoice->order->warehouse->address_line_1); ?>

                     <?php if($invoice->order->warehouse->address_line_2): ?>, <?php echo e($invoice->order->warehouse->address_line_2); ?><?php endif; ?>
                     , <?php echo e($invoice->order->warehouse->city ?? 'Rajkot'); ?>, <?php echo e($invoice->order->warehouse->state ?? 'Gujarat'); ?> - <?php echo e($invoice->order->warehouse->pincode ?? '360003'); ?><br>
                  <?php else: ?>
                     The One World (B), 1005, Ayodhya Circle<br>
                  <?php endif; ?>
                  <strong>Email:</strong> <?php echo e($invoice->order?->warehouse?->email ?: 'info@krushifyagro.com'); ?><br>
                  <strong>GST:</strong> <?php echo e($invoice->order?->warehouse?->gstin ?: '24AAMCK0386L1Z6'); ?>

               </div>
            </td>
            <td width="40%">
               <strong>Invoice No:</strong> <?php echo e($invoice->invoice_no); ?><br>
               <strong>Order No:</strong> <?php echo e($invoice->order->order_no ?? 'N/A'); ?><br>
               <strong>Dated:</strong> <?php echo e($invoice->invoice_date ? $invoice->invoice_date->format('d-m-Y') : 'N/A'); ?><br>
               <strong>Payment Mode:</strong>
               <?php echo e(ucfirst($invoice->order->payment_method ?? 'Cash')); ?><br>
               <?php if(strtolower($invoice->order->payment_method ?? '') === 'cod'): ?>
               <strong>To Collect:</strong>
               Rs. <?php echo e(number_format($invoice->net_amount, 2)); ?><br>
               <?php endif; ?>
               <br>
               <?php if($invoice->order?->warehouse?->reference_no): ?>
               <strong>Reference No:</strong> <?php echo e($invoice->order->warehouse->reference_no); ?><br>
               <?php endif; ?>
               <?php if($invoice->order?->warehouse?->seed_lic_no): ?>
               Seed Lic No.: <?php echo e($invoice->order->warehouse->seed_lic_no); ?><br>
               <?php endif; ?>
               <?php if($invoice->order?->warehouse?->pesti_lic_no): ?>
               Pesti Lic No.: <?php echo e($invoice->order->warehouse->pesti_lic_no); ?>

               <?php endif; ?>
            </td>
         </tr>
      </table>
      <br>
      <!-- ADDRESSES -->
      <table>
         <tr>
            <th width="50%" align="left">Customer Address</th>
            <th width="50%" align="left">Shipping Address</th>
         </tr>
         <tr>
            <td>
               <table class="no-border">
                  <tr>
                     <td class="label">Name</td>
                     <td>
                        <?php echo e($invoice->order->party->name ?? 'N/A'); ?>

                     </td>
                  </tr>
                  <tr>
                     <td class="label">Mobile</td>
                     <td><?php echo e($invoice->order->party->phone ?? 'N/A'); ?></td>
                  </tr>
                  <?php if($invoice->order->billingAddress): ?>
                  <tr>
                     <td class="label">Address</td>
                     <td><?php echo e($invoice->order->billingAddress->address_line_1); ?></td>
                  </tr>
                  <?php if($invoice->order->billingAddress->address_line_2): ?>
                  <tr>
                     <td></td>
                     <td><?php echo e($invoice->order->billingAddress->address_line_2); ?></td>
                  </tr>
                  <?php endif; ?>
                  <tr>
                     <td class="label">Village</td>
                     <td><?php echo e($invoice->order->billingAddress->village->village_name ?? $invoice->order->billingAddress->city ?? '-'); ?></td>
                  </tr>
                  <tr>
                     <td class="label">Taluka</td>
                     <td><?php echo e($invoice->order->billingAddress->village->taluka_name ?? '-'); ?></td>
                  </tr>
                  <tr>
                     <td class="label">District</td>
                     <td><?php echo e($invoice->order->billingAddress->village->district_name ?? '-'); ?></td>
                  </tr>
                  <tr>
                     <td class="label">Post Office</td>
                     <td><?php echo e($invoice->order->billingAddress->village->post_so_name ?? '-'); ?></td>
                  </tr>
                  <tr>
                     <td class="label">State / PIN</td>
                     <td>
                        <?php echo e($invoice->order->billingAddress->state); ?> -
                        <?php echo e($invoice->order->billingAddress->pincode); ?>

                     </td>
                  </tr>
                  <tr>
                     <td class="label">Country</td>
                     <td><?php echo e($invoice->order->billingAddress->country ?? 'India'); ?></td>
                  </tr>
                  <?php else: ?>
                  <tr>
                     <td colspan="2">N/A</td>
                  </tr>
                  <?php endif; ?>
               </table>
            </td>
            <td>
               <table class="no-border">
                  <tr>
                     <td class="label">Name</td>
                     <td>
                        <?php echo e($invoice->order->party->name ?? 'N/A'); ?>

                     </td>
                  </tr>
                  <tr>
                     <td class="label">Mobile</td>
                     <td><?php echo e($invoice->order->party->phone ?? 'N/A'); ?></td>
                  </tr>
                  <?php if($invoice->order->shippingAddress): ?>
                  <tr>
                     <td class="label">Address</td>
                     <td><?php echo e($invoice->order->shippingAddress->address_line_1); ?></td>
                  </tr>
                  <?php if($invoice->order->shippingAddress->address_line_2): ?>
                  <tr>
                     <td></td>
                     <td><?php echo e($invoice->order->shippingAddress->address_line_2); ?></td>
                  </tr>
                  <?php endif; ?>
                  <tr>
                     <td class="label">Village</td>
                     <td><?php echo e($invoice->order->shippingAddress->village->village_name ?? $invoice->order->shippingAddress->city ?? '-'); ?></td>
                  </tr>
                  <tr>
                     <td class="label">Taluka</td>
                     <td><?php echo e($invoice->order->shippingAddress->village->taluka_name ?? '-'); ?></td>
                  </tr>
                  <tr>
                     <td class="label">District</td>
                     <td><?php echo e($invoice->order->shippingAddress->village->district_name ?? '-'); ?></td>
                  </tr>
                  <tr>
                     <td class="label">Post Office</td>
                     <td><?php echo e($invoice->order->shippingAddress->village->post_so_name ?? '-'); ?></td>
                  </tr>
                  <tr>
                     <td class="label">State / PIN</td>
                     <td>
                        <?php echo e($invoice->order->shippingAddress->state); ?> -
                        <?php echo e($invoice->order->shippingAddress->pincode); ?>

                     </td>
                  </tr>
                  <tr>
                     <td class="label">Country</td>
                     <td><?php echo e($invoice->order->shippingAddress->country ?? 'India'); ?></td>
                  </tr>
                  <?php else: ?>
                  <tr>
                     <td colspan="2">Same as Billing</td>
                  </tr>
                  <?php endif; ?>
               </table>
            </td>
         </tr>
      </table>
      <br>
      <!-- ITEMS -->
<?php
$shippingState = strtolower($invoice->order->shippingAddress->state ?? $invoice->order->billingAddress->state ?? '');
$isInterState = $shippingState !== 'gujarat';

$totalTaxable = 0;
$totalCGST = 0;
$totalSGST = 0;
$totalIGST = 0;
?>

<table class="items">
   <thead>
      <tr>
         <th width="3%">Sl</th>
         <th width="28%">Description</th>
         <th width="7%">HSN</th>
         <th width="5%">Qty</th>
         <th width="9%">Rate</th>
         <th width="7%">Disc</th>
         <th width="11%">Taxable</th>

         <?php if($isInterState): ?>
            <th width="10%">IGST</th>
         <?php else: ?>
            <th width="10%">CGST</th>
            <th width="10%">SGST</th>
         <?php endif; ?>

         <th width="10%">Total</th>
      </tr>
   </thead>

   <tbody>
      <?php $__currentLoopData = $invoice->order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>

      <?php
         $baseTotal = $item->unit_price * $item->quantity;
         $discount = $item->discount_amount ?? 0;

         $taxPercent = floatval($item->tax_rate ?? 0);

         $taxableValue = $baseTotal - $discount;
         $taxAmount = ($taxableValue * $taxPercent) / 100;

         if ($isInterState) {
            $igstRate = $taxPercent;
            $igstAmount = $taxAmount;

            $cgstRate = 0;
            $sgstRate = 0;
            $cgstAmount = 0;
            $sgstAmount = 0;

            $totalIGST += $igstAmount;
         } else {
            $cgstRate = $taxPercent / 2;
            $sgstRate = $taxPercent / 2;

            $cgstAmount = $taxAmount / 2;
            $sgstAmount = $taxAmount / 2;

            $igstRate = 0;
            $igstAmount = 0;

            $totalCGST += $cgstAmount;
            $totalSGST += $sgstAmount;
         }

         $lineTotal = $item->total_amount ?? ($taxableValue + $taxAmount);
         $totalTaxable += $taxableValue;
      ?>

      <tr>
         <td class="text-center"><?php echo e($i + 1); ?></td>

         <td>
            <?php echo e($item->product->product_name ?? $item->product->name ?? 'Product'); ?>

            <span class="muted">(<?php echo e($item->product->sku ?? 'N/A'); ?>)</span>
         </td>

         <td class="text-center">
            <?php echo e($item->product->hsn_code ?? '-'); ?>

         </td>

         <td class="text-center"><?php echo e(floatval($item->quantity)); ?></td>

         <td class="text-right">
            <?php echo e(number_format($item->unit_price, 2)); ?>

         </td>

         <td class="text-right">
            <?php echo e(number_format($discount, 2)); ?>

         </td>

         <td class="text-right">
            <?php echo e(number_format($taxableValue, 2)); ?>

         </td>

         <?php if($isInterState): ?>
            <!-- IGST -->
            <td class="text-right">
               <?php if($igstRate > 0): ?>
                  <?php echo e(number_format($igstRate, 2)); ?>%
                  (<?php echo e(number_format($igstAmount, 2)); ?>)
               <?php else: ?>
                  -
               <?php endif; ?>
            </td>
         <?php else: ?>
            <!-- CGST -->
            <td class="text-right">
               <?php if($cgstRate > 0): ?>
                  <?php echo e(number_format($cgstRate, 2)); ?>%
                  (<?php echo e(number_format($cgstAmount, 2)); ?>)
               <?php else: ?>
                  -
               <?php endif; ?>
            </td>

            <!-- SGST -->
            <td class="text-right">
               <?php if($sgstRate > 0): ?>
                  <?php echo e(number_format($sgstRate, 2)); ?>%
                  (<?php echo e(number_format($sgstAmount, 2)); ?>)
               <?php else: ?>
                  -
               <?php endif; ?>
            </td>
         <?php endif; ?>

         <td class="text-right">
            <?php echo e(number_format($lineTotal, 2)); ?>

         </td>
      </tr>

      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
   </tbody>
</table>

<br>

<!-- TOTALS -->
<table class="totals">
   <tr>
      <td colspan="8" class="text-right bold">
         Total Taxable Value
      </td>
      <td class="text-right">
         <?php echo e(number_format($totalTaxable, 2)); ?>

      </td>
   </tr>

   <?php if($isInterState): ?>
      <tr>
         <td colspan="8" class="text-right bold">
            Total IGST
         </td>
         <td class="text-right">
            <?php echo e(number_format($totalIGST, 2)); ?>

         </td>
      </tr>
   <?php else: ?>
      <tr>
         <td colspan="8" class="text-right bold">
            Total CGST
         </td>
         <td class="text-right">
            <?php echo e(number_format($totalCGST, 2)); ?>

         </td>
      </tr>

      <tr>
         <td colspan="8" class="text-right bold">
            Total SGST
         </td>
         <td class="text-right">
            <?php echo e(number_format($totalSGST, 2)); ?>

         </td>
      </tr>
   <?php endif; ?>

   <tr class="grand-total">
      <td colspan="8" class="text-right">
         Grand Total
      </td>
      <td class="text-right">
         <?php echo e(number_format($invoice->net_amount, 2)); ?>

      </td>
   </tr>
</table>
<br>
      <!-- TERMS -->
      <table class="terms">
         <tr>
            <td>
               <strong>Terms & Conditions</strong><br>
               1. GST will be charged as applicable on all taxable goods and services.<br>
               2. Goods once sold (including seeds, fertilizers, and pesticides) will not be taken back or exchanged.<br>
               3. The quality and performance of agricultural inputs depend on soil, climate, and usage conditions; no guarantee of crop yield is provided.<br>
               4. The buyer is responsible for proper storage and usage as per product guidelines.<br>
               5. Any complaints regarding goods must be reported within 24 hours of delivery.<br>
               6. Interest may be charged on overdue payments as per agreed terms.<br>
               7. All disputes are subject to local jurisdiction.<br>
               8. This is a computer-generated invoice and does not require a signature.
            </td>
         </tr>
      </table>
   </body>
</html>
<?php /**PATH /home/user/metis/resources/views/orders/pdf/invoice.blade.php ENDPATH**/ ?>