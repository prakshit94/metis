@extends('layouts.app')

@section('title', 'Order Receipt - ' . $order->order_no)
@section('page', 'orders')

@section('content')
<div class="container py-4 print-container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0 rounded-4 overflow-hidden">
                <!-- Header -->
                <div class="card-header bg-body-tertiary p-4 p-md-5 border-bottom-0">
                    <div class="row align-items-center g-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="bg-primary text-white p-3 rounded-3 d-flex align-items-center justify-center" style="width: 50px; height: 50px;">
                                    <i class="bi bi-box fs-3"></i>
                                </div>
                                <h2 class="h4 mb-0 fw-bold tracking-tight text-uppercase">KRUSHIFY AGRO</h2>
                            </div>
                            <div class="small text-muted text-uppercase tracking-wider mb-1">Order Number</div>
                            <h3 class="h5 fw-bold mb-0 text-primary">{{ $order->order_no }}</h3>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="mb-3">
                                <div class="small text-muted text-uppercase tracking-wider mb-1">Date</div>
                                <div class="fw-bold">{{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('F d, Y') : 'N/A' }}</div>
                            </div>
                            <div>
                                <div class="small text-muted text-uppercase tracking-wider mb-1">Status</div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle text-uppercase px-3 py-2">
                                    {{ str_replace('_', ' ', $order->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="bg-body-tertiary bg-opacity-50 p-4 p-md-5 border-top border-bottom border-light-subtle">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h5 class="small fw-bold text-muted text-uppercase tracking-wider mb-3">Customer Details</h5>
                            <div class="h6 fw-bold mb-1">{{ $order->party ? trim($order->party->firstname . ' ' . $order->party->lastname . ' ' . $order->party->company_name) : 'N/A' }}</div>
                            @if($order->party?->email)<div class="text-muted small mb-1">{{ $order->party->email }}</div>@endif
                            @if($order->party?->phone)<div class="text-muted small mb-1">{{ $order->party->phone }}</div>@endif
                            @if($order->party?->gst_no)
                                <div class="text-primary font-monospace mt-2 small">GST: {{ $order->party->gst_no }}</div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h5 class="small fw-bold text-muted text-uppercase tracking-wider mb-3">Warehouse Info</h5>
                            <div class="h6 fw-bold mb-1">{{ $order->warehouse?->name ?: 'N/A' }}</div>
                            <div class="text-muted small">{{ $order->warehouse?->address_line_1 ?: 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="p-4 p-md-5">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead>
                                <tr class="border-bottom border-light-subtle text-uppercase small text-muted tracking-wider">
                                    <th class="pb-3" style="min-width: 250px;">Product Description</th>
                                    <th class="pb-3 text-center" style="width: 100px;">Qty</th>
                                    <th class="pb-3 text-end" style="width: 120px;">Unit Price</th>
                                    <th class="pb-3 text-end" style="width: 120px;">Discount</th>
                                    <th class="pb-3 text-end" style="width: 120px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr class="border-bottom border-light-subtle">
                                        <td class="py-4">
                                            <div class="fw-bold text-body-emphasis">{{ $item->product?->name }}</div>
                                            <div class="text-muted small font-monospace mt-1">{{ $item->product?->sku ?: 'SKU N/A' }}</div>
                                        </td>
                                        <td class="py-4 text-center fw-bold">{{ number_format($item->quantity, 0) }}</td>
                                        <td class="py-4 text-end text-muted">₹ {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="py-4 text-end text-success">-₹ {{ number_format($item->discount_amount, 2) }}</td>
                                        <td class="py-4 text-end fw-bold text-body-emphasis">₹ {{ number_format($item->total_amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-body-tertiary bg-opacity-25 p-4 p-md-5 border-top border-light-subtle">
                    <div class="row justify-content-end">
                        <div class="col-md-5 col-lg-4">
                            <div class="d-flex justify-content-between mb-3 text-muted small">
                                <span>Subtotal</span>
                                <span class="fw-medium">₹ {{ number_format($order->total_amount, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 text-success small">
                                <span>Discount Total</span>
                                <span class="fw-medium">-₹ {{ number_format($order->discount_amount, 2) }}</span>
                            </div>
                            @if($order->coupon_code)
                            <div class="d-flex justify-content-between mb-3 text-success text-opacity-75 small" style="margin-top: -10px;">
                                <span class="small font-monospace">Coupon Applied: {{ $order->coupon_code }}</span>
                            </div>
                            @endif
                            @if($order->appliedOffer)
                            <div class="d-flex justify-content-between mb-3 text-success text-opacity-75 small" style="margin-top: -10px;">
                                <span class="small font-monospace">Offer Applied: {{ $order->appliedOffer->name }}</span>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between mb-3 text-muted small">
                                <span>Tax Amount</span>
                                <span class="fw-medium">₹ {{ number_format($order->tax_amount, 2) }}</span>
                            </div>
                            <hr class="my-3 border-light-subtle">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h6 mb-0 text-uppercase fw-bold">Grand Total</span>
                                <span class="h3 mb-0 fw-bold text-primary">₹ {{ number_format($order->net_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer / Actions -->
                <div class="card-footer bg-body-tertiary p-4 border-top border-light-subtle text-center">
                    <p class="small text-muted mb-4">This is a computer generated document. No signature is required.</p>
                    <div class="d-flex align-items-center justify-content-center gap-2 print-hidden">
                        <button onclick="window.print()" class="btn btn-primary d-flex align-items-center gap-2 px-4">
                            <i class="bi bi-printer"></i> Print Receipt
                        </button>
                        <button onclick="window.history.back()" class="btn btn-outline-secondary px-4">
                            Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .print-hidden {
        display: none !important;
    }
    .print-container, .print-container * {
        visibility: visible;
    }
    .print-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    .card {
        box-shadow: none !important;
        border: none !important;
    }
}
</style>
@endsection
