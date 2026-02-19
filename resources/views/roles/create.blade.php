@extends('layouts.app')

@section('title', 'Add Role')
@section('page-title', 'Add Role')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="roles-app container-fluid mt-4">
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-user-tag"></i></span> Add New Role</h2>
            <p>Create a new role and assign permissions.</p>
            <a href="{{ route('roles.index') }}" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="card roles-form-card">
            <div class="card-header">
                <h5 class="mb-0">Role Details</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('roles.store') }}" method="post">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g., Manager, Editor" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label for="permissions" class="form-label">Permissions</label>
                            <select class="form-select @error('permissions') is-invalid @enderror" multiple aria-label="Permissions" id="permissions" name="permissions[]" style="height: 210px;">
                                @forelse ($permissions as $permission)
                                    <option value="{{ $permission->id }}" {{ in_array($permission->id, old('permissions') ?? []) ? 'selected' : '' }}>{{ $permission->name }}</option>
                                @empty
                                @endforelse
                            </select>
                            @error('permissions')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Select one or more permissions (Ctrl/Cmd+click for multiple)</small>
                        </div>

                        <div class="col-12 mt-3">
                            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Add Role</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <style>
        .roles-app * { box-sizing: border-box; }
        .roles-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .roles-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .roles-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
        .roles-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
        .roles-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
        .roles-app .btn-back { margin-left: auto; padding: 10px 20px; border-radius: 10px; border: 1px solid var(--gray-300); background: #fff; color: var(--gray-700); font-size: 0.875rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .roles-app .btn-back:hover { background: var(--gray-50); color: var(--gray-800); border-color: var(--gray-400); }
        .roles-app .alert { border-radius: 12px; }
        .roles-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red, #c62828); }
        .roles-app .roles-form-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .roles-app .roles-form-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
        .roles-app .roles-form-card .card-body { padding: 24px; }
    </style>
@endpush
