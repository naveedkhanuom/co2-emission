<!-- Sidebar Navigation -->
<nav id="sidebar">
    @php
        // Logo is dynamic per company (companies.logo). Uploaded logos are stored
        // as a path on the public disk; a legacy full URL is used as-is. Falls
        // back to the default brand logo when the company hasn't set one.
        $__company = current_company();
        $__logo = $__company?->logo;
        $__defaultLogo = 'https://cdn.prod.website-files.com/68ce511f0ec3dbdca3e16b5b/68ce5272a15164172603c206_logo%20green.avif';
        $__logoUrl = $__logo
            ? (\Illuminate\Support\Str::startsWith($__logo, ['http://', 'https://'])
                ? $__logo
                : \Illuminate\Support\Facades\Storage::disk('public')->url($__logo))
            : (app_logo_url() ?? $__defaultLogo); // company logo -> global app logo -> default
    @endphp
    <div class="sidebar-brand">
        <img
            class="sidebar-logo"
            src="{{ $__logoUrl }}"
            alt="{{ $__company?->name ?? 'GHG Monitor' }}"
            onerror="this.onerror=null;this.src='{{ $__defaultLogo }}';"
        >
    </div>

    @include('components.company-switcher')

    <ul class="sidebar-menu">

        {{-- ============ OVERVIEW ============ --}}
        <li class="sidebar-section">Overview</li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('home');
                $userRestricted = ! user_can_see_sidebar_route('home');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('data_health.index');
                $userRestricted = ! user_can_see_sidebar_route('data_health.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('data_health.index') }}" class="{{ request()->routeIs('data_health.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-heart-pulse"></i>
                <span>Data Health</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('analytics.index');
                $userRestricted = ! user_can_see_sidebar_route('analytics.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('analytics.index') }}" class="{{ request()->routeIs('analytics.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-chart-pie"></i>
                <span>Analytics</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        {{-- ============ DATA ENTRY ============ --}}
        <li class="sidebar-section">Data Entry</li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('scope_classifier.index');
                $userRestricted = ! user_can_see_sidebar_route('scope_classifier.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('scope_classifier.index') }}"
               class="{{ request()->routeIs('scope_classifier.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-search"></i>
                <span>Scope Finder</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('scope1_entry.index');
                $userRestricted = ! user_can_see_sidebar_route('scope1_entry.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('scope1_entry.index') }}"
               class="{{ request()->routeIs('scope1_entry.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-fire"></i>
                <span>Scope 1 Entry</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('scope2_entry.index');
                $userRestricted = ! user_can_see_sidebar_route('scope2_entry.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('scope2_entry.index') }}"
               class="{{ request()->routeIs('scope2_entry.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-bolt"></i>
                <span>Scope 2 Entry</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('scope3_entry.index');
                $userRestricted = ! user_can_see_sidebar_route('scope3_entry.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('scope3_entry.index') }}"
               class="{{ request()->routeIs('scope3_entry.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-globe-americas"></i>
                <span>Scope 3 Entry</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('utility.create');
                $userRestricted = ! user_can_see_sidebar_route('utility.create');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('utility.create') }}"
               class="{{ request()->routeIs('utility.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-file-upload"></i>
                <span>Upload Bills</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('ai_extract.index');
                $userRestricted = ! user_can_see_sidebar_route('ai_extract.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('ai_extract.index') }}"
               class="{{ request()->routeIs('ai_extract.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-robot"></i>
                <span>AI Extract <span class="badge bg-success ms-1" style="font-size:.6rem;">AI</span></span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('emissions.import.form');
                $userRestricted = ! user_can_see_sidebar_route('emissions.import.form');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('emissions.import.form') }}"
               class="{{ request()->routeIs('emissions.import*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-file-import"></i>
                <span>Import Data</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('emission_records.index');
                $userRestricted = ! user_can_see_sidebar_route('emission_records.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('emission_records.index') }}"
               class="{{ request()->routeIs('emission_records.index') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-keyboard"></i>
                <span>Manual Entry</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('boundary.index');
                $userRestricted = ! user_can_see_sidebar_route('boundary.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('boundary.index') }}"
               class="{{ request()->routeIs('boundary.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-compass-drafting"></i>
                <span>Boundary Advisor</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        {{-- ============ MANAGE DATA ============ --}}
        <li class="sidebar-section">Manage Data</li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('review_data.index');
                $userRestricted = ! user_can_see_sidebar_route('review_data.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('review_data.index') }}"
               class="{{ request()->routeIs('review_data.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-clipboard-check"></i>
                <span>Review Data</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('reporting_periods.index');
                $userRestricted = ! user_can_see_sidebar_route('reporting_periods.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('reporting_periods.index') }}"
               class="{{ request()->routeIs('reporting_periods.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-lock"></i>
                <span>Reporting Periods</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('data_quality.index');
                $userRestricted = ! user_can_see_sidebar_route('data_quality.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('data_quality.index') }}"
               class="{{ request()->routeIs('data_quality.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-check-circle"></i>
                <span>Data Quality</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('import_history.index');
                $userRestricted = ! user_can_see_sidebar_route('import_history.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('import_history.index') }}"
               class="{{ request()->routeIs('import_history.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-history"></i>
                <span>Import History</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('data_source.index');
                $userRestricted = ! user_can_see_sidebar_route('data_source.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('data_source.index') }}"
               class="{{ request()->routeIs('data_source.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-database"></i>
                <span>Data Source</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        {{-- ============ REPORTING ============ --}}
        <li class="sidebar-section">Reporting</li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('reports.index');
                $userRestricted = ! user_can_see_sidebar_route('reports.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('reports.index') }}"
               class="{{ request()->routeIs('reports.index') || request()->routeIs('reports.statistics') || request()->routeIs('reports.data') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('reports.ghg_protocol');
                $userRestricted = ! user_can_see_sidebar_route('reports.ghg_protocol');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('reports.ghg_protocol') }}"
               class="{{ request()->routeIs('reports.ghg_protocol') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-chart-bar"></i>
                <span>GHG Protocol Report</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('disclosure.index');
                $userRestricted = ! user_can_see_sidebar_route('disclosure.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('disclosure.index') }}"
               class="{{ request()->routeIs('disclosure.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-clipboard-check"></i>
                <span>Disclosure Reports</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('base_year_comparison.index');
                $userRestricted = ! user_can_see_sidebar_route('base_year_comparison.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('base_year_comparison.index') }}"
               class="{{ request()->routeIs('base_year_comparison.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-chart-line"></i>
                <span>Base Year Comparison</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('mrv.index');
                $userRestricted = ! user_can_see_sidebar_route('mrv.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('mrv.index') }}"
               class="{{ request()->routeIs('mrv.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-file-contract"></i>
                <span>MRV (EAD / EU-ETS)</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        {{-- ============ PROGRAMS ============ --}}
        <li class="sidebar-section">Programs</li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('targets.index');
                $userRestricted = ! user_can_see_sidebar_route('targets.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('targets.index') }}"
               class="{{ request()->routeIs('targets.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-bullseye"></i>
                <span>Targets & Goals</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('suppliers.index');
                $userRestricted = ! user_can_see_sidebar_route('suppliers.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('suppliers.index') }}"
               class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-truck"></i>
                <span>Suppliers</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        <li>
            @php
                $demoRestricted = demo_route_restricted('supplier_surveys.index');
                $userRestricted = ! user_can_see_sidebar_route('supplier_surveys.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('supplier_surveys.index') }}"
               class="{{ request()->routeIs('supplier_surveys.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-clipboard-list"></i>
                <span>Supplier Surveys</span>
                @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
            </a>
        </li>

        {{-- ============ SETTINGS ============ --}}
        <li class="sidebar-section">Configuration</li>

        <li class="has-submenu">
            <a href="#" class="submenu-toggle">
                <i class="fas fa-sliders-h"></i>
                <span>Settings</span>
                <i class="fas fa-chevron-right dropdown-arrow"></i>
            </a>
            <ul class="submenu">
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('assistant.index');
                        $userRestricted = ! user_can_see_sidebar_route('assistant.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{ route('assistant.index') }}" class="{{ request()->routeIs('assistant.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-robot"></i>
                        <span>Ask Your Data</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                {{-- Organisation --}}
                @if(auth()->user()?->is_super_admin || auth()->user()?->hasRole('Super Admin'))
                <li>
                    <a href="{{ route('settings.general') }}" class="{{ request()->routeIs('settings.general') ? 'active' : '' }}">
                        <i class="fas fa-cog"></i>
                        <span>General Settings</span>
                    </a>
                </li>
                @endif
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('companies.index');
                        $userRestricted = ! user_can_see_sidebar_route('companies.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{ route('companies.index') }}"
                       class="{{ request()->routeIs('companies.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-building"></i>
                        <span>Companies</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('facilities.index');
                        $userRestricted = ! user_can_see_sidebar_route('facilities.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{route('facilities.index')}}" class="{{ request()->routeIs('facilities.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-user-cog"></i>
                        <span>Facility / Location</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('departments.index');
                        $userRestricted = ! user_can_see_sidebar_route('departments.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{route('departments.index')}}" class="{{ request()->routeIs('departments.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-database"></i>
                        <span>Department</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('countries.index');
                        $userRestricted = ! user_can_see_sidebar_route('countries.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{ route('countries.index') }}" class="{{ request()->routeIs('countries.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-flag"></i>
                        <span>Countries</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                {{-- Emissions setup --}}
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('emission_sources.index');
                        $userRestricted = ! user_can_see_sidebar_route('emission_sources.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{ route('emission_sources.index') }}" class="{{ request()->routeIs('emission_sources.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-industry"></i>
                        <span>Emission Sources</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('emission_factors.index');
                        $userRestricted = ! user_can_see_sidebar_route('emission_factors.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{ route('emission_factors.index') }}" class="{{ request()->routeIs('emission_factors.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-calculator"></i>
                        <span>Emission Factors</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('energy_certificates.index');
                        $userRestricted = ! user_can_see_sidebar_route('energy_certificates.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{ route('energy_certificates.index') }}" class="{{ request()->routeIs('energy_certificates.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-certificate"></i>
                        <span>Energy Certificates</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                {{-- Access & security --}}
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('users.index');
                        $userRestricted = ! user_can_see_sidebar_route('users.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{ route('users.index') }}"
                       class="{{ request()->routeIs('users.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-users-cog"></i>
                        <span>Users</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('roles.index');
                        $userRestricted = ! user_can_see_sidebar_route('roles.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{ route('roles.index') }}"
                       class="{{ request()->routeIs('roles.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-shield-alt"></i>
                        <span>Roles & Permissions</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                <li>
                    @php
                        $demoRestricted = demo_route_restricted('audit-logs.index');
                        $userRestricted = ! user_can_see_sidebar_route('audit-logs.index');
                        $restricted = $demoRestricted || $userRestricted;
                        $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
                    @endphp
                    <a href="{{ route('audit-logs.index') }}"
                       class="{{ request()->routeIs('audit-logs.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-clock-rotate-left"></i>
                        <span>Audit Trail</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
                    </a>
                </li>
                {{-- System --}}
                <li>
                    <a href="{{ route('notifications.index') }}" class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-plug"></i>
                        <span>API Integrations</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>

</nav>
