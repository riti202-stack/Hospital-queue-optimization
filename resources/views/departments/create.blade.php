@extends('layouts.main')
@section('content')
<div class="container">
    <h2>Add Department</h2>
    <form action="{{ route('departments.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}">
            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
        </div>
        <button class="btn btn-primary">Save</button>
    </form>
</div>
@endsection