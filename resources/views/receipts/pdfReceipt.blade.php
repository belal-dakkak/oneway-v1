<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>One Way</title>
    <meta name="description" content="لقد قمتم بطلب الفاتورة من محلات وان واي" />
    <meta property="og:title" content="You have requested the bill from One Way stores"/>
    <meta property="og:url" content="https://www.bilalcollections.com" />
    <meta property="og:description" content="لقد قمتم بطلب الفاتورة من محلات وان واي" />
    <meta property="og:image" content="{{asset('custom/logo-icon.png')}}" />
    <meta property="og:type" content="article" />

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <link rel="stylesheet" type="text/css" href="https://www.fontstatic.com/f=bein-normal" />


    <style>
        body{
            font-size: 20px; line-height: 40px;
            font-family: 'bein-normal', sans-serif;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 40px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            color: #555;
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }

        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }

        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title {
            font-size: 20px;
            line-height: 20px;
            color: #333;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }

        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }

        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }

            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }

        /** RTL **/
        .invoice-box.rtl {
            direction: rtl;
        }

        .invoice-box.rtl table {
            text-align: right;
        }

        .invoice-box.rtl table tr td:nth-child(2) {
            text-align: left;
        }
    </style>
</head>

<body>
@php
    $currency = strtoupper($order->curr_type ?: 'USD');
    $moneyDecimals = $currency === 'SYP' ? 0 : 2;
    $formatMoney = static function ($value) use ($moneyDecimals, $currency) {
        return number_format((float) $value, $moneyDecimals, '.', ',').' '.$currency;
    };
