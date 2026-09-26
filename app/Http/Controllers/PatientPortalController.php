<?php

namespace App\Http\Controllers;


use App\Models\Appointment;
use App\Models\QueueEntry;
use App\Models\Doctor;
use App\Models\Department;
use App\Services\SchedulerService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PatientPortalController extends Controller
{
    protected SchedulerService $scheduler;

    public function __construct(SchedulerService $scheduler)
    {
        $this->scheduler = $scheduler;
    }
    
    public function bookForm()
    {
        $departments = Department::all();

        $doctors = Doctor::with('user','department')->where('is_available',true)->get();
        return view('patient.book',compact('departments','doctors'));
    }

    public function bookStore(Request $request)
    {
        $request->validate([

        'doctor_id'=>'required|exists:doctors,id',
        'department_id'=>'required|exists:departments,id',
        'scheduled_time'=>'required|date|after:now',
        ]);

        $clash = Appointment::where('doctor_id',$request->doctor_id)
        ->where('scheduled_time',$request->scheduled_time)
        ->where('status','booked')
        ->lockForUpdate()
        ->exists();

        if($clash)
            {
                return back()->withErrors(['scheduled_time'=>'this doctor is already booked at that time.please choose another slot.']);
            }

        Appointment::create([
            'patient_id'=>auth()->id(),
            'doctor_id'=>$request->doctor_id,
            'department_id'=>$request->department_id,
            'scheduled_time'=>$request->scheduled_time,
            'status'=>'booked',

        ]);

        return redirect()->route('patient.appointments')->with('success','Appointment booked successfully');
    }

    public function checkinForm()
    {
        $departments = Department::all();
        return view('patient.checkin',compact('departments'));
    }

    public function checkinStore(Request $request)
    {
        $request->validate([
            'department_id'=>'required|exists:departments,id',
            'urgency_level' => 'required|in:emergency,urgent,routine',

        ]);

        $weights = ['emergency'=>100,'urgent'=>50,'routine'=>10];

        $entry = QueueEntry::create([
            'patient_id'=>auth()->id(),
            'department_id'=>$request->department_id,
            'urgency_level'=>$request->urgency_level,
            'priority_score'=>$weights[$request->urgency_level],
            'status'=>'waiting',
            'checked_in_at'=>now(),

        ]);

        $entry->queueLogs()->create(['action'=>'checked_in']);

        return redirect()->route('patient.queue-status')->with('success','checked in successfully');
    }
    
     public function queueStatus()
    {
        return view('patient.queue-status');
    }

    public function queueStatusData()
    {
        $entry = QueueEntry::where('patient_id', auth()->id())
            ->whereIn('status', ['waiting', 'in_progress'])
            ->latest('checked_in_at')
            ->first();

        if (!$entry) {
            return response()->json(['active' => false]);
        }

        $ordered = $this->scheduler->getOrderedQueue($entry->department_id);
        $position = $ordered->search(fn($e) => $e->id === $entry->id);
        $position = $position === false ? null : $position + 1;

        return response()->json([
            'active' => true,
            'status' => $entry->status,
            'urgency_level' => $entry->urgency_level,
            'position' => $position,
            'estimated_wait' => $position ? ($position - 1) * 15 : 0,
            'waited_for' => Carbon::parse($entry->checked_in_at)->diffForHumans(null, true),
        ]);
    }

    public function appointments()
    {
        $appointments = Appointment::with(['doctor.user', 'department'])
            ->where('patient_id', auth()->id())
            ->latest('scheduled_time')
            ->paginate(10);

        return view('patient.appointments', compact('appointments'));
    }


    
    

    
    

    
}
