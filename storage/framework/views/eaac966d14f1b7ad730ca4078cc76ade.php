<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>COD Receipt - <?php echo e($order->order_no); ?></title>
    <style>
        @page {
            margin: 10px;
            size: a5;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #334155;
            line-height: 1.3;
        }

        .label-container {
            border: 2px solid #166534;
            border-radius: 6px;
            padding: 8px;
            max-width: 500px;
            margin: 0 auto;
            background-color: #f0fdf4;
        }

        .row {
            display: table;
            width: 100%;
        }

        .col {
            display: table-cell;
            vertical-align: top;
        }

        .box {
            border: 1px solid #86efac;
            background-color: #ffffff;
            border-radius: 4px;
            padding: 6px;
            margin-top: 6px;
        }

        .title {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #166534;
        }

        .big {
            font-size: 13px;
            font-weight: bold;
            color: #15803d;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .muted {
            font-size: 10px;
            color: #333;
        }

        .divider {
            border-top: 1px dashed #166534;
            margin: 8px 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #86efac;
            padding: 4px;
            text-align: left;
        }

        .items-table th {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>

<body>

    <div class="label-container">

        <div class="box">
            <div class="row">
                <div class="col big">
                    Pincode: <?php echo e(optional($order->shippingAddress ?? $order->billingAddress)->pincode ?? '-'); ?>

                </div>
                <div class="col right big">
                    COD Amount: Rs. <?php echo e(number_format($order->net_amount, 0)); ?>

                </div>
            </div>
        </div>

        <div class="center title" style="margin-top:8px;">
            BUSINESS PARCEL<br>
            CASH ON DELIVERY (COD)
        </div>

        <div class="center muted" style="margin-top:4px;">
            <strong>Order No: <?php echo e($order->order_no); ?></strong><br>
            Payment Office : Rajkot H.O. <br>
            Register No / E-Biller ID : 1211658094<br>
            Order Date: <?php echo e($order->created_at ? $order->created_at->format('d-m-Y H:i') : 'N/A'); ?>

        </div>

        <div class="divider"></div>

        <?php
            $address = $order->shippingAddress ?? $order->billingAddress;
        ?>

        <div class="box">
            <div style="font-weight: bold; text-decoration: underline; margin-bottom: 4px;">To,</div>
            <strong>Name:</strong> <?php echo e($order->party->name ?? 'N/A'); ?><br>
            <?php if($address): ?>
                <strong>Address:</strong> <?php echo e($address->address_line_1); ?><br>
                <?php if($address->address_line_2): ?> 
                    <?php echo e($address->address_line_2); ?><br>
                <?php endif; ?>
                <?php if($address->village): ?> 
                    <strong>Village:</strong> <?php echo e($address->village->village_name ?? $address->city ?? '-'); ?>,
                <?php endif; ?>
                <?php if($address->village && $address->village->taluka_name): ?> 
                    <strong>Taluka:</strong> <?php echo e($address->village->taluka_name); ?>, 
                <?php endif; ?>
                <strong>District:</strong> <?php echo e($address->village->district_name ?? '-'); ?><br>
                <strong>Post Office:</strong> <?php echo e($address->village->post_so_name ?? '-'); ?><br>
                <strong>State:</strong> <?php echo e($address->state); ?> - <?php echo e($address->pincode); ?><br>
            <?php else: ?>
                <strong>Address:</strong> N/A (No Address details available)<br>
            <?php endif; ?>
            <strong>Contact:</strong> <?php echo e($order->party->phone ?? 'N/A'); ?> <?php if($order->party->phone_number_2): ?> / <?php echo e($order->party->phone_number_2); ?> <?php endif; ?>
        </div>

        <div class="box">
            <div style="font-weight: bold; text-decoration: underline; margin-bottom: 4px;">From (Sender),</div>
            <strong><?php echo e($order->warehouse?->company_name ?: 'Krushify Agro Pvt. Ltd.'); ?></strong><br>
            <?php if($order->warehouse && $order->warehouse->address_line_1): ?>
                <?php echo e($order->warehouse->address_line_1); ?><br>
                <?php if($order->warehouse->address_line_2): ?><?php echo e($order->warehouse->address_line_2); ?><br><?php endif; ?>
                <?php echo e($order->warehouse->city ?? 'Rajkot'); ?>, <?php echo e($order->warehouse->state ?? 'Gujarat'); ?> - <?php echo e($order->warehouse->pincode ?? '360003'); ?>.
            <?php else: ?>
                Plot No 19, Raj Ind Amul Cross Road,<br>
                Ruda Transport Nagar,<br>
                360003 Rajkot, Gujarat.
            <?php endif; ?>
            | <strong>Mobile:</strong> <?php echo e($order->warehouse?->phone ?: '9199125925'); ?><br>
            <strong>GST:</strong> <?php echo e($order->warehouse?->gstin ?: '24AAMCK0386L1Z6'); ?>

        </div>

        <div class="divider"></div>

        <div class="muted center">
            If article undelivered, please arrange return to <strong>Rajkot H.O.</strong><br>
            <em>“I hereby certify that this article does not contain any dangerous or prohibited goods according to
                Indian Post rules.”</em>
        </div>

    </div>

</body>

</html>
<?php /**PATH /home/user/metis/resources/views/orders/pdf/cod.blade.php ENDPATH**/ ?>