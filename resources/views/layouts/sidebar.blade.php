<aside id="sidebar" role="navigation" aria-label="Main sidebar">
    <div class="sidebar-brand">
        <img src="https://altayaboon.com/assets/altayaboonlogosvg-BKWEtYJo.svg" alt="GHG Tracker Pro Logo">
    </div>

    <nav class="sidebar-nav">
        <ul class="list-unstyled mb-0">
            <li class="mb-1">
                <a href="#" class="sidebar-item active" aria-current="page">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="#" class="sidebar-item">
                    <i class="bi bi-eye"></i>
                    <span>Monitoring</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="#" class="sidebar-item">
                    <i class="bi bi-bar-chart-line"></i>
                    <span>Reports</span>
                </a>
            </li>

            <li class="mb-1">
                <!-- Logout -->
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="sidebar-item">
                    <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </li>


        </ul>
    </nav>
</aside>
