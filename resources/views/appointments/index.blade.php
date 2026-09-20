@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Appointments</h2>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <a href="{{ route('appointments.create') }}" class="btn btn-primary mb-3">Book Appointment</a>
    <table class="table table-bordered">
        <thead><tr><th>#</th><th>Patient</th><th>Doctor</th><th>Department</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($appointments as $a)
            <tr>
                <td>{{ $a->id }}</td>
                <td>{{ $a->patient->name }}</td>
                <td>{{ $a->doctor->user->name }}</td>
                <td>{{ $a->department->name }}</td>
                <td>{{ $a->scheduled_time }}</td>
                <td>{{ $a->status }}</td>
                <td>
                    <a href="{{ route('appointments.edit', $a) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('appointments.destroy', $a) }}" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $appointments->links() }}
</div>
@endsection