@extends('layouts.app')

@section('title', 'Roles')
@section('page-title', 'Roles')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="roles-app container-fluid mt-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Topbar -->
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-user-tag"></i></span> Roles & Permissions</h2>
            <p>Manage roles and their permissions.</p>
            @can('create-role')
            <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> Add Role
            </button>
            @endcan
        </div>

        <!-- DataTable Card -->
        <div class="card roles-datatable-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="mb-0">Roles List</h5>
                <div class="input-group" style="width: 280px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search roles...">
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="rolesTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>Role Name</th>
                                <th>Permissions</th>
                                <th width="200" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                            <tr>
                                <td>{{ $loop->iteration + ($roles->currentPage() - 1) * $roles->perPage() }}</td>
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
                                <td class="text-center">
                                    @if($role->name != 'Super Admin')
                                        @can('edit-role')
                                        <button type="button"
                                            class="btn btn-sm btn-warning editBtn"
                                            data-id="{{ $role->id }}"
                                            data-name="{{ $role->name }}"
                                            data-permissions="{{ $role->permissions->pluck('id')->toJson() }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal">
                                            <i class="fas fa-edit me-1"></i>Edit
                                        </button>
                                        @endcan

                                        @can('delete-role')
                                            @if(!auth()->user()->hasRole($role->name))
                                            <form method="POST"
                                                  action="{{ route('roles.destroy', $role->id) }}"
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this role?');">
                                                    <i class="fas fa-trash me-1"></i>Delete
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
                                <td colspan="4" class="text-center text-muted py-4">No roles found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="table-info-text">
                        Showing {{ $roles->firstItem() ?? 0 }} to {{ $roles->lastItem() ?? 0 }} of {{ $roles->total() }} roles
                    </div>
                    <div>{{ $roles->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= ADD MODAL ================= -->
@can('create-role')
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" action="{{ route('roles.store') }}">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div class="icon-circle">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h5 class="modal-title">Add New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
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
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div class="icon-circle">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h5 class="modal-title">Edit Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Role</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('styles')
    <style>
        .roles-app * { box-sizing: border-box; }
        .roles-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        .roles-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .roles-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
        .roles-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
        .roles-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
        .roles-app .btn-add { margin-left: auto; padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; border: none; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .roles-app .btn-add:hover { background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(46,125,50,.35); }

        .roles-app .alert { border-radius: 12px; border: 1px solid transparent; }
        .roles-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,50,0.25); color: var(--dark-green); }
        .roles-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }

        .roles-app .roles-datatable-card { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
        .roles-app .roles-datatable-card .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); background: linear-gradient(180deg, var(--gray-50) 0%, #fff 100%); }
        .roles-app .roles-datatable-card .card-header h5 { font-size: 1.0625rem; font-weight: 700; color: var(--gray-800); }
        .roles-app .roles-datatable-card .card-header .input-group { border-radius: 10px; overflow: hidden; border: 1px solid var(--gray-200); }
        .roles-app .roles-datatable-card .card-header .input-group-text { background: var(--gray-50); border: none; color: var(--gray-600); padding: 10px 14px; }
        .roles-app .roles-datatable-card .card-header .form-control { border: none; padding: 10px 14px; font-size: 0.875rem; }

        .roles-app .roles-datatable-card #rolesTable { width: 100%; border-collapse: separate; border-spacing: 0; }
        .roles-app .roles-datatable-card #rolesTable thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
        .roles-app .roles-datatable-card #rolesTable thead th:first-child { padding-left: 20px; }
        .roles-app .roles-datatable-card #rolesTable tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
        .roles-app .roles-datatable-card #rolesTable tbody td:first-child { padding-left: 20px; }
        .roles-app .roles-datatable-card #rolesTable tbody tr:hover td { background: var(--gray-50); }
        .roles-app .roles-datatable-card #rolesTable tbody tr:last-child td { border-bottom: none; }

        .roles-app .roles-datatable-card .card-footer { padding: 12px 20px; border-top: 1px solid var(--gray-200); background: var(--gray-50); font-size: 0.8125rem; color: var(--gray-600); }
        .roles-app .roles-datatable-card .card-footer .pagination { margin: 0; flex-wrap: wrap; }
        .roles-app .roles-datatable-card .card-footer .page-link { border-radius: 8px; margin: 0 2px; font-weight: 600; }
        .roles-app .roles-datatable-card .card-footer .page-item.active .page-link { background: var(--primary-green); border-color: var(--primary-green); }

        .modal-content { border-radius: 16px; }
        .modal-header { gap: 10px; }
        .icon-circle { width: 42px; height: 42px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; box-shadow: 0 4px 12px rgba(46,125,50,.25); flex: 0 0 auto; }
    </style>
@endpush

@push('scripts')
<script>
    (function () {
        var searchInput = document.getElementById('searchInput');
        var table = document.getElementById('rolesTable');
        if (searchInput && table) {
            searchInput.addEventListener('keyup', function () {
                var q = this.value.toLowerCase();
                var rows = table.querySelectorAll('tbody tr');
                rows.forEach(function (tr) {
                    var text = tr.textContent.toLowerCase();
                    tr.style.display = text.indexOf(q) === -1 ? 'none' : '';
                });
            });
        }

        document.addEventListener('click', function (e) {
            if (e.target.closest('.editBtn')) {
                var btn = e.target.closest('.editBtn');
                var id = btn.dataset.id;
                var name = btn.dataset.name;
                var permissions = JSON.parse(btn.dataset.permissions || '[]');
                document.getElementById('editForm').action = "{{ url('roles') }}/" + id;
                document.getElementById('editName').value = name;
                document.querySelectorAll('.permission-checkbox').forEach(function (cb) { cb.checked = false; });
                permissions.forEach(function (permId) {
                    var checkbox = document.getElementById('edit_perm_' + permId);
                    if (checkbox) checkbox.checked = true;
                });
            }
        });
    })();
</script>
@endpush