<!-- Sidebar Navigation -->
<nav id="sidebar">
    <div class="sidebar-header1">
        <img src="https://cdn.prod.website-files.com/68ce511f0ec3dbdca3e16b5b/68ce5272a15164172603c206_logo%20green.avif" style="width: 250px; padding-bottom: 20px; padding-top: 20px; padding-left: 6px;padding-right: 10px;">
        <!-- <h3><i class="fas fa-leaf me-2"></i> GHG Monitor</h3> -->
    </div>
    
    @include('components.company-switcher')
    
    <ul class="sidebar-menu">
        <li>
            <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="{{ route('emission_records.index') }}" 
               class="{{ request()->routeIs('emission_records.index') ? 'active' : '' }}">
                <i class="fas fa-keyboard"></i>
                <span>Manual Entry</span>
            </a>
        </li>

        <li>
            <a href="{{ route('emission_records.scope_entry') }}" 
               class="{{ request()->routeIs('emission_records.scope_entry') ? 'active' : '' }}">
                <i class="fas fa-layer-group"></i>
                <span>Scope-Based Entry</span>
            </a>
        </li>

        <li>
            <a href="{{ route('emissions.import.form') }}" 
               class="{{ request()->routeIs('emissions.import*') ? 'active' : '' }}">
                <i class="fas fa-file-import"></i>
                <span>Import Data</span>
            </a>
        </li>

        <li>
            <a href="{{ route('review_data.index') }}" 
               class="{{ request()->routeIs('review_data.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-check"></i>
                <span>Review Data</span>
            </a>
        </li>

        <li>
            <a href="{{ route('utility.create') }}" 
               class="{{ request()->routeIs('utility.*') ? 'active' : '' }}">
                <i class="fas fa-file-upload"></i>
                <span>Upload Bills</span>
            </a>
        </li>

        <li>
            <a href="{{ route('import_history.index') }}" 
               class="{{ request()->routeIs('import_history.*') ? 'active' : '' }}">
                <i class="fas fa-history"></i>
                <span>Import History</span>
            </a>
        </li>

        <li>
            <a href="{{ route('data_source.index') }}" 
               class="{{ request()->routeIs('data_source.*') ? 'active' : '' }}">
                <i class="fas fa-database"></i>
                <span>Data Source</span>
            </a>
        </li>

        <li>
            <a href="{{ route('reports.index') }}" 
               class="{{ request()->routeIs('reports.index') || request()->routeIs('reports.statistics') || request()->routeIs('reports.data') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i>
                <span>Reports</span>
            </a>
        </li>

        <li>
            <a href="{{ route('reports.ghg_protocol') }}" 
               class="{{ request()->routeIs('reports.ghg_protocol') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i>
                <span>GHG Protocol Report</span>
            </a>
        </li>

        <li>
            <a href="{{ route('targets.index') }}" 
               class="{{ request()->routeIs('targets.*') ? 'active' : '' }}">
                <i class="fas fa-bullseye"></i>
                <span>Targets & Goals</span>
            </a>
        </li>

        <li>
            <a href="{{ route('scope3.index') }}" 
               class="{{ request()->routeIs('scope3.*') ? 'active' : '' }}">
                <i class="fas fa-layer-group"></i>
                <span>Scope 3 Emissions</span>
            </a>
        </li>

        <li>
            <a href="{{ route('suppliers.index') }}" 
               class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                <i class="fas fa-truck"></i>
                <span>Suppliers</span>
            </a>
        </li>

        <li>
            <a href="{{ route('supplier_surveys.index') }}" 
               class="{{ request()->routeIs('supplier_surveys.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i>
                <span>Supplier Surveys</span>
            </a>
        </li>

        <li>
            <a href="{{ route('data_quality.index') }}" 
               class="{{ request()->routeIs('data_quality.*') ? 'active' : '' }}">
                <i class="fas fa-check-circle"></i>
                <span>Data Quality</span>
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
                    <a href="{{route('facilities.index')}}">
                        <i class="fas fa-user-cog"></i>
                        <span>Facility / Location</span>
                    </a>
                </li>
                <li>
                    <a href="{{route('departments.index')}}">
                        <i class="fas fa-database"></i>
                        <span>Department</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('emission_sources.index') }}">
                        <i class="fas fa-industry"></i>
                        <span>Emission Sources</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('emission_factors.index') }}">
                        <i class="fas fa-calculator"></i>
                        <span>Emission Factors</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('countries.index') }}">
                        <i class="fas fa-flag"></i>
                        <span>Countries</span>
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
            <a href="{{ route('companies.index') }}" 
               class="{{ request()->routeIs('companies.*') ? 'active' : '' }}">
                <i class="fas fa-building"></i>
                <span>Companies</span>
            </a>
        </li>
        <li>
            <a href="{{ route('users.index') }}" 
               class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="fas fa-users-cog"></i>
                <span>Users</span>
            </a>
        </li>
                <li>
                    <a href="{{ route('roles.index') }}">
                        <i class="fas fa-shield-alt"></i>
                        <span>Roles & Permissions</span>
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