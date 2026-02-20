<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GHG Emissions Dashboard</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap 5 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery (required for DataTables + Select2 in some pages) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <!-- Bootstrap Icons (used by some CRUD action buttons) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ApexCharts CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.css">
    
    <style>
        :root {
            --primary-green: #2e7d32;
            --light-green: #4caf50;
            --dark-green: #1b5e20;
            --primary-blue: #0277bd;
            --light-blue: #03a9f4;
            --gray-50: #f8f9fa;
            --gray-100: #f1f3f4;
            --gray-200: #e8eaed;
            --gray-300: #dadce0;
            --gray-600: #5f6368;
            --gray-800: #3c4043;
            --warning-orange: #f57c00;
            --danger-red: #d32f2f;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        #sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #fbfcfd 100%);
            box-shadow: 0 18px 50px rgba(17, 24, 39, 0.10);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            padding: 14px 12px 18px 12px;
            transition: all 0.3s;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(46, 125, 50, 0.3) transparent;
            scroll-behavior: smooth;
            border-right: 1px solid rgba(60, 64, 67, 0.08);
        }
        
        /* Custom Scrollbar for Webkit browsers (Chrome, Safari, Edge) */
        #sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        #sidebar::-webkit-scrollbar-track {
            background: transparent;
            margin: 10px 0;
        }
        
        #sidebar::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(46, 125, 50, 0.4) 0%, rgba(46, 125, 50, 0.6) 100%);
            border-radius: 10px;
            transition: background 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        #sidebar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(46, 125, 50, 0.6) 0%, rgba(46, 125, 50, 0.8) 100%);
            border: 1px solid rgba(46, 125, 50, 0.2);
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 10px 14px 10px;
            margin-bottom: 10px;
            border-radius: 16px;
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.10) 0%, rgba(3, 169, 244, 0.06) 100%);
            border: 1px solid rgba(46, 125, 50, 0.10);
        }

        .sidebar-logo {
            width: 100%;
            max-width: 220px;
            height: auto;
            display: block;
            filter: drop-shadow(0 6px 14px rgba(46, 125, 50, 0.14));
        }

        /* Make company switcher feel native */
        #sidebar .company-switcher,
        #sidebar .company-switcher-container {
            margin: 8px 0 14px 0;
        }

        /* Override component's embedded negative margins inside sidebar only */
        #sidebar .company-switcher-container {
            padding: 1px !important;
            background: transparent !important;
            border: 0 !important;
            margin: 8px 0 14px 0 !important;
        }

        #sidebar .company-switcher-header {
            border-radius: 14px !important;
            border-color: rgba(60, 64, 67, 0.15) !important;
        }

        #sidebar select,
        #sidebar .form-select,
        #sidebar .form-control {
            border-radius: 12px;
            border-color: rgba(60, 64, 67, 0.15);
            box-shadow: none;
        }

        #sidebar select:focus,
        #sidebar .form-select:focus,
        #sidebar .form-control:focus {
            border-color: rgba(46, 125, 50, 0.35);
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.12);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin: 2px 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--gray-800);
            text-decoration: none;
            border-radius: 14px;
            transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
            position: relative;
            border: 1px solid transparent;
        }

        .sidebar-menu a i {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 16px;
            color: var(--gray-600);
            background: rgba(60, 64, 67, 0.06);
            transition: background-color 0.18s ease, color 0.18s ease, transform 0.18s ease;
            flex-shrink: 0;
        }

        .sidebar-menu a span {
            font-weight: 400;
            letter-spacing: 0.1px;
        }

        .sidebar-menu a:hover {
            background: rgba(46, 125, 50, 0.08);
            color: var(--dark-green);
            border-color: rgba(46, 125, 50, 0.16);
            transform: translateX(2px);
        }

        .sidebar-menu a:hover i {
            background: rgba(46, 125, 50, 0.14);
            color: var(--primary-green);
            transform: translateY(-1px);
        }

        .sidebar-menu a.active {
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.16) 0%, rgba(3, 169, 244, 0.10) 100%);
            color: var(--dark-green);
            border-color: rgba(46, 125, 50, 0.22);
            box-shadow: 0 10px 22px rgba(46, 125, 50, 0.10);
        }

        .sidebar-menu a.active i {
            background: rgba(46, 125, 50, 0.18);
            color: var(--dark-green);
        }
        
        /* Demo restricted: show lock; link still works so click shows "no permission" page */
        .sidebar-menu a.demo-restricted {
            opacity: 0.95;
        }
        .sidebar-menu a.demo-restricted .fa-lock {
            flex-shrink: 0;
        }
        
        /* User restricted (per-user sidebar access): show lock; access will be blocked by middleware */
        .sidebar-menu a.user-restricted {
            opacity: 0.85;
        }
        .sidebar-menu a.user-restricted .fa-lock {
            flex-shrink: 0;
        }
        
        /* keep lock icon aligned nicely */
        .sidebar-menu a .fa-lock {
            margin-left: auto;
        }
        
        /* Main Content Styles */
        #content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            
            #content {
                margin-left: 0;
            }
            
            #sidebar.active {
                margin-left: 0;
            }
            
            #content.active {
                margin-left: 250px;
            }
        }
        
        /* Top Navigation */
        .top-navbar {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            padding: 10px 20px;
            position: sticky;
            top: 20px;
            z-index: 999;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 16px;
            margin: 0 0 20px 0;
            border: 1px solid rgba(46, 125, 50, 0.08);
        }
        
        .top-navbar .btn {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            border: none;
            color: white;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.2);
        }
        
        .top-navbar .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
            background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%);
        }
        
        .top-navbar h1 {
            font-size: 1.6rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        
        .notifications {
            position: relative;
            cursor: pointer;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(46, 125, 50, 0.08);
            transition: all 0.3s ease;
            color: var(--gray-600);
        }
        
        .notifications:hover {
            background: rgba(46, 125, 50, 0.15);
            color: var(--primary-green);
            transform: translateY(-2px);
        }
        
        .notifications i {
            font-size: 18px;
        }
        
        .notification-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: linear-gradient(135deg, #d32f2f 0%, #f44336 100%);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(211, 47, 47, 0.4);
            border: 2px solid white;
        }
        
        .user-profile .dropdown > a {
            padding: 8px 16px 8px 8px;
            border-radius: 12px;
            background: rgba(46, 125, 50, 0.05);
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .user-profile .dropdown > a:hover {
            background: rgba(46, 125, 50, 0.1);
            border-color: rgba(46, 125, 50, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.1);
        }
        
        .user-profile .dropdown > a .rounded-circle {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.25);
        }
        
        .user-profile .dropdown-menu {
            border: none;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-radius: 12px;
            padding: 8px;
            margin-top: 8px;
            min-width: 220px;
        }
        
        .user-profile .dropdown-item {
            padding: 10px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .user-profile .dropdown-item:hover {
            background: rgba(46, 125, 50, 0.1);
            color: var(--primary-green);
        }
        
        .user-profile .dropdown-item i {
            width: 20px;
        }
        
        /* KPI Cards */
        .kpi-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .kpi-card:hover {
            transform: translateY(-5px);
        }
        
        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0 5px 0;
        }
        
        .kpi-label {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        
        .kpi-change {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .change-positive {
            color: var(--light-green);
        }
        
        .change-negative {
            color: var(--danger-red);
        }
        
        /* Charts */
        .chart-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            height: 100%;
        }
        
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 20px;
        }
        
        /* Filters */
        .filters-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .filter-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--gray-800);
        }
        
        /* Data Table */
        .data-table-section {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }
        
        .table thead th {
            border-top: none;
            background-color: var(--gray-50);
            color: var(--gray-800);
            font-weight: 600;
        }
        
        .scope-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .scope-1 {
            background-color: rgba(46, 125, 50, 0.1);
            color: var(--primary-green);
        }
        
        .scope-2 {
            background-color: rgba(3, 169, 244, 0.1);
            color: var(--light-blue);
        }
        
        .scope-3 {
            background-color: rgba(121, 85, 72, 0.1);
            color: #795548;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 20px;
            color: var(--gray-600);
            font-size: 0.9rem;
            margin-top: 30px;
        }
        
        /* Toggle button for mobile */
        #sidebarCollapse {
            display: none;
        }
        
        @media (max-width: 768px) {
            #sidebarCollapse {
                display: flex;
            }
            
            .top-navbar {
                padding: 14px 18px;
                margin: 0 0 15px 0;
                top: 10px;
            }
            
            .top-navbar h1 {
                font-size: 1.3rem;
            }
            
            .user-profile {
                gap: 12px;
            }
            
            .notifications {
                width: 40px;
                height: 40px;
            }
        }


        /* Submenu Styles */
        .sidebar-menu .has-submenu {
            position: relative;
        }

        .sidebar-menu .has-submenu > a {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
            font-size: 0.8rem;
            margin-left: auto;
            margin-right: 5px;
            color: rgba(60, 64, 67, 0.55);
        }

        .has-submenu.active .dropdown-arrow {
            transform: rotate(90deg);
        }

        .submenu {
            list-style: none;
            padding: 6px 6px 8px 6px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: rgba(60, 64, 67, 0.04);
            margin: 6px 0 4px 0;
            border-radius: 14px;
            border: 1px solid rgba(60, 64, 67, 0.08);
        }

        .has-submenu.active .submenu {
            /* Expand fully; let the sidebar handle scrolling (avoid 2nd scrollbar) */
            max-height: 2000px;
            overflow: visible;
        }

        .submenu li {
            margin: 0;
        }

        .submenu a {
            padding: 9px 10px 9px 16px !important; /* Extra left padding for indentation */
            font-size: 0.9rem;
            border-radius: 12px !important;
        }

        .submenu a:hover {
            background: rgba(46, 125, 50, 0.10) !important;
        }

        .submenu i {
            width: 28px !important;
            height: 28px !important;
            font-size: 0.9rem !important;
        }

        /* Active state for submenu items */
        .submenu a.active {
            background: rgba(46, 125, 50, 0.14) !important;
            color: var(--dark-green) !important;
            border-left: 0;
        }
    </style>

    @stack('styles')
</head>
<body>
{{-- Sidebar include (blade) --}}
@include('layouts.sidebar')


    <main class="p-4 bg-light flex-grow-1">
        @yield('content')
    </main>


    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Submenu toggle functionality
        const submenuToggles = document.querySelectorAll('.submenu-toggle');
        
        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const parentLi = this.closest('.has-submenu');
                
                // Close other submenus if needed
                document.querySelectorAll('.has-submenu.active').forEach(activeMenu => {
                    if (activeMenu !== parentLi) {
                        activeMenu.classList.remove('active');
                    }
                });
                
                // Toggle current submenu
                parentLi.classList.toggle('active');
            });
        });
        
        // Mobile sidebar toggle
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        const sidebar = document.getElementById('sidebar');
        const content = document.getElementById('content');
        
        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                if (content) content.classList.toggle('active');
            });
        }
        
        // Close submenus when clicking outside (for mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!e.target.closest('#sidebar')) {
                    document.querySelectorAll('.has-submenu.active').forEach(menu => {
                        menu.classList.remove('active');
                    });
                }
            }
        });
    });
    </script>
@stack('scripts')
</body>
</html>
