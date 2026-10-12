@extends('layouts.main')
@section('content')
<style>
    .mq-card { border-radius:16px; border:2px solid #0f6e56; background:#f1faf7; padding:24px; margin-bottom:14px; }
    .mq-card.next { border-color:#fd7e14; background:#fff8f0; }
    .mq-card.called { border-color:#dc3545; background:#fff5f5; animation:mqPulse 1.5s infinite; }
    @keyframes mqPulse { 50% { box-shadow:0 0 0 10px rgba(220,53,69,.15); } }
    .mq-num { font-size:56px; font-weight:800; line-height:1; color:#0f6e56; }
    .mq-card.next .mq-num { color:#fd7e14; }
    .mq-card.called .mq-num { color:#dc3545; }
    .mq-label { font-size:12px; text-transform:uppercase; color:#6c757d; letter-spacing:.5px; }
    .mq-token { border:2px dashed #0f6e56; border-radius:12px; padding:10px 18px; text-align:center; background:#fff; }
</style>

<div class="page-card p-4" style="max-width:900px;margin:auto;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="ti ti-list-numbers"></i> My Queue</h3>
        <small class="text-muted" id="mq-updated"></small>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div id="mq-box"><div class="text-muted">Loading…</div></div>
</div>

<script>
(function () {
    const box = document.getElementById('mq-box');
    const upd = document.getElementById('mq-updated');
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    const ord = n => { const s = ['th','st','nd','rd'], v = n % 100; return n + (s[(v - 20) % 10] || s[v] || s[0]); };

    function card(q) {
        const called = q.state === 'called';
        const cls = called ? 'called' : (q.position === 1 ? 'next' : '');
        const big = called ? 'NOW' : (q.position ? ord(q.position) : '…');
        const label = called ? 'Your turn' : 'Your position';
        return `
        <div class="mq-card ${cls}">
            <div class="d-flex flex-wrap align-items-center gap-4">
                <div class="mq-token"><div class="mq-label">Queue no.</div><div class="fs-2 fw-bold">#${esc(q.token)}</div></div>
                <div class="text-center">
                    <div class="mq-label">${label}</div>
                    <div class="mq-num">${big}</div>
                    ${q.total && !called ? `<div class="small text-muted">of ${q.total} waiting</div>` : ''}
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-1">${esc(q.message)}</h5>
                    <div>${esc(q.doctor)} · ${esc(q.department)}${q.room ? ' · Room ' + esc(q.room) : ''}</div>
                    <small class="text-muted">
                        Checked in ${esc(q.checked_in)}${q.appointment ? ' · Appointment ' + esc(q.appointment) : ' · Walk-in'}
                        ${q.expected_at && !called ? ' · Expected around <strong>' + esc(q.expected_at) + '</strong> (~' + q.expected_in + ' min)' : ''}
                    </small>
                </div>
            </div>
            ${q.state === 'waiting' && q.position > 1 ? '<div class="small text-muted mt-2">Emergency patients are always seen first, so your position can change.</div>' : ''}
        </div>`;
    }

    function render(d) {
        upd.textContent = 'Updated ' + d.updated_at + ' · refreshes every 15 s';
        if (d.items.length) {
            box.innerHTML = d.items.map(card).join('');
        } else if (d.done) {
            box.innerHTML = `<div class="alert alert-success mb-0"><i class="ti ti-circle-check"></i>
                Your visit today (queue no. #${esc(d.done.token)}) with ${esc(d.done.doctor)} was completed at ${esc(d.done.at)}.</div>`;
        } else {
            box.innerHTML = `<div class="alert alert-light border mb-0">You are not in a queue right now.
                Check in from <a href="{{ route('patient.appointments') }}">My Appointments</a> on the day of your visit,
                or use <a href="{{ route('patient.checkin') }}">Check In</a> as a walk-in.</div>`;
        }
    }

    async function load() {
        try {
            const r = await fetch("{{ route('patient.queue-status.data') }}", { headers: { Accept: 'application/json' } });
            if (r.ok) render(await r.json());
        } catch (e) {}
    }

    load();
    setInterval(load, 15000);
    window.addEventListener('hq-alert', load);
})();
</script>
@endsection