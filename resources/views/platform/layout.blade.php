<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Back office') — {{ config('app.name') }}</title>
    <style>
        :root {
            color-scheme: light dark;
            --ground: #f1f4f5;
            --surface: #ffffff;
            --surface-2: #e7ecee;
            --ink: #14202a;
            --ink-2: #4c5c68;
            --ink-3: #7b8b96;
            --rule: #d3dbdf;
            --accent: #1f4e5f;
            --ok: #1f6b41;
            --ok-bg: #dff0e5;
            --warn: #8a4b1e;
            --warn-bg: #f7e9df;
            --bad: #8c2f2f;
            --bad-bg: #f6e2e2;
            --mute-bg: #e7ecee;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --ground: #0d151b; --surface: #15212a; --surface-2: #1d2c36;
                --ink: #e3eaed; --ink-2: #a2b1bb; --ink-3: #74858f;
                --rule: #2a3a45; --accent: #79b8cc;
                --ok: #7fc9a0; --ok-bg: #16302270;
                --warn: #db9560; --warn-bg: #2d1e1470;
                --bad: #e08a8a; --bad-bg: #2e181870;
                --mute-bg: #1d2c36;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--ground); color: var(--ink);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            font-size: 15px; line-height: 1.55;
        }
        header {
            background: var(--surface); border-bottom: 1px solid var(--rule);
            padding: .85rem 1.5rem; display: flex; align-items: center;
            justify-content: space-between; gap: 1rem; flex-wrap: wrap;
        }
        header .brand { font-weight: 600; letter-spacing: -.01em; }
        header .brand small { color: var(--ink-3); font-weight: 400; margin-left: .5rem; }
        main { max-width: 1100px; margin: 0 auto; padding: 1.75rem 1.5rem 4rem; }
        h1 { font-size: 1.4rem; margin: 0 0 .35rem; letter-spacing: -.012em; }
        .sub { color: var(--ink-3); margin: 0 0 1.5rem; font-size: .93rem; }
        .card {
            background: var(--surface); border: 1px solid var(--rule);
            border-radius: 6px; padding: 1.25rem 1.35rem; margin-bottom: 1.25rem;
        }
        .card h2 { font-size: 1.02rem; margin: 0 0 .9rem; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; }
        th {
            text-align: left; font-size: .68rem; text-transform: uppercase;
            letter-spacing: .1em; color: var(--ink-3); font-weight: 600;
            padding: 0 .75rem .55rem 0; border-bottom: 1px solid var(--rule);
        }
        td { padding: .7rem .75rem .7rem 0; border-bottom: 1px solid var(--rule); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        code, .mono { font-family: ui-monospace, "Cascadia Mono", Consolas, monospace; font-size: .86em; }
        .pill {
            display: inline-block; font-size: .7rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .06em;
            padding: .18rem .45rem; border-radius: 3px;
        }
        .pill-active { background: var(--ok-bg); color: var(--ok); }
        .pill-suspended, .pill-past_due { background: var(--warn-bg); color: var(--warn); }
        .pill-failed { background: var(--bad-bg); color: var(--bad); }
        .pill-provisioning, .pill-archived { background: var(--mute-bg); color: var(--ink-2); }
        label { display: block; font-size: .8rem; color: var(--ink-2); margin-bottom: .25rem; }
        input[type=text], input[type=email], input[type=password], select {
            width: 100%; padding: .5rem .6rem; border: 1px solid var(--rule);
            border-radius: 4px; background: var(--surface); color: var(--ink);
            font: inherit; font-size: .92rem;
        }
        input:focus, select:focus, button:focus-visible {
            outline: 2px solid var(--accent); outline-offset: 1px;
        }
        button, .btn {
            font: inherit; font-size: .88rem; cursor: pointer;
            border: 1px solid var(--rule); background: var(--surface);
            color: var(--ink); padding: .42rem .75rem; border-radius: 4px;
            text-decoration: none; display: inline-block;
        }
        button.primary { background: var(--accent); border-color: var(--accent); color: #fff; }
        button:hover { border-color: var(--ink-3); }
        .grid { display: grid; gap: .85rem; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        .notice { padding: .8rem 1rem; border-radius: 4px; margin-bottom: 1.25rem; border: 1px solid; }
        .notice-ok { background: var(--ok-bg); border-color: var(--ok); color: var(--ink); }
        .notice-bad { background: var(--bad-bg); border-color: var(--bad); color: var(--ink); }
        .errors { margin: 0; padding-left: 1.1rem; }
        .muted { color: var(--ink-3); }
        .right { text-align: right; }
        .tablewrap { overflow-x: auto; }
        form.inline { display: inline; }
    </style>
</head>
<body>
    @auth('platform')
        <header>
            <div class="brand">
                {{ config('app.name') }} <small>back office</small>
            </div>
            <div>
                <span class="muted mono">{{ auth('platform')->user()->email }}</span>
                <form class="inline" method="POST" action="{{ route('platform.logout') }}">
                    @csrf
                    <button type="submit">Sign out</button>
                </form>
            </div>
        </header>
    @endauth

    <main>
        @if (session('status'))
            <div class="notice notice-ok">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="notice notice-bad"><span class="mono">{{ session('error') }}</span></div>
        @endif

        @if ($errors->any())
            <div class="notice notice-bad">
                <ul class="errors">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
