@extends('layouts.main')
@section('content')
<div class="container">
    <h2>My Appointments</h2>
    <table class="table table-bordered">
        <thead><tr><th>Patient</th><th>Department</th><th>Time</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($appointments as $a)
            <tr>
                <td>{{ $a->patient->name }}</td>
                <td>{{ $a->department->name }}</td>
                <td>{{ $a->scheduled_time }}</td>
                <td>{{ $a->status }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center">No appointments.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $appointments->links() }}
</div>
@endsection