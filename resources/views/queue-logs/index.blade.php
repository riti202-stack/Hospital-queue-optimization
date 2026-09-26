@extends('layouts.main')
@section('content')
<div class="container">
    <h2>Queue Logs</h2>
    <p class="text-muted">Logs are created automatically by the system for crash recovery — there is no manual "create" form for this table.</p>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <table class="table table-bordered">
        <thead><tr><th>#</th><th>Queue Entry</th><th>Action</th><th>Logged At</th><th></th></tr></thead>
        <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->id }}</td>
                <td>{{ $log->queueEntry->patient->name ?? 'N/A' }} (#{{ $log->queue_entry_id }})</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->logged_at }}</td>
                <td>
                    <form action="{{ route('queue-logs.destroy', $log) }}" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection