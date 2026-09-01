<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Barcode Printer</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<div></div>
<script src="https://cdn.socket.io/4.0.1/socket.io.min.js" integrity="sha384-LzhRnpGmQP+lOvWruF/lgkcqD+WDVt9fU3H4BWmwP5u5LTmkUGafMcpZKNObVMLU" crossorigin="anonymous"></script>
<script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>

<script>
    let ip_address = '192.168.43.180';
    let socket_port = '12345';
    let socket = io(ip_address + ':' + socket_port);

    socket.broadcast.emit('hi');

</script>
</body>
</html>
