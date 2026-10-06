<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DoctorProfilePdfController extends Controller
{
    // Page with Department -> Doctor dropdowns
    public function form()
    {
        $departments = Department::with('doctors.user')->orderBy('name')->get();

        // { department_id: [ {id, name, room}, ... ] } for the dependent dropdown
        $doctorMap = $departments->mapWithKeys(fn ($dept) => [
            $dept->id => $dept->doctors->map(fn ($doc) => [
                'id'   => $doc->id,
                'name' => $doc->user->name ?? 'Doctor #' . $doc->id,
                'room' => $doc->room_no,
            ])->values(),
        ]);

        return view('admin.doctor-profile-pdf-form', compact('departments', 'doctorMap'));
    }

    // Form submit
    public function generate(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'doctor_id'     => 'required|exists:doctors,id',
            'from'          => 'nullable|date',
            'to'            => 'nullable|date|after_or_equal:from',
        ]);

        $doctor = Doctor::findOrFail($request->doctor_id);

        if ((int) $doctor->department_id !== (int) $request->department_id) {
            return back()->withErrors(['doctor_id' => 'This doctor is not in the selected department.'])->withInput();
        }

        return $this->buildPdf(
            $doctor,
            $request->from ?: now()->startOfMonth(),
            $request->to ?: now()->endOfMonth(),
            $request->boolean('include_contact'),
            $request->boolean('include_appointments')
        );
    }

    // "Download PDF" button on a doctor's profile page (current month, everything included)
    public function quick(Doctor $doctor)
    {
        return $this->buildPdf($doctor, now()->startOfMonth(), now()->endOfMonth(), true, true);
    }

    private function buildPdf(Doctor $doctor, $from, $to, bool $includeContact, bool $includeAppointments)
    {
        $doctor->load('user', 'department', 'qualifications');

        $from = Carbon::parse($from)->startOfDay();
        $to   = Carbon::parse($to)->endOfDay();

        $appointments = Appointment::with('patient')
            ->where('doctor_id', $doctor->id)
            ->whereBetween('scheduled_time', [$from, $to])
            ->orderBy('scheduled_time')
            ->get();

        $counts = $appointments->countBy('status');
        $stats = [
            'total'     => $appointments->count(),
            'booked'    => $counts['booked'] ?? 0,
            'completed' => $counts['completed'] ?? 0,
            'cancelled' => $counts['cancelled'] ?? 0,
            'no_show'   => $counts['no-show'] ?? 0,
            'morning'   => $appointments->filter(fn ($a) => Carbon::parse($a->scheduled_time)->hour < 12)->count(),
            'afternoon' => $appointments->filter(fn ($a) => Carbon::parse($a->scheduled_time)->hour >= 12)->count(),
        ];

        // Photo embedded as base64 (DomPDF cannot load /storage URLs)
        $photo = null;
        if ($doctor->photo && Storage::disk('public')->exists($doctor->photo)) {
            $mime  = Storage::disk('public')->mimeType($doctor->photo);
            $photo = 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($doctor->photo));
        }

        $age = $doctor->date_of_birth ? Carbon::parse($doctor->date_of_birth)->age : null;

        $licenseStatus = null;
        if ($doctor->license_expiry) {
            $expiry = Carbon::parse($doctor->license_expiry);
            $licenseStatus = $expiry->isPast() ? 'expired'
                : ($expiry->diffInDays(now()) <= 30 ? 'expiring' : 'valid');
        }

        $pdf = Pdf::loadView('admin.doctor-profile-pdf', compact(
            'doctor', 'appointments', 'stats', 'from', 'to',
            'includeContact', 'includeAppointments', 'photo', 'age', 'licenseStatus'
        ))->setPaper('a4');

        $name = Str::slug($doctor->user->name ?? 'doctor-' . $doctor->id);
        return $pdf->download("{$name}-profile-{$from->format('Y-m-d')}-to-{$to->format('Y-m-d')}.pdf");
    }
}