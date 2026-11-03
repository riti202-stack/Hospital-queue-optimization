<?php

namespace App\Http\Controllers;

use App\Models\DoctorReview;
use App\Models\QueueEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PatientReviewController extends Controller
{
    public function create(QueueEntry $entry)
    {
        if ($why = $this->cannotReview($entry)) {
            return redirect()->route('patient.records')->with('error', $why);
        }
        $entry->load('doctor');

        return view('patient.reviews.create', compact('entry'));
    }

    public function store(Request $request, QueueEntry $entry)
    {
        if ($why = $this->cannotReview($entry)) {
            return redirect()->route('patient.records')->with('error', $why);
        }

        $data = $request->validate([
            'rating'  => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ]);

        DoctorReview::create($data + [
            'doctor_id'      => $entry->doctor_id,
            'patient_id'     => $entry->patient_id,
            'queue_entry_id' => $entry->id,
        ]);

        return redirect()->route('patient.records')->with('success', __('Thank you for your feedback!'));
    }

    private function cannotReview(QueueEntry $entry): ?string
    {
        abort_unless((int) $entry->patient_id === auth()->id(), 403);

        if ($entry->status !== 'completed') {
            return __('You can rate a visit only after it is completed.');
        }
        if ($entry->review()->exists()) {
            return __('You have already rated this visit.');
        }
        if ($entry->completed_at && Carbon::parse($entry->completed_at)->lt(now()->subDays(30))) {
            return __('Visits older than 30 days can no longer be rated.');
        }
        return null;
    }
}