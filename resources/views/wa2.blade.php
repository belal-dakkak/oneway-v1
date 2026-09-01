<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>One Way</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<input type="hidden" id="order" value="{{$id}}">
<input type="hidden" id="number" value="{{$number}}">

<script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"></script>
<script>
$(function($) {
    $(document).ready(function() {
        let id = '{{$id}}';
        let number = '{{$number}}';
        let url = encodeURIComponent('{{route('invoice.show', $id)}}');
        window.location.href = `https://wa.me/${number}/?text=${url}`;
    });
})(jQuery);
</script>
</body>
</html>
