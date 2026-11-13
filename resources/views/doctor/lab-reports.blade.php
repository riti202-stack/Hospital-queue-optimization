@extends('layouts.main')

@section('content')
<div class="page-card">
    <h3 class="mb-3"><i class="ti ti-file-certificate"></i> Lab reports of my patients</h3>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light"><tr><th>Request</th><th>Patient</th><th>Tests</th><th>Uploaded</th><th>Lab note</th><th></th></tr></thead>
            <tbody>
                @forelse($reports as $r)
                    <tr>
                        <td class="fw-semibold">LT-{{ str_pad($r->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ \App\Models\User::find($r->patient_id)?->name }}</td>
                        <td class="small">{{ $r->items->pluck('test_name')->filter()->join(', ') ?: $r->items->count().' test(s)' }}</td>
                        <td class="small">{{ \Carbon\Carbon::parse($r->report_uploaded_at)->format('d M Y, g:i A') }}</td>
                        <td class="small">{{ $r->result_note ?? '—' }}</td>
                        <td><a href="{{ route('lab-reports.download', $r) }}" class="btn btn-sm btn-primary"><i class="ti ti-download"></i> Download</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No reports uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $reports->links() }}
</div>
@endsection