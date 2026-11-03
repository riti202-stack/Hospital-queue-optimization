@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:760px;">
    <a href="{{ route('lab-tests.index') }}" class="small text-decoration-none"><i class="ti ti-arrow-left"></i> Lab test catalog</a>
    <h3 class="mt-2 mb-4">{{ $test->exists ? 'Edit test' : 'Add test' }}</h3>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ $test->exists ? route('lab-tests.update', $test) : route('lab-tests.store') }}">
        @csrf
        @if($test->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">Test name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $test->name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Price (৳)</label>
                <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $test->price) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control" list="cats" value="{{ old('category', $test->category) }}" required>
                <datalist id="cats">
                    @foreach(\App\Models\LabTest::distinct()->orderBy('category')->pluck('category') as $c)<option value="{{ $c }}">@endforeach
                </datalist>
            </div>
            <div class="col-md-3">
                <label class="form-label">Room</label>
                <input type="text" name="room_no" class="form-control" value="{{ old('room_no', $test->room_no) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Duration (min)</label>
                <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $test->duration_minutes) }}">
            </div>
            <div class="col-12">
                <label class="form-label">Preparation (shown to patients)</label>
                <input type="text" name="preparation" class="form-control" value="{{ old('preparation', $test->preparation) }}" placeholder="e.g. Fast for 8–10 hours">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea name="description" rows="2" class="form-control">{{ old('description', $test->description) }}</textarea>
            </div>
            <div class="col-12 form-check ms-2">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="active" {{ old('is_active', $test->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="active">Available to patients</label>
            </div>
        </div>
        <button class="btn btn-brand mt-4"><i class="ti ti-check"></i> Save</button>
    </form>
</div>
@endsection