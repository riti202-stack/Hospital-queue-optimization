{{-- Needs: $rows (name, room, prep, price), $total, $meta (label => value), $code (or null), $date --}}
<div class="bg-white border rounded-4 p-4">
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
        <div>
            <div class="fs-4 fw-bold" style="color:#0f6e56;"><i class="ti ti-building-hospital"></i> Hospital Queue System</div>
            <div class="text-muted small">Laboratory test report</div>
        </div>
        <div class="text-end small">
            @if($code)<div class="fw-bold fs-5">{{ $code }}</div>@endif
            <div>{{ $date }}</div>
        </div>
    </div>

    <div class="row small mb-3">
        @foreach($meta as $label => $value)
            <div class="col-sm-6 mb-1"><span class="text-muted">{{ $label }}:</span> <strong>{{ $value }}</strong></div>
        @endforeach
    </div>

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Test</th><th>Room</th><th>Preparation</th><th class="text-end">Price</th></tr>
            </thead>
            <tbody>
            @foreach($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="fw-semibold">{{ $row['name'] }}</td>
                    <td>{{ $row['room'] ?: '—' }}</td>
                    <td class="small">{{ $row['prep'] ?: '—' }}</td>
                    <td class="text-end">৳ {{ number_format($row['price']) }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr><th colspan="4" class="text-end">Total</th><th class="text-end fs-5" style="color:#0f6e56;">৳ {{ number_format($total) }}</th></tr>
            </tfoot>
        </table>
    </div>
</div>