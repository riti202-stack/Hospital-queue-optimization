<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Department;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with(['patient', 'doctor.user', 'department'])->latest()->paginate(10);
        return view('appointments.index', compact('appointments'));
    }

    public function create()
    {
        $patients = User::where('role', 'patient')->get();
        $doctors = Doctor::with('user')->get();
        $departments = Department::all();
        return view('appointments.create', compact('patients', 'doctors', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'scheduled_time' => 'required|date',
        ]);
        // Concurrency-safe: check for clashing slot before creating
        $clash = Appointment::where('doctor_id', $request->doctor_id)
            ->where('scheduled_time', $request->scheduled_time)
            ->where('status', 'booked')
            ->lockForUpdate()
            ->exists();

        if ($clash) {
            return back()->withErrors(['scheduled_time' => 'This doctor is already booked at that time.']);
        }

        Appointment::create($request->only('patient_id', 'doctor_id', 'department_id', 'scheduled_time', 'status'));
        return redirect()->route('appointments.index')->with('success', 'Appointment booked.');
    }

    public function edit(Appointment $appointment)
    {
        $patients = User::where('role', 'patient')->get();
        $doctors = Doctor::with('user')->get();
        $departments = Department::all();
        return view('appointments.edit', compact('appointment', 'patients', 'doctors', 'departments'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $request->validate([
            'patient_id' => 'required|exists:users,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'scheduled_time' => 'required|date',
            'status' => 'required|in:booked,completed,cancelled,no-show',
        ]);
        $appointment->update($request->all());
        return redirect()->route('appointments.index')->with('success', 'Appointment updated.');
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return redirect()->route('appointments.index')->with('success', 'Appointment deleted.');
    }
}