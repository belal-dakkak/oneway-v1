<!DOCTYPE html>
<html lang="en">


<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title> Order {{ $order->id }} </title>

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'PT Sans', sans-serif;
        }

        @page {
            size: 2.8in 11in;
            margin-top: 0cm;
            margin-left: 0cm;
            margin-right: 0cm;
        }

        table {
            width: 100%;
        }

        tr {
            width: 100%;

        }

        h1 {
            text-align: center;
            vertical-align: middle;
        }

        #logo {
            width: 60%;
            text-align: center;
            -webkit-align-content: center;
            align-content: center;
            padding: 5px;
            margin: 2px;
            display: block;
            margin: 0 auto;
        }


        .items thead {
            text-align: center;
        }

        .center-align {
            text-align: center;
        }

        .bill-details td {
            font-size: 12px;
        }

        .receipt {
            font-size: medium;
        }

        .items {
            border-collapse: collapse
        }

        .items .heading {
            font-size: 12.5px;
            text-transform: uppercase;
            border-top:1px solid black;
            margin-bottom: 4px;
            border-bottom: 1px solid black;
            vertical-align: middle;
        }


        .items td {
            font-size: 15px;
            text-align: center;
            vertical-align: bottom;
        }


        .line {
            border-top:1px solid black !important;
        }

        p {
            padding: 1px;
            margin: 0;
            font-weight: bold !important;
            font-size:24px !important
        }

        section, footer {
            font-size: 12px;
        }

        #bodyContent {
            width: 580px;
            display: block;
            margin: auto;
            border: 1px solid #DDD;
            padding: 10px;
            margin-top: 20px;
        }

        td , th {
            text-align: center;
            font-size: 24px !important;
            font-weight: bold !important
        }

        .items tr td:first-child   {
            text-align: left !important;
        }

        .items td, .items th {
            border: 2px solid #000;
            vertical-align: middle;
            padding: 7px 5px;
            font-weight: bold !important
        }

        .footer_desc li {
            list-style-type: disc !important;
            margin-bottom: 7px !important;
            line-height: 1.4 !important;
            font-size: 28px !important;
            font-weight: bold
        }

        table p {
            font-size: 24px !important;
            font-weight: bold !important
        }

    </style>

</head>

