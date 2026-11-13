@if($tr->report_path)
    <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
        <div>
            <strong><i class="ti ti-file-certificate"></i> {{ __('Your lab report is ready.') }}</strong>
            @if($tr->result_note)<br><small>{{ __('Note from the lab') }}: {{ $tr->result_note }}</small>@endif
        </div>
        <a href="{{ route('lab-reports.download', $tr) }}" class="btn btn-success btn-sm"><i class="ti ti-download"></i> {{ __('Download report') }}</a>
    </div>
@endif