<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <title>Goods Receipt Note - {{ $receipt->grn_number }}</title>
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

</style>
   </head>
   <body>

      <!-- HEADER -->
      <table class="header-table">
         <tr>
            <td width="60%">
               <div class="header-title">GOODS RECEIPT NOTE (GRN)</div>
               <div class="muted" style="margin-top: 3px;">
                  <strong>GRN Number:</strong> {{ $receipt->grn_number }}<br>
                  <strong>Received Date:</strong> {{ $receipt->received_date ? \Carbon\Carbon::parse($receipt->received_date)->format('d-M-Y') : 'N/A' }}<br>
                  <strong>Related PO:</strong> {{ $receipt->purchaseOrder->po_number ?? 'N/A' }}
               </div>
            </td>
            <td width="40%" class="text-right">
               <div class="bold large-text">{{ $receipt->warehouse->company_name ?? 'Our Company' }}</div>
               <div class="muted" style="margin-top: 3px;">{{ $receipt->warehouse->address ?? 'Company Address' }}</div>
            </td>
         </tr>
      </table>

      <!-- ADDRESSES -->
      <table>
         <tr>
            <!-- SUPPLIER -->
            <td class="address-box" style="border-right: 1px solid #cbd5e1;">
               <div class="address-title">From (Supplier)</div>
               <table class="address-table">
                  <tr>
                     <td colspan="2" class="bold large-text" style="padding-bottom: 4px;">
                        {{ $receipt->purchaseOrder->supplier->company_name ?? ($receipt->purchaseOrder->supplier->firstname . ' ' . $receipt->purchaseOrder->supplier->lastname) }}
                     </td>
                  </tr>
                  <tr>
                     <td class="label">Phone</td>
                     <td>{{ $receipt->purchaseOrder->supplier->phone ?? 'N/A' }}</td>
                  </tr>
                  <tr>
                     <td class="label">Email</td>
                     <td>{{ $receipt->purchaseOrder->supplier->email ?? 'N/A' }}</td>
                  </tr>
               </table>
            </td>

            <!-- WAREHOUSE (Ship To) -->
            <td class="address-box">
               <div class="address-title">Received At (Warehouse)</div>
               <table class="address-table">
                  <tr>
                     <td colspan="2" class="bold large-text" style="padding-bottom: 4px;">
                        {{ $receipt->warehouse->name }}
                     </td>
                  </tr>
                  <tr>
                     <td class="label">Address</td>
                     <td>
                        <div><strong>Line 1:</strong> {{ $receipt->warehouse->address_line_1 }}</div>
                        @if($receipt->warehouse->city)
                           <div><strong>City:</strong> {{ $receipt->warehouse->city }}</div>
                        @endif
                     </td>
                  </tr>
               </table>
            </td>
         </tr>
      </table>

      <!-- ITEMS -->
      <table class="items">
         <thead>
            <tr>
               <th width="5%">Sl</th>
               <th width="35%">Product Description</th>
               <th width="10%">Expected Qty</th>
               <th width="10%">Received Qty</th>
               <th width="10%">Accepted Qty</th>
               <th width="10%">Rejected Qty</th>
               <th width="20%">Batch / Expiry</th>
            </tr>
         </thead>
         <tbody>
            @foreach($receipt->items as $i => $item)
            <tr>
               <td class="text-center">{{ $i + 1 }}</td>
               <td>
                  {{ $item->product->name ?? 'Product' }}
                  <span class="muted">({{ $item->product->sku ?? 'N/A' }})</span>
               </td>
               <td class="text-center">{{ floatval($item->purchaseOrder->items->where('product_id', $item->product_id)->first()->quantity ?? 0) }}</td>
               <td class="text-center">{{ floatval($item->received_qty) }}</td>
               <td class="text-center">{{ floatval($item->accepted_qty) }}</td>
               <td class="text-center" style="{{ floatval($item->rejected_qty) > 0 ? 'color: red; font-weight: bold;' : '' }}">
                  {{ floatval($item->rejected_qty) }}
               </td>
               <td class="text-center">
                  @if($item->batch_number)
                     {{ $item->batch_number }} <br>
                     <span class="muted">Exp: {{ $item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('d-M-Y') : 'N/A' }}</span>
                  @else
                     N/A
                  @endif
               </td>
            </tr>
            @endforeach
         </tbody>
      </table>

      <table class="header-table" style="margin-top: 20px;">
         <tr>
            <td>
                <strong>Notes:</strong> {{ $receipt->notes ?? 'No additional notes provided.' }}
            </td>
         </tr>
      </table>

   </body>
</html>
