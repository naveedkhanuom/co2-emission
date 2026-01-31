@extends('layouts.app')

@section('title', 'Roles')
@section('page-title', 'Roles')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container mt-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Roles & Permissions</h4>
            @can('create-role')
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                + Add Role
            </button>
            @endcan
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Role Name</th>
                        <th>Permissions</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $role->name }}</strong>
                            @if($role->name == 'Super Admin')
                                <span class="badge bg-danger ms-2">Protected</span>
                            @endif
                        </td>
                        <td>
                            @if($role->permissions->count() > 0)
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($role->permissions->take(5) as $permission)
                                        <span class="badge bg-secondary">{{ $permission->name }}</span>
                                    @endforeach
                                    @if($role->permissions->count() > 5)
                                        <span class="badge bg-info">+{{ $role->permissions->count() - 5 }} more</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">No permissions assigned</span>
                            @endif
                        </td>
                        <td>
                            @if($role->name != 'Super Admin')
                                @can('edit-role')
                                <button
                                    class="btn btn-sm btn-warning editBtn"
                                    data-id="{{ $role->id }}"
                                    data-name="{{ $role->name }}"
                                    data-permissions="{{ $role->permissions->pluck('id')->toJson() }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal">
                                    Edit
                                </button>
                                @endcan

                                @can('delete-role')
                                    @if(!auth()->user()->hasRole($role->name))
                                    <form method="POST"
                                          action="{{ route('roles.destroy', $role->id) }}"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this role?');">
                                            Delete
                                        </button>
                                    </form>
                                    @endif
                                @endcan
                            @else
                                <span class="text-muted small">Cannot edit/delete</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            No roles found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $roles->links() }}
    </div>
</div>

<!-- ================= ADD MODAL ================= -->
@can('create-role')
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Role</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Role Name *</label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name') }}" required placeholder="e.g., Manager, Editor">
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Permissions *</label>
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                <div class="row">
                                    @forelse($permissions as $permission)
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="permissions[]" value="{{ $permission->id }}" 
                                                   id="perm_{{ $permission->id }}"
                                                   {{ in_array($permission->id, old('permissions') ?? []) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="col-12 text-muted">
                                        No permissions available
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                            @error('permissions')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <small class="text-muted">Select one or more permissions for this role</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Role</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

<!-- ================= EDIT MODAL ================= -->
@can('edit-role')
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" id="editForm">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Role</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Role Name *</label>
                            <input type="text" name="name" id="editName" 
                                   class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Permissions *</label>
                            <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;" id="editPermissionsContainer">
                                <div class="row">
                                    @forelse($permissions ?? [] as $permission)
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input permission-checkbox" type="checkbox" 
                                                   name="permissions[]" value="{{ $permission->id }}" 
                                                   id="edit_perm_{{ $permission->id }}">
                                            <label class="form-check-label" for="edit_perm_{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="col-12 text-muted">
                                        Loading permissions...
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                            @error('permissions')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <small class="text-muted">Select one or more permissions for this role</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Role</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
    // Load permissions for edit modal
    @if(isset($permissions))
    const allPermissions = @json($permissions);
    @else
    const allPermissions = [];
    @endif

    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const permissions = JSON.parse(this.dataset.permissions || '[]');
            
            document.getElementById('editForm').action = `/roles/${id}`;
            document.getElementById('editName').value = name;
            
            // Reset all checkboxes
            document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check the permissions for this role
            permissions.forEach(permId => {
                const checkbox = document.getElementById('edit_perm_' + permId);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        });
    });
</script>
@endpush