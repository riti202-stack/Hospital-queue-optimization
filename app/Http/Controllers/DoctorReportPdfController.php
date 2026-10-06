<?php
namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class DoctorReportPdfController extends Controller
{
    public function form()
    {
        $departments = Department::all();
        return view('admin.doctor-report-pdf-form', compact('departments'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'date' => 'required|date',
            'include_contact' => 'nullable|boolean',
            'include_activity' => 'nullable|boolean',
            'include_appointments' => 'nullable|boolean',
        ]);

        $date = Carbon::parse($request->date);
        $includeContact = $request->boolean('include_contact');
        $includeActivity = $request->boolean('include_activity');
        $includeAppointments = $request->boolean('include_appointments');

        $departmentsQuery = Department::with(['doctors.user']);
        if ($request->department_id) {
            $departmentsQuery->where('id', $request->department_id);
        }
        $departments = $departmentsQuery->get();

        $activitySummary = null;
        $appointmentsToday = collect();

        if ($includeActivity || $includeAppointments) {
            $dayAppointments = Appointment::with(['patient', 'doctor.user', 'department'])
                ->whereDate('scheduled_time', $date)
                ->when($request->department_id, fn($q) => $q->where('department_id', $request->department_id))
                ->orderBy('scheduled_time')
                ->get();

            if ($includeAppointments) {
                $appointmentsToday = $dayAppointments;
            }

            if ($includeActivity) {
                $morning = $dayAppointments->filter(fn($a) => Carbon::parse($a->scheduled_time)->hour < 12);
                $afternoon = $dayAppointments->filter(fn($a) => Carbon::parse($a->scheduled_time)->hour >= 12);

                $availableQuery = Doctor::where('is_available', true);
                if ($request->department_id) {
                    $availableQuery->where('department_id', $request->department_id);
                }

                $activitySummary = [
                    'marked_available' => $availableQuery->count(),
                    'morning_active_doctors' => $morning->pluck('doctor_id')->unique()->count(),
                    'afternoon_active_doctors' => $afternoon->pluck('doctor_id')->unique()->count(),
                    'total_appointments' => $dayAppointments->count(),
                    'morning_appointments' => $morning->count(),
                    'afternoon_appointments' => $afternoon->count(),
                ];
            }
        }

        $pdf = Pdf::loadView('admin.doctor-report-pdf', compact(
            'departments', 'date', 'includeContact', 'includeActivity',
            'includeAppointments', 'activitySummary', 'appointmentsToday'
        ));

        $filename = 'doctor-report-' . $date->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}