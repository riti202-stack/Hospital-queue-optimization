<?php
namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::with(['user', 'department'])->latest()->paginate(10);
        return view('doctors.index', compact('doctors'));
    }

    public function create()
    {
        $users = User::where('role', 'doctor')->get();
        $departments = Department::all();
        return view('doctors.create', compact('users', 'departments'));
    }

    public function store(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'department_id' => 'required|exists:departments,id',
    ]);

    Doctor::create([
        'user_id' => $request->user_id,
        'department_id' => $request->department_id,
        'is_available' => $request->has('is_available'),
    ]);

    return redirect()->route('doctors.index')->with('success', 'Doctor added.');
}

    public function edit(Doctor $doctor)
    {
        $users = User::where('role', 'doctor')->get();
        $departments = Department::all();
        return view('doctors.edit', compact('doctor', 'users', 'departments'));
    }

    public function update(Request $request, Doctor $doctor)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
        ]);
        $doctor->update($request->only('user_id', 'department_id', 'is_available'));
        return redirect()->route('doctors.index')->with('success', 'Doctor updated.');
    }

    public function destroy(Doctor $doctor)
    {
        $doctor->delete();
        return redirect()->route('doctors.index')->with('success', 'Doctor deleted.');
    }
}