@extends('layouts.main')

@section('content')
<div class="page-card">
    <h3 class="mb-3"><i class="ti ti-star"></i> My ratings</h3>

    <div class="row g-4 mb-4">
        <div class="col-md-4 text-center">
            <div class="display-4 fw-bold">{{ $count ? number_format($avg, 1) : '—' }}</div>
            <div class="text-warning fs-4">
                @for($i = 1; $i <= 5; $i++)
                    <i class="ti {{ $avg >= $i ? 'ti-star-filled' : ($avg >= $i - 0.5 ? 'ti-star-half-filled' : 'ti-star') }}"></i>
                @endfor
            </div>
            <small class="text-muted">{{ $count }} review(s)</small>
        </div>
        <div class="col-md-8">
            @for($s = 5; $s >= 1; $s--)
                @php $c = $dist[$s] ?? 0; $pct = $count ? round($c * 100 / $count) : 0; @endphp
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span style="width:40px">{{ $s }} <i class="ti ti-star-filled text-warning"></i></span>
                    <div class="progress flex-grow-1" style="height:10px"><div class="progress-bar bg-warning" style="width:{{ $pct }}%"></div></div>
                    <span class="small text-muted" style="width:40px">{{ $c }}</span>
                </div>
            @endfor
        </div>
    </div>

    @forelse($reviews as $r)
        <div class="border-bottom py-2">
            <div class="d-flex justify-content-between">
                <span class="text-warning">@for($i = 1; $i <= 5; $i++)<i class="ti {{ $i <= $r->rating ? 'ti-star-filled' : 'ti-star' }}"></i>@endfor</span>
                <small class="text-muted">{{ $r->created_at->format('d M Y') }} · {{ \Illuminate\Support\Str::of($r->patient->name ?? 'Patient')->before(' ') }}
                    @unless($r->is_visible)<span class="badge bg-secondary">Hidden by admin</span>@endunless
                </small>
            </div>
            @if($r->comment)<p class="mb-0 mt-1">{{ $r->comment }}</p>@endif
        </div>
    @empty
        <p class="text-muted">No reviews yet.</p>
    @endforelse
    <div class="mt-3">{{ $reviews->links() }}</div>
</div>
@endsection