<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database temporarily unavailable | AquaTrack</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; box-sizing: border-box; background: #f4f8ff; color: #17224d; font: 16px/1.6 "Segoe UI", sans-serif; }
        main { width: min(100%, 34rem); padding: 2rem; background: #fff; border: 2px solid #3d4ca6; box-shadow: 6px 6px 0 #9bbbff; }
        h1 { margin-top: 0; color: #3d4ca6; font-size: 1.5rem; }
        a { display: inline-block; margin-top: .75rem; padding: .65rem 1rem; background: #3d4ca6; color: #fff; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
    <main>
        <h1>Database temporarily unavailable</h1>
        <p>{{ \App\Support\DatabaseFailure::message() }}</p>
        <p>If you manage this local installation, start the MySQL service and verify the <code>DB_*</code> settings in <code>.env</code>.</p>
        <a href="{{ url()->current() }}">Try again</a>
    </main>
</body>
</html>
