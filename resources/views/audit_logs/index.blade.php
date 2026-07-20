@extends('layouts.app')

@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="audit-app container-fluid mt-4">
        <!-- Topbar -->
        <div class="topbar">
            <h2><span class="sb"><i class="fas fa-clock-rotate-left"></i></span> Audit Trail</h2>
            <p>A complete, tamper-evident record of every change to your emissions data — who changed what, and when.</p>
        </div>

        <!-- Filters -->
        <div class="card filter-card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="User or page…">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Action</label>
                        <select name="event" class="form-select">
                            <option value="">All</option>
                            @foreach(['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted', 'restored' => 'Restored'] as $val => $label)
                                <option value="{{ $val }}" @selected(($filters['event'] ?? '') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Record type</label>
                        <select name="model" class="form-select">
                            <option value="">All</option>
                            @foreach($models as $type => $label)
                                <option value="{{ $type }}" @selected(($filters['model'] ?? '') === $type)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-green btn-sm"><i class="fas fa-filter me-1"></i> Apply</button>
                        <a href="{{ route('audit-logs.index') }}" class="btn btn-light btn-sm">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Log table -->
        <div class="card audit-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:170px">When</th>
                                <th>User</th>
                                <th style="width:110px">Action</th>
                                <th>Record</th>
                                <th style="width:90px" class="text-center">Changes</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($logs as $log)
                            @php
                                $badge = ['created' => 'success', 'updated' => 'info', 'deleted' => 'danger', 'restored' => 'secondary'][$log->event] ?? 'secondary';
                                $changeKeys = array_unique(array_merge(
                                    array_keys($log->new_values ?? []),
                                    array_keys($log->old_values ?? [])
                                ));
                            @endphp
                            <tr>
                                <td>
                                    <div>{{ $log->created_at->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $log->created_at->format('H:i') }} · {{ $log->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    {{ $log->user_name ?? '—' }}
                                    @if($log->ip_address)<br><small class="text-muted">{{ $log->ip_address }}</small>@endif
                                </td>
                                <td><span class="badge bg-{{ $badge }} text-capitalize">{{ $log->event }}</span></td>
                                <td>
                                    <strong>{{ $log->model_label }}</strong>
                                    <small class="text-muted">#{{ $log->auditable_id }}</small>
                                </td>
                                <td class="text-center">
                                    @if(count($changeKeys))
                                        <button class="btn btn-sm btn-light toggle-diff" data-bs-toggle="collapse" data-bs-target="#diff-{{ $log->id }}">
                                            {{ count($changeKeys) }} <i class="fas fa-chevron-down ms-1"></i>
                                        </button>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @if(count($changeKeys))
                            <tr class="diff-row">
                                <td colspan="5" class="p-0">
                                    <div class="collapse" id="diff-{{ $log->id }}">
                                        <div class="diff-wrap">
                                            <table class="diff-table">
                                                <thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead>
                                                <tbody>
                                                @foreach($changeKeys as $key)
                                                    <tr>
                                                    <td class="field">{{ str_replace('_', ' ', $key) }}</td>
                                                    <td class="before">{{ \Illuminate\Support\Str::limit(is_array(data_get($log->old_values, $key)) ? json_encode(data_get($log->old_values, $key)) : (data_get($log->old_values, $key) ?? '—'), 120) }}</td>
                                                    <td class="after">{{ \Illuminate\Support\Str::limit(is_array(data_get($log->new_values, $key)) ? json_encode(data_get($log->new_values, $key)) : (data_get($log->new_values, $key) ?? '—'), 120) }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">
                                <i class="fas fa-clock-rotate-left fa-2x mb-2 d-block opacity-50"></i>
                                No changes recorded yet. Activity will appear here as your team edits data.
                            </td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.audit-app .topbar { display:flex; align-items:center; gap:16px; margin-bottom:20px; flex-wrap:wrap; padding:20px 24px; background:linear-gradient(135deg,#fff,var(--gray-50)); border:1px solid var(--gray-200); border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.audit-app .topbar h2 { font-size:1.35rem; font-weight:700; display:flex; align-items:center; gap:10px; margin:0; color:var(--gray-800); }
.audit-app .topbar h2 .sb { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,var(--primary-green),var(--light-green)); color:#fff; }
.audit-app .topbar p { color:var(--gray-600); font-size:.875rem; flex:1; min-width:180px; margin:0; }
.audit-app .card { border:1px solid var(--gray-200); border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.audit-app .form-label { font-weight:600; font-size:.78rem; color:var(--gray-600); text-transform:uppercase; letter-spacing:.04em; }
.audit-app .btn-green { background:linear-gradient(135deg,var(--primary-green),var(--light-green)); border:none; color:#fff; font-weight:600; }
.audit-app .btn-green:hover { color:#fff; filter:brightness(.95); }
.audit-app thead th { background:var(--gray-100); color:var(--gray-600); font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:13px 16px; border:none; }
.audit-app tbody td { padding:12px 16px; font-size:.875rem; vertical-align:middle; border-bottom:1px solid var(--gray-100); }
.audit-app .diff-row td { padding:0; border-bottom:1px solid var(--gray-100); }
.audit-app .diff-wrap { padding:14px 24px; background:var(--gray-50); }
.audit-app .diff-table { width:100%; font-size:.82rem; }
.audit-app .diff-table th { text-align:left; color:var(--gray-500); font-weight:700; font-size:.7rem; text-transform:uppercase; padding:4px 10px; }
.audit-app .diff-table td { padding:5px 10px; border-bottom:1px solid var(--gray-200); }
.audit-app .diff-table .field { font-weight:600; color:var(--gray-700); white-space:nowrap; }
.audit-app .diff-table .before { color:#b91c1c; }
.audit-app .diff-table .after { color:#15803d; }
.audit-app .toggle-diff { font-size:.78rem; border:1px solid var(--gray-200); }
</style>
@endpush
