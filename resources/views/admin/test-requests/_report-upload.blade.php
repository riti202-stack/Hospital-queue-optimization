<div class="card shadow-sm mt-3">
    <div class="card-header fw-semibold"><i class="ti ti-file-upload"></i> Lab report</div>
    <div class="card-body">
        @if($tr->report_path)
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <span class="text-success small">
                    <i class="ti ti-circle-check"></i> Uploaded {{ \Carbon\Carbon::parse($tr->report_uploaded_at)->format('d M Y, g:i A') }}
                </span>
                <div class="d-flex gap-2">
                    <a href="{{ route('lab-reports.download', $tr) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-download"></i> Download</a>
                    <form method="POST" action="{{ route('admin.test-requests.report.destroy', $tr) }}" onsubmit="return confirm('Remove this report?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="ti ti-trash"></i></button>
                    </form>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.test-requests.report.upload', $tr) }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="report" accept=".pdf,.jpg,.jpeg,.png" class="form-control mb-2" required>
            <textarea name="result_note" rows="2" class="form-control mb-2" placeholder="Short note for the patient (optional)">{{ old('result_note', $tr->result_note) }}</textarea>
            <button class="btn btn-success w-100">
                <i class="ti ti-upload"></i> {{ $tr->report_path ? 'Replace report & notify' : 'Upload & notify patient' }}
            </button>
            <small class="text-muted d-block mt-1">PDF, JPG or PNG, max 5 MB.</small>
        </form>
    </div>
</div>