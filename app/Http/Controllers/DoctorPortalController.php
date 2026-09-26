<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SchedulerService;

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
    //
}
