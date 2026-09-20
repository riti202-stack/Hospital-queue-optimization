@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Edit User Role — {{ $user->name }}</h2>
    <form action="{{ route('users.update', $user) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label>Role</label>
            <select name="role" class="form-control">
                <option value="patient" {{ $user->role == 'patient' ? 'selected' : '' }}>Patient</option>
                <option value="doctor" {{ $user->role == 'doctor' ? 'selected' : '' }}>Doctor</option>
                <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection