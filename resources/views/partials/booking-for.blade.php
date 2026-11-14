@php $familyMembers = auth()->user()->dependents()->orderBy('name')->get(); @endphp

<div class="mb-3">
    <label class="form-label fw-semibold"><i class="ti ti-users"></i> {{ __('Booking for') }}</label>
    <div class="input-group">
        <select name="dependent_id" id="bookingFor" class="form-select @error('dependent_id') is-invalid @enderror">
            <option value="">{{ __('Myself') }} ({{ auth()->user()->name }})</option>
            @foreach($familyMembers as $m)
                <option value="{{ $m->id }}" @selected(old('dependent_id', request('dependent_id')) == $m->id)>
                    {{ $m->name }} ({{ __($m->relation) }})
                </option>
            @endforeach
        </select>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
            <i class="ti ti-plus"></i> {{ __('Add family member') }}
        </button>
    </div>
    @error('dependent_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

{{-- Pop-up. Inputs have NO name attribute, so they are never sent with the booking form,
     and the page is not reloaded, so the selected time slot stays selected. --}}
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-user-plus"></i> {{ __('Add family member') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="memberErrors"></div>
                <div class="row g-2">
                    <div class="col-12">
                        <label class="form-label">{{ __('Name') }} *</label>
                        <input type="text" class="form-control" data-field="name">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('Relation') }} *</label>
                        <select class="form-select" data-field="relation">
                            <option value="">{{ __('Select') }}</option>
                            @foreach(\App\Models\Dependent::RELATIONS as $r)
                                <option value="{{ $r }}">{{ __($r) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('Gender') }}</label>
                        <select class="form-select" data-field="gender">
                            <option value="">{{ __('Select') }}</option>
                            @foreach(\App\Models\Dependent::GENDERS as $g)
                                <option value="{{ $g }}">{{ __($g) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('Date of birth') }}</label>
                        <input type="date" class="form-control" data-field="date_of_birth" max="{{ today()->toDateString() }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('Blood group') }}</label>
                        <select class="form-select" data-field="blood_group">
                            <option value="">{{ __('Select') }}</option>
                            @foreach(\App\Models\Dependent::BLOOD_GROUPS as $b)
                                <option value="{{ $b }}">{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('Phone') }}</label>
                        <input type="text" class="form-control" data-field="phone" placeholder="01XXXXXXXXX">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-brand" id="saveMemberBtn">
                    <i class="ti ti-device-floppy"></i> {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('addMemberModal');
    const saveBtn = document.getElementById('saveMemberBtn');
    const errBox  = document.getElementById('memberErrors');
    const select  = document.getElementById('bookingFor');
    if (!modalEl || !saveBtn) return;

    // Bootstrap modals work best as a direct child of <body>
    document.body.appendChild(modalEl);

    saveBtn.addEventListener('click', async function () {
        const data = {};
        modalEl.querySelectorAll('[data-field]').forEach(el => {
            if (el.value.trim() !== '') data[el.dataset.field] = el.value.trim();
        });

        errBox.classList.add('d-none');
        saveBtn.disabled = true;

        try {
            const res = await fetch(@json(route('patient.family.store')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(data),
            });

            if (res.status === 201 || res.status === 200) {
                const m = await res.json();
                if (m.id) {
                    select.add(new Option(m.label, m.id, true, true));   // add + select
                    modalEl.querySelectorAll('[data-field]').forEach(el => el.value = '');
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                } else {
                    errBox.textContent = 'Saved, but the server did not return the new member. Update store() in PatientFamilyController.';
                    errBox.classList.remove('d-none');
                }
            } else if (res.status === 422) {
                const body = await res.json();
                errBox.innerHTML = Object.values(body.errors).flat().join('<br>');
                errBox.classList.remove('d-none');
            } else if (res.status === 419) {
                errBox.textContent = 'Your session expired. Please refresh the page and try again.';
                errBox.classList.remove('d-none');
            } else {
                errBox.textContent = 'Could not save (error ' + res.status + '). Check storage/logs/laravel.log.';
                errBox.classList.remove('d-none');
            }
        } catch (e) {
            errBox.textContent = 'Error: ' + e.message;
            errBox.classList.remove('d-none');
        } finally {
            saveBtn.disabled = false;
        }
    });
});
</script>