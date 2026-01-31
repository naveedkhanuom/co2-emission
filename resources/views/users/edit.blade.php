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
@endsection
