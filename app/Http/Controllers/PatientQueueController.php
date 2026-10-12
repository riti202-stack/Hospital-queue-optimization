<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\QueueEntry;
use App\Services\SchedulerService;
use Carbon\Carbon;

class PatientQueueController extends Controller
{
    public function __construct(protected SchedulerService $scheduler) {}

    /** My Queue page */
    public function status()
    {
        return view('patient.my-queue');
    }

    /** JSON for the live card (polled every 15 s) */
    public function statusData()
    {
        $uid = auth()->id();

        $active = QueueEntry::where('patient_id', $uid)
            ->whereIn('status', ['waiting', 'in_progress'])
            ->where('checked_in_at', '>=', today())
            ->orderBy('checked_in_at')
            ->get();

        $done = QueueEntry::with('doctor.user')
            ->where('patient_id', $uid)
            ->where('status', 'completed')
            ->whereDate('completed_at', today())
            ->latest('completed_at')
            ->first();

        return response()->json([
            'updated_at' => now()->format('h:i:s A'),
            'items'      => $active->map(fn ($e) => $this->scheduler->patientStatus($e))->values(),
            'done'       => $done ? [
                'token'  => $done->token_no,
                'doctor' => $done->doctor?->user?->name,
                'at'     => Carbon::parse($done->completed_at)->format('h:i A'),
            ] : null,
        ]);
    }

    /** My Appointments: today / upcoming / history, each with its live queue state */
    public function appointments()
    {
        $uid = auth()->id();
        $this->scheduler->ensureDailyCleanup();

        $appointments = Appointment::with(['doctor.user', 'department'])
            ->where('patient_id', $uid)
            ->orderBy('scheduled_time')
            ->get();

        // Latest queue entry for each appointment
        $entries = QueueEntry::whereIn('appointment_id', $appointments->pluck('id'))
            ->orderBy('id')->get()->keyBy('appointment_id');

        $inQueueNow = QueueEntry::where('patient_id', $uid)
            ->whereIn('status', ['waiting', 'in_progress'])
            ->where('checked_in_at', '>=', today())
            ->exists();

        $rows = $appointments->map(function ($a) use ($entries, $inQueueNow) {
            $e = $entries->get($a->id);
            $time = Carbon::parse($a->scheduled_time);
            $active = $e && in_array($e->status, ['waiting', 'in_progress'])
                && Carbon::parse($e->checked_in_at)->gte(today());

            $state = match (true) {
                $active && $e->status === 'in_progress'                      => 'called',
                $active                                                       => 'waiting',
                ($e && $e->status === 'completed') || $a->status === 'completed' => 'done',
                in_array($a->status, ['cancelled', 'canceled'])               => 'cancelled',
                in_array($a->status, ['no-show', 'no_show'])                  => 'missed',
                $time->isToday()                                              => $inQueueNow ? 'blocked' : 'checkin',
                $time->isFuture()                                             => 'upcoming',
                default                                                       => 'missed',
            };

            return (object) [
                'appointment' => $a,
                'entry'       => $e,
                'state'       => $state,
                'live'        => $active ? $this->scheduler->patientStatus($e) : null,
                'time'        => $time,
            ];
        });

        $today    = $rows->filter(fn ($r) => $r->time->isToday())->values();
        $upcoming = $rows->filter(fn ($r) => ! $r->time->isToday() && $r->time->isFuture() && $r->state === 'upcoming')->values();
        $history  = $rows->reject(fn ($r) => $r->time->isToday() || ($r->time->isFuture() && $r->state === 'upcoming'))
            ->sortByDesc(fn ($r) => $r->time->timestamp)->values();

        return view('patient.my-appointments', compact('today', 'upcoming', 'history'));
    }

    /** Check in for today's appointment */
    public function checkinAppointment(Appointment $appointment)
    {
        abort_unless((int) $appointment->patient_id === (int) auth()->id(), 403);

        if (! Carbon::parse($appointment->scheduled_time)->isToday()) {
            return back()->withErrors(['checkin' => 'You can check in only on the day of your appointment.']);
        }
        if ($appointment->status !== 'booked') {
            return back()->withErrors(['checkin' => 'This appointment can no longer be checked in.']);
        }

        $entry = $this->scheduler->checkIn(
            auth()->id(), $appointment->department_id, $appointment->doctor_id, 'routine', $appointment->id
        );

        return redirect()->route('patient.queue-status')
            ->with('success', "You are checked in. Your queue number is #{$entry->token_no}.");
    }

    /** Unread "called" / "completed" alerts (polled every 10 s on every patient page) */
    public function alerts()
    {
        $items = auth()->user()->unreadNotifications()
            ->where('created_at', '>=', today())
            ->latest()->take(5)->get()
            ->filter(fn ($n) => in_array($n->data['type'] ?? '', ['patient_called', 'consultation_completed']))
            ->sortBy('created_at')
            ->map(fn ($n) => ['id' => $n->id] + $n->data)
            ->values();

        return response()->json(['items' => $items]);
    }

    public function readAlert(string $id)
    {
        auth()->user()->unreadNotifications()->where('id', $id)->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}