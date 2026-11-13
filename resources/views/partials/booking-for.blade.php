@php $familyMembers = auth()->user()->dependents()->orderBy('name')->get(); @endphp
<div class="mb-3">
    <label class="form-label fw-semibold"><i class="ti ti-users"></i> {{ __('Booking for') }}</label>
    <select name="dependent_id" class="form-select @error('dependent_id') is-invalid @enderror">
        <option value="">{{ __('Myself') }} ({{ auth()->user()->name }})</option>
        @foreach($familyMembers as $m)
            <option value="{{ $m->id }}" @selected(old('dependent_id', request('dependent_id')) == $m->id)>
                {{ $m->name }} ({{ __($m->relation) }})
            </option>
        @endforeach
    </select>
    @error('dependent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <a href="{{ route('patient.family.create', ['return_to' => url()->full()]) }}" class="small">
        <i class="ti ti-plus"></i> {{ __('Add family member') }}
    </a>
</div>