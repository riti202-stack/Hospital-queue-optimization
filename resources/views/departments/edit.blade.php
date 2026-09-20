@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Edit Department</h2>
    <form action="{{ route('departments.update', $department) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $department->name) }}">
            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection