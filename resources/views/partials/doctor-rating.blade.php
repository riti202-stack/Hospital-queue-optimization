@php
    $ratingAvg   = $doctor->visibleReviews()->avg('rating');
    $ratingCount = $doctor->visibleReviews()->count();
@endphp
@if($ratingCount)
    <span class="text-warning">
        @for($i = 1; $i <= 5; $i++)
            <i class="ti {{ $ratingAvg >= $i ? 'ti-star-filled' : ($ratingAvg >= $i - 0.5 ? 'ti-star-half-filled' : 'ti-star') }}"></i>
        @endfor
    </span>
    <small class="text-muted">{{ number_format($ratingAvg, 1) }} · {{ $ratingCount }} {{ __('reviews') }}</small>
@else
    <small class="text-muted">{{ __('No ratings yet') }}</small>
@endif