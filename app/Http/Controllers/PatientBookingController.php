<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\QueueEntry;
use App\Services\SchedulerService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PatientBookingController extends Controller
{
    public function __construct(protected SchedulerService $scheduler) {}

    /** Doctors patients may see: background not flagged, license not expired */
    private function visible($query)
    {
        return $query
            ->where(fn ($q) => $q->whereNull('background_check_status')->orWhere('background_check_status', '!=', 'flagged'))
            ->where(fn ($q) => $q->whereNull('license_expiry')->orWhere('license_expiry', '>=', today()));
    }

    // ===================== Shared data =====================

    private function departmentList()
    {
        return Department::orderBy('name')->get()->map(function ($d) {
            $docs = $this->visible(Doctor::where('department_id', $d->id))
                ->get(['id', 'is_available', 'sub_specialty']);

            $d->doctor_count    = $docs->count();
            $d->available_count = $docs->where('is_available', true)->count();
            $d->services        = $docs->pluck('sub_specialty')->filter()->unique()->take(4)->values();

            return $d;
        });
    }

    private function doctorList(Department $department)
    {
        $waiting = QueueEntry::where('department_id', $department->id)
            ->where('status', 'waiting')
            ->where('checked_in_at', '>=', today())
            ->whereNotNull('doctor_id')
            ->selectRaw('doctor_id, count(*) as c')
            ->groupBy('doctor_id')
            ->pluck('c', 'doctor_id');

        return $this->visible(Doctor::with(['user', 'qualifications'])->where('department_id', $department->id))
            ->get()
            ->map(function ($d) use ($waiting) {
                $firstDegree = $d->qualifications->min('passing_year');
                $d->experience_years = $firstDegree
                    ? max(0, now()->year - (int) $firstDegree)
                    : ($d->date_of_joining ? (int) Carbon::parse($d->date_of_joining)->diffInYears(now()) : null);

                $d->degrees      = $d->qualifications->sortBy('passing_year')->pluck('degree')->join(', ');
                $d->waiting_now  = (int) ($waiting[$d->id] ?? 0);
                $d->wait_minutes = $d->waiting_now * $this->scheduler->averageConsultMinutes($d->id);

                return $d;
            })
            ->sortBy(fn ($d) => [$d->is_available ? 0 : 1, $d->user->name ?? ''])
            ->values();
    }

    /** Already in a queue today? Send them to My Queue */
    private function alreadyQueued()
    {
        $entry = QueueEntry::where('patient_id', auth()->id())
            ->whereIn('status', ['waiting', 'in_progress'])
            ->where('checked_in_at', '>=', today())
            ->first();

        return $entry
            ? redirect()->route('patient.queue-status')
                ->with('success', "You are already checked in (queue no. #{$entry->token_no}).")
            : null;
    }

    private function todaysAppointment()
    {
        return Appointment::with(['doctor.user', 'department'])
            ->where('patient_id', auth()->id())
            ->where('status', 'booked')
            ->whereDate('scheduled_time', today())
            ->orderBy('scheduled_time')
            ->first();
    }

    private function ensureVisible(Doctor $doctor): void
    {
        abort_unless($this->visible(Doctor::whereKey($doctor->id))->exists(), 404);
    }

    // ===================== BOOKING =====================

    public function departments()
    {
        return view('patient.booking.departments', [
            'departments' => $this->departmentList(),
            'mode'        => 'book',
        ]);
    }

    public function doctors(Department $department)
    {
        return view('patient.booking.doctors', [
            'department' => $department,
            'doctors'    => $this->doctorList($department),
            'mode'       => 'book',
        ]);
    }

    public function bookForm(Request $request, Doctor $doctor)
    {
        $this->ensureVisible($doctor);
        $doctor->load('user', 'department');

        $date = $this->pickDate($request->input('date'));

        $booked = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('scheduled_time', $date)
            ->whereIn('status', ['booked', 'completed'])
            ->pluck('scheduled_time')
            ->map(fn ($t) => Carbon::parse($t)->format('H:i'))
            ->all();

        $slots = [];
        foreach ([[9, 13, 'Morning'], [14, 17, 'Afternoon']] as [$from, $to, $period]) {
            for ($t = $date->copy()->setTime($from, 0); $t->lt($date->copy()->setTime($to, 0)); $t->addMinutes(15)) {
                $slots[$period][] = [
                    'time'  => $t->format('H:i'),
                    'label' => $t->format('h:i A'),
                    'free'  => ! in_array($t->format('H:i'), $booked) && $t->isFuture(),
                ];
            }
        }

        $freeCount = collect($slots)->flatten(1)->where('free', true)->count();

        return view('patient.booking.book', compact('doctor', 'date', 'slots', 'freeCount'));
    }

    public function bookStore(Request $request, Doctor $doctor)
    {
        $this->ensureVisible($doctor);

        $request->validate([
            'date' => 'required|date|after_or_equal:today|before_or_equal:' . today()->addDays(30)->toDateString(),
            'time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
        ], ['time.required' => 'Please choose a time slot.']);

        $at = Carbon::parse($request->date . ' ' . $request->time);

        if (! $doctor->is_available) {
            return back()->withErrors(['time' => 'This doctor is not taking bookings right now.']);
        }
        if ($at->isPast()) {
            return back()->withErrors(['time' => 'That time has already passed. Please choose a later slot.']);
        }
        if (Appointment::where('doctor_id', $doctor->id)->where('scheduled_time', $at)
                ->whereIn('status', ['booked', 'completed'])->exists()) {
            return back()->withErrors(['time' => 'Sorry, that slot was just taken. Please choose another.'])->withInput();
        }
        if (Appointment::where('patient_id', auth()->id())->where('doctor_id', $doctor->id)
                ->whereDate('scheduled_time', $at)->where('status', 'booked')->exists()) {
            return back()->withErrors(['time' => 'You already have a booking with this doctor on that day.']);
        }

        Appointment::create([
            'patient_id'     => auth()->id(),
            'doctor_id'      => $doctor->id,
            'department_id'  => $doctor->department_id,
            'scheduled_time' => $at,
            'status'         => 'booked',
        ]);

        return redirect()->route('patient.appointments')->with('success',
            'Booked with ' . ($doctor->user->name ?? 'the doctor') . ' on ' . $at->format('D, d M Y \a\t h:i A') . '.');
    }

    private function pickDate(?string $input): Carbon
    {
        try {
            $date = $input ? Carbon::parse($input)->startOfDay() : null;
        } catch (\Throwable $e) {
            $date = null;
        }

        if (! $date || $date->lt(today()) || $date->gt(today()->addDays(30))) {
            $date = now()->hour >= 17 ? today()->addDay() : today();
        }

        return $date;
    }

    // ===================== WALK-IN CHECK-IN =====================

    public function checkinDepartments()
    {
        if ($r = $this->alreadyQueued()) return $r;

        return view('patient.booking.departments', [
            'departments'      => $this->departmentList(),
            'mode'             => 'checkin',
            'todayAppointment' => $this->todaysAppointment(),
        ]);
    }

    public function checkinDoctors(Department $department)
    {
        if ($r = $this->alreadyQueued()) return $r;

        $unassignedWaiting = QueueEntry::where('department_id', $department->id)
            ->where('status', 'waiting')
            ->where('checked_in_at', '>=', today())
            ->whereNull('doctor_id')
            ->count();

        return view('patient.booking.doctors', [
            'department'        => $department,
            'doctors'           => $this->doctorList($department),
            'mode'              => 'checkin',
            'unassignedWaiting' => $unassignedWaiting,
        ]);
    }

    public function checkinConfirm(Department $department, ?Doctor $doctor = null)
    {
        if ($r = $this->alreadyQueued()) return $r;

        if ($doctor) {
            abort_unless($doctor->department_id === $department->id, 404);
            $this->ensureVisible($doctor);
            if (! $doctor->is_available) {
                return redirect()->route('patient.checkin.doctors', $department)
                    ->withErrors(['doctor' => 'This doctor is not seeing patients right now.']);
            }
            $doctor->load('user');
            $waitingNow = QueueEntry::where('doctor_id', $doctor->id)->where('status', 'waiting')
                ->where('checked_in_at', '>=', today())->count();
            $waitMinutes = $waitingNow * $this->scheduler->averageConsultMinutes($doctor->id);
        } else {
            $waitingNow = QueueEntry::where('department_id', $department->id)->whereNull('doctor_id')
                ->where('status', 'waiting')->where('checked_in_at', '>=', today())->count();
            $waitMinutes = null;
        }

        return view('patient.booking.checkin-confirm', compact('department', 'doctor', 'waitingNow', 'waitMinutes'));
    }

    public function checkinStore(Request $request)
    {
        if ($r = $this->alreadyQueued()) return $r;

        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'doctor_id'     => 'nullable|exists:doctors,id',
            'urgency_level' => 'required|in:emergency,urgent,routine',
        ], ['urgency_level.required' => 'Please tell us how urgent your condition is.']);

        $doctor = null;
        if (! empty($data['doctor_id'])) {
            $doctor = Doctor::findOrFail($data['doctor_id']);
            $this->ensureVisible($doctor);
            if ((int) $doctor->department_id !== (int) $data['department_id'] || ! $doctor->is_available) {
                return back()->withErrors(['doctor_id' => 'This doctor is not available for check-in.']);
            }
        }

        // If they have a booking today with this doctor, link it
        $appointmentId = $doctor
            ? Appointment::where('patient_id', auth()->id())->where('doctor_id', $doctor->id)
                ->where('status', 'booked')->whereDate('scheduled_time', today())->value('id')
            : null;

        $entry = $this->scheduler->checkIn(
            auth()->id(), (int) $data['department_id'], $doctor?->id, $data['urgency_level'], $appointmentId
        );

        return redirect()->route('patient.queue-status')
            ->with('success', "You are checked in. Your queue number is #{$entry->token_no}.");
    }
}