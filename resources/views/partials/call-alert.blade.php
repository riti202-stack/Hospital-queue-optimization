<div id="hq-alert" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:2000;align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:20px;max-width:460px;width:100%;padding:32px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);">
        <div id="hq-alert-icon" style="font-size:56px;line-height:1;"></div>
        <h3 id="hq-alert-title" class="mt-3 mb-2"></h3>
        <p id="hq-alert-msg" class="fs-5 mb-4"></p>
        <button id="hq-alert-ok" class="btn btn-lg w-100" style="background:#0f6e56;color:#fff;">OK</button>
    </div>
</div>

<script>
(function () {
    const url = "{{ route('patient.alerts') }}";
    const readUrl = "{{ url('/patient/alerts') }}";
    const csrf = "{{ csrf_token() }}";
    const wrap = document.getElementById('hq-alert');
    const icon = document.getElementById('hq-alert-icon');
    const title = document.getElementById('hq-alert-title');
    const msg = document.getElementById('hq-alert-msg');
    const ok = document.getElementById('hq-alert-ok');
    const queue = [], seen = new Set();
    let showing = null;

    function beep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            [0, 0.35, 0.7].forEach(t => {
                const o = ctx.createOscillator(), g = ctx.createGain();
                o.frequency.value = 880; o.connect(g); g.connect(ctx.destination);
                g.gain.setValueAtTime(0.2, ctx.currentTime + t);
                o.start(ctx.currentTime + t); o.stop(ctx.currentTime + t + 0.2);
            });
        } catch (e) {}
    }

    function show() {
        if (showing || !queue.length) return;
        showing = queue.shift();
        const called = showing.type === 'patient_called';
        icon.innerHTML = called
            ? '<i class="ti ti-bell-ringing" style="color:#dc3545"></i>'
            : '<i class="ti ti-circle-check" style="color:#198754"></i>';
        title.textContent = showing.title || '';
        msg.textContent = showing.message || '';
        ok.textContent = called ? "OK, I'm going" : 'OK';
        wrap.style.display = 'flex';
        if (called) beep();
        window.dispatchEvent(new Event('hq-alert'));
    }

    ok.addEventListener('click', async () => {
        const id = showing.id;
        wrap.style.display = 'none';
        showing = null;
        try {
            await fetch(readUrl + '/' + id + '/read', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
        } catch (e) {}
        show();
    });

    async function poll() {
        try {
            const r = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!r.ok) return;
            const d = await r.json();
            d.items.forEach(n => { if (!seen.has(n.id)) { seen.add(n.id); queue.push(n); } });
            show();
        } catch (e) {}
    }

    poll();
    setInterval(poll, 10000);
})();
</script>