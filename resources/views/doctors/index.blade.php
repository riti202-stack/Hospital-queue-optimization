@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="ti ti-stethoscope"></i> Doctors</h3>
        <a href="{{ route('doctors.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Add Doctor</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <table class="table align-middle">
        <thead><tr><th>#</th><th>Name</th><th>Department</th><th>Room</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @foreach($doctors as $doc)
            <tr>
                <td>{{ $doc->id }}</td>
                <td class="fw-semibold">{{ $doc->user->name }}</td>
                <td>{{ $doc->department->name }}</td>
                <td>{{$doc->room_no ?? '-' }}</td>
                <td><span class="badge {{ $doc->is_available ? 'bg-success' : 'bg-secondary' }}">{{ $doc->is_available ? 'Available' : 'Unavailable' }}</span></td>
                <td class="text-end">
                    <a href="{{ route('doctors.edit', $doc) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-edit"></i></a>
                    <form action="{{ route('doctors.destroy', $doc) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="ti ti-trash"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $doctors->links() }}
</div>
@endsection