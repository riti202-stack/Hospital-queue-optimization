@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:650px;">
    <h3 class="mb-4"><i class="ti ti-file-download"></i> Export Single Doctor Report</h3>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('admin.doctor-profile-pdf.generate') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" id="dept" class="form-select" required>
                <option value="">Select department</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }} ({{ $dept->doctors->count() }} doctors)
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Doctor</label>
            <select name="doctor_id" id="doctor" class="form-select" required disabled>
                <option value="">Select a department first</option>
            </select>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Appointments from</label>
                <input type="date" name="from" class="form-control" value="{{ old('from', now()->startOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Appointments to</label>
                <input type="date" name="to" class="form-control" value="{{ old('to', now()->endOfMonth()->format('Y-m-d')) }}">
            </div>
        </div>

        <div class="card entry-card p-3 mb-3">
            <div class="form-check mb-2">
                <input type="checkbox" name="include_contact" value="1" class="form-check-input" id="contact" checked>
                <label class="form-check-label" for="contact">Include personal &amp; emergency contact details</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="include_appointments" value="1" class="form-check-input" id="appts" checked>
                <label class="form-check-label" for="appts">Include appointment summary and full appointment list</label>
            </div>
            <small class="text-muted mt-2">Licensure, qualifications, employment and compliance details are always included.</small>
        </div>

        <button class="btn btn-brand"><i class="ti ti-download"></i> Download Doctor PDF</button>
    </form>
</div>

<script>
const doctorsByDept = @json($doctorMap);
const deptSelect = document.getElementById('dept');
const doctorSelect = document.getElementById('doctor');
const oldDoctor = "{{ old('doctor_id') }}";

function loadDoctors() {
    const list = doctorsByDept[deptSelect.value] || [];
    doctorSelect.innerHTML = '';

    if (!deptSelect.value) {
        doctorSelect.innerHTML = '<option value="">Select a department first</option>';
        doctorSelect.disabled = true;
        return;
    }
    if (list.length === 0) {
        doctorSelect.innerHTML = '<option value="">No doctors in this department</option>';
        doctorSelect.disabled = true;
        return;
    }

    doctorSelect.innerHTML = '<option value="">Select doctor</option>';
    list.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.id;
        opt.textContent = d.name + (d.room ? ' — Room ' + d.room : '');
        if (String(d.id) === oldDoctor) opt.selected = true;
        doctorSelect.appendChild(opt);
    });
    doctorSelect.disabled = false;
}

deptSelect.addEventListener('change', loadDoctors);
loadDoctors(); // restores the selection after a validation error
</script>
@endsection