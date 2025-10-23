@extends('layouts.app')

@section('title', 'Companies')
@section('page-title', 'Companies')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-semibold text-gray-800">Companies List</h2>
            <button id="addCompanyBtn" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Add Company</button>
        </div>

        <div class="overflow-x-auto bg-white shadow rounded-lg">
            <table id="companiesTable" class="min-w-full divide-y divide-gray-200 table-auto">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Industry</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200"></tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="companyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
            <h3 class="text-lg font-semibold mb-4" id="modalTitle">Add Company</h3>
            <form id="companyForm">
                @csrf
                <input type="hidden" name="id" id="companyId">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm mb-1">Name</label>
                    <input type="text" name="name" id="companyName" class="w-full border-gray-300 rounded px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm mb-1">Industry Type</label>
                    <input type="text" name="industry_type" id="industryType" class="w-full border-gray-300 rounded px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm mb-1">Country</label>
                    <input type="text" name="country" id="country" class="w-full border-gray-300 rounded px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm mb-1">Contact Person</label>
                    <input type="text" name="contact_person" id="contactPerson" class="w-full border-gray-300 rounded px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm mb-1">Email</label>
                    <input type="email" name="email" id="email" class="w-full border-gray-300 rounded px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm mb-1">Phone</label>
                    <input type="text" name="phone" id="phone" class="w-full border-gray-300 rounded px-3 py-2">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="closeModal" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded bg-blue-500 text-white hover:bg-blue-600">Save</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <!-- Tailwind DataTables CSS -->
{{--    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.tailwind.min.css">--}}
@endpush

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#companiesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('companies.index') }}",
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'name', name: 'name' },
                    { data: 'industry_type', name: 'industry_type' },
                    { data: 'country', name: 'country' },
                    { data: 'email', name: 'email' },
                    { data: 'phone', name: 'phone' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                language: {
                    paginate: {
                        previous: '<',
                        next: '>'
                    }
                },
                drawCallback: function() {
                    $('.dataTables_paginate > .pagination').addClass('space-x-1');
                    $('.dataTables_paginate > .pagination li a')
                        .addClass('px-3 py-1 rounded border border-gray-300 hover:bg-gray-200');
                }
            });

            // Open modal
            $('#addCompanyBtn').click(function() {
                $('#companyForm')[0].reset();
                $('#companyId').val('');
                $('#modalTitle').text('Add Company');
                $('#companyModal').removeClass('hidden');
            });

            $('#closeModal').click(function() {
                $('#companyModal').addClass('hidden');
            });

            // Submit form
            $('#companyForm').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('companies.store') }}",
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(res) {
                        table.ajax.reload();
                        $('#companyModal').addClass('hidden');
                        alert(res.message);
                    },
                    error: function(err) {
                        console.log(err);
                        alert('Error saving company.');
                    }
                });
            });

            // Edit
            $('#companiesTable').on('click', '.editBtn', function() {
                var id = $(this).data('id');
                $.get("{{ url('companies') }}/"+id+"/edit", function(data) {
                    $('#companyId').val(data.id);
                    $('#companyName').val(data.name);
                    $('#industryType').val(data.industry_type);
                    $('#country').val(data.country);
                    $('#contactPerson').val(data.contact_person);
                    $('#email').val(data.email);
                    $('#phone').val(data.phone);
                    $('#modalTitle').text('Edit Company');
                    $('#companyModal').removeClass('hidden');
                });
            });

            // Delete
            $('#companiesTable').on('click', '.deleteBtn', function() {
                if(confirm('Are you sure you want to delete this company?')) {
                    var id = $(this).data('id');
                    $.ajax({
                        url: "{{ url('companies') }}/"+id,
                        method: 'DELETE',
                        data: { _token: "{{ csrf_token() }}" },
                        success: function(res) {
                            table.ajax.reload();
                            alert(res.message);
                        }
                    });
                }
            });

            // View
            $('#companiesTable').on('click', '.viewBtn', function() {
                var id = $(this).data('id');
                $.get("{{ url('companies') }}/"+id, function(data) {
                    let details = `
Name: ${data.name}
Industry: ${data.industry_type}
Country: ${data.country}
Contact Person: ${data.contact_person}
Email: ${data.email}
Phone: ${data.phone}
            `;
                    alert(details);
                });
            });
        });
    </script>
@endpush
