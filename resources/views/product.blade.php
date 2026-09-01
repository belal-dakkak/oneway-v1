<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Barcode Printer</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @media print {

            @page {
                size: 38mm 25mm;
                margin: 0;
                padding: 0;
            }
            html, body {
                position: relative;
                width: 100%;
                height: 100%;
                max-width: 100%;
                max-height: 97%;
                margin: 0;
                padding: 0;
            }
            svg {
                width: 100%;
                height: 100%;
                max-width: 100%;
                max-height: 100%;
            }
        }
    </style>
</head>
<body>
<svg id="code128"></svg>

<script src="https://cdn.jsdelivr.net/jsbarcode/3.3.16/barcodes/JsBarcode.code128.min.js"></script>
<script>
    JsBarcode("#code128", '{{$product->barcode}}', {
        format: "CODE128",
        displayValue: false
    });
    window.print();
</script>
</body>
</html>
