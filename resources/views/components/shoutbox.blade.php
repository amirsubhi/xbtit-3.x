<div class="card" id="shoutbox">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-chat-left-dots"></i> Shoutbox</span>
        <span class="badge bg-secondary" id="shout-status">live</span>
    </div>

    {{-- Messages pane --}}
    <div id="shout-messages"
         style="height:200px;overflow-y:auto;padding:.5rem .75rem;font-size:.85rem;background:#fff;">
    </div>

    {{-- Input --}}
    @auth
        <div class="card-footer p-2">
            <form id="shout-form" class="d-flex gap-2" autocomplete="off">
                @csrf
                <input id="shout-input" type="text" class="form-control form-control-sm"
                       placeholder="Say something…" maxlength="500" required>
                <button class="btn btn-sm btn-primary" type="submit">
                    <i class="bi bi-send"></i>
                </button>
            </form>
        </div>
    @else
        <div class="card-footer p-2 text-center small text-muted">
            <a href="{{ route('login') }}">Log in</a> to chat.
        </div>
    @endauth
</div>

@push('scripts')
<script>
(function () {
    const box    = document.getElementById('shout-messages');
    const form   = document.getElementById('shout-form');
    const input  = document.getElementById('shout-input');
    const status = document.getElementById('shout-status');
    const csrf   = document.querySelector('meta[name="csrf-token"]').content;

    let lastId   = 0;
    let atBottom = true;

    box.addEventListener('scroll', () => {
        atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 20;
    });

    function scrollDown() {
        if (atBottom) box.scrollTop = box.scrollHeight;
    }

    function renderShout(s) {
        const time = new Date(s.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});

        const div      = document.createElement('div');
        div.className  = 'mb-1';

        const ts       = document.createElement('span');
        ts.className   = 'text-muted';
        ts.style.fontSize = '.75rem';
        ts.textContent = time + ' ';

        const user     = document.createElement('strong');
        user.className = 'text-primary';
        user.textContent = (s.author?.username ?? '?') + ': ';

        const msg      = document.createTextNode(s.message);

        div.appendChild(ts);
        div.appendChild(user);
        div.appendChild(msg);
        box.appendChild(div);

        if (lastId < s.id) lastId = s.id;
    }

    function poll() {
        fetch('/shoutbox?since=' + lastId)
            .then(r => r.json())
            .then(shouts => {
                shouts.forEach(renderShout);
                if (shouts.length) scrollDown();
                status.textContent = 'live';
                status.className   = 'badge bg-success';
            })
            .catch(() => {
                status.textContent = 'offline';
                status.className   = 'badge bg-danger';
            });
    }

    poll();
    setInterval(poll, 30000);

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const msg = input.value.trim();
            if (!msg) return;

            fetch('/shoutbox', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ message: msg }),
            })
            .then(r => r.json())
            .then(shout => {
                renderShout(shout);
                atBottom = true;
                scrollDown();
                input.value = '';
            });
        });
    }
})();
</script>
@endpush
