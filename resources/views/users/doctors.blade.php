@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h3 class="mb-0"><i class="ti ti-stethoscope"></i> Doctors</h3>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search name or email">
                <button class="btn btn-outline-secondary"><i class="ti ti-search"></i></button>
            </form>
            <a href="{{ route('users.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Add User</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @include('users._tabs', ['tab' => 'doctors'])

    @forelse($departments as $dept)
        <h5 class="mt-4 mb-2">
            <i class="ti ti-building-hospital"></i> {{ ucfirst($dept->name) }}
            <span class="badge bg-secondary">{{ $dept->doctor_list->count() }}</span>
        </h5>

        @if($dept->doctor_list->isEmpty())
            <p class="text-muted small ms-4">No doctors in this department.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle bg-white mb-3">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Email</th><th>Room</th><th>Specialization</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                    @foreach($dept->doctor_list as $doc)
                        <tr>
                            <td><a href="{{ route('doctors.show', $doc) }}" class="fw-semibold text-decoration-none">{{ $doc->user->name }}</a></td>
                            <td>{{ $doc->user->email }}</td>
                            <td>{{ $doc->room_no ?? '—' }}</td>
                            <td>{{ $doc->specialization ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $doc->is_available ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $doc->is_available ? 'Available' : 'Unavailable' }}
                                </span>
                                @if($doc->background_check_status === 'flagged')
                                    <span class="badge bg-danger">Flagged</span>
                                @endif
                                @if($doc->license_expiry && \Carbon\Carbon::parse($doc->license_expiry)->isPast())
                                    <span class="badge bg-danger">License expired</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('doctors.edit', $doc) }}" class="btn btn-sm btn-outline-secondary" title="Edit doctor profile"><i class="ti ti-id-badge-2"></i></a>
                                <a href="{{ route('users.edit', $doc->user) }}" class="btn btn-sm btn-outline-secondary" title="Edit login account"><i class="ti ti-edit"></i></a>
                                <form action="{{ route('users.destroy', $doc->user) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete account"
                                            onclick="return confirm('Delete the account of {{ addslashes($doc->user->name) }}?')"><i class="ti ti-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @empty
        <div class="alert alert-light border">No doctors found{{ $q ? ' for "'.$q.'"' : '' }}.</div>
    @endforelse

    @if($noProfile->isNotEmpty())
        <h5 class="mt-5 mb-2 text-danger"><i class="ti ti-alert-triangle"></i> Doctor accounts without a profile
            <span class="badge bg-danger">{{ $noProfile->count() }}</span></h5>
        <p class="text-muted small">These users have the doctor role but no department or room yet, so patients can't see them.</p>
        <table class="table align-middle bg-white">
            <thead class="table-light"><tr><th>Name</th><th>Email</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach($noProfile as $u)
                <tr>
                    <td class="fw-semibold">{{ $u->name }}</td>
                    <td>{{ $u->email }}</td>
                    <td class="text-end">
                        <a href="{{ route('doctors.create') }}" class="btn btn-sm btn-brand">Create profile</a>
                        <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-edit"></i></a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection