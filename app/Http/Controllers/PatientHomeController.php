<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\QueueEntry;
use Carbon\Carbon;

class PatientHomeController extends Controller
{
    public function index()
    {
        $uid = auth()->id();

        $visibleDoctors = Doctor::query()
            ->where(fn ($q) => $q->whereNull('background_check_status')->orWhere('background_check_status', '!=', 'flagged'))
            ->where(fn ($q) => $q->whereNull('license_expiry')->orWhere('license_expiry', '>=', today()))
            ->get(['id', 'department_id', 'is_available']);

        $departments = Department::orderBy('name')->get()->map(function ($d) use ($visibleDoctors) {
            $docs = $visibleDoctors->where('department_id', $d->id);
            $d->doctor_count = $docs->count();
            $d->available_count = $docs->where('is_available', true)->count();
            return $d;
        });

        $avgWait = QueueEntry::whereDate('started_at', today())
            ->whereNotNull('started_at')
            ->get(['checked_in_at', 'started_at'])
            ->avg(fn ($e) => Carbon::parse($e->checked_in_at)->diffInMinutes(Carbon::parse($e->started_at)));

        $stats = [
            'departments' => $departments->where('doctor_count', '>', 0)->count(),
            'doctors'     => $visibleDoctors->count(),
            'onDuty'      => $visibleDoctors->where('is_available', true)->count(),
            'avgWait'     => $avgWait !== null ? (int) round($avgWait) : null,
            'served'      => QueueEntry::where('status', 'completed')
                                ->where('completed_at', '>=', now()->startOfMonth())->count(),
        ];

        $myQueue = QueueEntry::with('doctor.user', 'department')
            ->where('patient_id', $uid)
            ->whereIn('status', ['waiting', 'in_progress'])
            ->where('checked_in_at', '>=', today())
            ->first();

        $nextAppointment = Appointment::with('doctor.user', 'department')
            ->where('patient_id', $uid)
            ->where('status', 'booked')
            ->where('scheduled_time', '>=', today())
            ->orderBy('scheduled_time')
            ->first();

        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 17 => 'Good afternoon',
            default          => 'Good evening',
        };

        return view('patient.home', compact('departments', 'stats', 'myQueue', 'nextAppointment', 'greeting'));
    }
}