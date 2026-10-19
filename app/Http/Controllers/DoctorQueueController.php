<?php

namespace App\Http\Controllers;

use App\Models\QueueEntry;
use App\Services\SchedulerService;
use Carbon\Carbon;
use App\Models\Appointment;

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


    /** My Appointments: today, upcoming, and the last 7 days of history */
public function appointments()
{
    $doctor = auth()->user()->doctor;
    abort_unless($doctor, 403, 'No doctor profile linked to this account.');

    // Closes yesterday's leftovers (booked -> completed / missed)
    $this->scheduler->ensureDailyCleanup();

    $base = Appointment::with('patient')->where('doctor_id', $doctor->id);

    $today = (clone $base)
        ->whereDate('scheduled_time', today())
        ->orderBy('scheduled_time')
        ->get();

    $upcoming = (clone $base)
        ->where('scheduled_time', '>=', today()->addDay())
        ->where('status', 'booked')
        ->orderBy('scheduled_time')
        ->get();

    // Only the last 7 days; older visits drop off this list automatically
    $historyList = (clone $base)
        ->where('scheduled_time', '>=', today()->subDays(7))
        ->where('scheduled_time', '<', today())
        ->orderByDesc('scheduled_time')
        ->get();

    $entries = QueueEntry::whereIn('appointment_id', $today->pluck('id')->merge($historyList->pluck('id')))
        ->orderBy('id')->get()->keyBy('appointment_id');

    // One status for every appointment, used by the view
    $statusOf = function ($a) use ($entries) {
        $e = $entries->get($a->id);
        return match (true) {
            in_array($a->status, ['cancelled', 'canceled'])                => ['Cancelled', 'bg-secondary'],
            $e && $e->status === 'in_progress'                             => ['With you now', 'bg-primary'],
            $e && $e->status === 'waiting'                                 => ['Waiting', 'bg-info text-dark'],
            ($e && $e->status === 'completed') || $a->status === 'completed' => ['Done', 'bg-success'],
            in_array($a->status, ['no-show', 'no_show'])                   => ['Missed', 'bg-warning text-dark'],
            Carbon::parse($a->scheduled_time)->isPast()                    => ['Not arrived (late)', 'bg-warning text-dark'],
            default                                                        => ['Not arrived yet', 'bg-light text-dark border'],
        };
    };

    $history = $historyList->groupBy(fn ($a) => Carbon::parse($a->scheduled_time)->toDateString());

    $stats = [
        'total'     => $today->count(),
        'checkedIn' => $today->filter(fn ($a) => $entries->has($a->id))->count(),
        'done'      => $today->filter(fn ($a) => $statusOf($a)[0] === 'Done')->count(),
    ];

    return view('doctor.appointments-list', compact('doctor', 'today', 'upcoming', 'history', 'entries', 'statusOf', 'stats'));
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