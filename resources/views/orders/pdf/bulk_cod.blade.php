<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk COD Receipts</title>
    <style>
        @page {
            size: 100mm 150mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100mm;
            height: 150mm;
            margin: 0 !important;
            padding: 4mm 0 0 0 !important;
            font-family: Arial, sans-serif;
            color: #000;
            background: #fff;
        }

        .page-break {
            page-break-after: always;
        }

        .wrapper {
            border: 2px solid #000;
            padding: 4pt;
            width: 90mm;
            margin: 0 auto;
        }

        table.full-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .brand-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 3pt;
            margin-bottom: 4pt;
        }

        .brand-name {
            font-size: 14pt;
            font-weight: 900;
            letter-spacing: 0.5pt;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .brand-subtitle {
            font-size: 7.5pt;
            font-weight: bold;
            color: #333;
        }

        .pincode-badge {
            font-size: 11pt;
            font-weight: bold;
            border: 2px solid #000;
            padding: 3pt 1pt;
            text-align: center;
            line-height: 1.1;
        }

        .cod-banner {
            background-color: #000;
            color: #fff;
            padding: 3pt 1pt;
            text-align: center;
            font-weight: bold;
            font-size: 10.5pt;
            border: 2px solid #000;
            line-height: 1.1;
        }

        .meta-table td {
            font-size: 7.5pt;
            padding: 1pt 0;
            vertical-align: top;
        }

        .section-box {
            border: 1px solid #000;
            padding: 4pt;
            margin-bottom: 4pt;
            font-size: 8pt;
            line-height: 1.25;
        }

        .customer-box {
            border: 2px solid #000;
            padding: 4pt;
            margin-bottom: 4pt;
        }

        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            margin-bottom: 3pt;
            padding-bottom: 1pt;
            font-size: 8pt;
        }

        .footer {
            text-align: center;
            font-size: 6.5pt;
            border-top: 1px dashed #000;
            padding-top: 2pt;
            margin-top: 4pt;
            line-height: 1.1;
        }
    </style>
</head>

<body>

@foreach($orders as $order)
    @php
        $address = $order->shippingAddress ?? $order->billingAddress;
        $shipment = $order->shipments()->latest()->first();
        $pincode = $address ? $address->pincode : '-';
    @endphp

    <div class="{{ !$loop->last ? 'page-break' : '' }}">
        <div class="wrapper">
            <!-- Prominent Brand Header -->
            <div class="brand-header">
                <div class="brand-name">KRUSHIFY AGRO</div>
                <div class="brand-subtitle">Krushify Agro Pvt. Ltd.</div>
            </div>

            <!-- COD & Pincode Header -->
            <table class="full-table" style="margin-bottom: 4pt;">
                <tr>
                    <td style="width: 48%; padding-right: 2pt;">
                        <div class="pincode-badge">PIN: {{ $pincode }}</div>
                    </td>
                    <td style="width: 52%; padding-left: 2pt;">
                        <div class="cod-banner">
                            COD: Rs. {{ number_format($order->net_amount, 0) }}
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Order Meta Header -->
            <div class="section-box" style="text-align: center; background-color: #f9f9f9;">
                <div style="font-weight: bold; font-size: 8pt;">BUSINESS PARCEL (COD)</div>
                <table class="full-table meta-table" style="margin-top: 2pt;">
                    <tr>
                        <td style="text-align: left; width: 50%;"><strong>Order:</strong> {{ $order->order_no }}</td>
                        <td style="text-align: right; width: 50%;"><strong>Date:</strong> {{ $order->created_at ? $order->created_at->format('d-m-Y') : '' }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: left; width: 50%;"><strong>Office:</strong> Rajkot H.O.</td>
                        <td style="text-align: right; width: 50%;"><strong>E-Biller:</strong> {{ $order->warehouse?->ebiller_id ?: '1211658094' }}</td>
                    </tr>
                </table>
            </div>

            @if($shipment)
            <div class="section-box" style="text-align: center; background-color: #f9f9f9;">
                <div style="font-size: 7.5pt;">
                    <strong>Weight:</strong> {{ $shipment->actual_weight_g ?? '-' }} g | 
                    <strong>Dimensions:</strong> {{ $shipment->length_cm ?? '-' }}x{{ $shipment->width_cm ?? '-' }}x{{ $shipment->height_cm ?? '-' }} cm | 
                    <strong>Tariff:</strong> Rs. {{ $shipment->shipping_cost ?? '-' }}
                </div>
            </div>
            @endif

            <!-- Deliver To Box (Customer) -->
            <div class="section-box customer-box">
                <div class="section-title">DELIVER TO (CUSTOMER)</div>
                <div style="font-size: 9.5pt; font-weight: bold; margin-bottom: 2pt;">{{ $order->party->name ?? 'N/A' }}</div>
                @if($address)
                    <div>{{ $address->address_line_1 }}</div>
                    @if($address->address_line_2)
                        <div>{{ $address->address_line_2 }}</div>
                    @endif
                    <div>
                        <strong>Village:</strong> {{ $address->village->village_name ?? $address->village_name ?? $address->city ?? '-' }} | 
                        <strong>Taluka:</strong> {{ $address->village->taluka_name ?? $address->taluka ?? '-' }}
                    </div>
                    <div><strong>Dist:</strong> {{ $address->village->district_name ?? $address->district ?? '-' }} | <strong>PO:</strong> {{ $address->village->post_so_name ?? $address->post_office ?? '-' }}</div>
                    <div><strong>State:</strong> {{ $address->state }} - <strong>{{ $pincode }}</strong></div>
                @else
                    <div>N/A (No Address details available)</div>
                @endif
                <div style="margin-top: 4pt; font-weight: bold; font-size: 8pt; background: #f0f0f0; padding: 2pt 4pt; display: inline-block; border: 1px solid #000;">
                    Mobile: {{ $order->party->mobile ?? $order->party->phone ?? 'N/A' }}
                </div>
            </div>

            <!-- Sender Box -->
            <div class="section-box" style="margin-bottom: 0;">
                <div class="section-title">RETURN ADDRESS (SENDER)</div>
                <div style="font-weight: bold; font-size: 8.5pt;">{{ $order->warehouse?->company_name ?: 'Krushify Agro Pvt. Ltd.' }}</div>
                @if($order->warehouse && $order->warehouse->address_line_1)
                    <div>{{ $order->warehouse->address_line_1 }}</div>
                    @if($order->warehouse->address_line_2)<div>{{ $order->warehouse->address_line_2 }}</div>@endif
                    <div>{{ $order->warehouse->city ?? 'Rajkot' }}, {{ $order->warehouse->state ?? 'Gujarat' }} - {{ $order->warehouse->pincode ?? '360003' }} | <strong>Ph:</strong> {{ $order->warehouse?->phone ?: '9199125925' }}</div>
                    <div><strong>GSTIN:</strong> {{ $order->warehouse?->gstin ?: '24AAMCK0386L1Z6' }}</div>
                @else
                    <div>Plot No 19, Raj Ind Amul Cross Road, Ruda Transport Nagar</div>
                    <div>360003 Rajkot, Gujarat. | <strong>Ph:</strong> 9199125925</div>
                    <div><strong>GSTIN:</strong> 24AAMCK0386L1Z6</div>
                @endif
            </div>

            <!-- Footer -->
            <div class="footer">
                <div>If undelivered, please return to <strong>Rajkot H.O.</strong></div>
                <div><i>Does not contain dangerous or prohibited goods per Indian Post rules.</i></div>
            </div>
        </div>
    </div>
@endforeach

</body>
</html>
