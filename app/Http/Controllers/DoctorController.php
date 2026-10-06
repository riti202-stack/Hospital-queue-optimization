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
        $departments = Department::with(['doctors.user'])->get();
        return view('doctors.index',compact('departments'));
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

    $photoPath = null;
    if($request->hasFile('photo'))
        {
            $photoPath = $request->file('photo')->store('doctor-photos','public');
        }

    $doctor = Doctor::create([
        ...$request->only([
            'user_id', 'department_id', 'room_no',
            'date_of_birth', 'gender', 'personal_phone', 'residential_address',
            'emergency_contact_name', 'emergency_contact_phone',
            'license_number', 'license_issuing_body', 'license_expiry',
            'specialization', 'sub_specialty', 'employment_type', 'date_of_joining',
            'working_hours', 'fee_share_percent', 'malpractice_insurance_policy_no',
            'background_check_status',
        ]),

        'photo'=>$photoPath,
        'is_available'=>$request->has('is_available'),
    ]);

    if($request->has('degree'))
        {
            foreach($request->degree as $i =>$degree)
                {
                    if($degree)
                        {
                            $doctor->qualifications()->create([
                                'degree'=>$degree,
                                'institution'=>$request->institution[$i],
                                'passing_year'=>$request->passing_year[$i],

                            ]);
                        }
                }
        }

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

        $photoPath = $doctor->photo;
        if($request->hasFile('photo'))
            {
                $photoPath = $request->file('photo')->store('doctor-photos','public');
            }

            $doctor->update([
        ...$request->only([
            'user_id', 'department_id', 'room_no',
            'date_of_birth', 'gender', 'personal_phone', 'residential_address',
            'emergency_contact_name', 'emergency_contact_phone',
            'license_number', 'license_issuing_body', 'license_expiry',
            'specialization', 'sub_specialty', 'employment_type', 'date_of_joining',
            'working_hours', 'fee_share_percent', 'malpractice_insurance_policy_no',
            'background_check_status',
        ]),

        'photo'=>$photoPath,
        'is_available'=>$request->has('is_available'),

            ]);

            return redirect()->route('doctors.index')->with('success','Doctor updated.');
        
    }

    public function show(Doctor $doctor)
    {
        $doctor->load('user','department','qualifications');
        return view('doctors.show',compact('doctor'));
    }

    public function destroy(Doctor $doctor)
    {
        $doctor->delete();
        return redirect()->route('doctors.index')->with('success', 'Doctor deleted.');
    }
}