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
      @foreach($invoices as $invoice)
      <div class="invoice-container {{ !$loop->last ? 'page-break' : '' }}">
         <div class="title">DELIVERY CHALLAN</div>
         <!-- HEADER -->
         <table>
            <tr>
               <td width="60%">
                  <div class="company-name">{{ $invoice->order?->warehouse?->company_name ?: 'Krushify Agro Pvt. Ltd.' }}</div>
                  <div class="muted">
                     <strong>Mobile:</strong> {{ $invoice->order?->warehouse?->phone ?: '9199125925' }}<br>
                     <strong>Address:</strong>
                     @if($invoice->order?->warehouse && $invoice->order->warehouse->address_line_1)
                        {{ $invoice->order->warehouse->address_line_1 }}
                        @if($invoice->order->warehouse->address_line_2), {{ $invoice->order->warehouse->address_line_2 }}@endif
                        , {{ $invoice->order->warehouse->city ?? 'Rajkot' }}, {{ $invoice->order->warehouse->state ?? 'Gujarat' }} - {{ $invoice->order->warehouse->pincode ?? '360003' }}<br>
                     @else
                        The One World (B), 1005, Ayodhya Circle<br>
                     @endif
                     <strong>Email:</strong> {{ $invoice->order?->warehouse?->email ?: 'info@krushifyagro.com' }}<br>
                     <strong>GST:</strong> {{ $invoice->order?->warehouse?->gstin ?: '24AAMCK0386L1Z6' }}
                  </div>
               </td>
               <td width="40%">
                  <strong>Invoice No:</strong> {{ $invoice->invoice_no }}<br>
                  <strong>Order No:</strong> {{ $invoice->order->order_no ?? 'N/A' }}<br>
                  <strong>Dated:</strong> {{ $invoice->invoice_date ? $invoice->invoice_date->format('d-m-Y') : 'N/A' }}<br>
                  <strong>Payment Mode:</strong> {{ ucfirst($invoice->order->payment_method ?? 'Cash') }}<br>
                  @if(strtolower($invoice->order->payment_method ?? '') === 'cod')
                  <strong>To Collect:</strong> ₹ {{ number_format($invoice->net_amount, 2) }}<br>
                  @endif
                  @if($invoice->order?->shipments && $shipment = $invoice->order->shipments->first())
                  <br>
                  <strong>Tracking No:</strong> {{ $shipment->tracking_no ?? 'N/A' }}<br>
                  @if($shipment->tracking_no)
                  @php
                     $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
                     $barcodeBase64 = base64_encode($generator->getBarcode($shipment->tracking_no, $generator::TYPE_CODE_128));
                  @endphp
                  <img src="data:image/svg+xml;base64,{{ $barcodeBase64 }}" style="height: 25px; max-width: 100%; margin-top: 3px; margin-bottom: 3px;" alt="Barcode" /><br>
                  @endif
                  @if($shipment->actual_weight_g)
                  <strong>Weight:</strong> {{ $shipment->actual_weight_g }} g<br>
                  @endif
                  @endif
                  <br>
                  @if($invoice->order?->warehouse?->reference_no)
                  <strong>Reference No:</strong> {{ $invoice->order->warehouse->reference_no }}<br>
                  @endif
                  @if($invoice->order?->warehouse?->seed_lic_no)
                  Seed Lic No.: {{ $invoice->order->warehouse->seed_lic_no }}<br>
                  @endif
                  @if($invoice->order?->warehouse?->pesti_lic_no)
                  Pesti Lic No.: {{ $invoice->order->warehouse->pesti_lic_no }}
                  @endif
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
                        <td>{{ $invoice->order->party->name ?? 'N/A' }}</td>
                     </tr>
                     <tr>
                        <td class="label">Mobile</td>
                        <td>{{ $invoice->order->party->phone ?? 'N/A' }}</td>
                     </tr>
                     @if($invoice->order->billingAddress)
                     <tr>
                        <td class="label">Address</td>
                        <td>{{ $invoice->order->billingAddress->address_line_1 }}</td>
                     </tr>
                     @if($invoice->order->billingAddress->address_line_2)
                     <tr>
                        <td class="label"></td>
                        <td>{{ $invoice->order->billingAddress->address_line_2 }}</td>
                     </tr>
                     @endif
                     <tr>
                        <td class="label">Village</td>
                        <td>{{ $invoice->order->billingAddress->village->village_name ?? $invoice->order->billingAddress->city ?? '-' }}</td>
                     </tr>
                     <tr>
                        <td class="label">Taluka</td>
                        <td>{{ $invoice->order->billingAddress->village->taluka_name ?? '-' }}</td>
                     </tr>
                     <tr>
                        <td class="label">District</td>
                        <td>{{ $invoice->order->billingAddress->village->district_name ?? '-' }}</td>
                     </tr>
                     <tr>
                        <td class="label">Post Office</td>
                        <td>{{ $invoice->order->billingAddress->village->post_so_name ?? '-' }}</td>
                     </tr>
                     <tr>
                        <td class="label">State / PIN</td>
                        <td>{{ $invoice->order->billingAddress->state }} -
                           {{ $invoice->order->billingAddress->pincode }}
                        </td>
                     </tr>
                     <tr>
                        <td class="label">Country</td>
                        <td>{{ $invoice->order->billingAddress->country ?? 'India' }}</td>
                     </tr>
                     @else
                     <tr>
                        <td colspan="2">N/A</td>
                     </tr>
                     @endif
                  </table>
               </td>
               <!-- Shipping -->
               <td>
                  <table class="no-border">
                     <tr>
                        <td class="label">Name</td>
                        <td>{{ $invoice->order->party->name ?? 'N/A' }}</td>
                     </tr>
                     <tr>
                        <td class="label">Mobile</td>
                        <td>{{ $invoice->order->party->phone ?? 'N/A' }}</td>
                     </tr>
                     @if($invoice->order->shippingAddress)
                     <tr>
                        <td class="label">Address</td>
                        <td>{{ $invoice->order->shippingAddress->address_line_1 }}</td>
                     </tr>
                     @if($invoice->order->shippingAddress->address_line_2)
                     <tr>
                        <td class="label"></td>
                        <td>{{ $invoice->order->shippingAddress->address_line_2 }}</td>
                     </tr>
                     @endif
                     <tr>
                        <td class="label">Village</td>
                        <td>{{ $invoice->order->shippingAddress->village->village_name ?? $invoice->order->shippingAddress->city ?? '-' }}</td>
                     </tr>
                     <tr>
                        <td class="label">Taluka</td>
                        <td>{{ $invoice->order->shippingAddress->village->taluka_name ?? '-' }}</td>
                     </tr>
                     <tr>
                        <td class="label">District</td>
                        <td>{{ $invoice->order->shippingAddress->village->district_name ?? '-' }}</td>
                     </tr>
                     <tr>
                        <td class="label">Post Office</td>
                        <td>{{ $invoice->order->shippingAddress->village->post_so_name ?? '-' }}</td>
                     </tr>
                     <tr>
                        <td class="label">State / PIN</td>
                        <td>{{ $invoice->order->shippingAddress->state }} -
                           {{ $invoice->order->shippingAddress->pincode }}
                        </td>
                     </tr>
                     <tr>
                        <td class="label">Country</td>
                        <td>{{ $invoice->order->shippingAddress->country ?? 'India' }}</td>
                     </tr>
                     @else
                     <tr>
                        <td colspan="2">Same as Billing</td>
                      </tr>
                     @endif
                  </table>
               </td>
            </tr>
         </table>
         <br>
         <!-- ITEMS -->
