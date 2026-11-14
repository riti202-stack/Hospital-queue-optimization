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
    $all = Appointment::with(['doctor.user', 'doctor.department', 'dependent'])
        ->where('patient_id', auth()->id())
        ->get();

    // Upcoming = still booked, today or later (soonest first)
    $upcoming = $all->filter(fn ($a) => $a->status === 'booked'
                    && \Carbon\Carbon::parse($a->scheduled_time)->gte(today()))
                    ->sortBy('scheduled_time')->values();

    // History = everything else (newest first)
    $past = $all->diff($upcoming)->sortByDesc('scheduled_time')->values();

    // Appointments already checked in today (to hide the Check in button)
    $checkedIn = QueueEntry::whereIn('appointment_id', $upcoming->pluck('id'))
        ->whereIn('status', ['waiting', 'in_progress'])
        ->pluck('appointment_id')->all();

    return view('patient.my-appointments', compact('upcoming', 'past', 'checkedIn'));
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
            ->filter(fn ($n) => in_array($n->data['type'] ?? '', ['patient_called', 'consultation_completed', 'test_update']))
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