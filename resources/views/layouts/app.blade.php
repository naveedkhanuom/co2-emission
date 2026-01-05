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
        /* Sidebar Styles */
        #sidebar {
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            padding-top: 0px;
            transition: all 0.3s;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: var(--primary-green);
            color: white;
            margin: -70px -15px 20px -15px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--gray-800);
            text-decoration: none;
            border-radius: 0 30px 30px 0;
            transition: all 0.2s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: var(--gray-100);
            color: var(--primary-green);
        }
        
        .sidebar-menu i {
            width: 30px;
            font-size: 18px;
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
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            position: sticky;
            top: 0;
            z-index: 999;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-navbar h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-green);
            margin: 0;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .notifications {
            position: relative;
            cursor: pointer;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-red);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            background-color: var(--primary-green);
            border: none;
            color: white;
            border-radius: 5px;
            padding: 8px 12px;
            display: none;
        }
        
        @media (max-width: 768px) {
            #sidebarCollapse {
                display: block;
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
        }

        .has-submenu.active .dropdown-arrow {
            transform: rotate(90deg);
        }

        .submenu {
            list-style: none;
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background-color: var(--gray-50);
            margin: 5px 0;
            border-radius: 0 20px 20px 0;
        }

        .has-submenu.active .submenu {
            max-height: 500px; /* Adjust based on content */
        }

        .submenu li {
            margin: 0;
        }

        .submenu a {
            padding: 10px 20px 10px 50px !important; /* Extra left padding for indentation */
            font-size: 0.9rem;
        }

        .submenu a:hover {
            background-color: var(--gray-100) !important;
        }

        .submenu i {
            width: 20px !important;
            font-size: 0.9rem !important;
        }

        /* Active state for submenu items */
        .submenu a.active {
            background-color: var(--gray-100) !important;
            color: var(--primary-green) !important;
            border-left: 3px solid var(--primary-green);
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
                content.classList.toggle('active');
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
