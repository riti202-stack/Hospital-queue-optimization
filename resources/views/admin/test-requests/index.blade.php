@extends('layouts.main')
@section('content')
<style>
    .tr-tabs .nav-link { color:#0f6e56; font-weight:600; border-radius:12px; }
    .tr-tabs .nav-link.active { background:#0f6e56; color:#fff; }
</style>
<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="ti ti-test-pipe"></i> Test requests</h3>
        <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-secondary"><i class="ti ti-list"></i> Lab test catalog</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <ul class="nav nav-pills tr-tabs gap-2 mb-4">
        @foreach($tabs as $key => [$title])
            <li class="nav-item">
                <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.test-requests.index', ['tab' => $key]) }}">
                    {{ $title }} <span class="badge bg-light text-dark ms-1">{{ $counts[$key] }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    @if($requests->isEmpty())
        <div class="alert alert-light border">Nothing here.</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead class="table-light">
                    <tr><th>Report</th><th>Patient</th><th>Tests</th><th>Total</th><th>Requested time</th><th>Booked / suggested</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($requests as $r)
                    @php [$label, $cls] = $r->statusBadge(); @endphp
                    <tr>
                        <td class="fw-bold">{{ $r->code }}</td>
                        <td>{{ $r->patient->name ?? '—' }}</td>
                        <td>{{ $r->items_count }}</td>
                        <td>৳ {{ number_format($r->total_amount) }}</td>
                        <td class="small">{{ $r->preferred_at->format('D, d M · h:i A') }}</td>
                        <td class="small">
                            @if($r->scheduled_at && in_array($r->status, ['scheduled', 'completed']))
                                {{ $r->scheduled_at->format('D, d M · h:i A') }} · {{ $r->room_no }}
                            @elseif($r->suggested_at)
                                Suggested {{ $r->suggested_at->format('D, d M · h:i A') }}
                            @else — @endif
                        </td>
                        <td><span class="badge {{ $cls }}">{{ $label }}</span></td>
                        <td class="text-end"><a href="{{ route('admin.test-requests.show', $r) }}" class="btn btn-sm btn-brand">Open</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    @endif
</div>
@endsection