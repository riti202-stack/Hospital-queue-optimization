@extends('layouts.main')
@section('content')
<div class="page-card text-center" id="status-container">
    <p class="text-muted">Loading your status…</p>
</div>

<script>
function render(data) {
    const el = document.getElementById('status-container');
    if (!data.active) {
        el.innerHTML = '<p class="text-muted mb-0">You are not currently in any queue. <a href="{{ route("patient.checkin") }}">Check in now</a>.</p>';
        return;
    }
    el.innerHTML = `
        <h3 class="mb-3"><span class="live-dot"></span>Live queue status</h3>
        <div class="display-4 fw-bold text-success mb-2">#${data.position ?? '—'}</div>
        <p class="text-muted mb-3">Estimated wait: ~${data.estimated_wait} min</p>
        <p><span class="badge ${data.urgency_level === 'emergency' ? 'badge-emergency' : data.urgency_level === 'urgent' ? 'badge-urgent' : 'badge-routine'}">${data.urgency_level}</span></p>
        <p class="text-muted small mb-0">Waiting for ${data.waited_for} · Status: ${data.status}</p>
    `;
}

function load() {
    fetch('{{ route("patient.queue-status.data") }}')
        .then(res => res.json())
        .then(render);
}

load();
setInterval(load, 5000);
</script>
@endsection