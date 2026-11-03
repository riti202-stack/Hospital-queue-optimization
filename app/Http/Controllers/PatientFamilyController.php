<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Dependent;
use App\Models\QueueEntry;
use App\Support\AppointmentRules;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientFamilyController extends Controller
{
    public function index(Request $request)
    {
        $members = $request->user()->dependents()->orderBy('name')->get();
        return view('patient.family.index', compact('members'));
    }

    public function create()
    {
        return view('patient.family.form', ['member' => new Dependent()]);
    }

    public function store(Request $request)
    {
        $request->user()->dependents()->create($this->validated($request));

        // came from the booking form? go back there
        if ($request->filled('return_to')) {
            return redirect($request->input('return_to'))->with('success', __('Family member added.'));
        }
        return redirect()->route('patient.family.index')->with('success', __('Family member added.'));
    }

    public function edit(Dependent $dependent)
    {
        $this->own($dependent);
        return view('patient.family.form', ['member' => $dependent]);
    }

    public function update(Request $request, Dependent $dependent)
    {
        $this->own($dependent);
        $dependent->update($this->validated($request));

        return redirect()->route('patient.family.index')->with('success', __('Family member updated.'));
    }

    public function destroy(Dependent $dependent)
    {
        $this->own($dependent);

        $busy = Appointment::where('dependent_id', $dependent->id)->whereNotIn('status', AppointmentRules::CLOSED)->exists()
             || QueueEntry::where('dependent_id', $dependent->id)->whereIn('status', ['waiting', 'in_progress'])->exists();

        if ($busy) {
            return back()->with('error', __('This member has an active appointment or is in the queue.'));
        }

        $dependent->delete();
        return back()->with('success', __('Family member removed.'));
    }

    private function own(Dependent $d): void
    {
        abort_unless((int) $d->user_id === auth()->id(), 403);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'          => 'required|string|max:100',
            'relation'      => ['required', Rule::in(Dependent::RELATIONS)],
            'gender'        => ['nullable', Rule::in(Dependent::GENDERS)],
            'date_of_birth' => 'nullable|date|before_or_equal:today',
            'blood_group'   => ['nullable', Rule::in(Dependent::BLOOD_GROUPS)],
            'phone'         => ['nullable', 'regex:/^(\+?88)?01\d{9}$/'],
        ]);
    }
}