@extends('layouts.main')

@section('content')
<div class="page-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="mb-0"><i class="ti ti-star"></i> Doctor reviews</h3>
        <form method="GET" class="d-flex gap-2">
            <select name="rating" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All ratings</option>
                @for($s = 5; $s >= 1; $s--)<option value="{{ $s }}" @selected($rating == $s)>{{ $s }} star</option>@endfor
            </select>
        </form>
    </div>

    @include('partials.flash')

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light"><tr><th>Date</th><th>Doctor</th><th>Patient</th><th>Rating</th><th>Comment</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($reviews as $r)
                    <tr>
                        <td class="small">{{ $r->created_at->format('d M Y') }}</td>
                        <td>{{ $r->doctor?->displayName() }}</td>
                        <td>{{ $r->patient->name ?? '—' }}</td>
                        <td class="text-warning text-nowrap">@for($i = 1; $i <= 5; $i++)<i class="ti {{ $i <= $r->rating ? 'ti-star-filled' : 'ti-star' }}"></i>@endfor</td>
                        <td class="small" style="max-width:340px">{{ $r->comment ?? '—' }}</td>
                        <td><span class="badge bg-{{ $r->is_visible ? 'success' : 'secondary' }}">{{ $r->is_visible ? 'Visible' : 'Hidden' }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('admin.reviews.toggle', $r) }}">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-{{ $r->is_visible ? 'secondary' : 'success' }}">{{ $r->is_visible ? 'Hide' : 'Show' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No reviews yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $reviews->links() }}
</div>
@endsection