@extends('layouts.main')

@section('content')
<div class="page-card" style="max-width:720px;margin:auto">
    <a href="{{ route('patient.family.index') }}" class="btn btn-link px-0"><i class="ti ti-arrow-left"></i> {{ __('Back') }}</a>
    <h3>{{ $member->exists ? __('Edit family member') : __('Add family member') }}</h3>

    @include('partials.flash')

    <form method="POST" action="{{ $member->exists ? route('patient.family.update', $member) : route('patient.family.store') }}">
        @csrf
        @if($member->exists) @method('PUT') @endif
        @if(request('return_to'))<input type="hidden" name="return_to" value="{{ request('return_to') }}">@endif

        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label">{{ __('Name') }} *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $member->name) }}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">{{ __('Relation') }} *</label>
                <select name="relation" class="form-select" required>
                    <option value="">{{ __('Select') }}</option>
                    @foreach(\App\Models\Dependent::RELATIONS as $r)
                        <option value="{{ $r }}" @selected(old('relation', $member->relation) === $r)>{{ __($r) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('Gender') }}</label>
                <select name="gender" class="form-select">
                    <option value="">{{ __('Select') }}</option>
                    @foreach(\App\Models\Dependent::GENDERS as $g)
                        <option value="{{ $g }}" @selected(old('gender', $member->gender) === $g)>{{ __($g) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('Date of birth') }}</label>
                <input type="date" name="date_of_birth" class="form-control" max="{{ today()->toDateString() }}"
                       value="{{ old('date_of_birth', optional($member->date_of_birth)->toDateString()) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('Blood group') }}</label>
                <select name="blood_group" class="form-select">
                    <option value="">{{ __('Select') }}</option>
                    @foreach(\App\Models\Dependent::BLOOD_GROUPS as $b)
                        <option value="{{ $b }}" @selected(old('blood_group', $member->blood_group) === $b)>{{ $b }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('Phone') }}</label>
                <input type="text" name="phone" class="form-control" placeholder="01XXXXXXXXX" value="{{ old('phone', $member->phone) }}">
            </div>
        </div>

        <button class="btn btn-brand mt-3"><i class="ti ti-device-floppy"></i> {{ __('Save') }}</button>
    </form>
</div>
@endsection