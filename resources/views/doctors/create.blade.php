@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Add Doctor</h2>
    <form action="{{ route('doctors.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>User</label>
            <select name="user_id" class="form-control">
                @foreach($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Department</label>
            <select name="department_id" class="form-control">
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_available" class="form-check-input" checked>
            <label class="form-check-label">Available</label>
        </div>
        <button class="btn btn-primary">Save</button>
    </form>
</div>
@endsection