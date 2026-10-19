@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="ti ti-shield-lock"></i> Admins</h3>
        <a href="{{ route('users.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Add User</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @include('users._tabs', ['tab' => 'admins'])

    <table class="table align-middle bg-white">
        <thead class="table-light"><tr><th>#</th><th>Name</th><th>Email</th><th>Since</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse($admins as $a)
            <tr>
                <td>{{ $a->id }}</td>
                <td class="fw-semibold">{{ $a->name }} @if($a->id === auth()->id())<span class="badge bg-light text-dark border">You</span>@endif</td>
                <td>{{ $a->email }}</td>
                <td>{{ $a->created_at?->format('d M Y') ?? '—' }}</td>
                <td class="text-end">
                    <a href="{{ route('users.edit', $a) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-edit"></i></a>
                    @if($a->id !== auth()->id())
                        <form action="{{ route('users.destroy', $a) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete admin {{ addslashes($a->name) }}?')"><i class="ti ti-trash"></i></button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No admins.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection