<?php
namespace App\Http\Controllers;

use App\Models\QueueEntry;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use Illuminate\Http\Request;

class QueueEntryController extends Controller
{

    protected SchedulerService $scheduler;

public function __construct(SchedulerService $scheduler)
{
    $this->scheduler = $scheduler;
}


    public function index()
    {
        $entries = QueueEntry::with(['patient', 'doctor.user', 'department'])
            ->orderByDesc('priority_score')->paginate(10);
        return view('queue-entries.index', compact('entries'));
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

