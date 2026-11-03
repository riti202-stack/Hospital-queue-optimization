@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="ti ti-list"></i> Lab test catalog</h3>
        <a href="{{ route('lab-tests.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Add test</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @forelse($tests as $category => $list)
        <h5 class="mt-4 mb-2">{{ $category }} <span class="badge bg-secondary">{{ $list->count() }}</span></h5>
        <table class="table align-middle bg-white">
            <thead class="table-light"><tr><th>Test</th><th>Price</th><th>Room</th><th>Preparation</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach($list as $t)
                <tr class="{{ $t->is_active ? '' : 'text-muted' }}">
                    <td class="fw-semibold">{{ $t->name }}</td>
                    <td>৳ {{ number_format($t->price) }}</td>
                    <td>{{ $t->room_no ?? '—' }}</td>
                    <td class="small">{{ $t->preparation ?? '—' }}</td>
                    <td><span class="badge {{ $t->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $t->is_active ? 'Active' : 'Hidden' }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('lab-tests.edit', $t) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-edit"></i></a>
                        <form action="{{ route('lab-tests.destroy', $t) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete {{ addslashes($t->name) }}?')"><i class="ti ti-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @empty
        <div class="alert alert-light border">No tests yet. Run the LabTestSeeder or add one.</div>
    @endforelse
</div>
@endsection