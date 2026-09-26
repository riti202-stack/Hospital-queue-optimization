@extends('layouts.main')
@section('content')
<div class="page-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="ti ti-stethoscope"></i> My Queue</h3>
        <span class="text-muted"><span class="live-dot"></span>Live</span>
    </div>

    <div id="queue-container" class="row g-3">
        <div class="col-12 text-center text-muted py-5">Loading queue…</div>
    </div>
</div>

<div class="modal fade" id="callModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Call patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="callModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-brand" id="confirmCallBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedEntryId = null;
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function urgencyBadgeClass(level) {
    return { emergency: 'badge-emergency', urgent: 'badge-urgent', routine: 'badge-routine' }[level] || 'bg-secondary';
}

function renderQueue(entries) {
    const container = document.getElementById('queue-container');
    if (entries.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted py-5">No patients waiting.</div>';
        return;
    }
    container.innerHTML = entries.map(e => `
        <div class="col-md-6 col-lg-4">
            <div class="card entry-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0">${e.patient_name}</h5>
                    <span class="badge ${urgencyBadgeClass(e.urgency_level)}">${e.urgency_level}</span>
                </div>
                <p class="text-muted mb-1"><i class="ti ti-clock"></i> Waiting ${e.waited_for}</p>
                <p class="text-muted mb-3"><i class="ti ti-chart-bar"></i> Priority score: ${e.priority_score}</p>
                <button class="btn btn-brand btn-sm mt-auto" onclick="openCallModal(${e.id}, '${e.patient_name}')">
                    <i class="ti ti-phone-call"></i> Call in
                </button>
            </div>
        </div>
    `).join('');
}

function openCallModal(id, name) {
    selectedEntryId = id;
    document.getElementById('callModalBody').textContent = `Mark ${name} as in progress?`;
    new bootstrap.Modal(document.getElementById('callModal')).show();
}

document.getElementById('confirmCallBtn').addEventListener('click', () => {
    fetch(`/doctor/queue/${selectedEntryId}/call`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
    }).then(() => {
        bootstrap.Modal.getInstance(document.getElementById('callModal')).hide();
        loadQueue();
    });
});

function loadQueue() {
    fetch('/doctor/queue/data')
        .then(res => res.json())
        .then(data => renderQueue(data));
}

loadQueue();
setInterval(loadQueue, 5000);
</script>
@endsection