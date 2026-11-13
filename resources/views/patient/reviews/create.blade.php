@extends('layouts.main')

@section('content')
<div class="page-card" style="max-width:640px;margin:auto">
    <a href="{{ route('patient.records') }}" class="btn btn-link px-0"><i class="ti ti-arrow-left"></i> {{ __('Back') }}</a>
    <h3><i class="ti ti-star"></i> {{ __('Rate your visit') }}</h3>
    <p class="text-muted">
        {{ __('Visit on :date with :doctor', [
            'date'   => \Carbon\Carbon::parse($entry->completed_at ?? $entry->checked_in_at)->format('d M Y'),
            'doctor' => $entry->doctor?->displayName(),
        ]) }}
    </p>

    @include('partials.flash')

    <form method="POST" action="{{ route('patient.reviews.store', $entry) }}">
        @csrf
        <label class="form-label fw-semibold">{{ __('Your rating') }}</label>
        <div class="star-input d-flex flex-row-reverse justify-content-end gap-1 mb-3">
            @for($s = 5; $s >= 1; $s--)
                <input type="radio" class="btn-check" name="rating" id="star{{ $s }}" value="{{ $s }}" @checked(old('rating') == $s) required>
                <label for="star{{ $s }}" class="fs-1" style="cursor:pointer"><i class="ti ti-star-filled"></i></label>
            @endfor
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('Comment (optional)') }}</label>
            <textarea name="comment" rows="3" class="form-control" maxlength="1000" placeholder="{{ __('What went well? What can we improve?') }}">{{ old('comment') }}</textarea>
        </div>

        <button class="btn btn-brand"><i class="ti ti-send"></i> {{ __('Submit review') }}</button>
    </form>
</div>

<style>
    .star-input label { color: #d0d5d4; transition: color .1s; }
    .star-input input:checked ~ label,
    .star-input label:hover,
    .star-input label:hover ~ label { color: #f5b301; }
</style>
@endsection