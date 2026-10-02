<?php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AppointmentPdfController extends Controller
{
    public function form()
    {
        $doctors = Doctor::with('user', 'department')->get();
        return view('admin.appointment-pdf-form', compact('doctors'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'date' => 'required|date',
        ]);

        $doctor = Doctor::with('user', 'department')->findOrFail($request->doctor_id);

        $appointments = Appointment::with('patient')
            ->where('doctor_id', $doctor->id)
            ->whereDate('scheduled_time', $request->date)
            ->orderBy('scheduled_time')
            ->get();

        $date = Carbon::parse($request->date);

        $pdf = Pdf::loadView('admin.appointment-pdf', compact('doctor', 'appointments', 'date'));

        $filename = 'appointments-' . str($doctor->user->name)->slug() . '-' . $date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }
}