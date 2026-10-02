@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:600px;">
    <h3 class="mb-4"><i class="ti ti-edit"></i> Edit Department</h3>
    <form action="{{ route('departments.update', $department) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $department->name) }}">
            @error('name')<small class="text-danger">{{ $message }}</small>@enderror
        </div>
        <button class="btn btn-brand"><i class="ti ti-check"></i> Update</button>
    </form>
</div>
@endsection