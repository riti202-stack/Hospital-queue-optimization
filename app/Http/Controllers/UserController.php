<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\QueueEntry;


class UserController extends Controller
{
    /** Overview: counts per role */
public function index()
{
    $counts = $this->roleCounts();
    $newPatientsThisWeek = User::where('role', 'patient')->where('created_at', '>=', now()->subDays(7))->count();
    $doctorsWithoutProfile = User::where('role', 'doctor')->whereNotIn('id', Doctor::pluck('user_id'))->count();

    return view('users.index', compact('counts', 'newPatientsThisWeek', 'doctorsWithoutProfile'));
}

/** Patients page */
public function patients(Request $request)
{
    $q = trim((string) $request->q);

    $patients = User::where('role', 'patient')
        ->when($q, fn ($query) => $query->where(fn ($w) =>
            $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
        ->orderBy('name')
        ->paginate(15)
        ->withQueryString();

    $ids = $patients->pluck('id');

    $visits = Appointment::selectRaw('patient_id, count(*) as total, max(scheduled_time) as latest')
        ->whereIn('patient_id', $ids)
        ->groupBy('patient_id')
        ->get()
        ->keyBy('patient_id');

    $inQueue = QueueEntry::whereIn('patient_id', $ids)
        ->whereIn('status', ['waiting', 'in_progress'])
        ->where('checked_in_at', '>=', today())
        ->get(['patient_id', 'status', 'token_no'])
        ->keyBy('patient_id');

    $counts = $this->roleCounts();

    return view('users.patients', compact('patients', 'visits', 'inQueue', 'q', 'counts'));
}

/** Doctors page, grouped by department */
public function doctors(Request $request)
{
    $q = strtolower(trim((string) $request->q));

    $departments = Department::with('doctors.user')->orderBy('name')->get()
        ->map(function ($dept) use ($q) {
            $docs = $dept->doctors->filter(fn ($d) => $d->user)->sortBy(fn ($d) => $d->user->name);
            if ($q) {
                $docs = $docs->filter(fn ($d) => str_contains(strtolower($d->user->name . ' ' . $d->user->email), $q));
            }
            $dept->doctor_list = $docs->values();
            return $dept;
        });

    if ($q) {
        $departments = $departments->filter(fn ($d) => $d->doctor_list->isNotEmpty())->values();
    }

    // Doctor accounts that have no doctor profile yet
    $noProfile = User::where('role', 'doctor')
        ->whereNotIn('id', Doctor::pluck('user_id'))
        ->orderBy('name')
        ->get();

    $counts = $this->roleCounts();

    return view('users.doctors', compact('departments', 'noProfile', 'q', 'counts'));
}

/** Admins page */
public function admins()
{
    $admins = User::where('role', 'admin')->orderBy('name')->get();
    $counts = $this->roleCounts();

    return view('users.admins', compact('admins', 'counts'));
}

private function roleCounts(): array
{
    return User::selectRaw('role, count(*) as c')->groupBy('role')->pluck('c', 'role')->all();
}

    public function create()
{
    return view('users.create');
}

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:6',
        'role' => 'required|in:patient,doctor,admin',
    ]);

    User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
        'role' => $request->role,
    ]);

    return redirect()->route('users.index')->with('success', 'User created.');
}

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:patient,doctor,admin']);
        $user->update($request->only('role'));
        return redirect()->route('users.index')->with('success', 'User role updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted.');
    }
}