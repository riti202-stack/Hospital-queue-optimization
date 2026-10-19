@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h3 class="mb-0"><i class="ti ti-user-heart"></i> Patients</h3>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search name or email">
                <button class="btn btn-outline-secondary"><i class="ti ti-search"></i></button>
            </form>
            <a href="{{ route('users.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Add User</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @include('users._tabs', ['tab' => 'patients'])

    @if($patients->isEmpty())
        <div class="alert alert-light border">No patients found{{ $q ? ' for "'.$q.'"' : '' }}.</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead class="table-light">
                    <tr><th>#</th><th>Name</th><th>Email</th><th>Appointments</th><th>Latest appointment</th><th>Today</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @foreach($patients as $p)
                    @php $v = $visits->get($p->id); $qe = $inQueue->get($p->id); @endphp
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td class="fw-semibold">{{ $p->name }}</td>
                        <td>{{ $p->email }}</td>
                        <td>{{ $v->total ?? 0 }}</td>
                        <td>{{ $v?->latest ? \Carbon\Carbon::parse($v->latest)->format('d M Y, h:i A') : '—' }}</td>
                        <td>
                            @if($qe)
                                <span class="badge {{ $qe->status === 'in_progress' ? 'bg-primary' : 'bg-info text-dark' }}">
                                    #{{ $qe->token_no }} · {{ $qe->status === 'in_progress' ? 'With doctor' : 'Waiting' }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('users.edit', $p) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-edit"></i></a>
                            <form action="{{ route('users.destroy', $p) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete {{ addslashes($p->name) }}?')"><i class="ti ti-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $patients->links() }}
    @endif
</div>
@endsection