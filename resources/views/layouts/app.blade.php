<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GHG Dashboard')</title>

    <!-- Bootstrap 5 + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Inter font (keeps the same look) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            overflow-x: hidden;
        }

        /* Sidebar */
        #sidebar {
            width: 250px;
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #e5e7eb;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            transition: transform 0.28s ease;
            box-shadow: 2px 0 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }

        /* On small screens hide sidebar by default */
        @media (max-width: 991.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
        }

        /* Sidebar header/logo block */
        .sidebar-brand {
            margin: 14px;
            padding: 12px;
            border-radius: 12px;
            text-align: center;
            background: linear-gradient(90deg,#0d6efd,#60a5fa);
            box-shadow: 0 4px 10px rgba(14,50,112,0.12);
        }
        .sidebar-brand img { height: 48px; width: auto; }

        /* Sidebar nav items */
        .sidebar-nav {
            padding: 0.75rem;
            overflow-y: auto;
            flex: 1;
        }
        .sidebar-item {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .6rem .9rem;
            color: #334155;
            border-radius: 8px;
            text-decoration: none;
            transition: all .15s ease;
            border-left: 4px solid transparent;
            font-weight: 500;
        }
        .sidebar-item:hover {
            background: #f1f5f9;
            color: #0d6efd;
        }
        .sidebar-item.active {
            background: #e6f4ff;
            color: #0d6efd;
            border-left-color: #0d6efd;
            font-weight: 600;
        }

        /* main content layout */
        .main-content {
            margin-left: 250px;
            transition: margin-left .28s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 991.98px) {
            .main-content { margin-left: 0; }
        }

        /* header */
        header.site-header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
            padding: 12px 20px;
            z-index: 1035;
        }

        /* card style like your tailwind version */
        .dashboard-card {
            background: #fff;
            border: 1px solid #e6eef8;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(7,22,48,0.04);
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(7,22,48,0.08);
        }

        /* scrollbar color (webkit) */
        .sidebar-nav::-webkit-scrollbar { width: 8px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: #93c5fd; border-radius: 6px; }
        .sidebar-nav::-webkit-scrollbar-track { background: #f1f5f9; }
    </style>

    @stack('styles')
</head>
<body>
{{-- Sidebar include (blade) --}}
@include('layouts.sidebar')

{{-- Main content container --}}
<div class="main-content" id="mainContent">
    <header class="site-header d-flex align-items-center justify-content-between sticky-top">
        <div class="d-flex align-items-center">
            <button class="btn btn-outline-secondary d-lg-none me-2" id="btnToggleSidebar" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="mb-0 fw-semibold">@yield('page-title','Dashboard')</h5>
        </div>

        <div class="d-flex align-items-center">
            <span class="text-muted small me-3 d-none d-sm-inline">@yield('company-info', 'Acme Manufacturing | FY 2024 (Q3)')</span>
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;">AM</div>
        </div>
    </header>

    <main class="p-4 bg-light flex-grow-1">
        @yield('content')
    </main>
</div>

<!-- Chart.js (kept) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Bootstrap bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // sidebar toggle (mobile)
    (function () {
        const sidebar = document.getElementById('sidebar');
        const btn = document.getElementById('btnToggleSidebar');
        const main = document.getElementById('mainContent');

        if (!btn || !sidebar) return;

        btn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // click outside to close on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 991.98) {
                if (!sidebar.contains(e.target) && !btn.contains(e.target) && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            }
        });

        // close sidebar on link click (mobile)
        sidebar.addEventListener('click', (e) => {
            const target = e.target.closest('a');
            if (target && window.innerWidth <= 991.98) {
                sidebar.classList.remove('show');
            }
        });
    })();
</script>

@stack('scripts')
</body>
</html>
