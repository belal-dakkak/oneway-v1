<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Orders - One Way</title>
</head>
<body>
    <?php
        $admin_email  = $settings['email'];
        $admin_mobile = $settings['phone'];
        $shop_address = $settings['address'];
    ?>
    <link href="http://netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">

    <style type="text/css">
        body {
            width: 100% !important;
            min-height: 100% !important;
            font-size: 12px;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;

        }
        .page-break {
            page-break-after: always;
        }

        * {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif !important;
        }

        .container {
            width: 700px;
        }

        .outer_border {
            /* border: 1px solid #999999 !important; */
            /* padding: 4% !important; */
            margin-bottom: 2% !important;
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
            text-align: center !important;
            color: #000 !important
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
    </style>

    <div class="container-fluid" >
    <div class="outer_border" >
        {{-- <h1 style="color:#FECDD3;font-weight: bold;font-size:30px; text-align:center; padding-right: 30px;" id="invoice">INVOICE</h1> --}}

        <div class="row">
            <table width="100%" height="70" border="0" class="table_pad">
                <tr>
                    <td width="35%" style="border: 0;">
                        <div class="pull-left top_box p-4">
                            {{-- <h2 class="text_color1" style="font-size:20px">{{$settings['title']}}</h2> --}}
                            {{$shop_address}}<br> Phone : {{$admin_mobile}} <br> Email : {{$admin_email}} <br> <p style="color:red;">Website : www.oneway.fashion</p>
                        </div>
                    </td>
                    <td width="40%" style="border: 0;">
                        {{-- <h2 style="color:black;font-weight: bold;font-size:30px; text-align:right; padding-right: 30px;" id="invoice">INVOICE</h2><br> --}}
                        <img src="{{ asset('custom/logo-icon-black.png') }}" align="center" style="border-radius: 20px;align-items:center;margin-left: 14%;margin-bottom: -40px;margin-top: 40px;" width="120" height="120" alt="" srcset=""><br>
                        {{--  <span align="center" style="text-align: center;font-size:18px;margin-left:2%;"> وان واي لتجارة الملابس ذ. م . م</span><br>  --}}
                        {{--  <span align="center" style="text-align: center;font-size:18px;">ONE WAY CLOTHING TRADING</span>  --}}
                    </td>
                    <td width="25%" style="border: 0;">
                        {{-- <div class="">
                            <table width="100%" border="0">
                            <tr>
                                <td colspan="2">
                                <div class="bg_color1" style="text-indent:10px;font-size: 14px;width: 50%;height: 26px;line-height: 24px;color:black; ">Merchant </div>
                                <table width="100%" border="0">
                                    <tr>
                                        <td width="100%">Name: {{$creditor->name}}</td>
                                    </tr>
                                    <tr>
                                        <td width="100%">Phone: {{$creditor->phone}}</td>
                                    </tr>
                                </table>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                            </tr>
                            </table>
                        </div> --}}
                    </td>
                </tr>
            </table>
            <table width="100%" height="70" border="0" class="table_pad">
                <tr>
                    <td width="30%" style="border: 0;">
                    </td>
                    <td width="30%" style="border: 0;">
                        <h2 style="color:black;font-weight: bold;font-size:20px; text-align:center;" id="invoice">Sales Statement</h2><br>
                    </td>
                    <td width="40%" style="border: 0;">
                        {{-- <h2 style="color:black;font-weight: bold;font-size:20px; text-align:center;" id="invoice">INVOICE</h2><br> --}}
                        <div  class=" pull-right top_box   p-4">

                            <table width="100%" height="70" border="0" class="table_pad">
                                <tr>
                                    <td width="30%" style="border: 0;" class=""></td>
                                    <td width="30%" class="td-font bg_color1" style="color:black;border:1px solid black;">Date</td>
                                    <td width="40%" class="td-font">{{now()->format('Y-m-d')}} </td>
                                </tr>
                                @if ($startDate)
                                    <tr>
                                        <td width="30%" style="border: 0;" class=""></td>
                                        <td width="30%" class="td-font bg_color1" style="color:black;border:1px solid black;">Start Date</td>
                                        <td width="40%" class="td-font">{{$startDate}} </td>
                                    </tr>
                                @endif
                                @if ($endDate)
                                    <tr>
                                        <td width="30%" style="border: 0;" class=""></td>
                                        <td width="30%" class="td-font bg_color1" style="color:black;border:1px solid black;">End Date</td>
                                        <td width="40%" class="td-font">{{$endDate}} </td>
                                    </tr>
                                @endif
                                {{-- <tr>
                                    <td width="30%" style="border: 0;"  class=""></td>
                                    <td width="30%" class="td-fon bg_color1"style="color:black;border:1px solid black;">ACC</td>
                                    <td width="40%" class="td-font" >{{$debtor->name}}</td>
                                </tr> --}}
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

        </div>

        <dd style="clear:both;"></dd>

        @php
            $total_price_without_tax = 0;
            $total_tax_value = 0;
            $total_price_with_tax = 0;
            $total_order_price = 0;
            $total_qunatity = 0;
            $counter = 1;
        @endphp

        <div class="row" style="margin-bottom: 50px">
            <table height="82" class=" " style=" width:100%;">
                <tr class="bg_color1">
                    <td class="td-med"> NO <br> رقم التسلسل </td>
                    <td class="td-med"> Seller Name <br>  اسم المحل </td>
                    <td class="td-med"> Order NO <br> رقم الفاتورة  </td>
                    <td class="td-med"> Date <br> تاريخ الفاتورة </td>
                    <td class="td-med"> Client Name <br> اسم العميل  </td>
                    @if($seller != null && $seller->enable_tax == 'yes')
                    <td class="td-med"> Customer TRN <br> TRN العميل </td>
                    <td class="td-med"> Total Amount Without Vat<br> اجمالي الفاتورة بدون الضريبة </td>
                    <td class="td-med"> Total Vat<br> اجمالي الضريبة  </td>
                    <td class="td-med"> Total Amount With Vat<br> اجمالي الفاتورة شامل الضريبة </td>
                    @else
                    <td class="td-med"> Total Amount <br> اجمالي الفاتورة  </td>
                    @endif
                    <td class="td-med"> Total number of pieces<br> اجمالي عدد العناصر  </td>
                </tr>

                @if($all_orders != null && is_array($all_orders) && array_key_exists('orders',$all_orders))


                    @foreach($all_orders['orders'] as $key => $item)

                        {{--  @if($item->items()->sum('qty') > 0)  --}}

                            @php
                                $order_price_without_tax = $item->items()->sum('price_without_tax_paid');
                                $order_tax_value = $item->items()->sum('tax_value_paid') ;
                                $order_price_with_tax = $item->items()->sum('price_without_tax_paid') + $item->items()->sum('tax_value_paid') ;
                                $order_total_price = $item->items()->sum('total_price_paid');
                            @endphp

                            <tr class=" ">
                                <td class="td-med"> {{ $counter }} </td>
                                <td class="td-med"> {{ @$item->seller->name }} </td>
                                <td class="td-med"> {{ $item->id }} </td>
                                <td class="td-med"> {{ $item->date }} </td>
                                <td class="td-med"> {{ @$item->buyer->name }} </td>

                                @if($seller != null && $seller->enable_tax == 'yes')
                                    <td class="td-med"> {{ $item->trn }} </td>
                                    <td class="td-med"> {{ $item->items()->sum('qty') > 0 ? number_format($order_price_without_tax,2) : 0 }} </td>
                                    <td class="td-med"> {{ $item->items()->sum('qty') > 0 ?number_format($order_tax_value,2) : 0 }} </td>
                                    <td class="td-med"> {{ $item->items()->sum('qty') > 0 ? number_format($order_price_with_tax,2) : 0 }} </td>
                                @else
                                    <td class="td-med"> {{ number_format($order_total_price,2) }} </td>
                                @endif

                                <td class="td-med"> {{ $item->items()->sum('qty') }} </td>
                            </tr>

                            @php
                                $total_price_without_tax = $total_price_without_tax + ($item->items()->sum('qty') > 0 ? $order_price_without_tax : 0);
                                $total_tax_value = $total_tax_value + ($item->items()->sum('qty') > 0 ? $order_tax_value : 0);
                                $total_price_with_tax = $total_price_with_tax + ($item->items()->sum('qty') > 0 ? $order_price_with_tax : 0);

                                $total_order_price = $total_order_price + ($item->items()->sum('qty') > 0 ? $order_total_price : 0);

                                $total_qunatity = $total_qunatity + $item->items()->sum('qty');

                                $counter = $counter + 1;
                            @endphp

                        {{--  @endif  --}}

                    @endforeach

                @endif

            </table>

        </div>



        <div class="row">
            <table height="82" class=" " style=" width:100%;">
                <tr class="bg_color1">
                    <td colspan="6" @if($seller != null && $seller->enable_tax == 'yes') style="width: 55.5% !important" @else style="width: 71% !important" @endif class="td-med"></td>

                    @if($seller != null && $seller->enable_tax == 'yes')
                        {{--  <td class="td-med">NO.</td>
                        <td height="12" style="padding-left: 10px;" class="td-med">Seller Name <br> اسم المحل</td>  --}}
                        <td class="td-med">Total Amount Without Vat<br>المبلغ الكلي بدون الضريبة</td>
                        <td class="td-med">Total Vat<br>اجمالي الضريبة</td>
                        {{--  <td class="td-med">Amount Paid<br>المبلغ المدفوع</td>
                        <td class="td-med">Remain Amount<br>المبلغ المتبقي</td>  --}}
                        <td class="td-med">Total Amount With Vat<br>المبلغ الكلي شامل الضريبة</td>
                    @else
                        <td class="td-med">Total Amount <br>المبلغ الكلي </td>
                    @endif

                    <td class="td-med">Total number of pieces<br>اجمالي عدد القطع</td>

                </tr>
                @foreach($orders as $key => $item)
                    <tr class=" ">
                        <td colspan="6" @if($seller != null && $seller->enable_tax == 'yes') style="width: 55.5% !important" @else style="width: 71% !important" @endif class="td-med"></td>

                        @if($seller != null && $seller->enable_tax == 'yes')
                            {{--  <td class="td-med"> {{ $key+1 }} </td>
                            <td class="td-med"> {{ @$item->seller->name }} </td>  --}}
                            <td class="td-med"> {{ number_format($total_price_without_tax,2) }} </td>
                            <td class="td-med"> {{ number_format($total_tax_value,2) }} </td>
                            {{--  <td class="td-med"> {{ number_format($item->paid_price,2) }} </td>
                            <td class="td-med"> {{ number_format($item->remain_price,2) }} </td>  --}}
                            <td class="td-med"> {{ number_format($total_price_with_tax,2) }} </td>
                        @else
                            <td class="td-med"> {{ number_format($total_order_price,2) }} </td>
                        @endif

                        <td class="td-med"> {{ $total_qunatity }} </td>
                    </tr>
                @endforeach
            </table>

            {{-- <table height="82" class=" " style=" width:100%;">
                <tr class="">
                    <td width="100%">&nbsp;</td>
                    <td width="60%">&nbsp;</td>
                    <td width="40%"><hr></td>
                </tr>
                <tr class=" ">
                    <td>&nbsp;</td>
                    <td class="td-border">
                        <strong>Total </strong>
                    </td>
                    <td class="td-border">{{$order->total_price}} AED</td>
                </tr>
            </table> --}}

            {{-- <table height="82" class=" " style=" width:100%;">
                <tr class="total">
                    <td width="20%" style="border: 0;" class="td-med"></td>
                    <td width="20%" style="border: 0;" class="td-med"></td>
                    <td width="60%" style="border: 0;" class="td-med"></td>
                </tr>
                <tr class="total">
                    <td width="20%" style="border: 0;" class="td-med"></td>
                    <td width="20%" style="border: 0;" class="td-med"></td>
                    <td width="60%" style="border: 0;" class="td-med"></td>
                </tr>
                <tr class="total">
                    <td width="20%" style="border: 0;" class="td-med"></td>
                    <td width="20%" style="border: 0;" class="td-med"></td>
                    <td width="60%" style="border: 0;" class="td-med"></td>
                </tr>
                <tr class="total">
                    <td width="20%" class="td-med1 bg_color1" style="color: black;">إجمالي حساب التاجر<br> total merchant account</td>
                    <td width="20%" class="td-med1">{{$totalAccount}}</td>
                    <td width="60%" style="border: 0;" class="td-med"></td>
                </tr>
                <tr class="total">
                    <td width="20%" class="td-med1 bg_color1" style="color: black;">إجمالي المدفوعات<br> Total payments</td>
                    <td width="20%" class="td-med1">{{$totalPaid}}</td>
                    <td width="60%" style="border: 0;" class="td-med"></td>
                </tr>

                <tr class="total">
                    <td width="20%" class="td-med1 bg_color1" style="color: black;">إجمالي المرتجعات <br> Total refunds</td>
                    <td width="20%" class="td-med1">{{$totalRefund}}</td>
                    <td width="60%" style="border: 0;" class="td-med"></td>
                </tr>


                <tr class="total">
                    <td width="20%" class="td-med bg_color1" style="color: black;">الباقي <br> rem of amount</td>
                    <td width="20%" style="color: darkred" class="td-med">{{$debit->amount}}</td>
                    <td width="60%" style="border: 0;" class="td-med"></td>
                </tr>
            </table> --}}
        </div>
        {{-- <div class="page-break"></div> --}}
        {{-- <div class="row" style="padding-top: 200px;">
            <div style="text-align:center"> If you have any question about this invoice, please contact <br /> {{$settings['title']}}, {{$admin_mobile}}, {{$admin_email}}
                <br />
                <b>Thank You For Your Business!</b>
            </div>
        </div> --}}
    </div>
    </div>

</body>
</html>
