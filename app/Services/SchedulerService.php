<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\QueueEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SchedulerService
{
    protected array $urgencyWeights = [
        'emergency' => 1000,
        'urgent'    => 50,
        'routine'   => 10,
    ];

    protected float $agingRate = 0.5;
    protected float $agingCap = 60;

    // ================= Check-in =================

    /** The one way to put a patient in a queue (number + duplicate guard happen in the model) */
    public function checkIn(int $patientId, int $departmentId, ?int $doctorId = null,
                            string $urgency = 'routine', ?int $appointmentId = null): QueueEntry
    {
        $this->ensureDailyCleanup();
        $this->fillMissingTokens();

        $entry = QueueEntry::create([
            'patient_id'     => $patientId,
            'department_id'  => $departmentId,
            'doctor_id'      => $doctorId,
            'appointment_id' => $appointmentId,
            'urgency_level'  => $urgency,
            'status'         => 'waiting',
            'checked_in_at'  => now(),
        ]);

        $entry->load('appointment');
        $entry->priority_score = $this->calculatePriority($entry);
        $entry->saveQuietly();

        return $entry;
    }

    /** Gives a queue number to any of today's entries that don't have one (e.g. seeded rows) */
    public function fillMissingTokens(): void
    {
        if (! QueueEntry::whereNull('token_no')->where('checked_in_at', '>=', today())->exists()) {
            return;
        }

        $rows = QueueEntry::where('checked_in_at', '>=', today())
            ->orderBy('checked_in_at')->orderBy('id')
            ->get(['id', 'department_id', 'token_no', 'checked_in_at']);

        $next = [];
        foreach ($rows as $r) {
            $next[$r->department_id] = max($next[$r->department_id] ?? 0, (int) $r->token_no);
        }

        foreach ($rows->whereNull('token_no') as $r) {
            $next[$r->department_id]++;
            DB::table('queue_entries')->where('id', $r->id)->update([
                'token_no'      => $next[$r->department_id],
                'checked_in_at' => $r->checked_in_at,
            ]);
        }
    }

    // ================= Priority =================

    public function calculatePriority(QueueEntry $entry): float
    {
        return $this->breakdown($entry)['score'];
    }

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

    protected function waitStartsAt(QueueEntry $entry): Carbon
    {
        $checkedIn = Carbon::parse($entry->checked_in_at);

        if ($entry->appointment_id && $entry->appointment?->scheduled_time) {
            return $checkedIn->max(Carbon::parse($entry->appointment->scheduled_time));
        }

        return $checkedIn;
    }

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

    public function refreshAllPriorities(): void
    {
        $this->refreshPriorities(QueueEntry::with('appointment')->where('status', 'waiting')->get());
    }

    // ================= Queue =================

    public function getOrderedQueue(?int $departmentId = null, ?int $doctorId = null)
    {
        $this->ensureDailyCleanup();
        $this->fillMissingTokens();

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

    public function current(int $doctorId): ?QueueEntry
    {
        $this->ensureDailyCleanup();
        $this->fillMissingTokens();

        return QueueEntry::with(['patient', 'appointment'])
            ->where('doctor_id', $doctorId)
            ->where('status', 'in_progress')
            ->where('checked_in_at', '>=', today())
            ->latest('started_at')
            ->first();
    }

    /** Finishes the current patient and calls the top-ranked one (the model notifies both) */
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

    /** The model's events update the appointment and notify the patient */
    public function completeEntry(QueueEntry $entry): void
    {
        if ($entry->status === 'completed') {
            return;
        }

        $entry->update(['status' => 'completed', 'completed_at' => now()]);
    }

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

    /** What the PATIENT sees — same ranking as the doctor and admin screens */
    public function patientStatus(QueueEntry $entry): array
    {
        $entry->loadMissing('doctor.user', 'department', 'appointment');

        $room = $entry->doctor?->room_no;

        $base = [
            'id'          => $entry->id,
            'token'       => $entry->token_no,
            'doctor'      => $entry->doctor?->user?->name ?? 'Next available doctor',
            'department'  => $entry->department?->name,
            'room'        => $room,
            'urgency'     => $entry->urgency_level,
            'checked_in'  => Carbon::parse($entry->checked_in_at)->format('h:i A'),
            'appointment' => $entry->appointment?->scheduled_time
                ? Carbon::parse($entry->appointment->scheduled_time)->format('h:i A') : null,
        ];

        if ($entry->status === 'in_progress') {
            return $base + [
                'state' => 'called', 'position' => 0, 'ahead' => 0, 'total' => null,
                'expected_at' => null, 'expected_in' => 0,
                'message' => "It's your turn. Please go to " . ($room ? "Room {$room}" : "the doctor's room") . ' now.',
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
                ? 'You are next. Please stay near ' . ($room ? "Room {$room}" : "the doctor's room") . '.'
                : "{$index} patient" . ($index > 1 ? 's' : '') . ' ahead of you.',
        ];
    }

    // ================= End-of-day cleanup =================

    public function ensureDailyCleanup(): void
    {
        $key = 'queue-cleanup-' . today()->toDateString();
        if (Cache::has($key)) {
            return;
        }
        $this->closeStaleEntries();
        Cache::put($key, true, now()->endOfDay());
    }

    public function closeStaleEntries(): array
    {
        $start = today();
        $result = ['in_progress_completed' => 0, 'waiting_expired' => 0, 'appointments_completed' => 0, 'appointments_missed' => 0];

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
                $result['in_progress_completed']++;
            });

        $result['waiting_expired'] = QueueEntry::where('status', 'waiting')
            ->where('checked_in_at', '<', $start)
            ->update(['status' => $this->hasEnum('queue_entries', 'status', 'expired') ? 'expired' : 'completed', 'updated_at' => now()]);

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