<body>


    <?php
        $admin_email  = $settings['email'];
        $admin_mobile = $settings['phone'];
        $shop_address = $settings['address'];
    ?>

    <div id="bodyContent" style="margin-bottom: 20px;">

        <table style="margin: auto;direction: rtl;display: flex;justify-content: center;margin-bottom: 10px;">
            <tbody>

                <tr style="text-align: center;">
                    <td colspan="2" style="height: 80px !important">
                        <p style="margin-top: 10px;">
                            @if($order->seller && $order->seller->enable_tax == 'yes')
                                <span style="font-size:30px !important">
                                    فاتورة ضريبية / TAX INVOICE
                                </span>
                            @else
                                <span>
                                    فاتوره / Receipt
                                </span>
                            @endif
                        </p>
                    </td>

                </tr>


					@if($order->seller && $order->seller->enable_tax == 'yes')

					<tr style="text-align: center;">
						<td colspan="2" style="height: 80px !important">
							{{ $order->seller->trn }}
							TRN
						</td>

					</tr>
					@endif

            </tbody>
        </table>

        <div>
            <hr style="border: 2px dotted #000;">
        </div>

        <table class="bill-details">
            <tbody>
                <tr>
                    <td>
                        One Way
                    </td>
                    <td>
                        <p>
                            Tel 1 : +971 545 516 995
                        </p>
                        <p>
                            Tel 2 : +971 564 533 655
                        </p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <img class="media" data-src="{{ asset('custom/logo-icon-black.png') }}" src="{{ asset('custom/logo-icon-black.png') }}" style="width: 150px;">

                    </td>
                    <td style="text-align: left;">
                        <p style="margin-bottom: 10px;">
                            Branch 1 : Ajman Industrial 2 Beirut Street
                        </p>
                        <p style="margin-bottom: 10px;">
                            Branch 2 : Sharjah City centre
                        </p>
                        <p style="margin-bottom: 10px;">
                            Branch 3 : Lebanon Beirut
                        </p>
						<p style="margin-bottom: 10px;">
                            Branch 4 : Turkiye Istanbul Merter
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="text-align: center;display: none;">
            <p style="position: relative;margin-bottom: 10px;">
                <span style="display: inline-block;direction: rtl;">
                    <span>

                    </span>
                </span>

                <span style="position: absolute;right: 64%;">
                    123
                </span>
            </p>
            <p style="direction: rtl;">
                فاتوره / Receipt
            </p>
        </div>

        <div>
            <hr style="border: 2px dotted #000;">
        </div>

        <table style="margin: auto;direction: rtl;display: flex;justify-content: center;margin-bottom: 10px;">
            <tbody>

                <tr style="text-align: center;">
                    <td style="height: 80px !important">
                        رقم العملية /  Op.No
                    </td>
                    <td style="height: 80px !important">
                        {{ $order->id }}
                    </td>
                </tr>





            </tbody>
        </table>

        <div>
            <hr style="border: 2px dotted #000;">
        </div>

        <table style="direction: rtl;text-align: center;margin: auto;">
            <tbody>
                <tr>
                    <td>
                        التاريخ / Date
                    </td>
                    <td>
                        {{ Carbon\Carbon::parse($order->created_at)->format('Y-m-d') }}
                    </td>
                    <td>
                        الوقت / Time
                    </td>
                    <td>
                        {{ Carbon\Carbon::parse($order->created_at)->format('h:i A') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div>
            <hr style="border: 2px dotted #000;">
        </div>

        <table style="direction: rtl;text-align: center;margin: auto;">
            <tbody>
                <tr>
                    <td style="height: 40px">
                        العميل / Client
                    </td>
                    <td style="height: 40px">
                        {{ @$order->buyer->name }}
                    </td>
                </tr>

                @if($order->seller && $order->seller->enable_tax == 'yes')
                <tr>
                    <td style="height: 40px">
                    Customer TRN
                    </td>
                    <td style="height: 40px">
                        {{ $order->trn }}
                    </td>
                </tr>
                @endif

                <tr>
                    <td style="height: 40px">
                        البائع / Seller
                    </td>
                    <td style="height: 40px">
                        {{ @$order->seller->name }}
                    </td>
                </tr>
                <tr>
                    <td style="height: 40px">
                        الشاحن / Shipper
                    </td>
                    <td style="height: 40px">
                        {{ @$order->shipper->name }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div>
            <hr style="border: 2px dotted #000;">
        </div>


        <table class="items">
            <thead>
                <tr>
                    <th class="heading">Model</th>
                    <th class="heading">Qty</th>
                    <th class="heading">Rate</th>
                    @if($order->seller && $order->seller->enable_tax == 'yes')
                    <th class="heading">Amount Exel.Vat</th>
                    <th class="heading">Vat @ {{ $order->seller->tax_ratio }}%</th>
                    <th class="heading">Amount Ancl Vat</th>
                    @else
                    <th class="heading">Amount</th>
                    @endif
                </tr>
            </thead>

            @php
                $qty = 0;
                $k = 0;
                $len = $items->count() >= 10 ? 0 : 10 - $items->count();
                $moneyDecimals = strtoupper($Currency) === 'SYP' ? 0 : 2;
            @endphp

            <tbody>

                @foreach($items as $k => $order_item)

                    <tr>
                        <td> {{$order_item->name}} </td>
                        <td> {{$order_item->qty}} </td>
                        <td class="price"> {{ number_format($order_item->item_price, $moneyDecimals) }} {{ $Currency }} </td>

                        @if($order->seller && $order->seller->enable_tax == 'yes')
                        <td class="price"> {{ number_format($order_item->price_without_tax * $order_item->qty, $moneyDecimals)  }} {{ $Currency }} </td>
                        <td class="price"> {{ number_format($order_item->tax_value * $order_item->qty, $moneyDecimals) }} {{ $Currency }} </td>
                        <td class="price"> {{ number_format(($order_item->price_without_tax + $order_item->tax_value) * $order_item->qty, $moneyDecimals) }} {{ $Currency }} </td>
                        @else
                        <td class="price"> {{ number_format($order_item->item_price * $order_item->qty, $moneyDecimals) }} {{ $Currency }} </td>
                        @endif
                    </tr>

                    @php
                        $qty += $order_item->qty;
                    @endphp

                @endforeach

                @php
                    [ 'ar' => $polAr, 'en' => $polEn ] = getRefundPolicy();
                @endphp


                @if($order->seller && $order->seller->enable_tax == 'yes')
                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up line">
                          الإجمالي بدون الضريبة / Total bill EXel.Vat
                    </td>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="line price">
                        {{$order->price_without_tax}}
                    </td>
                </tr>
                @else
                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up line">
                         الإجمالي / Total
                    </td>
                    <td style="text-align: center !important;direction: rtl;" colspan="1" class="line price">
                        {{$order->total_price}}
                    </td>
                </tr>
                @endif


                @if($order->discount > 0)
                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up line">
                        الخصم / Discount
                    </td>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="line price">
                        {{$order->discount}}
                    </td>
                </tr>
                @endif

                @if(!empty($order->shipping_fee) && $order->shipping_fee > 0)
                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up line">
                        رسوم الشحن / Shipping Fee
                    </td>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="line price">
                        {{$order->shipping_fee}} {{ $Currency }}
                    </td>
                </tr>
                @endif

                @if(!empty($order->cod_fee) && $order->cod_fee > 0)
                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up line">
                        رسوم الدفع عند الاستلام / COD Fee
                    </td>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="line price">
                        {{$order->cod_fee}} {{ $Currency }}
                    </td>
                </tr>
                @endif

                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up line">
                        إجمالي الضريبة  / Total VAT
                    </td>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="line price">
                        {{$order->tax_value}}
                    </td>
                </tr>

                @if($order->seller && $order->seller->enable_tax == 'yes')
                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up line">
                         الإجمالي شامل الضريبة / Total bill Incl VAT
                    </td>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="line price">
                        {{ number_format($order->price_without_tax + $order->tax_value, $moneyDecimals) }} {{ $Currency }}
                    </td>
                </tr>
                @endif

                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up line">
                        المدفوع / Paid
                    </td>
                    <td style="text-align: center !important;direction: rtl;" @if($order->seller && $order->seller->enable_tax == 'yes') colspan="3" @else colspan="1" @endif class="line price">
                        {{$order->paid_price}}
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up line">
                        المتبقي / Remaining
                    </td>
                    <td style="text-align: center !important;direction: rtl;" @if($order->seller && $order->seller->enable_tax == 'yes') colspan="3" @else colspan="1" @endif class="line price">
                        {{$order->remain_price}}
                    </td>
                </tr>

                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up line">
                        إجمالي العدد / Total Qty
                    </td>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="line price">
                        {{$qty}}
                    </td>
                </tr>

                <tr>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="sum-up">
                        طريقة الدفع / Payment Method
                    </td>
                    <td style="text-align: center !important;direction: rtl;" colspan="3" class="price">
                        @php
                            $payment_type = "Pay by Cash";
                            if($order->payment_type == 0){
                                    $payment_type = "Pay by Cash";
                            }elseif($order->payment_type == 1){
                                    $payment_type = "Pay by Credit/Debit Card";
                            }elseif($order->payment_type == 2){
                                    $payment_type = "Pay by Cheque";
                            }
                        @endphp
                        {{$payment_type}}
                    </td>
                </tr>

            </tbody>
        </table>

        <div style="text-align:center;margin: 20px auto;display: flex;flex-direction:column;align-items:center;justify-content: center;">
            {!! DNS1D::getBarcodeSVG($order->barcode, "C128", 2, 60, '#2A3239') !!}
            <p style="font-size:18px !important;font-weight:bold !important;margin-top:4px;letter-spacing:2px;">{{ $order->barcode }}</p>
        </div>

        <div>
            <hr style="border: 2px dotted #000;">
        </div>

        <div style="clear: both"></div>

        <div style="text-align:center;margin: 20px auto;">
            <div style="width: 30%;float: left;">
                {!! DNS2D::getBarcodeSVG('https://www.oneway.fashion', 'QRCODE',7,7) !!}
            </div>

            <div style="width: 70%;float: left;">
                <p style="margin-bottom: 20px">
                    {{ Carbon\Carbon::parse($order->created_at)->format('Y-m-d h:i A') }}
                </p>
                <p style="font-size: 25px !important;font-weight: bold !important;margin-bottom: 10px;">
                    لتصفح الموديلات
                    <br>
                    To Browse Models
                </p>
                <p>
                    https://www.oneway.fashion/
                </p>
            </div>
        </div>

        <div style="clear: both;margin-bottom:50px"></div>

        <p style="text-align: center;font-size: 40px !important;font-weight: bold !important;border-bottom: 1px solid #000;">
            سياسة الأستبدال
        </p>

        <div lang="ar" dir="rtl">
            <ul class="footer_desc">
                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                    مده الاستبدال 3 أيام من تاريخ الفاتورة
                </li>
                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                    لا يوجد لدينا ترجيع واسترداد نقدى
                </li>
                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                    يجب أن تكون السلع المراد استبدالها بحالة جيدة وقابلة للعرض بغلافها وبطاقتها .
                </li>
                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                    يجب أن تكون السلع المراد استبدالها مرفقة بايصال الشراء الأصلى.
                </li>
                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                    يجب ان تكون السلع المراد استبدالها غير مطابقة للمواصفات القياسية أو بها خلل أو عيب لا يكون ظاهرا عند الشراء.
                </li>

            </ul>
        </div>

        <div lang="en" dir="ltr" style="margin-top:30px !important">
            <ul class="footer_desc">
                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                    The replacement period is 3 days from the date
                </li>
                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                    We do not offer returns or cash refunds
                </li>
                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                    The goods to be exchanged must be in good condition, and displayable with their packaging and tags
                </li>
                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                    The goods to be exchanged must be accompanied by the original purchase receipt.
                </li>
                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                    The goods to be replaced must not conform to standard specifications or have a defect or defect that is not apparent upon purchase.
                </li>


            </ul>
        </div>

        <div style="text-align: center;">
            <p>
                شكرا لزيارتكم وان واي
            </p>
            <p>
                Thank You For Visiting Oneway
            </p>
        </div>


        <script>
            window.print();
        </script>



    </div>

</body>

</html>
