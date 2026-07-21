<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Supplier Survey')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-green: #2e7d32;
            --light-green: #4caf50;
            --dark-green: #1b5e20;
            --gray-50: #f8f9fa;
            --gray-600: #5f6368;
            --gray-800: #3c4043;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .portal-header {
            background: #ffffff;
            border-bottom: 1px solid rgba(46, 125, 50, 0.12);
            box-shadow: 0 2px 10px rgba(17, 24, 39, 0.05);
            padding: 14px 0;
        }

        .portal-header .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            color: var(--dark-green);
            font-size: 1.15rem;
        }

        .portal-header .brand i {
            color: var(--primary-green);
        }

        /* Neutralise the admin layout's #content offset in case views reuse the id */
        #content {
            margin: 0 !important;
            padding: 0 !important;
        }

        main.portal-main {
            flex: 1 0 auto;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%);
        }

        .portal-footer {
            flex-shrink: 0;
            text-align: center;
            padding: 18px;
            color: var(--gray-600);
            font-size: 0.85rem;
        }
    </style>

    @stack('styles')
</head>
<body>
    <header class="portal-header">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="brand">
                <i class="fas fa-leaf"></i>
                <span>{{ $survey->company?->name ?? config('app.name', 'GHG Emissions Portal') }}</span>
            </div>
            <span class="text-muted small d-none d-sm-inline">Supplier Data Portal</span>
        </div>
    </header>

    <main class="portal-main">
        @yield('content')
    </main>

    <footer class="portal-footer">
        &copy; {{ date('Y') }} {{ $survey->company?->name ?? config('app.name', 'GHG Emissions Portal') }}. Secure supplier submission.
    </footer>

    @stack('scripts')
</body>
</html>
