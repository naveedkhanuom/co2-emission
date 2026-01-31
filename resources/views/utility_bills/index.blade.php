@extends('layouts.app')

@section('content')
<div id="content">
    @include('layouts.top-nav')
    
    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">
                            <i class="fas fa-file-invoice me-2 text-primary"></i>
                            Utility Bills
                        </h4>
                        <p class="text-muted mb-0 small">View and manage uploaded utility bills</p>
                    </div>
                    <a href="{{ route('utility.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Upload New Bill
                    </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-body">
                @if($bills->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Bill Type</th>
                                    <th>Supplier</th>
                                    <th>Bill Date</th>
                                    <th>Consumption</th>
                                    <th>Cost</th>
                                    <th>File</th>
                                    <th>Emission Record</th>
                                    <th>Uploaded By</th>
                                    <th>Uploaded At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bills as $bill)
                                    <tr>
                                        <td>{{ $bill->id }}</td>
                                        <td>
                                            <span class="badge bg-{{ $bill->bill_type === 'electricity' ? 'info' : 'warning' }}">
                                                {{ ucfirst($bill->bill_type) }}
                                            </span>
                                        </td>
                                        <td>{{ $bill->supplier_name ?? 'N/A' }}</td>
                                        <td>{{ $bill->bill_date ? $bill->bill_date->format('Y-m-d') : 'N/A' }}</td>
                                        <td>
                                            @if($bill->consumption)
                                                {{ number_format($bill->consumption, 2) }} {{ $bill->consumption_unit ?? 'kWh' }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($bill->cost)
                                                {{ number_format($bill->cost, 2) }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ asset('storage/'.$bill->file_path) }}" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                        </td>
                                        <td>
                                            @if($bill->emission_record_id)
                                                <a href="{{ route('review_data.index') }}" 
                                                   class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-check me-1"></i>Created
                                                </a>
                                            @else
                                                <span class="text-muted">Not created</span>
                                            @endif
                                        </td>
                                        <td>{{ $bill->uploader->name ?? 'N/A' }}</td>
                                        <td>{{ $bill->created_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No bills uploaded yet</h5>
                        <p class="text-muted">Upload your first utility bill to extract emission data</p>
                        <a href="{{ route('utility.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Upload Bill
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
