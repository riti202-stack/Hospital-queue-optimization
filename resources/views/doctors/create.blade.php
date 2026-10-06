@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:800px;">
    <h3 class="mb-4"><i class="ti ti-plus"></i> Add Doctor — Full Credentialing Profile</h3>
    <form action="{{ route('doctors.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <h6 class="text-muted mt-2 mb-3">Account & Assignment</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">User</label>
                <select name="user_id" class="form-select" required>
                    @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select" required>
                    @foreach($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Room number</label>
                <input type="text" name="room_no" class="form-control" placeholder="e.g. 3B-245">
            </div>
            <div class="col-md-6">
                <label class="form-label">Photo</label>
                <input type="file" name="photo" class="form-control" accept="image/*">
            </div>
        </div>

        <h6 class="text-muted mt-4 mb-3">Personal & Contact</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Date of birth</label>
                <input type="date" name="date_of_birth" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="">Select</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Personal phone</label>
                <input type="text" name="personal_phone" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label">Residential address</label>
                <input type="text" name="residential_address" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Emergency contact name</label>
                <input type="text" name="emergency_contact_name" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Emergency contact phone</label>
                <input type="text" name="emergency_contact_phone" class="form-control">
            </div>
        </div>

        <h6 class="text-muted mt-4 mb-3">Licensure & Specialization</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">License number</label>
                <input type="text" name="license_number" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Issuing body</label>
                <input type="text" name="license_issuing_body" class="form-control" placeholder="e.g. BMDC">
            </div>
            <div class="col-md-4">
                <label class="form-label">License expiry</label>
                <input type="date" name="license_expiry" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Specialization</label>
                <input type="text" name="specialization" class="form-control" placeholder="e.g. Cardiology">
            </div>
            <div class="col-md-6">
                <label class="form-label">Sub-specialty</label>
                <input type="text" name="sub_specialty" class="form-control">
            </div>
        </div>

        <h6 class="text-muted mt-4 mb-3">Qualifications</h6>
        <div id="qual-rows">
            <div class="row g-2 mb-2 qual-row">
                <div class="col-4"><input type="text" name="degree[]" class="form-control form-control-sm" placeholder="Degree (e.g. MBBS)"></div>
                <div class="col-5"><input type="text" name="institution[]" class="form-control form-control-sm" placeholder="Institution"></div>
                <div class="col-3"><input type="number" name="passing_year[]" class="form-control form-control-sm" placeholder="Year"></div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="addQualRow()">+ Add another degree</button>

        <h6 class="text-muted mt-4 mb-3">Employment & Operational</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="form-label">Employment type</label>
                <select name="employment_type" class="form-select">
                    <option value="full_time">Full-time</option>
                    <option value="part_time">Part-time</option>
                    <option value="visiting">Visiting consultant</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Date of joining</label>
                <input type="date" name="date_of_joining" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Fee share (%)</label>
                <input type="number" step="0.01" name="fee_share_percent" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label">Working hours / OPD days</label>
                <input type="text" name="working_hours" class="form-control" placeholder="e.g. Sun-Thu 9am-5pm, OPD Tue & Thu">
            </div>
        </div>

        <h6 class="text-muted mt-4 mb-3">Legal & Compliance</h6>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="form-label">Malpractice insurance policy no.</label>
                <input type="text" name="malpractice_insurance_policy_no" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Background check status</label>
                <select name="background_check_status" class="form-select">
                    <option value="pending">Pending</option>
                    <option value="cleared">Cleared</option>
                    <option value="flagged">Flagged</option>
                </select>
            </div>
        </div>

        <div class="form-check mb-4">
            <input type="checkbox" name="is_available" class="form-check-input" checked>
            <label class="form-check-label">Available</label>
        </div>

        <button class="btn btn-brand"><i class="ti ti-check"></i> Save Doctor Profile</button>
    </form>
</div>
<script>
function addQualRow() {
    const row = document.querySelector('.qual-row').cloneNode(true);
    row.querySelectorAll('input').forEach(i => i.value = '');
    document.getElementById('qual-rows').appendChild(row);
}
</script>
@endsection