<?php

namespace App\Http\Controllers;

use App\Models\QueueEntry;
use App\Services\SchedulerService;
use Carbon\Carbon;

class DoctorQueueController extends Controller
{
    public function __construct(protected SchedulerService $scheduler) {}

    public function index()
    {
        $doctor = auth()->user()->doctor;
        abort_unless($doctor, 403, 'No doctor profile linked to this account.');

        $current = $this->scheduler->current($doctor->id);
        $queue   = $this->scheduler->getOrderedQueue($doctor->department_id, $doctor->id);

        // Average consultation time from today's completed patients (default 12 min)
        $avgMinutes = (int) round(QueueEntry::where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->whereNotNull('started_at')
            ->get()
            ->avg(fn ($e) => Carbon::parse($e->started_at)->diffInMinutes(Carbon::parse($e->completed_at))) ?: 12);
        $avgMinutes = max(3, $avgMinutes);

        // Time left for the patient currently inside
        $remainingNow = 0;
        if ($current) {
            $spent = Carbon::parse($current->started_at)->diffInMinutes(now());
            $remainingNow = max(2, $avgMinutes - $spent);
        }

        // Explain each position + expected call time
        $rows = $queue->values()->map(function ($entry, $i) use ($avgMinutes, $remainingNow) {
            $b = $this->scheduler->breakdown($entry);
            return [
                'entry'      => $entry,
                'position'   => $i + 1,
                'breakdown'  => $b,
                'reason'     => $this->reason($entry, $b, $i),
                'expectedAt' => now()->addMinutes($remainingNow + $i * $avgMinutes),
            ];
        });

        $seenToday = QueueEntry::where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->count();

        return view('doctor.queue', [
            'doctor'     => $doctor,
            'current'    => $current,
            'next'       => $rows->first(),
            'upcoming'   => $rows->slice(1)->values(),
            'avgMinutes' => $avgMinutes,
            'seenToday'  => $seenToday,
        ]);
    }

    /** Main button: finish current patient, start the top-ranked one */
    public function callNext()
    {
        $doctor = auth()->user()->doctor;
        $next = $this->scheduler->callNext($doctor->id, $doctor->department_id);

        return redirect()->route('doctor.queue')->with('success',
            $next ? 'Now seeing ' . ($next->patient->name ?? 'patient') . '.' : 'Queue is empty.');
    }

    /** Finish current patient without calling anyone (break / end of shift) */
    public function complete()
    {
        $doctor = auth()->user()->doctor;
        $current = $this->scheduler->current($doctor->id);

        if ($current) {
            $current->update(['status' => 'completed', 'completed_at' => now()]);
            $current->appointment?->update(['status' => 'completed']);
        }

        return redirect()->route('doctor.queue')->with('success', 'Consultation completed.');
    }

    /** Override: call a specific patient out of turn */
    public function callSpecific(QueueEntry $entry)
    {
        $doctor = auth()->user()->doctor;
        abort_unless(
            $entry->status === 'waiting'
            && ($entry->doctor_id === null || $entry->doctor_id === $doctor->id)
            && $entry->department_id === $doctor->department_id,
            403
        );

        if ($current = $this->scheduler->current($doctor->id)) {
            $current->update(['status' => 'completed', 'completed_at' => now()]);
            $current->appointment?->update(['status' => 'completed']);
        }

        $entry->update(['status' => 'in_progress', 'started_at' => now(), 'doctor_id' => $doctor->id]);

        return redirect()->route('doctor.queue')->with('success',
            'Called ' . ($entry->patient->name ?? 'patient') . ' out of turn.');
    }

    /** Plain-language reason for a patient's position */
    private function reason(QueueEntry $entry, array $b, int $index): string
    {
        $wait = $b['minutes'] . ' min';

        $text = match ($entry->urgency_level) {
            'emergency' => 'Emergency case: seen before everyone else',
            'urgent'    => "Urgent case, waiting {$wait}",
            default     => "Routine, waiting {$wait}",
        };

        if ($b['early_arrival']) {
            $text .= ' (arrived early, wait counted from appointment time)';
        } elseif (! $entry->appointment_id) {
            $text .= ' · walk-in';
        }

        if ($index === 0 && $entry->urgency_level !== 'emergency') {
            $text .= ' · highest priority in queue';
        }

        return $text;
    }
}