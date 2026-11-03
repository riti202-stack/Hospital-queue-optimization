<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\QueueEntry;
use App\Models\TestRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PatientTestController extends Controller
{
    /** All tests with prices */
    public function catalog(Request $request)
    {
        $tests = LabTest::where('is_active', true)->orderBy('category')->orderBy('name')->get()->groupBy('category');
        $selected = collect($request->input('tests', []))->map(fn ($v) => (int) $v)->all();

        return view('patient.tests.catalog', compact('tests', 'selected'));
    }

    /** Selected tests become a report with the total */
    public function review(Request $request)
    {
        $request->validate([
            'tests'   => 'required|array|min:1',
            'tests.*' => 'integer|exists:lab_tests,id',
        ], ['tests.required' => 'Please select at least one test.']);

        $tests = LabTest::whereIn('id', $request->tests)->where('is_active', true)->orderBy('category')->get();
        $total = $tests->sum('price');

        // Doctors this patient has booked or seen
        $uid = auth()->id();
        $doctorIds = Appointment::where('patient_id', $uid)->pluck('doctor_id')
            ->merge(QueueEntry::where('patient_id', $uid)->whereNotNull('doctor_id')->pluck('doctor_id'))
            ->filter()->unique();
        $myDoctors = Doctor::with('user')->whereIn('id', $doctorIds)->get();

        $defaultDate = now()->hour >= 17 ? today()->addDay() : today();
        if ($defaultDate->isFriday()) $defaultDate->addDay();

        return view('patient.tests.review', compact('tests', 'total', 'myDoctors', 'defaultDate'));
    }

    /** Send the report to the administration */
    public function store(Request $request)
    {
        $data = $request->validate([
            'tests'          => 'required|array|min:1',
            'tests.*'        => 'integer|exists:lab_tests,id',
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'doctor_id'      => 'nullable|exists:doctors,id',
            'patient_note'   => 'nullable|string|max:500',
        ]);

        $at = Carbon::parse($data['preferred_date'] . ' ' . $data['preferred_time']);
        if ($at->isPast() || ! TestRequest::isOpen($at)) {
            return back()->withInput()->withErrors(['preferred_time' =>
                'Please choose a future time when the lab is open (Saturday–Thursday, 8:00 AM – 6:00 PM).']);
        }

        $tests = LabTest::whereIn('id', $data['tests'])->where('is_active', true)->get();

        $req = TestRequest::create([
            'patient_id'   => auth()->id(),
            'doctor_id'    => $data['doctor_id'] ?? null,
            'status'       => 'pending',
            'preferred_at' => $at,
            'total_amount' => $tests->sum('price'),
            'patient_note' => $data['patient_note'] ?? null,
        ]);

        foreach ($tests as $t) {
            $req->items()->create(['lab_test_id' => $t->id, 'test_name' => $t->name, 'price' => $t->price]);
        }

        return redirect()->route('patient.tests.requests.show', $req)
            ->with('success', "Your test report {$req->code} was sent to the administration. We will confirm your time soon.");
    }

    public function index()
    {
        $requests = TestRequest::withCount('items')
            ->where('patient_id', auth()->id())
            ->latest()
            ->get();

        return view('patient.tests.index', compact('requests'));
    }

    public function show(TestRequest $testRequest)
    {
        $this->own($testRequest);
        $testRequest->load('items.labTest', 'doctor.user');

        return view('patient.tests.show', ['r' => $testRequest]);
    }

    /** Patient accepts the suggested time; admin then books it */
    public function accept(TestRequest $testRequest)
    {
        $this->own($testRequest);
        abort_unless($testRequest->status === 'suggested', 403);

        $testRequest->update(['status' => 'accepted']);

        return back()->with('success', 'Thank you. The administration will now confirm your booking.');
    }

    /** Patient declines: either proposes another time or cancels */
    public function decline(Request $request, TestRequest $testRequest)
    {
        $this->own($testRequest);
        abort_unless($testRequest->status === 'suggested', 403);

        if ($request->filled('preferred_date') && $request->filled('preferred_time')) {
            $at = Carbon::parse($request->preferred_date . ' ' . $request->preferred_time);
            if ($at->isPast() || ! TestRequest::isOpen($at)) {
                return back()->withErrors(['preferred_time' => 'Please choose a future time inside lab hours.']);
            }
            $testRequest->update(['status' => 'pending', 'preferred_at' => $at, 'suggested_at' => null]);

            return back()->with('success', 'Your new preferred time was sent to the administration.');
        }

        $testRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Your test request was cancelled.');
    }

    public function cancel(TestRequest $testRequest)
    {
        $this->own($testRequest);
        abort_unless(in_array($testRequest->status, ['pending', 'suggested', 'accepted']), 403);

        $testRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Your test request was cancelled.');
    }

    private function own(TestRequest $r): void
    {
        abort_unless((int) $r->patient_id === (int) auth()->id(), 403);
    }
}