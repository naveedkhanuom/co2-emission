@extends('layouts.app')

@section('title', 'Departments')
@section('page-title', 'Departments')

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

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Departments</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                + Add Department
            </button>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Facility</th>
                        <th>Department Name</th>
                        <th>Description</th>
                        <th width="160">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $department->facility->name ?? '-' }}</td>
                        <td>{{ $department->name }}</td>
                        <td>{{ $department->description }}</td>
                        <td>
                            <button
                                class="btn btn-sm btn-warning editBtn"
                                data-id="{{ $department->id }}"
                                data-name="{{ $department->name }}"
                                data-description="{{ $department->description }}"
                                data-facility="{{ $department->facility_id }}"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal">
                                Edit
                            </button>

                            <form method="POST"
                                  action="{{ route('departments.destroy', $department->id) }}"
                                  class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this department?')">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No departments found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= ADD MODAL ================= -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" action="{{ route('departments.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Department</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Facility *</label>
                            <select name="facility_id" class="form-select" required>
                                <option value="">Select Facility</option>
                                @foreach($facilities as $facility)
                                    <option value="{{ $facility->id }}">
                                        {{ $facility->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Department Name *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary">Save Department</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ================= EDIT MODAL ================= -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" id="editForm">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Facility *</label>
                            <select name="facility_id" id="editFacility" class="form-select" required>
                                @foreach($facilities as $facility)
                                    <option value="{{ $facility->id }}">
                                        {{ $facility->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Department Name *</label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editDescription"
                                      class="form-control" rows="3"></textarea>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success">Update Department</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            document.getElementById('editForm').action = `/departments/${id}`;

            document.getElementById('editName').value = this.dataset.name;
            document.getElementById('editDescription').value = this.dataset.description;
            document.getElementById('editFacility').value = this.dataset.facility;
        });
    });
</script>
@endpush