@endphp
<div class="invoice-box rtl">
    <table cellpadding="0" cellspacing="0" style="margin-bottom: 20px">
        <tr class="top">
            <td colspan="5">
                <table dir="ltr">
                    <tr>
                        <td class="title" style="float: left">
{{--                            <img src="{{public_path(). '/custom/logo-icon.png'}}" style="width: 100%; max-width: 300px" />--}}
                            <img src="{{asset('custom/logo-icon.png')}}" width="300" height="300" style="width: 100%; border-radius: 20px;" />
                        </td>
                        <td></td>
                        <td></td>

                        <td dir="rtl" style="font-size: 20px; line-height: 40px">
                            Invoice / الفاتورة #: {{$order->barcode}}<br />
                            Sent date / تاريخ الإرسال: {{$order->sent_date}}<br />
                            Order date /تاريخ الطلبية: {{$order->date}}<br />

                            <br>
                            <br>
                            <br>
                            {{$settings['title']}}<br />
                            <span dir="ltr">Tel / الهاتف: {{$settings['phone']}}</span><br />

                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr class="information">
            <td colspan="2">
            </td>
        </tr>

        @if($order->buyer)
            <tr class="heading" >
                <td>اسم الزبون/ة / Customer name</td>
                <td></td>
                <td></td>

                <td>رقم الزبون/ة / Customer number</td>
            </tr>
            <tr class="details">
                <td>{{$order->buyer->name}}</td>
                <td></td>
                <td></td>

                <td dir="ltr">{{$order->buyer->phone}}</td>
            </tr>
        @endif

        @if($order->shipper)
            <tr class="heading">
                <td>اسم شركة الشحن / Shipping company name</td>
                <td></td>
                <td></td>

                <td>رقم شركة الشحن / Shipping company phone number</td>
            </tr>

            <tr class="details">
                <td>{{$order->shipper->name}}</td>
                <td></td>
                <td></td>

                <td dir="ltr">{{$order->shipper->phone}}</td>
            </tr>
        @endif

        <tr class="heading">
            <td>المنتج / product</td>
            <td align="center">السعر / price</td>
            <td align="center">العدد / QTY</td>
            <td align="center">السعر الإجمالي / Total</td>
        </tr>
		
        @foreach($items as $item)
        <tr class="item">
            <td>{{$item->name}}</td>

            <td align="center">{{$formatMoney($item->item_price)}} &nbsp;&nbsp;&nbsp;</td>
            <td align="center">({{$item->qty}}x) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td align="center">{{$formatMoney($item->total_price)}}</td>
        </tr>
        @endforeach
    </table>
    <br>
    <hr>
    <br>
    <table cellpadding="0" cellspacing="0">
        @if($order->discount > 0)
            <tr class="total">
                <td style="font-weight: bold">إجمالي الفاتورة قبل الخصم <br> Total bill before discount</td>
                <td></td>
                <td>{{$formatMoney($order->total_price_before_discount)}}</td>
            </tr>
            <tr class="total">
                <td style="font-weight: bold">الخصم <br> Discount</td>
                <td></td>
                <td>{{$formatMoney($order->discount)}}</td>
            </tr>
        @endif
        <tr class="total">
            <td style="font-weight: bold">إجمالي الفاتورة <br> Total bill</td>
            <td></td>
            <td style="font-weight: bolder; color: darkslateblue">{{$formatMoney($order->total_price)}}</td>
        </tr>
        @if(isset($displayTotal) && $displayTotal !== null)
            <tr class="total">
                <td style="font-weight: bold">المقابل التقريبي (للعرض فقط)<br>Approximate display value</td>
                <td></td>
                <td style="font-weight: bolder; color: #64748b">≈ {{ number_format($displayTotal, $displayDecimals, '.', ',') }} {{ $displayCurrency }}</td>
            </tr>
        @endif
        <tr class="total">
            <td style="font-weight: bold">إجمالي المدفوعات <br> Total payments</td>
            <td></td>
            <td style="font-weight: bolder; color: darkslateblue">{{$formatMoney($order->paid_price)}}</td>
        </tr>
        <tr class="total">
            <td style="font-weight: bold;">الباقي <br> rem of amount</td>
            <td></td>
            <td style="font-weight: bolder; color: darkred">{{$formatMoney($order->remain_price)}}</td>
        </tr>
    </table>

    {{-- <div style="display: flex; justify-content: space-between; margin-top: 100px">
        <div style="display: flex; justify-content: flex-end">
            <svg fill="#000000" xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 50 50" width="40px" height="40px">    <path d="M25,3C12.85,3,3,12.85,3,25c0,11.03,8.125,20.137,18.712,21.728V30.831h-5.443v-5.783h5.443v-3.848 c0-6.371,3.104-9.168,8.399-9.168c2.536,0,3.877,0.188,4.512,0.274v5.048h-3.612c-2.248,0-3.033,2.131-3.033,4.533v3.161h6.588 l-0.894,5.783h-5.694v15.944C38.716,45.318,47,36.137,47,25C47,12.85,37.15,3,25,3z"/></svg>
            <p style="line-height: 10px; padding-right: 5px">Bilal collection</p>
        </div>
        <div style="display: flex; justify-content: flex-end">
            <svg fill="#000000" xmlns="http://www.w3.org/2000/svg"  viewBox="0 0 50 50" width="40px" height="40px">    <path d="M41,4H9C6.243,4,4,6.243,4,9v32c0,2.757,2.243,5,5,5h32c2.757,0,5-2.243,5-5V9C46,6.243,43.757,4,41,4z M37.006,22.323 c-0.227,0.021-0.457,0.035-0.69,0.035c-2.623,0-4.928-1.349-6.269-3.388c0,5.349,0,11.435,0,11.537c0,4.709-3.818,8.527-8.527,8.527 s-8.527-3.818-8.527-8.527s3.818-8.527,8.527-8.527c0.178,0,0.352,0.016,0.527,0.027v4.202c-0.175-0.021-0.347-0.053-0.527-0.053 c-2.404,0-4.352,1.948-4.352,4.352s1.948,4.352,4.352,4.352s4.527-1.894,4.527-4.298c0-0.095,0.042-19.594,0.042-19.594h4.016 c0.378,3.591,3.277,6.425,6.901,6.685V22.323z"/></svg>
            <p style="line-height: 10px; padding-right: 5px">@bilalcollection</p>
        </div>
    </div> --}}
</div>
</body>
</html>
