@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="mb-0"><i class="ti ti-file-text"></i> My test reports</h3>
        <a href="{{ route('patient.tests') }}" class="btn btn-brand"><i class="ti ti-plus"></i> New test request</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @if($requests->isEmpty())
        <div class="alert alert-light border">You have not requested any tests yet.</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle bg-white">
                <thead class="table-light">
                    <tr><th>Report</th><th>Sent</th><th>Tests</th><th>Total</th><th>Status</th><th>Date &amp; time</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($requests as $r)
                    @php [$label, $cls] = $r->statusBadge(); @endphp
                    <tr class="{{ $r->status === 'suggested' ? 'table-info' : '' }}">
                        <td class="fw-bold">{{ $r->code }}</td>
                        <td>{{ $r->created_at->format('d M Y') }}</td>
                        <td>{{ $r->items_count }}</td>
                        <td>৳ {{ number_format($r->total_amount) }}</td>
                        <td><span class="badge {{ $cls }}">{{ $r->status === 'suggested' ? 'Please respond' : $label }}</span></td>
                        <td class="small">
                            @if($r->status === 'scheduled' || $r->status === 'completed')
                                {{ $r->scheduled_at->format('D, d M · h:i A') }} · Room {{ $r->room_no }}
                            @elseif($r->status === 'suggested')
                                Suggested: {{ $r->suggested_at->format('D, d M · h:i A') }}
                            @else
                                Preferred: {{ $r->preferred_at->format('D, d M · h:i A') }}
                            @endif
                        </td>
                        <td class="text-end"><a href="{{ route('patient.tests.requests.show', $r) }}" class="btn btn-sm btn-outline-success">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection