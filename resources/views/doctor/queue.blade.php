@extends('layouts.main')
@section('content')
<div class="container">
    <h2>My Queue — {{ $doctor->department->name ?? '' }}</h2>
    <table class="table table-bordered">
        <thead><tr><th>Patient</th><th>Urgency</th><th>Priority score</th><th>Waiting since</th></tr></thead>
        <tbody>
        @forelse($entries as $entry)
            <tr>
                <td>{{ $entry->patient->name }}</td>
                <td>{{ ucfirst($entry->urgency_level) }}</td>
                <td>{{ number_format($entry->priority_score, 1) }}</td>
                <td>{{ \Carbon\Carbon::parse($entry->checked_in_at)->diffForHumans() }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center">No patients waiting.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection