@extends('layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@push('styles')
<style>
    .notif-card { border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-radius: 12px; }
    .notif-list { list-style: none; padding: 0; margin: 0; }
    .notif-item {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        padding: 16px 18px;
        border-bottom: 1px solid var(--gray-200);
        transition: background 0.15s ease;
    }
    .notif-item:last-child { border-bottom: none; }
    .notif-item:hover { background: rgba(46,125,50,0.04); }
    .notif-item.unread { background: rgba(3,169,244,0.06); }
    .notif-item.unread:hover { background: rgba(3,169,244,0.10); }
    .notif-icon {
        width: 44px; height: 44px; flex-shrink: 0;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px;
    }
    .notif-icon.primary { background: rgba(46,125,50,0.12); color: var(--primary-green); }
    .notif-icon.success { background: rgba(76,175,80,0.14); color: #388e3c; }
    .notif-icon.info    { background: rgba(3,169,244,0.12); color: var(--light-blue); }
    .notif-icon.warning { background: rgba(245,124,0,0.14); color: var(--warning-orange); }
    .notif-icon.danger  { background: rgba(211,47,47,0.12); color: var(--danger-red); }
    .notif-body { flex-grow: 1; min-width: 0; }
    .notif-title { font-weight: 600; color: var(--gray-800); margin: 0; }
    .notif-title a { color: inherit; text-decoration: none; }
    .notif-title a:hover { color: var(--primary-green); }
    .notif-message { color: var(--gray-600); font-size: 0.9rem; margin: 2px 0 0; }
    .notif-time { color: var(--gray-600); font-size: 0.78rem; margin-top: 6px; display: block; }
    .notif-actions { display: flex; gap: 6px; flex-shrink: 0; }
    .notif-dot { width: 9px; height: 9px; border-radius: 50%; background: var(--light-blue); flex-shrink: 0; margin-top: 6px; }
    .notif-empty { text-align: center; padding: 60px 20px; color: var(--gray-600); }
    .notif-empty i { font-size: 46px; color: var(--gray-300); margin-bottom: 14px; }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid mt-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h4 class="mb-0 fw-bold">Notifications</h4>
                <small class="text-muted">
                    {{ $unread }} unread of {{ $notifications->total() }} total
                </small>
            </div>
            @if($unread > 0)
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-check-double me-1"></i> Mark all as read
                    </button>
                </form>
            @endif
        </div>

        <div class="card notif-card">
            @if($notifications->count() === 0)
                <div class="notif-empty">
                    <i class="fas fa-bell-slash d-block"></i>
                    <p class="mb-0">You're all caught up. No notifications yet.</p>
                </div>
            @else
                <ul class="notif-list">
                    @foreach($notifications as $n)
                        @php
                            $d = $n->data;
                            $color = $d['color'] ?? 'primary';
                            $icon = $d['icon'] ?? 'fa-bell';
                            $url = $d['url'] ?? null;
                        @endphp
                        <li class="notif-item {{ $n->read_at ? '' : 'unread' }}" id="notif-{{ $n->id }}">
                            <div class="notif-icon {{ $color }}">
                                <i class="fas {{ $icon }}"></i>
                            </div>
                            <div class="notif-body">
                                <p class="notif-title">
                                    @if($url)
                                        <a href="{{ route('notifications.open', $n->id) }}">{{ $d['title'] ?? 'Notification' }}</a>
                                    @else
                                        {{ $d['title'] ?? 'Notification' }}
                                    @endif
                                </p>
                                <p class="notif-message">{{ $d['message'] ?? '' }}</p>
                                <span class="notif-time"><i class="far fa-clock me-1"></i>{{ $n->created_at->diffForHumans() }}</span>
                            </div>
                            @unless($n->read_at)
                                <div class="notif-dot" title="Unread"></div>
                            @endunless
                            <div class="notif-actions">
                                @unless($n->read_at)
                                    <button type="button" class="btn btn-sm btn-light js-mark-read" data-id="{{ $n->id }}" title="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endunless
                                <button type="button" class="btn btn-sm btn-light text-danger js-delete" data-id="{{ $n->id }}" title="Remove">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="mt-3">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function post(url) {
        return fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });
    }

    document.querySelectorAll('.js-mark-read').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            post('{{ url('notifications') }}/' + id + '/read').then(function (r) {
                if (r.ok) {
                    const item = document.getElementById('notif-' + id);
                    item.classList.remove('unread');
                    const dot = item.querySelector('.notif-dot');
                    if (dot) dot.remove();
                    btn.remove();
                }
            });
        });
    });

    document.querySelectorAll('.js-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            fetch('{{ url('notifications') }}/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            }).then(function (r) {
                if (r.ok) {
                    const item = document.getElementById('notif-' + id);
                    item.style.transition = 'opacity 0.2s';
                    item.style.opacity = '0';
                    setTimeout(function () { item.remove(); }, 200);
                }
            });
        });
    });
});
</script>
@endpush
