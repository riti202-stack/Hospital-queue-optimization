@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Doctors</h2>
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    <a href="{{ route('doctors.create') }}" class="btn btn-primary mb-3">Add Doctor</a>
    <table class="table table-bordered">
        <thead><tr><th>#</th><th>Name</th><th>Department</th><th>Available</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($doctors as $doc)
            <tr>
                <td>{{ $doc->id }}</td>
                <td>{{ $doc->user->name }}</td>
                <td>{{ $doc->department->name }}</td>
                <td>{{ $doc->is_available ? 'Yes' : 'No' }}</td>
                <td>
                    <a href="{{ route('doctors.edit', $doc) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('doctors.destroy', $doc) }}" method="POST" style="display:inline">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $doctors->links() }}
</div>
@endsection