@php
$shippingState = strtolower(trim($invoice->order->shippingAddress->state ?? $invoice->order->billingAddress->state ?? ''));
$warehouseState = strtolower(trim($invoice->order->warehouse->state ?? 'gujarat'));
$isInterState = $shippingState !== $warehouseState;

$totalTaxable = 0;
$totalCGST = 0;
$totalSGST = 0;
$totalIGST = 0;
@endphp

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

            @if($isInterState)
                <th width="10%">IGST</th>
            @else
                <th width="10%">CGST</th>
                <th width="10%">SGST</th>
            @endif

            <th width="10%">Total</th>
        </tr>
    </thead>

    <tbody>
        @foreach($invoice->order->items as $i => $item)
        @php
        $baseTotal = $item->unit_price * $item->quantity;
        $discount = $item->discount_amount ?? 0;
        $taxPercent = floatval($item->tax_rate > 0 ? $item->tax_rate : ($item->product->taxRate->rate ?? 0));

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
        @endphp

        <tr>
            <td class="text-center">{{ $i + 1 }}</td>

            <td>
                {{ $item->product->product_name ?? $item->product->name ?? 'Product' }}<br>
                <small class="muted">{{ $item->product->sku ?? 'N/A' }}</small>
            </td>

            <td class="text-center">
                {{ $item->product->hsn_code ?? '-' }}
            </td>

            <td class="text-center">{{ floatval($item->quantity) }}</td>

            <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>

            <td class="text-right">{{ number_format($discount, 2) }}</td>

            <td class="text-right">{{ number_format($taxableValue, 2) }}</td>

            @if($isInterState)
                <td class="text-right">
                    @if($igstRate > 0)
                        <span style="font-size:9px;">{{ $igstRate }}%</span><br>
                        {{ number_format($igstAmount, 2) }}
                    @else
                        -
                    @endif
                </td>
            @else
                <td class="text-right">
                    @if($cgstRate > 0)
                        <span style="font-size:9px;">{{ $cgstRate }}%</span><br>
                        {{ number_format($cgstAmount, 2) }}
                    @else
                        -
                    @endif
                </td>

                <td class="text-right">
                    @if($sgstRate > 0)
                        <span style="font-size:9px;">{{ $sgstRate }}%</span><br>
                        {{ number_format($sgstAmount, 2) }}
                    @else
                        -
                    @endif
                </td>
            @endif

            <td class="text-right">{{ number_format($lineTotal, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<br>

<!-- TOTALS -->
<table class="totals">
    <tr>
        <td colspan="8" class="text-right bold">Total Taxable Value</td>
        <td class="text-right">{{ number_format($totalTaxable, 2) }}</td>
    </tr>

    @if($isInterState)
        <tr>
            <td colspan="8" class="text-right bold">Total IGST</td>
            <td class="text-right">{{ number_format($totalIGST, 2) }}</td>
        </tr>
    @else
        <tr>
            <td colspan="8" class="text-right bold">Total CGST</td>
            <td class="text-right">{{ number_format($totalCGST, 2) }}</td>
        </tr>
        <tr>
            <td colspan="8" class="text-right bold">Total SGST</td>
            <td class="text-right">{{ number_format($totalSGST, 2) }}</td>
        </tr>
    @endif

    <tr class="grand-total">
        <td colspan="8" class="text-right">Grand Total</td>
        <td class="text-right">{{ number_format($invoice->net_amount, 2) }}</td>
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
      @endforeach
   </body>
</html>
