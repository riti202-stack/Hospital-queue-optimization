@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:600px;">
    <h3 class="mb-4"><i class="ti ti-file-download"></i> Export Doctor's Appointment List</h3>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form action="{{ route('admin.appointment-pdf.generate') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Doctor</label>
            <select name="doctor_id" class="form-select" required>
                <option value="">Select doctor</option>
                @foreach($doctors as $doc)
                    <option value="{{ $doc->id }}">{{ $doc->user->name }} — {{ $doc->department->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
        </div>
        <button class="btn btn-brand"><i class="ti ti-download"></i> Download PDF</button>
    </form>
</div>
@endsection