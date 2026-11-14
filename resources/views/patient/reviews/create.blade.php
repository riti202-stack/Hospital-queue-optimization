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
        <label class="form-label fw-semibold d-block">{{ __('Your rating') }}</label>
        <div class="star-input mb-1">
            @for($s = 5; $s >= 1; $s--)
                <input type="radio" name="rating" id="star{{ $s }}" value="{{ $s }}" @checked(old('rating') == $s) required>
                <label for="star{{ $s }}" title="{{ $s }} / 5">★</label>
            @endfor
        </div>
        <small class="text-muted d-block mb-3" id="ratingText">{{ __('Tap a star') }}</small>

        <div class="mb-3">
            <label class="form-label">{{ __('Comment (optional)') }}</label>
            <textarea name="comment" rows="3" class="form-control" maxlength="1000" placeholder="{{ __('What went well? What can we improve?') }}">{{ old('comment') }}</textarea>
        </div>

        <button class="btn btn-brand"><i class="ti ti-send"></i> {{ __('Submit review') }}</button>
    </form>
</div>

<style>
    .star-input { display: inline-flex; flex-direction: row-reverse; gap: 6px; }
    .star-input input { position: absolute; opacity: 0; width: 0; height: 0; }
    .star-input label { font-size: 44px; line-height: 1; color: #cfd6d4; cursor: pointer; transition: color .1s, transform .1s; }
    .star-input label:hover { transform: scale(1.12); }
    .star-input input:checked ~ label,
    .star-input label:hover,
    .star-input label:hover ~ label { color: #f5b301; }
</style>

<script>
document.querySelectorAll('.star-input input').forEach(r => r.addEventListener('change', () => {
    const t = ['', 'Poor', 'Fair', 'Good', 'Very good', 'Excellent'];
    document.getElementById('ratingText').textContent = r.value + ' / 5 · ' + t[r.value];
}));
</script>
@endsection