@extends('layouts.app')

@section('title', 'Utility Bills')
@section('page-title', 'Utility Bills')

@push('styles')
<style>
.utility-bills-app * { box-sizing: border-box; }
.utility-bills-app { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
/* Topbar - same as Scope 1/2/3 */
.utility-bills-app .topbar { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; padding: 20px 24px; background: linear-gradient(135deg, #fff 0%, var(--gray-50) 100%); border: 1px solid var(--gray-200); border-radius: 16px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.utility-bills-app .topbar h2 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px; margin: 0; color: var(--gray-800); }
.utility-bills-app .topbar h2 .sb { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; font-size: 1rem; font-weight: 700; box-shadow: 0 2px 8px rgba(46,125,50,.25); }
.utility-bills-app .topbar p { color: var(--gray-600); font-size: 0.875rem; flex: 1; min-width: 180px; margin: 0; line-height: 1.4; }
.utility-bills-app .btn-add { margin-left: auto; padding: 10px 20px; border-radius: 10px; background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; border: none; font-size: 0.875rem; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; box-shadow: 0 2px 8px rgba(46,125,50,.25); transition: transform .2s, box-shadow .2s; text-decoration: none; }
.utility-bills-app .btn-add:hover { background: linear-gradient(135deg, var(--dark-green) 0%, var(--primary-green) 100%); color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(46,125,50,.35); }
/* Alerts */
.utility-bills-app .alert { border-radius: 12px; border: 1px solid transparent; }
.utility-bills-app .alert-success { background: rgba(76,175,80,0.1); border-color: rgba(76,175,80,0.25); color: var(--dark-green); }
.utility-bills-app .alert-danger { background: rgba(211,47,47,0.08); border-color: rgba(211,47,47,0.2); color: var(--danger-red); }
/* Table card */
.utility-bills-app .tw { background: #fff; border: 1px solid var(--gray-200); border-radius: 16px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.utility-bills-app .tw .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.utility-bills-app #utilityBillsTable { width: 100%; border-collapse: separate; border-spacing: 0; }
.utility-bills-app #utilityBillsTable thead th { background: var(--gray-100); color: var(--gray-600); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 14px 16px; border: none; border-bottom: 1px solid var(--gray-200); }
.utility-bills-app #utilityBillsTable thead th:first-child { padding-left: 20px; }
.utility-bills-app #utilityBillsTable tbody td { padding: 14px 16px; font-size: 0.875rem; color: var(--gray-800); border: none; border-bottom: 1px solid var(--gray-100); vertical-align: middle; }
.utility-bills-app #utilityBillsTable tbody td:first-child { padding-left: 20px; }
.utility-bills-app #utilityBillsTable tbody tr:hover td { background: var(--gray-50); }
.utility-bills-app #utilityBillsTable tbody tr:last-child td { border-bottom: none; }
/* Badges */
.utility-bills-app .badge-type { padding: 4px 10px; border-radius: 100px; font-size: 0.75rem; font-weight: 600; }
.utility-bills-app .badge-type.electricity { background: rgba(2,119,189,0.12); color: var(--primary-blue); }
.utility-bills-app .badge-type.fuel { background: rgba(245,124,0,0.12); color: var(--warning-orange); }
/* Action buttons */
.utility-bills-app .btn-view { padding: 6px 12px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; border: 1.5px solid var(--gray-200); background: #fff; color: var(--gray-700); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all .15s; }
.utility-bills-app .btn-view:hover { border-color: var(--primary-green); background: rgba(46,125,50,0.08); color: var(--primary-green); }
.utility-bills-app .btn-created { padding: 6px 12px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; border: none; background: rgba(76,175,80,0.15); color: var(--light-green); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all .15s; }
.utility-bills-app .btn-created:hover { background: rgba(76,175,80,0.25); color: var(--dark-green); }
/* Empty state */
.utility-bills-app .empty-state { text-align: center; padding: 48px 24px; }
.utility-bills-app .empty-state .empty-icon { width: 80px; height: 80px; border-radius: 20px; background: var(--gray-100); color: var(--gray-400); display: inline-flex; align-items: center; justify-content: center; font-size: 2.5rem; margin-bottom: 16px; }
.utility-bills-app .empty-state h5 { font-size: 1.125rem; font-weight: 700; color: var(--gray-700); margin-bottom: 8px; }
.utility-bills-app .empty-state p { font-size: 0.875rem; color: var(--gray-600); margin-bottom: 20px; }
.utility-bills-app .empty-state .btn-add { margin-left: 0; }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="utility-bills-app container-fluid mt-4">
        <!-- Topbar -->
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-file-invoice"></i></span> Utility Bills</h2>
            <p>View and manage uploaded utility bills for emission data extraction</p>
            <a href="{{ route('utility.create') }}" class="btn-add">
                <i class="fas fa-plus"></i> Upload New Bill
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="tw">
            @if($bills->count() > 0)
                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="utilityBillsTable">
                        <thead>
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
                                        <span class="badge-type {{ $bill->bill_type }}">
                                            {{ ucfirst($bill->bill_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $bill->supplier_name ?? '—' }}</td>
                                    <td>{{ $bill->bill_date ? $bill->bill_date->format('Y-m-d') : '—' }}</td>
                                    <td>
                                        @if($bill->consumption)
                                            {{ number_format($bill->consumption, 2) }} {{ $bill->consumption_unit ?? 'kWh' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $bill->cost ? number_format($bill->cost, 2) : '—' }}</td>
                                    <td>
                                        <a href="{{ asset('storage/'.$bill->file_path) }}" target="_blank" class="btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                    <td>
                                        @if($bill->emission_record_id)
                                            <a href="{{ route('review_data.index') }}" class="btn-created">
                                                <i class="fas fa-check"></i> Created
                                            </a>
                                        @else
                                            <span style="color: var(--gray-500); font-size: 0.875rem;">Not created</span>
                                        @endif
                                    </td>
                                    <td>{{ $bill->uploader->name ?? '—' }}</td>
                                    <td>{{ $bill->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-file-invoice"></i></div>
                    <h5>No bills uploaded yet</h5>
                    <p>Upload your first utility bill to extract emission data automatically.</p>
                    <a href="{{ route('utility.create') }}" class="btn-add">
                        <i class="fas fa-plus"></i> Upload Bill
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
