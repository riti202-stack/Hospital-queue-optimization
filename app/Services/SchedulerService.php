<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\QueueEntry;
use App\Notifications\ConsultationCompleted;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    /** Maximum points a patient can gain from waiting */
    protected float $agingCap = 60;

    /** Priority = urgency weight + min(effective wait x aging rate, cap) */
    public function calculatePriority(QueueEntry $entry): float
    {
        return $this->breakdown($entry)['score'];
    }

    /** Same calculation, returned in parts so screens can explain the score */
    public function breakdown(QueueEntry $entry): array
    {
        $base = $this->urgencyWeights[$entry->urgency_level] ?? 0;

        $waitStart = $this->waitStartsAt($entry);
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
     * Booked patient who arrived early: waiting counts from the appointment slot.
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

    /** Recalculate only the entries being shown */
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
     * Ordered waiting list for today.
     * Pass $doctorId for a doctor's queue: their patients + unassigned walk-ins of their department.
     */
    public function getOrderedQueue(?int $departmentId = null, ?int $doctorId = null)
    {
        $this->ensureDailyCleanup();

        $query = QueueEntry::with(['patient', 'doctor.user', 'department', 'appointment'])
            ->where('status', 'waiting')
            ->where('checked_in_at', '>=', today());

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

        return $entries->sortBy([
            ['priority_score', 'desc'],
            ['checked_in_at', 'asc'],
        ])->values();
    }

    /** The patient currently with the doctor, if any */
    public function current(int $doctorId): ?QueueEntry
    {
        $this->ensureDailyCleanup();

        return QueueEntry::with(['patient', 'appointment'])
            ->where('doctor_id', $doctorId)
            ->where('status', 'in_progress')
            ->where('checked_in_at', '>=', today())
            ->latest('started_at')
            ->first();
    }

    /** "Call next": finishes the current patient and starts the top-ranked one */
    public function callNext(int $doctorId, int $departmentId): ?QueueEntry
    {
        if ($current = $this->current($doctorId)) {
            $this->completeEntry($current);
        }

        $next = $this->getOrderedQueue($departmentId, $doctorId)->first();
        if ($next) {
            $next->update([
                'status'     => 'in_progress',
                'started_at' => now(),
                'doctor_id'  => $doctorId,
            ]);
        }

        return $next;
    }

    /**
     * The ONE place a consultation is completed:
     * queue entry -> completed, appointment -> completed, patient notified.
     */
    public function completeEntry(QueueEntry $entry): void
    {
        if ($entry->status === 'completed') {
            return;
        }

        $entry->update(['status' => 'completed', 'completed_at' => now()]);
        $entry->appointment?->update(['status' => 'completed']);

        try {
            $entry->loadMissing('patient', 'doctor.user', 'department');
            $entry->patient?->notify(new ConsultationCompleted($entry));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Doctor's average consultation length today (12 until there is data, minimum 3) */
    public function averageConsultMinutes(int $doctorId): int
    {
        $avg = QueueEntry::where('doctor_id', $doctorId)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->whereNotNull('started_at')
            ->get()
            ->avg(fn ($e) => Carbon::parse($e->started_at)->diffInMinutes(Carbon::parse($e->completed_at)));

        return max(3, (int) round($avg ?: 12));
    }

    /** What the PATIENT sees for one of their queue entries */
    public function patientStatus(QueueEntry $entry): array
    {
        $entry->loadMissing('doctor.user', 'department', 'appointment');

        $room = $entry->doctor?->room_no;
        $checkedIn = Carbon::parse($entry->checked_in_at);

        $token = QueueEntry::where('department_id', $entry->department_id)
            ->when($entry->doctor_id, fn ($q) => $q->where('doctor_id', $entry->doctor_id))
            ->whereDate('checked_in_at', $checkedIn->toDateString())
            ->where(function ($q) use ($entry) {
                $q->where('checked_in_at', '<', $entry->checked_in_at)
                  ->orWhere(fn ($q2) => $q2->where('checked_in_at', $entry->checked_in_at)->where('id', '<=', $entry->id));
            })
            ->count();

        $base = [
            'id'          => $entry->id,
            'token'       => $token,
            'doctor'      => $entry->doctor?->user?->name ?? 'Next available doctor',
            'department'  => $entry->department?->name,
            'room'        => $room,
            'urgency'     => $entry->urgency_level,
            'checked_in'  => $checkedIn->format('h:i A'),
            'appointment' => $entry->appointment?->scheduled_time
                ? Carbon::parse($entry->appointment->scheduled_time)->format('h:i A') : null,
        ];

        if ($entry->status === 'in_progress') {
            return $base + [
                'state' => 'called', 'position' => 0, 'ahead' => 0, 'total' => null,
                'expected_at' => null, 'expected_in' => 0,
                'message' => 'It\'s your turn. Please go to ' . ($room ? "Room {$room}" : 'the doctor\'s room') . ' now.',
            ];
        }

        $ordered = $this->getOrderedQueue($entry->department_id, $entry->doctor_id)->values();
        $index = $ordered->search(fn ($e) => (int) $e->id === (int) $entry->id);

        if ($index === false) {
            return $base + [
                'state' => 'unknown', 'position' => null, 'ahead' => null, 'total' => $ordered->count(),
                'expected_at' => null, 'expected_in' => null,
                'message' => 'Your position is being updated. Please wait near the reception.',
            ];
        }

        $avg = $entry->doctor_id ? $this->averageConsultMinutes($entry->doctor_id) : 12;
        $remaining = 0;
        if ($entry->doctor_id && ($cur = $this->current($entry->doctor_id))) {
            $remaining = max(2, $avg - (int) Carbon::parse($cur->started_at)->diffInMinutes(now()));
        }
        $minutes = $remaining + $index * $avg;

        return $base + [
            'state'       => 'waiting',
            'position'    => $index + 1,
            'ahead'       => $index,
            'total'       => $ordered->count(),
            'expected_at' => now()->addMinutes($minutes)->format('h:i A'),
            'expected_in' => $minutes,
            'message'     => $index === 0
                ? 'You are next. Please stay near ' . ($room ? "Room {$room}" : 'the doctor\'s room') . '.'
                : "{$index} patient" . ($index > 1 ? 's' : '') . ' ahead of you.',
        ];
    }

    // ================= End-of-day cleanup =================

    /** Runs closeStaleEntries() at most once per day (no cron needed) */
    public function ensureDailyCleanup(): void
    {
        $key = 'queue-cleanup-' . today()->toDateString();
        if (Cache::has($key)) {
            return;
        }
        $this->closeStaleEntries();
        Cache::put($key, true, now()->endOfDay());
    }

    /** Closes everything left open from previous days */
    public function closeStaleEntries(): array
    {
        $start = today();
        $result = ['in_progress_completed' => 0, 'waiting_expired' => 0, 'appointments_completed' => 0, 'appointments_missed' => 0];

        // 1. Called in but never completed -> completed
        QueueEntry::with('appointment')
            ->where('status', 'in_progress')
            ->where('checked_in_at', '<', $start)
            ->get()
            ->each(function (QueueEntry $e) use (&$result) {
                $startedAt = Carbon::parse($e->started_at ?? $e->checked_in_at);
                $e->update([
                    'status'       => 'completed',
                    'completed_at' => $startedAt->copy()->addMinutes(15)->min($startedAt->copy()->endOfDay()),
                ]);
                $e->appointment?->update(['status' => 'completed']);
                $result['in_progress_completed']++;
            });

        // 2. Checked in but never called -> expired
        $result['waiting_expired'] = QueueEntry::where('status', 'waiting')
            ->where('checked_in_at', '<', $start)
            ->update(['status' => $this->hasEnum('queue_entries', 'status', 'expired') ? 'expired' : 'completed', 'updated_at' => now()]);

        // 3. Past appointments still "booked" -> completed (if seen) or no-show
        $noShow = $this->hasEnum('appointments', 'status', 'no-show') ? 'no-show'
            : ($this->hasEnum('appointments', 'status', 'no_show') ? 'no_show' : 'cancelled');

        Appointment::where('status', 'booked')
            ->where('scheduled_time', '<', $start)
            ->get()
            ->each(function (Appointment $a) use ($noShow, &$result) {
                $seen = QueueEntry::where('appointment_id', $a->id)->where('status', 'completed')->exists();
                $a->update(['status' => $seen ? 'completed' : $noShow]);
                $seen ? $result['appointments_completed']++ : $result['appointments_missed']++;
            });

        return $result;
    }

    /** True if an enum column allows this value (or the column is not an enum) */
    protected function hasEnum(string $table, string $column, string $value): bool
    {
        static $cache = [];
        $k = "$table.$column";
        if (! array_key_exists($k, $cache)) {
            $cache[$k] = null;
            try {
                $info = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);
                if ($info && str_starts_with(strtolower($info[0]->Type), 'enum(')) {
                    preg_match_all("/'([^']*)'/", $info[0]->Type, $m);
                    $cache[$k] = $m[1];
                }
            } catch (\Throwable $e) {
            }
        }
        return $cache[$k] === null || in_array($value, $cache[$k], true);
    }
}