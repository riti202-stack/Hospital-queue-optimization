<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\QueueEntry;
use Illuminate\Http\Request;

class PatientRecordController extends Controller
{
    public function index(Request $request)
    {
        $member  = (string) $request->query('member', 'all');
        $members = $request->user()->dependents()->orderBy('name')->get();

        $visits = QueueEntry::with(['doctor', 'prescription', 'review', 'dependent'])
            ->where('patient_id', $request->user()->id)
            ->where('status', 'completed')
            ->when($member === 'self', fn ($q) => $q->whereNull('dependent_id'))
            ->when(ctype_digit($member), fn ($q) => $q->where('dependent_id', (int) $member))
            ->latest('completed_at')
            ->paginate(10)->withQueryString();

        return view('patient.records.index', compact('visits', 'members', 'member'));
    }

    public function show(Prescription $prescription)
    {
        $this->own($prescription);
        $prescription->load('items', 'doctor', 'dependent');

        return view('patient.records.show', ['rx' => $prescription]);
    }

    public function pdf(Prescription $prescription)
    {
        $this->own($prescription);
        return DoctorPrescriptionController::pdfResponse($prescription);
    }

    private function own(Prescription $rx): void
    {
        abort_unless((int) $rx->patient_id === auth()->id(), 403);
    }
}