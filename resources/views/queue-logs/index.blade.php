@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <h3 class="mb-2"><i class="ti ti-history"></i> Queue Logs</h3>
    <p class="text-muted small mb-4">Logs are created automatically by the system for crash recovery.</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <table class="table align-middle">
        <thead><tr><th>#</th><th>Queue Entry</th><th>Action</th><th>Logged At</th><th></th></tr></thead>
        <tbody>
        @foreach($logs as $log)
            <tr>
                <td>{{ $log->id }}</td>
                <td>{{ $log->queueEntry->patient->name ?? 'N/A' }} (#{{ $log->queue_entry_id }})</td>
                <td><span class="badge bg-secondary">{{ $log->action }}</span></td>
                <td>{{ $log->logged_at }}</td>
                <td>
                    <form action="{{ route('queue-logs.destroy', $log) }}" method="POST">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="ti ti-trash"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $logs->links() }}
</div>
@endsection