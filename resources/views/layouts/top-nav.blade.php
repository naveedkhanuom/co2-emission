        <!-- Top Navigation Bar -->
        <nav class="top-navbar">
            <button type="button" id="sidebarCollapse" class="btn">
                <i class="fas fa-bars"></i>
            </button>
            
            <h1>@yield('page-title', 'GHG Emissions Dashboard')</h1>
            
            <div class="user-profile">
                <div class="notifications">
                    <i class="fas fa-bell fa-lg text-muted"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                            <span class="text-white fw-bold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</span>
                        </div>
                        <div>
                            <span class="fw-bold">{{ auth()->user()->name ?? 'User' }}</span>
                            <div class="text-muted small">{{ auth()->user()->email ?? '' }}</div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Account Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>