<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
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
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #2A3239;
            color: #ffffff;
            padding: 12px;
            text-align: left;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .success-box {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Order Confirmed</h1>
        </div>
        <div class="body">
            <div class="success-box">
                <p><strong>Thank you for your order!</strong></p>
                <p>We have received your order and it's being processed.</p>
            </div>

            <p>Dear {{ $order->first_name }},</p>
            <p>Your order has been successfully placed. Here are the details:</p>
            
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
                        <td>Payment Method:</td>
                        <td>{{ $order->payment_label }}</td>
                    </tr>
                </table>
            </div>

            <p><strong>Order Items:</strong></p>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Size</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->product->simple_name }}</td>
                        <td>{{ $item->size ?? '-' }}</td>
                        <td>{{ $item->qty }}</td>
                        <td>{{ number_format($item->item_price, 2) }}</td>
                        <td>{{ number_format($item->total_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="order-info">
                <table>
                    <tr>
                        <td>Subtotal:</td>
                        <td>{{ number_format($order->total_price - $order->shipping_fee - $order->cod_fee, $order->curr_type === 'SYP' ? 0 : 2) }} {{ $order->curr_type ?: ($order->country_id == 2 ? 'AED' : 'USD') }}</td>
                    </tr>
                    <tr>
                        <td>Discount:</td>
                        <td>-{{ number_format($order->discount, $order->curr_type === 'SYP' ? 0 : 2) }} {{ $order->curr_type ?: ($order->country_id == 2 ? 'AED' : 'USD') }}</td>
                    </tr>
                    <tr>
                        <td>Shipping Fee:</td>
                        <td>{{ number_format($order->shipping_fee, $order->curr_type === 'SYP' ? 0 : 2) }} {{ $order->curr_type ?: ($order->country_id == 2 ? 'AED' : 'USD') }}</td>
                    </tr>
                    @if($order->cod_fee > 0)
                    <tr>
                        <td>COD Fee:</td>
                        <td>{{ number_format($order->cod_fee, $order->curr_type === 'SYP' ? 0 : 2) }} {{ $order->curr_type ?: ($order->country_id == 2 ? 'AED' : 'USD') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td><strong>{{ number_format($order->total_price, $order->curr_type === 'SYP' ? 0 : 2) }} {{ $order->curr_type ?: ($order->country_id == 2 ? 'AED' : 'USD') }}</strong></td>
                    </tr>
                    @if($order->display_currency && $order->display_rate > 0)
                    <tr>
                        <td>Approximate display value:</td>
                        <td>≈ {{ number_format($order->total_price * $order->display_rate, $order->display_currency === 'SYP' ? 0 : 2) }} {{ $order->display_currency }} (display only)</td>
                    </tr>
                    @endif
                </table>
            </div>

            <p><strong>Shipping Address:</strong></p>
            <p>{{ $order->first_name }} {{ $order->last_name }}<br>
            {{ $order->address }}, {{ $order->city }}<br>
            Building: {{ $order->building_name }}, Flat: {{ $order->flat_number }}<br>
            Phone: {{ $order->phone }}</p>

            <p>We will notify you once your order status changes. You can track your order status by contacting our support team.</p>

            <p>If you have any questions, please don't hesitate to contact us.</p>
            
            <p>Thank you for shopping with OneWay!</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} OneWay. All rights reserved.
        </div>
    </div>
</body>
</html>
