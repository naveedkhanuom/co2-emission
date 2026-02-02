@extends('layouts.app')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-user-edit me-2"></i>Edit User
                            </h5>
                            <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show">
                                {{ session('success') }}
                                <button class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show">
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <form action="{{ route('users.update', $user->id) }}" method="post">
                            @csrf
                            @method("PUT")

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           value="{{ old('name', $user->name) }}"
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
                                           value="{{ old('email', $user->email) }}"
                                           placeholder="user@example.com"
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password"
                                           placeholder="Leave blank to keep current password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Leave blank if you don't want to change the password</small>
                                </div>

                                <div class="col-md-6">
                                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirmation" 
                                           name="password_confirmation"
                                           placeholder="Confirm new password">
                                </div>

                                <div class="col-md-12">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="is_demo_user" value="1" id="is_demo_user"
                                               {{ old('is_demo_user', $user->is_demo_user ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_demo_user">
                                            Demo user (sidebar links you restrict below will show with a lock; clicking shows "no permission")
                                        </label>
                                    </div>
                                </div>

                                @php
                                    $restrictableOptions = config('demo.restrictable_sidebar_options', []);
                                    $restrictedSidebarRoutes = old('restricted_sidebar_routes', $user->restricted_sidebar_routes ?? []);
                                    if (!is_array($restrictedSidebarRoutes)) {
                                        $restrictedSidebarRoutes = [];
                                    }
                                @endphp
                                <div id="restricted_sidebar_box" class="col-md-12 {{ old('is_demo_user', $user->is_demo_user ?? false) ? '' : 'd-none' }}">
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
                                                                   {{ in_array($role, old('roles', $userRoles ?? [])) ? 'checked' : '' }}>
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
                                                                       {{ in_array($role, old('roles', $userRoles ?? [])) ? 'checked' : '' }}>
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
                                        <input class="form-check-input" type="checkbox" name="use_default_sidebar" value="1" id="use_default_sidebar"
                                               {{ old('use_default_sidebar', $useDefaultSidebar ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="use_default_sidebar">
                                            Use default access (from role) — show all links the user has permission for
                                        </label>
                                    </div>
                                    <div id="sidebar_routes_box" class="border rounded p-3 {{ old('use_default_sidebar', $useDefaultSidebar ?? true) ? 'd-none' : '' }}" style="max-height: 320px; overflow-y: auto; background-color: #f8f9fa;">
                                        <p class="small text-muted mb-2">Or select specific sidebar links to enable for this user:</p>
                                        @php $menuItems = $sidebarMenuItems ?? config('sidebar.menu_items', []); @endphp
                                        @foreach($menuItems as $section => $items)
                                            <div class="mb-3">
                                                <strong class="text-uppercase small text-secondary">{{ $section }}</strong>
                                                <div class="ms-2 mt-1">
                                                    @foreach($items as $routeName => $label)
                                                        <div class="form-check">
                                                            <input class="form-check-input sidebar-route-cb" type="checkbox" name="sidebar_routes[]" value="{{ $routeName }}" id="sb_{{ $routeName }}"
                                                                   {{ in_array($routeName, old('sidebar_routes', $allowedSidebarRoutes ?? []), true) ? 'checked' : '' }}>
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
                                        <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-times me-1"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save me-1"></i>Update User
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('use_default_sidebar').addEventListener('change', function() {
    document.getElementById('sidebar_routes_box').classList.toggle('d-none', this.checked);
    document.querySelectorAll('.sidebar-route-cb').forEach(function(cb) { cb.disabled = this.checked; }.bind(this));
});
document.querySelectorAll('.sidebar-route-cb').forEach(function(cb) {
    cb.disabled = document.getElementById('use_default_sidebar').checked;
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
