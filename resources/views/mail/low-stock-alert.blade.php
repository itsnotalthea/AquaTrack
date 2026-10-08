<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Low stock alert</title>
</head>
<body style="font-family:'Nunito Sans',sans-serif;color:#1f2a5c;line-height:1.6">
    <p>The following AquaTrack stock items are at or below their threshold:</p>

    <ul>
        @foreach ($items as $item)
            <li style="text-transform:capitalize">{{ str_replace('_', ' ', $item->name) }}: <strong>{{ $item->quantity }}</strong> on hand, threshold {{ $item->threshold }}</li>
        @endforeach
    </ul>

    <p>Thresholds can be adjusted in the admin inventory settings.</p>
</body>
</html>