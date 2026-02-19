@extends('layouts.app')

@section('title', 'Create User')
@section('page-title', 'Create User')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="users-app container-fluid mt-4">
        <!-- Topbar -->
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-user-plus"></i></span> Add New User</h2>
            <p>Create a new system user account.</p>
            <a href="{{ route('users.index') }}" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="card users-form-card">
            <div class="card-header">
                <h5 class="mb-0">User Details</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('users.store') }}" method="post">
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name') }}"
                                           placeholder="Enter full name"
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           value="{{ old('email') }}"
                                           placeholder="user@example.com"
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password"
                                           placeholder="Enter password"
                                           required>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation"
                                           placeholder="Confirm password"
                                           required>
                                </div>

                                {{-- Company access: primary company + additional companies user can switch to --}}
                                <div class="col-md-12">
                                    <label class="form-label d-block">Company access</label>
                                    <p class="small text-muted mb-2">Assign the primary company and any additional companies this user can switch to in the app.</p>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="company_id" class="form-label">Primary company</label>
                                            <select class="form-select" name="company_id" id="company_id">
                                                <option value="">— None —</option>
                                                @foreach($companies ?? [] as $company)
                                                    <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                                        {{ $company->name }}{{ $company->code ? ' (' . $company->code . ')' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Default company when the user logs in</small>
                                        </div>
                                    </div>
                                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa;">
                                        <span class="form-label d-block small mb-2">Additional companies (user can switch to these via the company switcher)</span>
                                        @forelse($companies ?? [] as $company)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input company-access-cb" type="checkbox" name="company_access[]" value="{{ $company->id }}" id="company_access_{{ $company->id }}"
                                                       {{ in_array($company->id, old('company_access', []), true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="company_access_{{ $company->id }}">
                                                    {{ $company->name }}{{ $company->code ? ' (' . $company->code . ')' : '' }}
                                                </label>
                                            </div>
                                        @empty
                                            <p class="text-muted small mb-0">No active companies. Add companies first.</p>
                                        @endforelse
                                    </div>
                                    @error('company_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    @error('company_access.*')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="is_demo_user" value="1" id="is_demo_user"
                                               {{ old('is_demo_user', false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_demo_user">
                                            Demo user (sidebar links you restrict below will show with a lock; clicking shows "no permission")
                                        </label>
                                    </div>
                                </div>

                                @php
                                    $restrictableOptions = config('demo.restrictable_sidebar_options', []);
                                    $restrictedSidebarRoutes = old('restricted_sidebar_routes', []);
                                @endphp
                                <div id="restricted_sidebar_box" class="col-md-12 {{ old('is_demo_user', false) ? '' : 'd-none' }}">
                                    <label class="form-label d-block">Restrict these sidebar links (demo user will see all links; checked ones show with lock and no access)</label>
                                    <div class="border rounded p-3" style="background-color: #fff9e6;">
                                        @foreach($restrictableOptions as $routeKey => $label)
                                            <div class="form-check mb-2">
                                                <input class="form-check-input restricted-route-cb" type="checkbox" name="restricted_sidebar_routes[]" value="{{ $routeKey }}" id="restrict_{{ md5($routeKey) }}"
                                                       {{ in_array($routeKey, $restrictedSidebarRoutes, true) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="restrict_{{ md5($routeKey) }}">{{ $label }}</label>
                                            </div>
                                        @endforeach
                                        <small class="text-muted">Checked items will appear in the sidebar with a lock; when the demo user clicks, they see "Demo user have no permission".</small>
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <label for="roles" class="form-label">Roles <span class="text-danger">*</span></label>
                                    <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto; background-color: #f8f9fa;">
                                        <div class="row">
                                            @forelse ($roles as $role)
                                                <div class="col-md-6 mb-2">
                                                    <div class="form-check">
                                                        @if ($role != 'Super Admin')
                                                            <input class="form-check-input" 
                                                                   type="checkbox" 
                                                                   name="roles[]" 
                                                                   value="{{ $role }}" 
                                                                   id="role_{{ $loop->index }}"
                                                                   {{ in_array($role, old('roles') ?? []) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="role_{{ $loop->index }}">
                                                                {{ $role }}
                                                            </label>
                                                        @else
                                                            @if (Auth::user()->hasRole('Super Admin'))
                                                                <input class="form-check-input" 
                                                                       type="checkbox" 
                                                                       name="roles[]" 
                                                                       value="{{ $role }}" 
                                                                       id="role_{{ $loop->index }}"
                                                                       {{ in_array($role, old('roles') ?? []) ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="role_{{ $loop->index }}">
                                                                    {{ $role }}
                                                                    <span class="badge bg-danger ms-2">Protected</span>
                                                                </label>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="col-12 text-muted">
                                                    No roles available
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                    @error('roles')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Select one or more roles for this user</small>
                                </div>

                                {{-- Sidebar links: choose which menu items this user can see --}}
                                <div class="col-md-12 mt-3">
                                    <label class="form-label d-block">Sidebar access</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="use_default_sidebar" value="1" id="use_default_sidebar" checked>
                                        <label class="form-check-label" for="use_default_sidebar">
                                            Use default access (from role) — show all links the user has permission for
                                        </label>
                                    </div>
                                    <div id="sidebar_routes_box" class="border rounded p-3 d-none" style="max-height: 320px; overflow-y: auto; background-color: #f8f9fa;">
                                        <p class="small text-muted mb-2">Or select specific sidebar links to enable for this user:</p>
                                        @php $menuItems = $sidebarMenuItems ?? config('sidebar.menu_items', []); @endphp
                                        @foreach($menuItems as $section => $items)
                                            <div class="mb-3">
                                                <strong class="text-uppercase small text-secondary">{{ $section }}</strong>
                                                <div class="ms-2 mt-1">
                                                    @foreach($items as $routeName => $label)
                                                        <div class="form-check">
                                                            <input class="form-check-input sidebar-route-cb" type="checkbox" name="sidebar_routes[]" value="{{ $routeName }}" id="sb_{{ $routeName }}"
                                                                   {{ in_array($routeName, old('sidebar_routes', []), true) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="sb_{{ $routeName }}">{{ $label }}</label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>Add User
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .users-app * { box-sizing: border-box; }
        .users-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        .users-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .users-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
        .users-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
        .users-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
        .users-app .btn-back { margin-left: auto; padding: 10px 20px; border-radius: 10px; border: 1px solid var(--gray-300); background: #fff; color: var(--gray-700); font-size: 0.875rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .users-app .btn-back:hover { background: var(--gray-50); color: var(--gray-800); border-color: var(--gray-400); }

        .users-app .alert { border-radius: 12px; border: 1px solid transparent; }
        .users-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red, #c62828); }

        .users-app .users-form-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .users-app .users-form-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
        .users-app .users-form-card .card-body { padding: 24px; }
    </style>
@endpush

@push('scripts')
<script>
document.getElementById('use_default_sidebar').addEventListener('change', function() {
    document.getElementById('sidebar_routes_box').classList.toggle('d-none', this.checked);
    document.querySelectorAll('.sidebar-route-cb').forEach(function(cb) { cb.disabled = this.checked; }.bind(this));
});
document.getElementById('is_demo_user').addEventListener('change', function() {
    var box = document.getElementById('restricted_sidebar_box');
    if (this.checked) {
        box.classList.remove('d-none');
    } else {
        box.classList.add('d-none');
        document.querySelectorAll('.restricted-route-cb').forEach(function(cb) { cb.checked = false; });
    }
});
</script>
@endpush
@endsection
