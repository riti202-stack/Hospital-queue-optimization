@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:600px;">
    <h3 class="mb-4"><i class="ti ti-edit"></i> Edit Doctor</h3>
    <form action="{{ route('doctors.update', $doctor) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">User</label>
            <select name="user_id" class="form-select">
                @foreach($users as $user)<option value="{{ $user->id }}" {{ $doctor->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                @foreach($departments as $dept)<option value="{{ $dept->id }}" {{ $doctor->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class ="form-label">Room Number</label>
            <input type ="text" name="room_no" class="form-control" value="{{old('room_no',$doctor->room_no?? '')}}"placeholder="e.g. 3B-245">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_available" class="form-check-input" {{ $doctor->is_available ? 'checked' : '' }}>
            <label class="form-check-label">Available</label>
        </div>
        <button class="btn btn-brand"><i class="ti ti-check"></i> Update</button>
    </form>
</div>
@endsection