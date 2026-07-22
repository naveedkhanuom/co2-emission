        <!-- Top Navigation Bar -->
        <nav class="top-navbar">
            <button type="button" id="sidebarCollapse" class="btn">
                <i class="fas fa-bars"></i>
            </button>
            
            <h1>@yield('page-title', 'GHG Emissions Dashboard')</h1>
            
            <div class="user-profile">
                <div class="dropdown">
                    <div class="notifications" id="notifBell" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" role="button" tabindex="0" title="Notifications">
                        <i class="fas fa-bell fa-lg text-muted"></i>
                        <span class="notification-badge" id="notifBadge" style="display:none;">0</span>
                    </div>
                    <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0" aria-labelledby="notifBell">
                        <div class="notif-dd-header d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="fw-bold">Notifications</span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="notifMarkAll">Mark all read</button>
                        </div>
                        <div class="notif-dd-body" id="notifList">
                            <div class="notif-dd-empty text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <a href="{{ route('notifications.index') }}" class="notif-dd-footer d-block text-center py-2 text-decoration-none">
                            View all notifications
                        </a>
                    </div>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                            <span class="text-white fw-bold">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</span>
                        </div>
                        <div>
                            <span class="fw-bold">{{ auth()->user()->name ?? 'User' }}</span>
                            <div class="text-muted small">{{ auth()->user()->email ?? '' }}</div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i> Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

<style>
    .notif-dropdown {
        width: 360px;
        max-width: 92vw;
        border: none;
        box-shadow: 0 12px 34px rgba(17, 24, 39, 0.16);
        border-radius: 14px;
        margin-top: 10px;
        overflow: hidden;
    }
    .notif-dd-header { border-bottom: 1px solid var(--gray-200); }
    .notif-dd-header .btn-link { color: var(--primary-green); font-size: 0.82rem; }
    .notif-dd-body { max-height: 380px; overflow-y: auto; }
    .notif-dd-item {
        display: flex; gap: 12px; align-items: flex-start;
        padding: 12px 16px;
        border-bottom: 1px solid var(--gray-100);
        text-decoration: none; color: var(--gray-800);
        transition: background 0.15s ease;
    }
    .notif-dd-item:last-child { border-bottom: none; }
    .notif-dd-item:hover { background: rgba(46, 125, 50, 0.05); }
    .notif-dd-item.unread { background: rgba(3, 169, 244, 0.06); }
    .notif-dd-ico {
        width: 38px; height: 38px; flex-shrink: 0;
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 15px;
    }
    .notif-dd-ico.primary { background: rgba(46,125,50,0.12); color: var(--primary-green); }
    .notif-dd-ico.success { background: rgba(76,175,80,0.14); color: #388e3c; }
    .notif-dd-ico.info    { background: rgba(3,169,244,0.12); color: var(--light-blue); }
    .notif-dd-ico.warning { background: rgba(245,124,0,0.14); color: var(--warning-orange); }
    .notif-dd-ico.danger  { background: rgba(211,47,47,0.12); color: var(--danger-red); }
    .notif-dd-txt { min-width: 0; flex-grow: 1; }
    .notif-dd-title { font-weight: 600; font-size: 0.88rem; margin: 0; }
    .notif-dd-msg {
        font-size: 0.8rem; color: var(--gray-600); margin: 1px 0 0;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .notif-dd-time { font-size: 0.72rem; color: var(--gray-600); margin-top: 3px; }
    .notif-dd-footer { background: var(--gray-50); color: var(--primary-green); font-weight: 600; font-size: 0.85rem; }
    .notif-dd-footer:hover { background: var(--gray-100); color: var(--dark-green); }
    .notif-dd-empty { font-size: 0.85rem; }
</style>

<script>
(function () {
    // The top-nav partial is included on every page; only wire the bell once.
    if (window.__notifBellInit) return;
    window.__notifBellInit = true;

    document.addEventListener('DOMContentLoaded', function () {
        const badge = document.getElementById('notifBadge');
        const list = document.getElementById('notifList');
        const markAll = document.getElementById('notifMarkAll');
        if (!badge || !list) return;

        const csrf = document.querySelector('meta[name="csrf-token"]');
        const token = csrf ? csrf.getAttribute('content') : '';
        const feedUrl = "{{ route('notifications.feed') }}";
        const markAllUrl = "{{ route('notifications.readAll') }}";

        function esc(s) {
            return (s || '').replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        function render(data) {
            const count = data.count || 0;
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }

            if (!data.items || data.items.length === 0) {
                list.innerHTML = '<div class="notif-dd-empty text-center text-muted py-4">'
                    + '<i class="fas fa-bell-slash d-block mb-2" style="font-size:22px;opacity:.4;"></i>'
                    + 'No notifications yet</div>';
                return;
            }

            list.innerHTML = data.items.map(function (n) {
                return '<a href="' + n.openUrl + '" class="notif-dd-item ' + (n.read ? '' : 'unread') + '">'
                    + '<div class="notif-dd-ico ' + esc(n.color) + '"><i class="fas ' + esc(n.icon) + '"></i></div>'
                    + '<div class="notif-dd-txt">'
                    + '<p class="notif-dd-title">' + esc(n.title) + '</p>'
                    + '<p class="notif-dd-msg">' + esc(n.message) + '</p>'
                    + '<div class="notif-dd-time"><i class="far fa-clock me-1"></i>' + esc(n.time) + '</div>'
                    + '</div></a>';
            }).join('');
        }

        function load() {
            fetch(feedUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) { if (d) render(d); })
                .catch(function () { /* offline — leave last state */ });
        }

        if (markAll) {
            markAll.addEventListener('click', function (e) {
                e.preventDefault();
                fetch(markAllUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                }).then(function (r) { if (r.ok) load(); });
            });
        }

        load();
        setInterval(load, 60000); // refresh unread count every minute
    });
})();
</script>