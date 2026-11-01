<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <title>Bulk Purchase Orders</title>
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

th, td {
   border: 1px solid #cbd5e1;
   padding: 4px 5px;
   vertical-align: top;
}

th {
   background-color: #f8fafc;
   font-weight: bold;
   text-align: left;
   color: #475569;
}

/* Typography utilities */
.bold { font-weight: bold; }
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-uppercase { text-transform: uppercase; }
.muted { color: #64748b; }
.large-text { font-size: 14px; }
.header-title { font-size: 16px; color: #1e293b; font-weight: bold; }

/* Structural tables */
.no-border, .no-border th, .no-border td {
   border: none;
}

/* Header section */
.header-table {
   border: none;
   margin-bottom: 8px;
}
.header-table td {
   border: none;
   padding: 2px 0;
}
.logo {
   max-width: 120px;
   max-height: 40px;
}

/* Addresses */
.address-box {
   width: 50%;
   padding: 8px;
   vertical-align: top;
}
.address-title {
   font-size: 9.5px;
   font-weight: bold;
   margin-bottom: 4px;
   color: #1e293b;
   text-transform: uppercase;
   border-bottom: 1px solid #e2e8f0;
   padding-bottom: 3px;
}
.address-table {
   width: 100%;
   border: none;
}
.address-table td {
   border: none;
   padding: 1.5px 0;
}
.address-table .label {
   color: #64748b;
   width: 75px;
}

/* Items */
.items {
   margin-top: 10px;
}
.items th {
   border-bottom-width: 2px;
   text-align: center;
}
.items td {
   vertical-align: middle;
}
.items .text-left {
   text-align: left;
}

/* Totals */
.totals td {
   padding: 4px 5px;
}
.grand-total td {
   background-color: #f1f5f9;
   font-weight: bold;
   font-size: 10px;
}

/* Footer / Terms */
.terms {
   margin-top: 15px;
   background: #f8fafc;
}
.terms td {
   padding: 8px;
   font-size: 7.5px;
   color: #64748b;
}

</style>
   </head>
   <body>
      @foreach($orders as $order)
      <!-- HEADER -->
      <table class="header-table">
         <tr>
            <td width="60%">
               <div class="header-title">PURCHASE ORDER</div>
               <div class="muted" style="margin-top: 3px;">
                  <strong>PO Number:</strong> {{ $order->po_number }}<br>
                  <strong>PO Date:</strong> {{ $order->created_at->format('d-M-Y') }}<br>
                  <strong>Expected Delivery:</strong> {{ $order->expected_delivery_date ? \Carbon\Carbon::parse($order->expected_delivery_date)->format('d-M-Y') : 'N/A' }}
               </div>
            </td>
            <td width="40%" class="text-right">
               <!-- You can replace this with your actual logo -->
               <!-- <img src="logo.png" class="logo"> -->
               <div class="bold large-text">{{ $order->warehouse->company_name ?? 'Our Company' }}</div>
               <div class="muted" style="margin-top: 3px;">{{ $order->warehouse->address ?? 'Company Address' }}</div>
               <div class="muted">GSTIN: {{ $order->warehouse->gstin ?? 'N/A' }}</div>
            </td>
         </tr>
      </table>

      <!-- ADDRESSES -->
      <table>
         <tr>
            <!-- SUPPLIER (Receiver) -->
            <td class="address-box" style="border-right: 1px solid #cbd5e1;">
               <div class="address-title">To (Supplier)</div>
               <table class="address-table">
                  <tr>
                     <td colspan="2" class="bold large-text" style="padding-bottom: 4px;">
                        {{ $order->supplier->company_name ?? ($order->supplier->firstname . ' ' . $order->supplier->lastname) }}
                     </td>
                  </tr>
                  <tr>
                     <td class="label">GSTIN / PAN</td>
                     <td>{{ $order->supplier->gst_no ?? 'N/A' }} / {{ $order->supplier->pan_no ?? 'N/A' }}</td>
                  </tr>
                  <tr>
                     <td class="label">Phone</td>
                     <td>{{ $order->supplier->phone ?? 'N/A' }}</td>
                  </tr>
                  <tr>
                     <td class="label">Email</td>
                     <td>{{ $order->supplier->email ?? 'N/A' }}</td>
                  </tr>
                  <tr>
                     <td class="label">Address</td>
                     <td>
                        <div><strong>Line 1:</strong> {{ $order->supplier->address_line_1 }}</div>
                        @if($order->supplier->address_line_2)
                           <div><strong>Line 2:</strong> {{ $order->supplier->address_line_2 }}</div>
                        @endif
                        @if($order->supplier->village_name)
                           <div><strong>Village:</strong> {{ $order->supplier->village_name }}</div>
                        @endif
                        @if($order->supplier->taluka)
                           <div><strong>Taluka:</strong> {{ $order->supplier->taluka }}</div>
                        @endif
                        @if($order->supplier->city)
                           <div><strong>District/City:</strong> {{ $order->supplier->city }}</div>
                        @endif
                     </td>
                  </tr>
                  <tr>
                     <td class="label">State / PIN</td>
                     <td>
                        {{ $order->supplier->state ?? '' }} - {{ $order->supplier->pincode ?? '' }}
                     </td>
                  </tr>
               </table>
            </td>

            <!-- WAREHOUSE (Ship To) -->
            <td class="address-box">
               <div class="address-title">Ship To (Warehouse)</div>
               <table class="address-table">
                  <tr>
                     <td colspan="2" class="bold large-text" style="padding-bottom: 4px;">
                        {{ $order->warehouse->name }}
                     </td>
                  </tr>
                  <tr>
                     <td class="label">Phone</td>
                     <td>{{ $order->warehouse->phone ?? 'N/A' }}</td>
                  </tr>
                  <tr>
                     <td class="label">Email</td>
                     <td>{{ $order->warehouse->email ?? 'N/A' }}</td>
                  </tr>
                  <tr>
                     <td class="label">Address</td>
                     <td>
                        <div><strong>Line 1:</strong> {{ $order->warehouse->address_line_1 }}</div>
                        @if($order->warehouse->address_line_2)
                           <div><strong>Line 2:</strong> {{ $order->warehouse->address_line_2 }}</div>
                        @endif
                        @if($order->warehouse->village_name)
                           <div><strong>Village:</strong> {{ $order->warehouse->village_name }}</div>
                        @endif
                        @if($order->warehouse->taluka)
                           <div><strong>Taluka:</strong> {{ $order->warehouse->taluka }}</div>
                        @endif
                        @if($order->warehouse->city)
                           <div><strong>District/City:</strong> {{ $order->warehouse->city }}</div>
                        @endif
                     </td>
                  </tr>
                  <tr>
                     <td class="label">State / PIN</td>
                     <td>
                        {{ $order->warehouse->state ?? '' }} - {{ $order->warehouse->pincode ?? '' }}
                     </td>
                  </tr>
               </table>
            </td>
         </tr>
      </table>

      <!-- ITEMS -->
@php
$supplierState = strtolower(trim($order->supplier->state ?? ''));
$warehouseState = strtolower(trim($order->warehouse->state ?? ''));
// Fallback logic for interstate if state is missing
$isInterState = ($supplierState !== '' && $warehouseState !== '') ? ($supplierState !== $warehouseState) : false;

$totalIGST = 0;
$totalCGST = 0;
$totalSGST = 0;
$totalTaxable = 0;
@endphp

<table class="items">
   <thead>
      <tr>
         <th width="5%">Sl</th>
         <th width="30%">Product Description</th>
         <th width="8%">Qty</th>
         <th width="10%">Unit Price</th>
         <th width="8%">Discount</th>
         <th width="11%">Taxable</th>
         @if($isInterState)
            <th width="12%">IGST</th>
         @else
            <th width="6%">CGST</th>
            <th width="6%">SGST</th>
         @endif
         <th width="10%">Net Total</th>
      </tr>
   </thead>

   <tbody>
      @foreach($order->items as $i => $item)

      @php
         $taxPercent = floatval($item->tax_rate ?? 0);
         $taxAmount = floatval($item->tax_amount ?? 0);
         
         $discount = floatval($item->discount_amount ?? 0);
         $taxableValue = ($item->quantity * $item->unit_price) - $discount;
         $totalTaxable += $taxableValue;

         if ($isInterState) {
            $igstRate = $taxPercent;
            $igstAmount = $taxAmount;
            $totalIGST += $igstAmount;
         } else {
            $cgstRate = $taxPercent / 2;
            $sgstRate = $taxPercent / 2;
            $cgstAmount = $taxAmount / 2;
            $sgstAmount = $taxAmount / 2;
            $totalCGST += $cgstAmount;
            $totalSGST += $sgstAmount;
         }
      @endphp

      <tr>
         <td class="text-center">{{ $i + 1 }}</td>
         <td>
            {{ $item->product->name ?? 'Product' }}
            <span class="muted">({{ $item->product->sku ?? 'N/A' }})</span>
         </td>
         <td class="text-center">{{ floatval($item->quantity) }}</td>
         <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
         <td class="text-right">{{ number_format($discount, 2) }}</td>
         <td class="text-right">{{ number_format($taxableValue, 2) }}</td>

         @if($isInterState)
            <!-- IGST -->
            <td class="text-right">
               @if($igstRate > 0)
                  {{ number_format($igstRate, 2) }}%<br>
                  ({{ number_format($igstAmount, 2) }})
               @else
                  -
               @endif
            </td>
         @else
            <!-- CGST -->
            <td class="text-right">
               @if($cgstRate > 0)
                  {{ number_format($cgstRate, 2) }}%<br>
                  ({{ number_format($cgstAmount, 2) }})
               @else
                  -
               @endif
            </td>
            <!-- SGST -->
            <td class="text-right">
               @if($sgstRate > 0)
                  {{ number_format($sgstRate, 2) }}%<br>
                  ({{ number_format($sgstAmount, 2) }})
               @else
                  -
               @endif
            </td>
         @endif

         <td class="text-right">
            {{ number_format($item->net_amount ?? ($taxableValue + $taxAmount), 2) }}
         </td>
      </tr>

      @endforeach
   </tbody>
</table>

<!-- TOTALS -->
<table class="totals" style="border-top: none;">
   <tr>
      <td colspan="7" class="text-right bold" style="border-top: none;">Sub Total</td>
      <td class="text-right" style="border-top: none;">
         {{ number_format($order->total_amount, 2) }}
      </td>
   </tr>
   
   @if($order->discount_amount > 0)
   <tr>
      <td colspan="7" class="text-right bold">Total Discount</td>
      <td class="text-right text-danger">
         -{{ number_format($order->discount_amount, 2) }}
      </td>
   </tr>
   @endif

   <tr>
      <td colspan="7" class="text-right bold">Total Taxable Value</td>
      <td class="text-right">
         {{ number_format($totalTaxable, 2) }}
      </td>
   </tr>

   @if($isInterState)
      @if($totalIGST > 0)
      <tr>
         <td colspan="7" class="text-right bold">Total IGST</td>
         <td class="text-right">{{ number_format($totalIGST, 2) }}</td>
      </tr>
      @endif
   @else
      @if($totalCGST > 0)
      <tr>
         <td colspan="7" class="text-right bold">Total CGST</td>
         <td class="text-right">{{ number_format($totalCGST, 2) }}</td>
      </tr>
      <tr>
         <td colspan="7" class="text-right bold">Total SGST</td>
         <td class="text-right">{{ number_format($totalSGST, 2) }}</td>
      </tr>
      @endif
   @endif

   <tr class="grand-total">
      <td colspan="7" class="text-right" style="font-size: 11px;">Grand Total</td>
      <td class="text-right" style="font-size: 11px;">
         ₹ {{ number_format($order->net_amount ?? $order->total_amount, 2) }}
      </td>
   </tr>
</table>

      <!-- TERMS -->
      <table class="terms">
         <tr>
            <td>
               <strong>Terms & Conditions</strong><br>
               1. Please send two copies of your invoice.<br>
               2. Enter this order in accordance with the prices, terms, delivery method, and specifications listed above.<br>
               3. Please notify us immediately if you are unable to ship as specified.<br>
               4. Send all correspondence to the Ship To address.
            </td>
         </tr>
      </table>
      @if(!$loop->last)
         <div style="page-break-after: always;"></div>
      @endif
      @endforeach
   </body>
</html>
