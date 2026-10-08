<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Your weekly refill reminder</title>
</head>
<body style="font-family:'Nunito Sans',sans-serif;color:#1f2a5c;line-height:1.6">
    <p>Hi {{ $reminder->customer?->name ?? 'there' }}, it is time for your weekly AquaTrack refill. Place your order here: <a href="{{ $url }}">{{ $url }}</a></p>
    <p style="color:#3d4ca6">AquaTrack Water Station</p>
</body>
</html>