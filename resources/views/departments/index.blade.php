@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="ti ti-building-hospital"></i> Departments</h3>
        <a href="{{ route('departments.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Add Department</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <table class="table align-middle">
        <thead><tr><th>#</th><th>Name</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @foreach($departments as $dept)
            <tr>
                <td>{{ $dept->id }}</td>
                <td class="fw-semibold">{{ $dept->name }}</td>
                <td class="text-end">
                    <a href="{{ route('departments.edit', $dept) }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-edit"></i></a>
                    <form action="{{ route('departments.destroy', $dept) }}" method="POST" class="d-inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this department?')"><i class="ti ti-trash"></i></button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $departments->links() }}
</div>
@endsection