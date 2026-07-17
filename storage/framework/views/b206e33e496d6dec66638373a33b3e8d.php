<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <title>Bulk Invoice Print</title>
      <style>
@page {
   size: A4 portrait;
   margin: 10mm 10mm;
}

body {
   font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
   font-size: 9px;
   color: #334155;
   line-height: 1.2;
}

table {
   width: 100%;
   border-collapse: collapse;
   page-break-inside: auto;
}

tr {
   page-break-inside: avoid;
}

th,
td {
   border: 1px solid #cbd5e1;
   padding: 4px;
   vertical-align: top;
}

.text-right {
   text-align: right;
}

.text-center {
   text-align: center;
}

.bold {
   font-weight: bold;
}

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
}

.company-name {
   font-size: 12px;
   font-weight: bold;
   color: #1e40af;
}

.muted {
   color: #555;
}

.header-table td {
   border: 1px solid #cbd5e1;
}

.no-border td {
   border: none;
   padding: 2px 3px;
}

.label {
   width: 38%;
   font-weight: bold;
   white-space: nowrap;
}

.items thead th {
   background: #1e40af;
   color: #fff;
   text-align: center;
}

.items td {
   padding: 4px 3px;
}

.totals td {
   padding: 4px;
}

.grand-total {
   font-size: 11px;
   font-weight: bold;
   background: #f1f5f9;
}

.terms {
   font-size: 9px;
   line-height: 1.25;
}

.page-break {
   page-break-after: always;
}
.invoice-container:last-child {
   page-break-after: auto;
}
</style>
   </head>
   <body>
      <?php $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <div class="invoice-container <?php echo e(!$loop->last ? 'page-break' : ''); ?>">
         <div class="title">DELIVERY CHALLAN</div>
         <!-- HEADER -->
         <table>
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
                     <strong>Email:</strong> info@krushifyagro.com<br>
                     <strong>GST:</strong> <?php echo e($invoice->order?->warehouse?->gstin ?: '24AAMCK0386L1Z6'); ?>

                  </div>
               </td>
               <td width="40%">
                  <strong>Invoice No:</strong> <?php echo e($invoice->invoice_no); ?><br>
                  <strong>Order No:</strong> <?php echo e($invoice->order->order_no ?? 'N/A'); ?><br>
                  <strong>Dated:</strong> <?php echo e($invoice->invoice_date ? $invoice->invoice_date->format('d-m-Y') : 'N/A'); ?><br>
                  <strong>Payment Mode:</strong> <?php echo e(ucfirst($invoice->order->payment_method ?? 'Cash')); ?><br>
                  <?php if(strtolower($invoice->order->payment_method ?? '') === 'cod'): ?>
                  <strong>To Collect:</strong> Rs. <?php echo e(number_format($invoice->net_amount, 2)); ?><br>
                  <?php endif; ?>
                  <br>
                  <strong>Reference No.</strong><br>
                  Seed Lic No.: GAN/FSR220001380/2022-2023<br>
                  Pesti Lic No.: GAN/FP1220002020/2022-2023
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
               <!-- Billing -->
               <td>
                  <table class="no-border">
                     <tr>
                        <td class="label">Name</td>
                        <td><?php echo e($invoice->order->party->name ?? 'N/A'); ?></td>
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
                        <td class="label"></td>
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
                        <td><?php echo e($invoice->order->billingAddress->state); ?> -
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
               <!-- Shipping -->
               <td>
                  <table class="no-border">
                     <tr>
                        <td class="label">Name</td>
                        <td><?php echo e($invoice->order->party->name ?? 'N/A'); ?></td>
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
                        <td class="label"></td>
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
                        <td><?php echo e($invoice->order->shippingAddress->state); ?> -
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
            <th width="4%">Sl</th>
            <th width="26%">Description</th>
            <th width="8%">HSN</th>
            <th width="6%">Qty</th>
            <th width="10%">Rate</th>
            <th width="8%">Disc.</th>
            <th width="12%">Taxable</th>

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
                <?php echo e($item->product->product_name ?? $item->product->name ?? 'Product'); ?><br>
                <small class="muted"><?php echo e($item->product->sku ?? 'N/A'); ?></small>
            </td>

            <td class="text-center">
                <?php echo e($item->product->hsn_code ?? '-'); ?>

            </td>

            <td class="text-center"><?php echo e(floatval($item->quantity)); ?></td>

            <td class="text-right"><?php echo e(number_format($item->unit_price, 2)); ?></td>

            <td class="text-right"><?php echo e(number_format($discount, 2)); ?></td>

            <td class="text-right"><?php echo e(number_format($taxableValue, 2)); ?></td>

            <?php if($isInterState): ?>
                <td class="text-right">
                    <?php if($igstRate > 0): ?>
                        <span style="font-size:9px;"><?php echo e($igstRate); ?>%</span><br>
                        <?php echo e(number_format($igstAmount, 2)); ?>

                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            <?php else: ?>
                <td class="text-right">
                    <?php if($cgstRate > 0): ?>
                        <span style="font-size:9px;"><?php echo e($cgstRate); ?>%</span><br>
                        <?php echo e(number_format($cgstAmount, 2)); ?>

                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>

                <td class="text-right">
                    <?php if($sgstRate > 0): ?>
                        <span style="font-size:9px;"><?php echo e($sgstRate); ?>%</span><br>
                        <?php echo e(number_format($sgstAmount, 2)); ?>

                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            <?php endif; ?>

            <td class="text-right"><?php echo e(number_format($lineTotal, 2)); ?></td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>

<br>

<!-- TOTALS -->
<table class="totals">
    <tr>
        <td colspan="8" class="text-right bold">Total Taxable Value</td>
        <td class="text-right"><?php echo e(number_format($totalTaxable, 2)); ?></td>
    </tr>

    <?php if($isInterState): ?>
        <tr>
            <td colspan="8" class="text-right bold">Total IGST</td>
            <td class="text-right"><?php echo e(number_format($totalIGST, 2)); ?></td>
        </tr>
    <?php else: ?>
        <tr>
            <td colspan="8" class="text-right bold">Total CGST</td>
            <td class="text-right"><?php echo e(number_format($totalCGST, 2)); ?></td>
        </tr>
        <tr>
            <td colspan="8" class="text-right bold">Total SGST</td>
            <td class="text-right"><?php echo e(number_format($totalSGST, 2)); ?></td>
        </tr>
    <?php endif; ?>

    <tr class="grand-total">
        <td colspan="8" class="text-right">Grand Total</td>
        <td class="text-right"><?php echo e(number_format($invoice->net_amount, 2)); ?></td>
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
      </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
   </body>
</html>
<?php /**PATH /home/user/metis/resources/views/orders/pdf/bulk_invoice.blade.php ENDPATH**/ ?>