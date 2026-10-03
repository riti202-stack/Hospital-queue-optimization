<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\SchedulerService;
use App\Models\QueueEntry;

class DoctorPortalController extends Controller
{
    protected SchedulerService $scheduler;

    public function __construct(SchedulerService $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    public function queue()
    {
        $doctor =auth()->user()->doctor;

        if(!$doctor)
            {
                abort(403,'Your account is not linked to a doctor profile');
            }

            $entries = $this->scheduler->getOrderedQueue()->where('doctor_id',$doctor->id);

            return view('doctor.queue',compact('doctor','entries'));
    }

    public function availability()
    {
        $doctor = auth()->user()->doctor;

        if(!$doctor)
            {
                abort(403,'Your account is not linked to doctor profile');
            }

            return view('doctor.availability',compact('doctor'));
    }

    public function toggleAvailability(Request $request)
    {
        $doctor = auth()->user()->doctor;

        if(!$doctor)
            {
                abort(403,'Your account is not linked to a doctor profile');
            }

            $doctor->is_available = $request->has('is_available');

            $doctor->save();

            return redirect()->route('doctor.availability')->with('success','Availability updated.');
    }

    public function queueData()
{
    $doctor = auth()->user()->doctor;
    if (!$doctor) {
        return response()->json([]);
    }

    $entries = $this->scheduler->getOrderedQueue()->where('doctor_id', $doctor->id);

    return response()->json($entries->map(function ($e) {
        return [
            'id' => $e->id,
            'patient_name' => $e->patient->name,
            'urgency_level' => $e->urgency_level,
            'priority_score' => number_format($e->priority_score, 1),
            'waited_for' => \Carbon\Carbon::parse($e->checked_in_at)->diffForHumans(null, true),
        ];
    })->values());
}

public function callPatient(\App\Models\QueueEntry $entry)
{
    $entry->update(['status' => 'in_progress', 'started_at' => now()]);
    $entry->queueLogs()->create(['action' => 'in_progress']);

    return response()->json(['success' => true]);
}

    public function appointments()
    {
        $doctor = auth()->user()->doctor;

        if(!$doctor)
            {
                abort(403,'Your account is not linked to a doctor profile');
            }

            $appointments = \App\Models\Appointment::with(['patient','department'])
            ->where('doctor_id',$doctor->id)
            ->latest('scheduled_time')
            ->paginate(10);

            return view('doctor.appointments',compact('appointments'));
    }

    public function referPatient(QueueEntry $entry)
    {
        $entry->update(['status'=>'referred']);
        $entry->queueLogs()->create(['action'=>'referred_for_tests']);

        return response()->json(['success'=> true]);
    }

    public function referredList()
    {
        $doctor =auth()->user()->doctor;
        if(!$doctor)
            {
                abort(403,'Your account is not linked to a doctor profile.');
            }

            $referred = QueueEntry::with('patient')
            ->where('doctor_id',$doctor->id)
            ->where('status','referred')
            ->get()
            ->map(fn($e) =>  [
                'id'=>$e->id,
                'patient_name'=>$e->patient->name,
                'urgency_level'=>$e->urgency_level,

            ]);

            return response()->json($referred);
    }

    public function returnPatient(QueueEntry $entry)
{
    $entry->update([
        'status' => 'waiting',
        'checked_in_at' => now(),
    ]);
    $entry->queueLogs()->create(['action' => 'returned_with_reports']);

    return response()->json(['success' => true]);
}

    public function completePatient(Request $request, QueueEntry $entry)
{
    $request->validate([
        'diagnosis' => 'required|string',
        'notes' => 'nullable|string',
    ]);

    $doctor = auth()->user()->doctor;

    \App\Models\MedicalRecord::create([
        'patient_id' => $entry->patient_id,
        'doctor_id' => $doctor->id,
        'department_id' => $entry->department_id,
        'queue_entry_id' => $entry->id,
        'diagnosis' => $request->diagnosis,
        'notes' => $request->notes,
        'visit_date' => now()->toDateString(),
    ]);

    $entry->update(['status' => 'completed', 'completed_at' => now()]);
    $entry->queueLogs()->create(['action' => 'completed']);

    if ($entry->appointment_id) {
        $entry->appointment()->update(['status' => 'completed']);
    }

    return response()->json(['success' => true]);
}
    //
}
