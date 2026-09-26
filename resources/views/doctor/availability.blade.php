@extends('layouts.main')
@section('content')
<div class="container">
    <h2>My Availability</h2>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <form action="{{ route('doctor.availability.update') }}" method="POST">
        @csrf
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_available" class="form-check-input" {{ $doctor->is_available ? 'checked' : '' }}>
            <label class="form-check-label">I am currently available to see patients</label>
        </div>
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection