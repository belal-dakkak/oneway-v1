<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #2A3239;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 10px 0 0;
            font-size: 24px;
        }
        .body {
            padding: 30px;
            color: #333333;
            line-height: 1.6;
        }
        .body p {
            margin: 0 0 16px;
        }
        .order-info {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .order-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .order-info td {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .order-info td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .order-info tr:last-child td {
            border-bottom: none;
        }
        .footer {
            background-color: #f4f4f4;
            text-align: center;
            padding: 20px;
            font-size: 12px;
            color: #999999;
        }
        .status-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            text-align: center;
        }
        .status-pending {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .status-ongoing {
            background-color: #cce5ff;
            border-color: #007bff;
            color: #004085;
        }
        .status-delivered {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Order Status Update</h1>
        </div>
        <div class="body">
            <p>Dear {{ $order->first_name }},</p>
            
            <div class="status-box {{ $newStatus == 'Pending' ? 'status-pending' : ($newStatus == 'Ongoing' ? 'status-ongoing' : 'status-delivered') }}">
                <p><strong>Your order status has been updated!</strong></p>
                <p>Order #{{ $order->barcode }} is now: <strong>{{ $newStatus }}</strong></p>
            </div>

            <p>Here are the details of your order:</p>
            
            <div class="order-info">
                <table>
                    <tr>
                        <td>Order Number:</td>
                        <td>#{{ $order->barcode }}</td>
                    </tr>
                    <tr>
                        <td>Order Date:</td>
                        <td>{{ $order->date }}</td>
                    </tr>
                    <tr>
                        <td>Previous Status:</td>
                        <td>{{ $oldStatus }}</td>
                    </tr>
                    <tr>
                        <td>Current Status:</td>
                        <td>{{ $newStatus }}</td>
                    </tr>
                    <tr>
                        <td>Total Amount:</td>
                        <td>{{ number_format($order->total_price, 2) }} {{ $order->country_id == 2 ? 'AED' : 'USD' }}</td>
                    </tr>
                </table>
            </div>

            @if($newStatus == 'Pending')
            <p>Your order is being processed and will be shipped soon. We will notify you once it's on the way.</p>
            @elseif($newStatus == 'Ongoing')
            <p>Your order is now on the way! It should reach you soon. Thank you for your patience.</p>
            @elseif($newStatus == 'Delivered')
            <p>Your order has been delivered successfully. We hope you enjoy your purchase!</p>
            @endif

            <p>If you have any questions about your order, please don't hesitate to contact us.</p>
            
            <p>Thank you for shopping with OneWay!</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} OneWay. All rights reserved.
        </div>
    </div>
</body>
</html>
