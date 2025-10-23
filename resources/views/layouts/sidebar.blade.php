<div class="d-flex">
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar p-2">
        <!-- Logo -->
        <div class="sidebar-logo mb-3">
            <a href="{{ route('home') }}">
                <img src="{{ asset('logo.png') }}" alt="Logo" class="img-fluid">
            </a>
        </div>

        <!-- Dashboard -->
        <a href="{{ route('home') }}" class="{{ request()->is('home') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
        </a>

        <!-- Logout -->
        <a href="{{ route('logout') }}"
           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>

        <!-- Gold accent stripe -->
        <div class="sidebar-accent"></div>
    </div>

    <!-- Page Content -->
    <div class="flex-grow-1">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary me-3" id="toggleSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <span class="navbar-brand mb-0 h1">Dashboard</span>
                <div class="ms-auto">
                    <a class="btn btn-brand" href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        {{ __('Logout') }}
                    </a>
                </div>
            </div>
        </nav>
