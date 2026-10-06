<?php

namespace App\Services;

use App\Models\QueueEntry;
use Carbon\Carbon;

class SchedulerService
{
    /**
     * Base weight per urgency level.
     * Emergency is far above the others so it can NEVER be overtaken by aging.
     */
    protected array $urgencyWeights = [
        'emergency' => 1000,
        'urgent'    => 50,
        'routine'   => 10,
    ];

    /** Points gained per minute of waiting (prevents starvation) */
    protected float $agingRate = 0.5;

    /**
     * Maximum points a patient can gain from waiting.
     * With 60: a routine patient can reach at most 70, so after ~80 minutes they pass a
     * newly arrived urgent patient (50), but never an emergency (1000+).
     */
    protected float $agingCap = 60;

    /**
     * Priority = urgency weight + min(effective wait x aging rate, cap)
     */
    public function calculatePriority(QueueEntry $entry): float
    {
        return $this->breakdown($entry)['score'];
    }

    /**
     * Same calculation, returned in parts so the doctor's screen can explain the score.
     */
    public function breakdown(QueueEntry $entry): array
    {
        $base = $this->urgencyWeights[$entry->urgency_level] ?? 0;

        $waitStart = $this->waitStartsAt($entry);
        // max(0, ...) protects against clock/timezone mismatch ever producing negative waits
        $minutes = max(0, $waitStart->diffInMinutes(now(), false));

        $aging = min($minutes * $this->agingRate, $this->agingCap);

        return [
            'base'          => $base,
            'minutes'       => (int) round($minutes),
            'aging'         => round($aging, 1),
            'score'         => round($base + $aging, 1),
            'waiting_since' => $waitStart,
            'early_arrival' => $waitStart->gt(Carbon::parse($entry->checked_in_at)),
        ];
    }

    /**
     * Walk-in: waiting counts from check-in.
     * Booked patient who arrived early: waiting counts from the appointment slot,
     * so arriving 40 minutes early does not let them jump ahead of patients due now.
     */
    protected function waitStartsAt(QueueEntry $entry): Carbon
    {
        $checkedIn = Carbon::parse($entry->checked_in_at);

        if ($entry->appointment_id && $entry->appointment?->scheduled_time) {
            $slot = Carbon::parse($entry->appointment->scheduled_time);
            return $checkedIn->max($slot);
        }

        return $checkedIn;
    }

    /**
     * Recalculate only the entries being shown (not the whole hospital).
     */
    public function refreshPriorities($entries): void
    {
        foreach ($entries as $entry) {
            $score = $this->calculatePriority($entry);
            if ((float) $entry->priority_score !== $score) {
                $entry->priority_score = $score;
                $entry->saveQuietly();
            }
        }
    }

    /** Kept for compatibility with existing calls */
    public function refreshAllPriorities(): void
    {
        $this->refreshPriorities(QueueEntry::with('appointment')->where('status', 'waiting')->get());
    }

    /**
     * Ordered waiting list.
     * Pass $doctorId for a doctor's own queue: their patients + unassigned walk-ins of their department.
     */
    public function getOrderedQueue(?int $departmentId = null, ?int $doctorId = null)
    {
        $query = QueueEntry::with(['patient', 'doctor.user', 'department', 'appointment'])
            ->where('status', 'waiting');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($doctorId) {
            $query->where(function ($q) use ($doctorId) {
                $q->where('doctor_id', $doctorId)->orWhereNull('doctor_id');
            });
        }

        $entries = $query->get();
        $this->refreshPriorities($entries);

        // Highest score first; on a tie, whoever checked in first
        return $entries->sortBy([
            ['priority_score', 'desc'],
            ['checked_in_at', 'asc'],
        ])->values();
    }

    /**
     * The patient currently with the doctor (status in_progress), if any.
     */
    public function current(int $doctorId): ?QueueEntry
    {
        return QueueEntry::with(['patient', 'appointment'])
            ->where('doctor_id', $doctorId)
            ->where('status', 'in_progress')
            ->latest('started_at')
            ->first();
    }

    /**
     * "Call next": finishes the current patient and starts the top-ranked one.
     */
    public function callNext(int $doctorId, int $departmentId): ?QueueEntry
    {
        $current = $this->current($doctorId);
        if ($current) {
            $current->update(['status' => 'completed', 'completed_at' => now()]);
            $current->appointment?->update(['status' => 'completed']);
        }

        $next = $this->getOrderedQueue($departmentId, $doctorId)->first();
        if ($next) {
            $next->update([
                'status'     => 'in_progress',
                'started_at' => now(),
                'doctor_id'  => $doctorId, // assigns an unassigned walk-in to this doctor
            ]);
        }

        return $next;
    }
}