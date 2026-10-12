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


    /** PAGE 1: all departments */
public function index(Request $request, SchedulerService $scheduler)
{
    $user = $request->user();

    // A doctor goes straight to their own queue
    if ($user->role === 'doctor' && $user->doctor) {
        return redirect()->route('queue-entries.doctor', [
            $user->doctor->department_id, $user->doctor->id,
        ]);
    }

    $scheduler->ensureDailyCleanup();

    $active = $this->activeToday();

    $departments = Department::withCount('doctors')->orderBy('name')->get()
        ->map(function ($d) use ($active) {
            $mine = $active->where('department_id', $d->id);
            $d->waiting_count = $mine->where('status', 'waiting')->count();
            $d->seeing_count  = $mine->where('status', 'in_progress')->count();
            return $d;
        });

    return view('queue-entries.index', compact('departments'));
}

/** PAGE 2: doctors of one department */
public function department(Department $department, SchedulerService $scheduler)
{
    $scheduler->ensureDailyCleanup();

    $active = $this->activeToday()->where('department_id', $department->id);

    $doctors = Doctor::with('user')
        ->where('department_id', $department->id)
        ->get()
        ->sortBy(fn ($d) => $d->user->name ?? '')
        ->values()
        ->map(function ($d) use ($active, $scheduler) {
            $d->waiting_count = $active->where('doctor_id', $d->id)->where('status', 'waiting')->count();
            $d->current = $scheduler->current($d->id);
            return $d;
        });

    // Walk-ins not yet assigned to a doctor in this department
    $unassigned = $scheduler->getOrderedQueue($department->id)
        ->whereNull('doctor_id')->values();

    return view('queue-entries.department', compact('department', 'doctors', 'unassigned'));
}

/** PAGE 3: one doctor's queue */
public function doctor(Department $department, Doctor $doctor, SchedulerService $scheduler)
{
    abort_unless($doctor->department_id === $department->id, 404);

    // A doctor may only open their own queue
    $user = auth()->user();
    if ($user->role === 'doctor') {
        abort_unless($user->doctor && $user->doctor->id === $doctor->id, 403);
    }

    $doctor->load('user');
    $current = $scheduler->current($doctor->id);
    $queue = $scheduler->getOrderedQueue($department->id, $doctor->id)
        ->where('doctor_id', $doctor->id)
        ->values();

    $completedToday = QueueEntry::where('doctor_id', $doctor->id)
        ->where('status', 'completed')
        ->whereDate('completed_at', today())
        ->count();

    return view('queue-entries.doctor', compact('department', 'doctor', 'current', 'queue', 'completedToday'));
}

/** Today's waiting + with-doctor entries (used for counts) */
private function activeToday()
{
    return QueueEntry::whereIn('status', ['waiting', 'in_progress'])
        ->where('checked_in_at', '>=', today())
        ->get(['id', 'department_id', 'doctor_id', 'status']);
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

