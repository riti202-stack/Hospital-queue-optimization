@extends('layouts.main')
@section('content')
<div class="page-card">
    <h3 class="mb-4"><i class="ti ti-clipboard-check"></i> Walk-in check-in</h3>
    <form action="{{ route('patient.checkin.store') }}" method="POST" class="row g-3">
        @csrf
        <div class="col-md-6">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select" required>
                <option value="">Select department</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">How urgent is your condition?</label>
            <div class="d-flex gap-2">
                <label class="btn btn-outline-danger flex-fill">
                    <input type="radio" name="urgency_level" value="emergency" class="d-none" required> Emergency
                </label>
                <label class="btn btn-outline-warning flex-fill">
                    <input type="radio" name="urgency_level" value="urgent" class="d-none"> Urgent
                </label>
                <label class="btn btn-outline-success flex-fill">
                    <input type="radio" name="urgency_level" value="routine" class="d-none"> Routine
                </label>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-brand"><i class="ti ti-login"></i> Check in</button>
        </div>
    </form>
</div>
<script>
document.querySelectorAll('.btn-outline-danger, .btn-outline-warning, .btn-outline-success').forEach(label => {
    label.addEventListener('click', () => {
        document.querySelectorAll('.btn-outline-danger, .btn-outline-warning, .btn-outline-success')
            .forEach(l => l.classList.remove('active'));
        label.classList.add('active');
    });
});
</script>
@endsection