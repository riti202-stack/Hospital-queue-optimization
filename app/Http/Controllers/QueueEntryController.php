<?php
namespace App\Http\Controllers;

use App\Models\QueueEntry;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Services\SchedulerService;
use Carbon\Carbon;

class QueueEntryController extends Controller
{

    protected SchedulerService $scheduler;

public function __construct(SchedulerService $scheduler)
{
    $this->scheduler = $scheduler;
}


    public function index(Request $request,SchedulerService $scheduler)
    {
        $user = $request->user();

        if($user->role ==='doctor' && ! $request->filled('department') && $user->doctor)
            {
                return redirect()->route('queue-entries.index',[
                    'department'=>$user->doctor->department_id,

                    'doctor'=>$user->doctor->id,

                ]);
            }

            $scheduler->ensureDailyCleanup();

        $active = QueueEntry::whereIn('status',['waiting','in_progress'])
            ->where('checked_in_at','>=',today())
            ->get(['id','department_id','doctor_id','status']);

        $departments = Department::orderBy('name')->get()->map(function ($d) use ($active)
        {
            $d->active_count = $active->where('department_id',$d->id)->count();

            return $d;
        });

        $department = $request->filled('department')
            ? $departments->firstWhere('id',(int) $request->department)
            : null;


        $doctors = collect();
        $selectedDoctor = null;
        $current = null;
        $queue = collect();
        $unassignedCount =0;
        $completedToday =0;
        $showUnassigned = false;

        if($department)
            {
                $doctors = Doctor::with('user')
                     ->where('department_id',$department->id)
                     ->get()
                     ->sortBy(fn ($d) => $d->user->name ?? '')
                     ->values()
                     ->map(function($d) use ($active)
                     {
                        $mine = $active->where('doctor_id',$d->id);
                        $d->waiting_count=$mine->where('status','waiting')->count();
                        $d->is_seeing = $mine->where('status','in_progress')->isNotEmpty();
                        return $d;
                     });


                     $unassignedCount = $active->where('department_id',$department->id)
                     ->whereNull('doctor_id')->where('status','waiting')->count();


                     if($request->doctor === 'unassigned')
                        {
                            $showUnassigned = true;
                            $queue = $scheduler->getOrderedQueue($department->id)
                            ->whereNull('doctor_id')->values();

                        }

                        elseif($request->filled('doctor'))
                            {
                                $selectedDoctor = $doctors->firstWhere('id',(int)$request->doctor);

                                if($selectedDoctor)
                                    {
                                        $current = $scheduler->current($selectedDoctor->id);
                                        $queue = $scheduler->getOrderedQueue($department->id,$selectedDoctor->id)
                                        ->where('doctor_id',$selectedDoctor->id)
                                        ->values();

                                        $completedToday = QueueEntry::where('doctor_id',$selectedDoctor->id)
                                        ->where('status','completed')
                                        ->whereDate('completed_at',today())
                                        ->count();
                                    }
                            }
            }

            return view('queue-entries.index',compact(
                'departments','department','doctors','selectedDoctor','current','queue','unassignedCount','completedToday','showUnassigned'
            ));


            

        
    }

    public function create()
    {
        $patients = User::where('role', 'patient')->get();
        $doctors = Doctor::with('user')->get();
        $departments = Department::all();
        return view('queue-entries.create', compact('patients', 'doctors', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'urgency_level' => 'required|in:emergency,urgent,routine',
        ]);

        $weights = ['emergency' => 100, 'urgent' => 50, 'routine' => 10];
        $entry = QueueEntry::create([
            ...$request->only('patient_id', 'doctor_id', 'department_id', 'urgency_level'),
            'priority_score' => $weights[$request->urgency_level],
            'status' => 'waiting',
        ]);

        // crash-recovery log entry
        $entry->queueLogs()->create(['action' => 'checked_in']);

        return redirect()->route('queue-entries.index')->with('success', 'Patient checked in.');
    }

    public function edit(QueueEntry $queueEntry)
    {
        $patients = User::where('role', 'patient')->get();
        $doctors = Doctor::with('user')->get();
        $departments = Department::all();
        return view('queue-entries.edit', compact('queueEntry', 'patients', 'doctors', 'departments'));
    }

    public function update(Request $request, QueueEntry $queueEntry)
    {
        $request->validate([
            'status' => 'required|in:waiting,in_progress,completed',
        ]);
        $queueEntry->update($request->only('status', 'doctor_id'));
        $queueEntry->queueLogs()->create(['action' => $request->status]);
        return redirect()->route('queue-entries.index')->with('success', 'Queue entry updated.');
    }

    public function destroy(QueueEntry $queueEntry)
    {
        $queueEntry->delete();
        return redirect()->route('queue-entries.index')->with('success', 'Queue entry removed.');
    }
}

