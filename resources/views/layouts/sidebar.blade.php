<!-- Sidebar Navigation -->
<nav id="sidebar">
    <div class="sidebar-header1">
        <img src="https://cdn.prod.website-files.com/68ce511f0ec3dbdca3e16b5b/68ce5272a15164172603c206_logo%20green.avif" style="width: 250px; padding-bottom: 20px; padding-top: 20px; padding-left: 6px;padding-right: 10px;">
        <!-- <h3><i class="fas fa-leaf me-2"></i> GHG Monitor</h3> -->
    </div>
    
    @include('components.company-switcher')
    
    <ul class="sidebar-menu">
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
                $demoRestricted = demo_route_restricted('emission_records.scope_entry');
                $userRestricted = ! user_can_see_sidebar_route('emission_records.scope_entry');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('emission_records.scope_entry') }}" 
               class="{{ request()->routeIs('emission_records.scope_entry') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-layer-group"></i>
                <span>Scope-Based Entry</span>
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
                $demoRestricted = demo_route_restricted('scope3.index');
                $userRestricted = ! user_can_see_sidebar_route('scope3.index');
                $restricted = $demoRestricted || $userRestricted;
                $title = $demoRestricted ? demo_restricted_tooltip() : ($userRestricted ? 'You do not have access to this page.' : null);
            @endphp
            <a href="{{ route('scope3.index') }}" 
               class="{{ request()->routeIs('scope3.*') ? 'active' : '' }}{{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
               @if($title) title="{{ $title }}" @endif>
                <i class="fas fa-layer-group"></i>
                <span>Scope 3 Emissions</span>
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
            <a href="#">
                <i class="fas fa-project-diagram"></i>
                <span>Projects & Initiatives</span>
            </a>
        </li>

        <li>
            <a href="#">
                <i class="fas fa-users"></i>
                <span>Team Management</span>
            </a>
        </li>


        <!-- Settings with Submenu -->
        <li class="has-submenu">
            <a href="#" class="submenu-toggle">
                <i class="fas fa-sliders-h"></i>
                <span>Settings</span>
                <i class="fas fa-chevron-right dropdown-arrow"></i>
            </a>
            <ul class="submenu">
                <li>
                    <a href="#">
                        <i class="fas fa-cog"></i>
                        <span>General Settings</span>
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
                <li>
                    <a href="#">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="fas fa-chart-line"></i>
                        <span>Reporting Settings</span>
                    </a>
                </li>
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
                       class="{{ request()->routeIs('roles.*') ? 'active' : '' }} {{ $demoRestricted ? ' demo-restricted' : '' }}{{ $userRestricted ? ' user-restricted' : '' }}"
                       @if($title) title="{{ $title }}" @endif>
                        <i class="fas fa-shield-alt"></i>
                        <span>Roles & Permissions</span>
                        @if($restricted)<i class="fas fa-lock ms-1 text-warning" style="font-size: 0.75rem;" @if($title) title="{{ $title }}" @endif></i>@endif
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
