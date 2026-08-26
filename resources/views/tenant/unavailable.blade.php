<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $heading }} — {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: #f6f8f9;
            color: #16232c;
            padding: 1.5rem;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #0e161c; color: #e3eaed; }
            .card { background: #16212a; border-color: #2a3a45; }
            .account { color: #a2b1bb; }
        }
        .card {
            max-width: 30rem;
            background: #fff;
            border: 1px solid #d5dcdf;
            border-radius: 6px;
            padding: 2rem;
        }
        h1 { margin: 0 0 .75rem; font-size: 1.35rem; }
        p { margin: 0 0 1rem; line-height: 1.6; }
        p:last-child { margin-bottom: 0; }
        .account {
            font-size: .875rem;
            color: #5a6772;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $heading }}</h1>
        <p>{{ $message }}</p>
        @if (! empty($account))
            <p class="account">Workspace: {{ $account }}</p>
        @endif
    </div>
</body>
</html>
