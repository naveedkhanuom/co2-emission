<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'GHG Dashboard')</title>

    <!-- Tailwind & Chart.js -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap">

    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .sidebar { background-color: #ffffff; border-right: 1px solid #e5e7eb; box-shadow: 2px 0 6px rgba(0,0,0,0.05); }
        .sidebar-item { border-left: 4px solid transparent; transition: all 0.2s; color: #334155; }
        .sidebar-item:hover { background-color: #f1f5f9; color: #1e40af; }
        .sidebar-item.active { background-color: #e0f2fe; border-left-color: #3b82f6; color: #1e40af; font-weight: 600; }
        .dashboard-card { background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 4px 8px rgba(0,0,0,0.05); transition: all 0.3s ease; }
        .dashboard-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .scrollbar::-webkit-scrollbar { width: 8px; }
        .scrollbar::-webkit-scrollbar-thumb { background-color: #93c5fd; border-radius: 4px; }
        .scrollbar::-webkit-scrollbar-track { background-color: #f1f5f9; }
    </style>

    @stack('styles')
</head>

<body class="flex h-screen overflow-hidden">

<!-- Sidebar -->
@include('layouts.sidebar')

<!-- Main Content -->
<div class="flex-1 flex flex-col overflow-auto scrollbar">

    <!-- Header -->
    <header class="bg-white sticky top-0 z-10 p-4 flex justify-between items-center border-b border-gray-200 shadow-sm">
        <button id="sidebar-toggle" class="md:hidden text-gray-600 hover:text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <h1 id="page-title" class="text-xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h1>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500 hidden sm:inline">@yield('company-info','Acme Manufacturing | FY 2024 (Q3)')</span>
            <div class="h-9 w-9 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-base border-2 border-blue-300">AM</div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="p-6 flex-1 overflow-y-auto bg-gray-50">
        @yield('content')
    </main>

</div>

<script>
    document.getElementById('sidebar-toggle').addEventListener('click', () => {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
    });
</script>

@stack('scripts')
</body>
</html>
