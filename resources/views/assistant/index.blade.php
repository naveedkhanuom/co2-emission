@extends('layouts.app')

@section('title', 'Ask Your Data')
@section('page-title', 'Ask Your Data')

@push('styles')
<style>
    .ask-wrap { max-width: 900px; margin: 0 auto; }
    .ask-hero {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
        color: #fff; border-radius: 16px; padding: 22px 24px; margin-bottom: 18px;
        display: flex; align-items: center; gap: 16px;
        box-shadow: 0 10px 26px rgba(46,125,50,0.22);
    }
    .ask-hero .ai-orb {
        width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
        background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center;
        font-size: 24px;
    }
    .ask-hero h4 { margin: 0; font-weight: 700; }
    .ask-hero p { margin: 2px 0 0; opacity: 0.9; font-size: 0.9rem; }
    .ask-hero .form-select {
        width: auto; margin-left: auto; border: none; border-radius: 10px;
        background: rgba(255,255,255,0.2); color: #fff; font-weight: 600; font-size: 0.85rem;
    }
    .ask-hero .form-select option { color: #333; }

    .ask-banner {
        background: rgba(3,169,244,0.08); border: 1px solid rgba(3,169,244,0.25);
        color: #0369a1; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; font-size: 0.88rem;
    }

    .chat-card {
        background: #fff; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        display: flex; flex-direction: column; height: calc(100vh - 300px); min-height: 420px;
    }
    .chat-scroll { flex-grow: 1; overflow-y: auto; padding: 22px; }
    .chat-empty { text-align: center; color: var(--gray-600); padding: 26px 12px; }
    .chat-empty .ic { font-size: 42px; color: var(--primary-green); opacity: 0.35; margin-bottom: 10px; }

    .suggestions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-top: 14px; }
    .suggestion {
        background: rgba(46,125,50,0.07); border: 1px solid rgba(46,125,50,0.18); color: var(--dark-green);
        border-radius: 999px; padding: 8px 14px; font-size: 0.82rem; cursor: pointer; transition: all 0.15s ease;
        text-align: left;
    }
    .suggestion:hover { background: rgba(46,125,50,0.14); transform: translateY(-1px); }

    .msg { display: flex; gap: 12px; margin-bottom: 18px; align-items: flex-start; }
    .msg .avatar {
        width: 36px; height: 36px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 15px;
    }
    .msg.assistant .avatar { background: rgba(46,125,50,0.12); color: var(--primary-green); }
    .msg.user { flex-direction: row-reverse; }
    .msg.user .avatar { background: rgba(3,169,244,0.14); color: var(--light-blue); }
    .bubble {
        border-radius: 14px; padding: 12px 16px; max-width: 82%; line-height: 1.55; font-size: 0.92rem;
    }
    .msg.assistant .bubble { background: var(--gray-50); border: 1px solid var(--gray-200); color: var(--gray-800); }
    .msg.user .bubble { background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%); color: #fff; }
    .bubble p { margin: 0 0 8px; } .bubble p:last-child { margin-bottom: 0; }
    .bubble ul { margin: 4px 0 8px; padding-left: 20px; } .bubble li { margin: 2px 0; }
    .bubble strong { font-weight: 700; }
    .bubble code { background: rgba(0,0,0,0.06); padding: 1px 5px; border-radius: 4px; font-size: 0.86em; }
    .msg.user .bubble code { background: rgba(255,255,255,0.2); }
    .bubble .ans-table {
        width: 100%; border-collapse: collapse; margin: 6px 0 10px; font-size: 0.86rem;
    }
    .bubble .ans-table th, .bubble .ans-table td {
        text-align: left; padding: 7px 10px; border-bottom: 1px solid var(--gray-200);
        font-variant-numeric: tabular-nums;
    }
    .bubble .ans-table th { background: rgba(46,125,50,0.07); font-weight: 700; color: var(--dark-green); }
    .bubble .ans-table tr:last-child td { border-bottom: none; }
    .msg.user .bubble .ans-table th, .msg.user .bubble .ans-table td { border-color: rgba(255,255,255,0.25); }
    .msg.user .bubble .ans-table th { background: rgba(255,255,255,0.15); color: #fff; }
    .bubble .grounded-tag {
        display: inline-flex; align-items: center; gap: 5px; margin-top: 8px;
        font-size: 0.72rem; color: var(--gray-600); border-top: 1px solid var(--gray-200); padding-top: 6px;
    }

    .typing span {
        display: inline-block; width: 7px; height: 7px; margin: 0 2px; border-radius: 50%;
        background: var(--primary-green); opacity: 0.4; animation: blink 1.2s infinite both;
    }
    .typing span:nth-child(2){ animation-delay: 0.2s; } .typing span:nth-child(3){ animation-delay: 0.4s; }
    @keyframes blink { 0%,80%,100%{opacity:0.25;} 40%{opacity:0.9;} }

    .chat-input { border-top: 1px solid var(--gray-200); padding: 14px 18px; display: flex; gap: 10px; align-items: flex-end; }
    .chat-input textarea {
        flex-grow: 1; resize: none; border: 1px solid var(--gray-300); border-radius: 12px;
        padding: 11px 14px; font-size: 0.92rem; max-height: 120px; outline: none;
    }
    .chat-input textarea:focus { border-color: var(--primary-green); box-shadow: 0 0 0 0.15rem rgba(46,125,50,0.12); }
    .chat-send {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
        border: none; color: #fff; border-radius: 12px; width: 46px; height: 46px; flex-shrink: 0;
        font-size: 17px; cursor: pointer; transition: transform 0.15s ease;
    }
    .chat-send:hover { transform: translateY(-2px); } .chat-send:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
    .chat-disclaimer { text-align: center; font-size: 0.72rem; color: var(--gray-600); margin-top: 10px; }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid mt-4">
        <div class="ask-wrap">
            <div class="ask-hero">
                <div class="ai-orb"><i class="fas fa-robot"></i></div>
                <div>
                    <h4>Ask Your Data</h4>
                    <p>Ask about your emissions in plain language — answers come from your own inventory.</p>
                </div>
                <select class="form-select" id="askPeriod" title="Reporting period">
                    <option value="12">Last 12 months</option>
                    <option value="ytd">Year to date</option>
                    <option value="3">Last 3 months</option>
                </select>
            </div>

            @unless($aiEnabled)
                <div class="ask-banner">
                    <i class="fas fa-circle-info me-1"></i>
                    The AI assistant isn't configured yet, so answers will be automatic data summaries. Add an <code>ANTHROPIC_API_KEY</code> to enable full natural-language replies.
                </div>
            @endunless

            <div class="chat-card">
                <div class="chat-scroll" id="chatScroll">
                    <div class="chat-empty" id="chatEmpty">
                        <div class="ic"><i class="fas fa-comments"></i></div>
                        <p class="mb-1 fw-semibold">What would you like to know about your emissions?</p>
                        <p class="mb-0 small text-muted">Pick a question to get started, or type your own.</p>
                        <div class="suggestions">
                            @foreach($suggestions as $s)
                                <button type="button" class="suggestion js-suggestion">{{ $s }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="chat-input">
                    <textarea id="chatInput" rows="1" placeholder="Ask about your Scope 1, 2 or 3 emissions…" maxlength="2000"></textarea>
                    <button class="chat-send" id="chatSend" title="Send"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
            <p class="chat-disclaimer">
                <i class="fas fa-shield-halved me-1"></i>
                Answers are generated from your company's data only. Verify figures before external reporting.
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const askUrl = "{{ route('assistant.ask') }}";
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const scroll = document.getElementById('chatScroll');
    const empty = document.getElementById('chatEmpty');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');
    const period = document.getElementById('askPeriod');

    let history = [];   // [{role, content}]
    let busy = false;

    function escapeHtml(s) {
        return s.replace(/[&<>]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]));
    }

    // Minimal, safe markdown: bold, italics, inline code, bullet lists, tables.
    function inlineMd(s) {
        return s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/`([^`]+)`/g, '<code>$1</code>')
                .replace(/(^|[\s(])_(.+?)_(?=[\s.,)]|$)/g, '$1<em>$2</em>');
    }
    function cells(row) {
        return row.trim().replace(/^\||\|$/g, '').split('|').map(c => c.trim());
    }
    function render(text) {
        const lines = escapeHtml(text).split('\n');
        let html = '', inList = false, i = 0;
        while (i < lines.length) {
            const ln = lines[i];
            const next = lines[i + 1] || '';
            // GitHub-style table: a |..| header followed by a |---| separator row.
            const isRow = /^\s*\|.*\|\s*$/.test(ln);
            const isSep = /^\s*\|?[\s:|-]*-[\s:|-]*$/.test(next) && next.indexOf('|') !== -1;
            if (isRow && isSep) {
                if (inList) { html += '</ul>'; inList = false; }
                const head = cells(ln).map(c => '<th>' + inlineMd(c) + '</th>').join('');
                i += 2;
                let body = '';
                while (i < lines.length && /^\s*\|.*\|\s*$/.test(lines[i])) {
                    body += '<tr>' + cells(lines[i]).map(c => '<td>' + inlineMd(c) + '</td>').join('') + '</tr>';
                    i++;
                }
                html += '<table class="ans-table"><thead><tr>' + head + '</tr></thead><tbody>' + body + '</tbody></table>';
                continue;
            }
            const md = inlineMd(ln);
            if (/^\s*[•\-\*]\s+/.test(ln)) {
                if (!inList) { html += '<ul>'; inList = true; }
                html += '<li>' + md.replace(/^\s*[•\-\*]\s+/, '') + '</li>';
            } else {
                if (inList) { html += '</ul>'; inList = false; }
                if (ln.trim() !== '') html += '<p>' + md + '</p>';
            }
            i++;
        }
        if (inList) html += '</ul>';
        return html;
    }

    function addMessage(role, text, ai) {
        if (empty) empty.style.display = 'none';
        const wrap = document.createElement('div');
        wrap.className = 'msg ' + role;
        const icon = role === 'user' ? 'fa-user' : 'fa-robot';
        let inner = '<div class="avatar"><i class="fas ' + icon + '"></i></div>'
            + '<div class="bubble">' + render(text);
        if (role === 'assistant' && ai === false) {
            inner += '<div class="grounded-tag"><i class="fas fa-calculator"></i> Computed from your data</div>';
        } else if (role === 'assistant') {
            inner += '<div class="grounded-tag"><i class="fas fa-database"></i> Grounded in your inventory</div>';
        }
        inner += '</div>';
        wrap.innerHTML = inner;
        scroll.appendChild(wrap);
        scroll.scrollTop = scroll.scrollHeight;
    }

    function showTyping() {
        const el = document.createElement('div');
        el.className = 'msg assistant';
        el.id = 'typingRow';
        el.innerHTML = '<div class="avatar"><i class="fas fa-robot"></i></div>'
            + '<div class="bubble"><div class="typing"><span></span><span></span><span></span></div></div>';
        scroll.appendChild(el);
        scroll.scrollTop = scroll.scrollHeight;
    }
    function hideTyping() { const t = document.getElementById('typingRow'); if (t) t.remove(); }

    function send(question) {
        if (busy || !question.trim()) return;
        busy = true; sendBtn.disabled = true;
        addMessage('user', question);
        history.push({ role: 'user', content: question });
        input.value = ''; input.style.height = 'auto';
        showTyping();

        fetch(askUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ question: question, history: history.slice(0, -1), date_range: period.value }),
        })
        .then(r => r.json())
        .then(d => {
            hideTyping();
            const answer = d.answer || 'No answer returned.';
            addMessage('assistant', answer, d.ai);
            history.push({ role: 'assistant', content: answer });
        })
        .catch(() => {
            hideTyping();
            addMessage('assistant', 'Something went wrong. Please try again.', false);
        })
        .finally(() => { busy = false; sendBtn.disabled = false; input.focus(); });
    }

    document.querySelectorAll('.js-suggestion').forEach(function (b) {
        b.addEventListener('click', function () { send(this.textContent.trim()); });
    });

    sendBtn.addEventListener('click', function () { send(input.value); });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(input.value); }
    });
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
});
</script>
@endpush
