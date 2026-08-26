<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
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
            code { background: #1e2c36; }
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
        code {
            background: #eef1f3;
            padding: .15em .4em;
            border-radius: 3px;
            font-size: .9em;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ config('app.name') }}</h1>
        <p>Each organisation has its own address. Sign in at your organisation's, which looks like <code>yourcompany.{{ config('tenancy.central_domains')[0] ?? 'example.com' }}</code>.</p>
        <p>If you don't know yours, ask whoever administers your account.</p>
    </div>
</body>
</html>
