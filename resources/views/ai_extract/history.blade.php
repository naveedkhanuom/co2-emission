@extends('layouts.app')

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex align-items-center">
                <div>
                    <h4 class="mb-0"><i class="fas fa-clock-rotate-left me-2 text-primary"></i>AI Extraction History</h4>
                    <p class="text-muted mb-0 small">Every AI document run for your company — the proposal and how much was saved.</p>
                </div>
                <a href="{{ route('ai_extract.index') }}" class="btn btn-primary btn-sm ms-auto">
                    <i class="fas fa-plus me-1"></i>New extraction
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>When</th>
                                <th>File</th>
                                <th>Type</th>
                                <th>By</th>
                                <th class="text-center">Proposed</th>
                                <th class="text-center">Saved</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($extractions as $e)
                            <tr>
                                <td class="small text-nowrap">{{ $e->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-truncate" style="max-width:280px">
                                    <i class="fas fa-file me-1 text-muted"></i>{{ $e->file_name }}
                                </td>
                                <td class="small text-muted">{{ $e->document_type ?? '—' }}</td>
                                <td class="small">{{ $e->user?->name ?? '—' }}</td>
                                <td class="text-center">{{ $e->items_count }}</td>
                                <td class="text-center">{{ $e->saved_count }}</td>
                                <td>
                                    @php $sc = ['extracted'=>'bg-info','saved'=>'bg-success','discarded'=>'bg-secondary','failed'=>'bg-danger'][$e->status] ?? 'bg-secondary'; @endphp
                                    <span class="badge {{ $sc }}">{{ ucfirst($e->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No extractions yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $extractions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
