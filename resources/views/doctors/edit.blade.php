@extends('layouts.main')
@section('content')
<div class="container">
    <h2>Edit Doctor</h2>
    <form action="{{ route('doctors.update', $doctor) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label>User</label>
            <select name="user_id" class="form-control">
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ $doctor->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Department</label>
            <select name="department_id" class="form-control">
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $doctor->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_available" class="form-check-input" {{ $doctor->is_available ? 'checked' : '' }}>
            <label class="form-check-label">Available</label>
        </div>
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection