<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesDoctor;
use App\Models\Prescription;
use App\Models\QueueEntry;
use App\Notifications\PrescriptionReady;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorPrescriptionController extends Controller
{
    use ResolvesDoctor;

    public function create(QueueEntry $entry)
    {
        $this->authorizeEntry($entry);

        if ($entry->prescription) {
            return redirect()->route('doctor.prescriptions.edit', $entry->prescription);
        }

        $entry->load('patient', 'dependent');

        return view('doctor.prescriptions.form', [
            'rx'          => new Prescription(),
            'patientName' => $entry->patientLabel(),
            'patientId'   => $entry->patient_id,
            'dependentId' => $entry->dependent_id,
            'token'       => $entry->token_no,
            'past'        => $this->pastFor($entry->patient_id, $entry->dependent_id),
            'action'      => route('doctor.prescriptions.store', $entry),
        ]);
    }

    public function store(Request $request, QueueEntry $entry)
    {
        $this->authorizeEntry($entry);
        abort_if($entry->prescription()->exists(), 409, 'A prescription already exists for this visit.');

        $data = $this->validated($request);

        $rx = DB::transaction(function () use ($entry, $data) {
            $rx = Prescription::create([
                'queue_entry_id' => $entry->id,
                'appointment_id' => $entry->appointment_id,
                'patient_id'     => $entry->patient_id,
                'dependent_id'   => $entry->dependent_id,
                'doctor_id'      => $entry->doctor_id,
            ] + collect($data)->except('items')->all());

            $this->saveItems($rx, $data['items'] ?? []);
            return $rx;
        });

        $rx->patient?->notify(new PrescriptionReady($rx));

        return redirect()->route('doctor.queue')->with('success', 'Prescription ' . $rx->code() . ' saved and sent to the patient.');
    }

    public function edit(Prescription $prescription)
    {
        $this->authorizeRx($prescription);
        $prescription->load('items', 'patient', 'dependent', 'queueEntry');

        return view('doctor.prescriptions.form', [
            'rx'          => $prescription,
            'patientName' => $prescription->patientLabel(),
            'patientId'   => $prescription->patient_id,
            'dependentId' => $prescription->dependent_id,
            'token'       => $prescription->queueEntry->token_no ?? '—',
            'past'        => $this->pastFor($prescription->patient_id, $prescription->dependent_id, $prescription->id),
            'action'      => route('doctor.prescriptions.update', $prescription),
        ]);
    }

    public function update(Request $request, Prescription $prescription)
    {
        $this->authorizeRx($prescription);
        $data = $this->validated($request);

        DB::transaction(function () use ($prescription, $data) {
            $prescription->update(collect($data)->except('items')->all());
            $this->saveItems($prescription, $data['items'] ?? []);
        });

        return redirect()->route('doctor.prescriptions.edit', $prescription)->with('success', 'Prescription updated.');
    }

    public function pdf(Prescription $prescription)
    {
        $this->authorizeRx($prescription);
        return self::pdfResponse($prescription);
    }

    public static function pdfResponse(Prescription $rx)
    {
        $rx->load('items', 'doctor', 'patient', 'dependent');
        return Pdf::loadView('pdf.prescription', ['rx' => $rx])->download($rx->code() . '.pdf');
    }

    // ---------- helpers ----------

    private function authorizeEntry(QueueEntry $entry): void
    {
        abort_unless(
            (int) $entry->doctor_id === $this->currentDoctor()->id
                && in_array($entry->status, ['in_progress', 'completed'], true),
            403,
            'You can write a prescription only for your own patient who has been called.'
        );
    }

    private function authorizeRx(Prescription $rx): void
    {
        abort_unless((int) $rx->doctor_id === $this->currentDoctor()->id, 403);
    }

    private function pastFor(int $patientId, ?int $dependentId, ?int $exceptId = null)
    {
        return Prescription::with('items', 'doctor')
            ->where('patient_id', $patientId)
            ->where('dependent_id', $dependentId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->latest()->take(5)->get();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'symptoms'            => 'nullable|string|max:1000',
            'diagnosis'           => 'required|string|max:1000',
            'tests_advised'       => 'nullable|string|max:1000',
            'advice'              => 'nullable|string|max:1000',
            'follow_up_date'      => 'nullable|date|after:today',
            'items'               => 'nullable|array',
            'items.*.medicine'    => 'nullable|string|max:120',
            'items.*.dosage'      => 'nullable|string|max:30',
            'items.*.duration'    => 'nullable|string|max:30',
            'items.*.instruction' => 'nullable|string|max:120',
        ]);
    }

    private function saveItems(Prescription $rx, array $items): void
    {
        $rx->items()->delete();

        foreach ($items as $item) {
            if (blank($item['medicine'] ?? null)) continue;

            $rx->items()->create([
                'medicine'    => $item['medicine'],
                'dosage'      => $item['dosage'] ?: '—',
                'duration'    => $item['duration'] ?: '—',
                'instruction' => $item['instruction'] ?? null,
            ]);
        }
    }
}