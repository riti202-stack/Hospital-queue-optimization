@extends('layouts.main')

@section('content')
<div class="page-card">
    <h3 class="mb-0"><i class="ti ti-notes-medical"></i> {{ __('My Medical Records') }}</h3>
    <p class="text-muted">{{ __('Visit history and prescriptions for you and your family.') }}</p>

    @include('partials.flash')

    <ul class="nav nav-pills mb-3 flex-wrap">
        <li class="nav-item"><a class="nav-link @if($member === 'all') active @endif" href="{{ route('patient.records') }}">{{ __('All') }}</a></li>
        <li class="nav-item"><a class="nav-link @if($member === 'self') active @endif" href="{{ route('patient.records', ['member' => 'self']) }}">{{ __('Myself') }}</a></li>
        @foreach($members as $m)
            <li class="nav-item"><a class="nav-link @if($member === (string) $m->id) active @endif" href="{{ route('patient.records', ['member' => $m->id]) }}">{{ $m->name }}</a></li>
        @endforeach
    </ul>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr><th>{{ __('Date') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Doctor') }}</th><th>{{ __('Department') }}</th><th>{{ __('Prescription') }}</th><th>{{ __('Rating') }}</th></tr>
            </thead>
            <tbody>
                @forelse($visits as $v)
                    @php $done = \Carbon\Carbon::parse($v->completed_at ?? $v->checked_in_at); @endphp
                    <tr>
                        <td>{{ $done->format('d M Y') }}</td>
                        <td>{{ $v->dependent ? $v->dependent->name.' ('.__($v->dependent->relation).')' : __('Myself') }}</td>
                        <td>{{ $v->doctor?->displayName() }}</td>
                        <td>{{ $v->doctor?->department?->name ?? '—' }}</td>
                        <td>
                            @if($v->prescription)
                                <a href="{{ route('patient.records.prescription', $v->prescription) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-prescription"></i> {{ __('View') }}
                                </a>
                            @else
                                <small class="text-muted">{{ __('Not written yet') }}</small>
                            @endif
                        </td>
                        <td>
                            @if($v->review)
                                <span class="text-warning" title="{{ __('Rated') }}">
                                    @for($i = 1; $i <= 5; $i++)<i class="ti {{ $i <= $v->review->rating ? 'ti-star-filled' : 'ti-star' }}"></i>@endfor
                                </span>
                            @elseif($done->gte(now()->subDays(30)))
                                <a href="{{ route('patient.reviews.create', $v) }}" class="btn btn-sm btn-outline-warning"><i class="ti ti-star"></i> {{ __('Rate visit') }}</a>
                            @else
                                <small class="text-muted">—</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No visits yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $visits->links() }}
</div>
@endsection