@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Live Queue</h2>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <a href="{{ route('queue-entries.create') }}" class="btn btn-primary mb-3">Check In Patient</a>
    <table class="table table-bordered">
        <thead><tr><th>#</th><th>Patient</th><th>Urgency</th><th>Priority</th><th>Doctor</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($entries as $e)
            <tr>
                <td>{{ $e->id }}</td>
                <td>{{ $e->patient->name }}</td>
                <td>{{ ucfirst($e->urgency_level) }}</td>
                <td>{{ $e->priority_score }}</td>
                <td>{{ $e->doctor->user->name ?? '—' }}</td>
                <td>{{ $e->status }}</td>
                <td>
                    <a href="{{ route('queue-entries.edit', $e) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('queue-entries.destroy', $e) }}" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $entries->links() }}
</div>
@endsection