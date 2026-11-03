<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Support\AppointmentRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatientAppointmentController extends Controller
{
    public function cancel(Request $request, Appointment $appointment)
    {
        $this->own($appointment);

        if ($why = AppointmentRules::cannotChange($appointment)) {
            return back()->with('error', $why);
        }

        $data = $request->validate(['cancel_reason' => 'nullable|string|max:255']);

        $appointment->forceFill([
            'status'        => AppointmentRules::CANCELLED,
            'cancelled_at'  => now(),
            'cancel_reason' => $data['cancel_reason'] ?? null,
        ])->save();

        return back()->with('success', __('Your appointment was cancelled.'));
    }

    public function rescheduleForm(Request $request, Appointment $appointment)
    {
        $this->own($appointment);

        if ($why = AppointmentRules::cannotChange($appointment, true)) {
            return redirect()->route('patient.appointments')->with('error', $why);
        }

        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : (AppointmentRules::slotAt($appointment)?->startOfDay() ?? today());

        if ($date->lt(today())) $date = today();
        if ($date->gt(today()->addDays(30))) $date = today()->addDays(30);

        $appointment->load('doctor');

        return view('patient.appointments.reschedule', [
            'appointment' => $appointment,
            'date'        => $date,
            'slots'       => AppointmentRules::slots($appointment->doctor, $date, $appointment->id),
            'left'        => AppointmentRules::MAX_RESCHEDULES - (int) $appointment->reschedule_count,
        ]);
    }

    public function reschedule(Request $request, Appointment $appointment)
    {
        $this->own($appointment);

        if ($why = AppointmentRules::cannotChange($appointment, true)) {
            return redirect()->route('patient.appointments')->with('error', $why);
        }

        $data = $request->validate([
            'date' => 'required|date|after_or_equal:today|before_or_equal:' . today()->addDays(30)->toDateString(),
            'time' => 'required|date_format:H:i',
        ]);

        $at = Carbon::parse($data['date'] . ' ' . $data['time']);

        DB::transaction(function () use ($appointment, $at, $data) {
            // lock this doctor's appointments for that day so two patients can't take the same slot
            Appointment::where('doctor_id', $appointment->doctor_id)
                ->whereDate(AppointmentRules::DATE_COL, $data['date'])
                ->lockForUpdate()->get();

            $slots = AppointmentRules::slots($appointment->doctor, $at->copy()->startOfDay(), $appointment->id);

            if (empty($slots[$data['time']])) {
                throw ValidationException::withMessages(['time' => __('That time is no longer available. Please choose another.')]);
            }

            $appointment->forceFill(AppointmentRules::slotAttributes($at) + [
                'reschedule_count' => (int) $appointment->reschedule_count + 1,
                'reminder_sent_at' => null,
            ])->save();
        });

        return redirect()->route('patient.appointments')
            ->with('success', __('Your appointment was rescheduled to :time.', ['time' => $at->format('D, d M Y · g:i A')]));
    }

    private function own(Appointment $a): void
    {
        abort_unless((int) $a->patient_id === auth()->id(), 403);
    }
}