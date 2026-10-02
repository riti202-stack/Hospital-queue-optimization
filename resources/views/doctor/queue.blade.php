@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="ti ti-stethoscope"></i> My Queue</h3>
        <span class="text-muted"><span class="live-dot"></span>Live</span>
    </div>

    <div id="queue-container" class="row g-3">
        <div class="col-12 text-center text-muted py-5">Loading queue…</div>
    </div>

    <hr class="my-4">

    <h5 class="mb-3"><i class="ti ti-flask"></i> Sent for tests — awaiting return</h5>
    <div id="referred-container" class="row g-3">
        <div class="col-12 text-muted">Loading…</div>
    </div>
</div>

<div class="modal fade" id="callModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionModalTitle">Call patient</h5>
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
let selectedAction = null; // 'call', 'refer', or 'return'
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
                <div class="d-flex gap-2 mt-auto">
                    <button class="btn btn-brand btn-sm flex-fill" onclick="openModal(${e.id}, '${e.patient_name}', 'call')">
                        <i class="ti ti-phone-call"></i> Call in
                    </button>
                    <button class="btn btn-outline-warning btn-sm flex-fill" onclick="openModal(${e.id}, '${e.patient_name}', 'refer')">
                        <i class="ti ti-flask"></i> Send for tests
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function renderReferred(entries) {
    const container = document.getElementById('referred-container');
    if (entries.length === 0) {
        container.innerHTML = '<div class="col-12 text-muted">No patients currently out for tests.</div>';
        return;
    }
    container.innerHTML = entries.map(e => `
        <div class="col-md-6 col-lg-4">
            <div class="card entry-card p-3 h-100 d-flex flex-row justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">${e.patient_name}</div>
                    <span class="badge ${urgencyBadgeClass(e.urgency_level)}">${e.urgency_level}</span>
                </div>
                <button class="btn btn-brand btn-sm" onclick="openModal(${e.id}, '${e.patient_name}', 'return')">
                    <i class="ti ti-corner-down-left"></i> Returned
                </button>
            </div>
        </div>
    `).join('');
}

function openModal(id, name, action) {
    selectedEntryId = id;
    selectedAction = action;
    const titles = {
        call: 'Call patient',
        refer: 'Send for tests',
        return: 'Patient returned',
    };
    const bodies = {
        call: `Mark ${name} as in progress?`,
        refer: `Send ${name} out for tests? They'll move out of the active queue until marked returned.`,
        return: `Add ${name} back into the active queue with their reports?`,
    };
    document.getElementById('actionModalTitle').textContent = titles[action];
    document.getElementById('callModalBody').textContent = bodies[action];
    new bootstrap.Modal(document.getElementById('callModal')).show();
}

document.getElementById('confirmCallBtn').addEventListener('click', () => {
    const endpoints = { call: 'call', refer: 'refer', return: 'return' };
    fetch(`/doctor/queue/${selectedEntryId}/${endpoints[selectedAction]}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
    }).then(() => {
        bootstrap.Modal.getInstance(document.getElementById('callModal')).hide();
        loadQueue();
        loadReferred();
    });
});

function loadQueue() {
    fetch('/doctor/queue/data').then(res => res.json()).then(renderQueue);
}
function loadReferred() {
    fetch('{{ route("doctor.queue.referred") }}').then(res => res.json()).then(renderReferred);
}

loadQueue();
loadReferred();
setInterval(() => { loadQueue(); loadReferred(); }, 5000);
</script>
@endsection