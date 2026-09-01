<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Received</title>
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
        .btn {
            display: inline-block;
            background-color: #2A3239;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 New Order Received</h1>
        </div>
        <div class="body">
            <p>Hello,</p>
            <p>A new order has been placed on the website. Here are the details:</p>
            
            <div class="order-info">
                <table>
                    <tr>
                        <td>Order Number:</td>
                        <td>#{{ $order->barcode }}</td>
                    </tr>
                    <tr>
                        <td>Customer Name:</td>
                        <td>{{ $order->first_name }} {{ $order->last_name }}</td>
                    </tr>
                    <tr>
                        <td>Email:</td>
                        <td>{{ $order->email }}</td>
                    </tr>
                    <tr>
                        <td>Phone:</td>
                        <td>{{ $order->phone }}</td>
                    </tr>
                    <tr>
                        <td>Payment Method:</td>
                        <td>{{ $order->payment_label }}</td>
                    </tr>
                    <tr>
                        <td>Total Amount:</td>
                        <td>{{ number_format($order->total_price, $order->curr_type === 'SYP' ? 0 : 2) }} {{ $order->curr_type ?: ($order->country_id == 2 ? 'AED' : 'USD') }}</td>
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
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->product->simple_name }}</td>
                        <td>{{ $item->size ?? '-' }}</td>
                        <td>{{ $item->qty }}</td>
                        <td>{{ number_format($item->item_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="order-info">
                <table>
                    <tr>
                        <td>Subtotal:</td>
                        <td>{{ number_format($order->total_price - $order->shipping_fee - $order->cod_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Shipping Fee:</td>
                        <td>{{ number_format($order->shipping_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td>COD Fee:</td>
                        <td>{{ number_format($order->cod_fee, 2) }}</td>
                    </tr>
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td><strong>{{ number_format($order->total_price, 2) }}</strong></td>
                    </tr>
                </table>
            </div>

            <p><strong>Shipping Address:</strong></p>
            <p>{{ $order->address }}, {{ $order->city }}<br>
            Building: {{ $order->building_name }}, Flat: {{ $order->flat_number }}</p>

            <center>
                <a href="{{ $adminUrl }}" class="btn">View Order in Admin Panel</a>
            </center>

            <p>Please process this order as soon as possible.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} OneWay. All rights reserved.
        </div>
    </div>
</body>
</html>
