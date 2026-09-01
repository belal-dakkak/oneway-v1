<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
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
            font-size: 10px;
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
            padding: 0% 1.5%;
        }

        .border {
            border: 1px solid #CCCCCC !important;
        }

        .small_text {
            font-size: 17px !important;
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
            font-size: 17px !important;
        }
        .td-med{
            border:solid 1px #1d1d1d;
            font-size: 12px !important;
            text-align: center !important;
            color: #000 !important
        }
        .td-med1{
            border:solid 1px #1d1d1d;
            font-size: 10px !important;
        }
        .td-font{
            border:solid 1px #1d1d1d;
            font-size: 10px !important;
        }
        td {
            padding: 3px !important;
        }

		@page  {
			/*margin: 2cm 2cm 1.5cm 1.5cm; You can specify the margin of the page*/
			/*size: 21cm 29.7cm; You can specify the print size as well*/
			margin: .5cm;
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
                        <img src="{{ asset('custom/logo-icon-black.png') }}" align="center" style="border-radius: 20px;align-items:center;margin-left: 3%;margin-bottom: -20px;margin-top: 40px;" width="120" height="120" alt="" srcset=""><br>
                        {{--  <span align="center" style="text-align: center;font-size:18px;margin-left:2%;"> وان واي لتجارة الملابس ذ. م . م</span><br>  --}}
                        {{--  <span align="center" style="text-align: center;font-size:18px;">ONE WAY CLOTHING TRADING</span>  --}}
						                        <h2 style="color:black;font-weight: bold;font-size:20px; text-align:center;margin-left: 100px !important;" id="invoice">Sales Statement</h2><br><br>

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



        <div class="row" style="margin-bottom: 50px">
            <table height="82" class=" " style=" width:100%;">
                <tr class="bg_color1">
                    <td class="td-med"> المحل </td>
                    <td class="td-med"> التاريخ </td>
                    <td class="td-med"> إجمالي المرتجعات </td>
                    <td class="td-med"> اجمالي عدد العناصر </td>
                    <td class="td-med"> اجمالي المبيعات بدون الضريبة </td>
                    <td class="td-med"> إجمالي الضريبه	 </td>
                    <td class="td-med"> اجمالي المبيعات شاملة الضريبة </td>
                    <td class="td-med"> عملة الدفع </td>
                </tr>

                @if($all_orders != null && array_key_exists('orders',$all_orders))

                    @foreach($all_orders['orders'] as $item)

                            <tr class=" ">
                                <td class="td-med"> {{ $item['shop_name'] }} </td>
                                <td class="td-med"> {{ $item['date'] }} </td>
                                <td class="td-med"> {{ $item['total_refund'] ? $item['total_refund'] : 0 }} </td>
                                <td class="td-med"> {{ $item['count'] ? $item['count'] : 0 }} </td>
                                <td class="td-med"> {{ $item['price_without_tax'] ? $item['price_without_tax'] : 0 }} </td>
                                <td class="td-med"> {{ $item['tax_value'] ? $item['tax_value'] : 0 }} </td>
                                <td class="td-med"> {{ $item['total_price'] ? $item['total_price'] : 0 }} </td>
                                <td class="td-med"> AED </td>
                            </tr>

                    @endforeach

                @endif

            </table>

        </div>


        <div class="row">
            <table height="82" class=" " style=" width:100%;">
                <tr class="bg_color1">
                    <td colspan="3" style="width: 23% !important" class="td-med"></td>
                    <td class="td-med"> إجمالي المرتجعات </td>
                    <td class="td-med"> اجمالي عدد العناصر </td>
                    <td class="td-med"> اجمالي المبيعات بدون الضريبة </td>
                    <td class="td-med"> إجمالي الضريبه </td>
                    <td class="td-med"> اجمالي المبيعات شاملة الضريبة	 </td>

                </tr>

                <tr class=" ">
                    <td colspan="3" style="width: 23% !important" class="td-med"></td>
                    <td class="td-med"> {{ $all_orders != null && array_key_exists('totalRefunds',$all_orders) ? number_format($all_orders['totalRefunds'],2) : 0 }} </td>
                    <td class="td-med"> {{ $all_orders != null && array_key_exists('count',$all_orders) ? number_format($all_orders['count'],2) : 0 }} </td>
                    <td class="td-med"> {{ $all_orders != null && array_key_exists('total_price_without_tax',$all_orders) ? number_format($all_orders['total_price_without_tax'],2) : 0 }} </td>
                    <td class="td-med"> {{ $all_orders != null && array_key_exists('total_tax_value',$all_orders) ? number_format($all_orders['total_tax_value'],2) : 0 }} </td>
                    <td class="td-med"> {{ $all_orders != null && array_key_exists('total',$all_orders) ? number_format($all_orders['total'],2) : 0 }} </td>

                </tr>
            </table>
        </div>




    </div>
    </div>

</body>
</html>
