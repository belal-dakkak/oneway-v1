<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>Document</title>
</head>
<body>
    <?php
        $admin_email  = $settings['email'];
        $admin_mobile = $settings['phone'];
        $shop_address = $settings['address'];
    ?>

    <style type="text/css">
        body {
            width: 100% !important;
            min-height: 100% !important;
            font-size: 12px;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;
            padding:0 !important;
            margin:0 !important;

        }
        .page-break {
            page-break-after: always;
        }

        * {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;
            padding:0 !important;
            margin:0 !important;
        }

        body {
            margin: 0px!important;
            padding: 0px!important;
        }



        .container {
            width: 700px;
        }

        .outer_border {
            /* border: 1px solid #999999 !important; */
            /* padding: 4% !important; */
            margin-bottom: 0% !important;
        }

        .top_box {
            width: 47%;
            padding: 0%
        }

        .table_pad {
            padding: 0% 2%;
        }

        .border {
            border: 1px solid #CCCCCC !important;
        }

        .small_text {
            font-size: 20px !important;
        }

        .bg_color1 {
            background: #FECDD3;
            color: #fff;
        }



        .text_color1 {
            color: #FECDD3;
        }
        .td-border{
            border:solid 1px #1d1d1d;
            font-size: 22px !important;
        }
        .td-med{
            border:solid 1px #1d1d1d;
            font-size: 15px !important;
        }
        .td-med1{
            border:solid 1px #1d1d1d;
            font-size: 13px !important;
        }
        .td-font{
            border:solid 1px #1d1d1d;
            font-size: 12px !important;
        }
        td {
            padding: 4px;

        }

        @page {
            margin-top: 20px;
            margin-bottom: 0cm;
            padding-bottom: 0cm;
          }

    </style>

    <div class="container-fluid" >

        <div class="outer_border" style="position:relative" >

            <div class="row">

                <table width="100%" height="70" border="0" class="table_pad">
                    <tr>
                        <td width="35%" style="border: 0;">
                            <div class="pull-left top_box">

                                {{$shop_address}}<br>

                                <span style="color: red">Branch 1</span> : Ajman Industrial 2 Beirut Street <br>
                                <span style="color: red">Branch 2</span> : Sharjah City centre <br>

                                <span style="color: red">Phone</span> : +971 545 516 995 <br>
                                <span style="color: red">Phone</span> : +971 564 533 655 <br>

                                <br>Lebanon, Beirut <br>
                                <span style="color: red">Branch 3</span> : Lebanon Beirut <br>
                                <span style="color: red">Phone</span> : +961 76 658 734 <br>
                                <span style="color: red">Phone</span> : +961 81 730 725 <br>

                            </div>
                        </td>

                        <td width="40%" style="border: 0;">
                            {{-- <h2 style="color:black;font-weight: bold;font-size:30px; text-align:right; padding-right: 30px;" id="invoice">INVOICE</h2><br> --}}
                            <img src="{{ public_path('custom/logo-icon-black.png') }}" align="center" style="border-radius: 20px;align-items:center;margin-left:9%;margin-bottom:-35px;" width="120" height="120" alt="" srcset=""><br><br>

                            @if($order->seller && $order->seller->enable_tax == 'yes')
                            <p align="center" style="text-align: center;font-size:18px;">
                                {{ @$order->seller->name }}
                            </p>
                            @else
                            <span align="center" style="text-align: center;font-size:18px;margin-left:2%;"> وان واي لتجارة الملابس ذ. م . م</span><br>
                            <span align="center" style="text-align: center;font-size:18px;">ONE WAY CLOTHING TRADING</span>
                            @endif

                        </td>

                        <td width="25%" style="border: 0;">
                            <div class="">
                                <table width="100%" border="0">
                                <tr>
                                    <td colspan="2">
                                    <div class="bg_color1" style="text-indent:10px;font-size: 14px;width: 50%;height: 26px;line-height: 24px;color:black; ">BILL TO </div>
                                    <table width="100%" border="0">
                                        <tr>
                                            <td width="100%">Name: {{$order->buyer->name??'No name'}}</td>
                                        </tr>
                                        <tr>
                                            <td width="100%">Phone: {{$order->buyer->phone??'No phone'}}</td>
                                        </tr>
                                        <tr>
                                            <td width="100%"> Address: {{$order->buyer->address??'No address'}} </td>
                                        </tr>
                                    </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2"></td>
                                </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>

            </div>

            <div class="row" style="margin-top:-50px !important">
                <table width="100%" height="70" border="0" class="table_pad">
                    <tr>
                        <td width="50%" style="border: 0;">

                            <br>Türkiye  <br>
                            <span style="color: red">Branch 4</span> : Türkiye Istanbul Merter <br>
                            <span style="color: red">Phone</span> : +905 004001621 <br><br>

                            <p style="color:red;">Website : www.oneway.fashion</p>
                            <span style="color: red">Email</span> : theoneway.fashion@gmail.com <br>

                        </td>
                        <td width="30%" style="border: 0;">
                            <h2 style="color:black;font-weight: bold;font-size:20px; text-align:center;" id="invoice">

                                @if($order->seller && $order->seller->enable_tax == 'yes')
                                    <div style="text-align: center;">
                                        <table height="70" border="0" style="text-align: center !important">
                                            <tr>
                                                <td width="30%" class="td-font bg_color1" style="color:black;border:1px solid black;">TRN</td>
                                                <td width="70%" class="td-font"> {{ $order->seller->trn }} </td>
                                            </tr>
                                        </table>
                                        <br>
                                    </div>
                                @endif

                                <span style="display: block">
                                    {{ $order->seller && $order->seller->enable_tax == 'yes' ? 'TAX INVOICE' : 'INVOICE' }}
                                </span>

                                @if($order->seller && $order->seller->enable_tax == 'yes')
                                    <p> فاتورة ضريبية </p>
                                    <br> <br>
                                @endif
                            </h2>
                            <br>
                        </td>
                        <td width="20%" style="border: 0;">
                            {{-- <h2 style="color:black;font-weight: bold;font-size:20px; text-align:center;" id="invoice">INVOICE</h2><br> --}}
                            <div style="" class=" pull-right top_box   p-4">
                                <table width="100%" height="70" border="0" class="table_pad">
                                    <tr>
                                        <td width="30%" style="border: 0;" class=""></td>
                                        <td width="30%" class="td-font bg_color1" style="color:black;border:1px solid black;">Date</td>
                                        <td width="40%" class="td-font"><?php $invoice_date = date('Y-m-d', strtotime($order->created_at)); echo $invoice_date;?> </td>
                                    </tr>
                                    <tr>
                                        <td width="30%" style="border: 0;"  class=""></td>
                                        <td width="30%" class="td-fon bg_color1"style="color:black;border:1px solid black;">Invoice #</td>
                                        <td width="40%" class="td-font" >{{$order->barcode}}</td>
                                    </tr>
                                    <tr>
                                        <td width="30%" style="border: 0;" class=""></td>
                                        <td width="30%" class="td-font bg_color1" style="color:black;border:1px solid black;">Order ID</td>
                                        <td width="40%" class="td-font">{{$order->id}}</td>
                                    </tr>
                                    @if($order->seller && $order->seller->enable_tax == 'yes')
                                    <tr>
                                        <td width="30%" style="border: 0;" class=""></td>
                                        <td width="30%" class="td-font bg_color1" style="color:black;border:1px solid black;">Customer TRN</td>
                                        <td width="40%" class="td-font">{{$order->trn}}</td>
                                    </tr>
                                    @endif
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <dd style="clear:both;"></dd>

            <div class="row">

                <table height="82" class=" " style=" width:100%;">
                    <tr class="bg_color1">
                        <td width="7%" class="td-med">NO.</td>
                        <td width="30%" height="12" style="padding-left: 10px;" class="td-med">DESCRIPTION</td>
                        <td width="7%" class="td-med">QTY</td>
                        <td width="10%" class="td-med">RATE</td>
                        @if($order->seller && $order->seller->enable_tax == 'yes')
                        <td style="padding-right: 10px;" width="15%" align="right" class="td-med">AMOUNT EXel.Vat</td>
                        <td style="padding-right: 10px;" width="10%" align="right" class="td-med">Vat @ {{ $order->seller->tax_ratio }}%</td>
                        <td style="padding-right: 10px;" width="15%" align="right" class="td-med">AMOUNT Incl VAT</td>
                        @else
                        <td style="padding-right: 10px;" width="15%" align="right" class="td-med">AMOUNT</td>
                        @endif
                    </tr>
                    @php
                        $qty = 0;
                        $k = 0;
                        $len = $items->count() >= 10 ? 0 : 10 - $items->count();
                    @endphp

                    @foreach($items as $k => $order_item)
                        <tr class=" ">
                            <td class="td-med" style=""> {{$k+1}} </td>
                            <td class="td-med" style=""> {{$order_item->name}} </td>
                            <td class="td-med">{{$order_item->qty}}</td>
                            <td class="td-med">{{ round(number_format($order_item->item_price,2)) }} {{ $Currency }} </td>

                            @if($order->seller && $order->seller->enable_tax == 'yes')
                            <td align="center" class="td-med"> {{ number_format($order_item->price_without_tax * $order_item->qty,2) }} {{ $Currency }}</td>
                            <td align="center" class="td-med"> {{ number_format($order_item->tax_value * $order_item->qty,2) }} {{ $Currency }}</td>
                            <td align="center" class="td-med"> {{ round(number_format(($order_item->price_without_tax + $order_item->tax_value)) * $order_item->qty,2) }} {{ $Currency }}</td>
                            @else
                            <td align="center" class="td-med"> {{ round(number_format($order_item->item_price * $order_item->qty,2)) }} {{ $Currency }}</td>
                            @endif
                        </tr>
                        @php
                            $qty += $order_item->qty;
                        @endphp
                    @endforeach

                    {{--  @for($i = 1;$i <= $len;$i++)
                    <tr class=" ">
                        <td class="td-med" style=""> {{$k+1+$i}} </td>
                        <td class="td-med" style="">   </td>
                        <td class="td-med">  </td>
                        <td class="td-med"> </td>
                        <td align="center" class="td-med">  </td>
                    </tr>
                    @endfor  --}}
                </table>


            </div>

            {{-- Refund Policy --}}
            @php
                [ 'ar' => $polAr, 'en' => $polEn ] = getRefundPolicy();
            @endphp

            <div class="row">

                <div style="float: left; width: 28%;">
                    <div style="width: 100% !important;">
                        <table class=" " style=" width:100%;" width="100%">
                            <tr class="total" style="width: 100% !important">
                                <td width="50%" style="border: 0;" class="td-med"></td>
                                <td width="50%" style="border: 0;" class="td-med"></td>
                            </tr>
                            <tr class="total" style="width: 100% !important">
                                <td width="50%" style="border: 0;" class="td-med"></td>
                                <td width="50%" style="border: 0;" class="td-med"></td>
                            </tr>
                            <tr class="total" style="width: 100% !important">
                                <td width="50%" style="border: 0;" class="td-med"></td>
                                <td width="50%" style="border: 0;" class="td-med"></td>
                            </tr>
                            <tr class="total" style="width: 100% !important">
                                <td width="50%" class="td-med1 bg_color1" style="color: black;">إجمالي عدد القطع<br> Total Qty</td>
                                <td width="50%" class="td-med1">{{$qty}}</td>
                            </tr>
                            <tr class="total" style="width: 100% !important">
                                <td width="50%" class="td-med1 bg_color1" style="color: black;">طريقة الدفع<br> Payment Method</td>
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
                                <td width="50%" class="td-med1">{{$payment_type}}</td>
                            </tr>
                            @if($order->discount > 0)
                                <tr class="total" style="width: 100% !important">
                                    <td width="50%" class="td-med1 bg_color1" style="color: black;">إجمالي الفاتورة قبل الخصم <br> Total bill before discount</td>
                                    <td width="50%" class="td-med1">{{$order->total_price_before_discount}}</td>
                                    <td width="60%" style="border: 0;" class="td-med"></td>
                                </tr>
                                <tr class="total" style="width: 100% !important">
                                    <td width="50%" class="td-med1 bg_color1" style="color: black;">الخصم <br> Discount</td>
                                    <td width="50%" class="td-med">{{$order->discount}}</td>
                                </tr>
                            @endif

                            @if($order->seller && $order->seller->enable_tax == 'yes')

                            <tr class="total" style="width: 100% !important">
                                <td width="50%" class="td-med1 bg_color1" style="color: black;">إجمالي  الفاتورة بدون الضريبة <br> Total bill EXel.Vat</td>
                                <td width="50%" class="td-med1">{{$order->price_without_tax}}</td>
                            </tr>

                            <tr class="total" style="width: 100% !important">
                                <td width="50%" class="td-med1 bg_color1" style="color: black;">إجمالي الضريبة  <br> Total VAT</td>
                                <td width="50%" class="td-med1">{{$order->tax_value}}</td>
                            </tr>

                            <tr class="total" style="width: 100% !important">
                                <td width="50%" class="td-med1 bg_color1" style="color: black;">إجمالي الفاتورة شامل الضريبة <br> Total bill Incl VAT</td>
                                <td width="50%" class="td-med1">{{ number_format($order->price_without_tax + $order->tax_value,2) }}</td>
                            </tr>

                            @else
                            <tr class="total" style="width: 100% !important">
                                <td width="50%" class="td-med1 bg_color1" style="color: black;">إجمالي الفاتورة <br> Total bill</td>
                                <td width="50%" class="td-med1">{{$order->total_price}}</td>
                            </tr>
                            @endif

                            <tr class="total" style="width: 100% !important">
                                <td width="50%" class="td-med bg_color1" style="color: black;">إجمالي المدفوعات <br> Total payments</td>
                                <td width="50%" class="td-med">{{$order->paid_price}}</td>
                            </tr>
                            <tr class="total" style="width: 100% !important">
                                <td width="50%" class="td-med bg_color1" style="color: black;">الباقي <br> rem of amount</td>
                                <td width="50%" style="color: darkred" class="td-med">{{$order->remain_price}}</td>
                            </tr>
                        </table>
                    </div>
                </div>


                <div style="float: left; width: 72%;">

                    <div>

                        <h3 style="margin:0;padding:0;line-height:0;direction:rtl;text-align:right">
                            سياسة التبديل
                        </h3>

                        <div lang="ar" dir="rtl">
                            <ul>
                                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                                    مده الاستبدال 3 أيام من تاريخ الفاتورة
                                </li>
                                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                                    يجب أن تكون السلع المراد استبدالها بحالة جيدة وقابلة للعرض بغلافها وبطاقتها ومرفقة بايصال الشراء الأصلى.
                                </li>
                                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                                    يجب ان تكون السلع المراد استبدالها غير مطابقة للمواصفات القيساسية أو بها خلل أو عيب لا يكون ظاهرا عند الشراء.
                                </li>
                                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                                    لا يوجد لدينا ترجيع واسترداد نقدى
                                </li>
                                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                                    يجب استلام الطلبية خلال مده اقصاها 5 ايام من تاريخ الطلب والا تلغى الطلبية بدون استرجاع العربون شكرا لتعاملكم مع وان واي
                                </li>
                            </ul>
                        </div>

                        <div lang="en" dir="ltr" style="margin-top:-20px !important">
                            <ul>
                                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                                    The replacement period is 3 days from the date
                                </li>
                                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                                    The goods to be exchanged must be in good condition, presentable with their packaging and label, and accompanied by the original purchase receipt.
                                </li>
                                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                                    The goods to be replaced must not conform to standard specifications or have a defect or defect that is not apparent upon purchase.
                                </li>
                                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                                    We do not offer returns or cash refunds
                                </li>
                                <li style="list-style-type: disclosure-closed;font-size: 12px;margin-bottom: 0px;line-height:1.2">
                                    The order must be received within a maximum period of 5 days from the date of the order, otherwise the order will be canceled without refunding the deposit. Thank you for dealing with One Way.
                                </li>
                            </ul>
                        </div>


                    </div>

                    <div style="width: 100% !important;">
                        <table class=" " style=" width:100%;" width="100%">
                            <tr class="total" style="width: 100% !important">
                                <td width="20%" class="td-med bg_color1" style="color: black;"> ملاحظات <br> Comments </td>
                                <td width="90%" style="color: darkred" class="td-med"> {{ $order->notes }}   </td>
                            </tr>
                        </table>
                    </div>

                </div>

            </div>

            <div class="row" style="margin-top: 10px">

                <div style="float: left; width: 70%;">
                    <div style="font-size: 15px;font-weight: bold;color: rgba(0, 0, 0, 0.83);">
                        <div style="margin-bottom: 30px; margin-left: 38px">Manager / المدير</div>
                        <div style="width:210px;border-bottom:1px solid rgba(0, 0, 0, 0.3);"></div>
                    </div>
                </div>

                <div style="float: right !important; width: 30%;">
                    <div style="font-size: 15px;font-weight: bold;color: rgba(0, 0, 0, 0.83);">
                        <div style="margin-bottom: 30px; margin-left: 38px;text-align:right !important">Recipient / المستلم</div>
                        <div style="width:210px;border-bottom:1px solid rgba(0, 0, 0, 0.3);"></div>
                    </div>
                </div>

            </div>

            <div class="row">
                <div style="width: 100%;">
                    <div style="text-align:center;padding-top: 0px;">
                        <p style="display:block;margin-bottom:0">
                            If you have any question about this invoice, please contact <br /> {{$settings['title']}}, {{$admin_mobile}}, {{$admin_email}}
                        </p>
                        <b style="display:block">Thank You For Your Business!</b>
                    </div>
                </div>
            </div>


        </div>

    </div>

</body>
</html>
