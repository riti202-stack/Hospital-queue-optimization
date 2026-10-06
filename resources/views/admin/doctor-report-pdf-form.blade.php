@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:600px;">
    <h3 class="mb-4"><i class="ti ti-file-download"></i> Export Doctor Report</h3>
    <form action="{{ route('admin.doctor-report-pdf.generate') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                <option value="">All departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Date (for activity & appointments)</label>
            <input type="date" name="date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
        </div>

        <div class="card entry-card p-3 mb-3">
            <div class="form-check mb-2">
                <input type="checkbox" name="include_contact" value="1" class="form-check-input" id="contact" checked>
                <label class="form-check-label" for="contact">Include contact & professional details (phone, license, specialization)</label>
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="include_activity" value="1" class="form-check-input" id="activity" checked>
                <label class="form-check-label" for="activity">Include daily activity summary (active doctors — morning / afternoon)</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="include_appointments" value="1" class="form-check-input" id="appts" checked>
                <label class="form-check-label" for="appts">Include that day's full appointment list</label>
            </div>
        </div>

        <button class="btn btn-brand"><i class="ti ti-download"></i> Download PDF</button>
    </form>
</div>
@endsection