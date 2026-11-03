<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\QueueEntry;
use Carbon\Carbon;

final class AppointmentRules
{
    // ⚠ Your appointments table column names.
    // If you store ONE datetime column (e.g. 'scheduled_at'), put it in DATE_COL and set TIME_COL = null.
    public const DATE_COL = 'appointment_date';
    public const TIME_COL = 'appointment_time';

    public const CANCELLED       = 'cancelled';
    public const CLOSED          = ['cancelled', 'completed', 'checked_in', 'in_progress', 'no_show'];
    public const CUTOFF_HOURS    = 2;   // can change until 2 hours before
    public const MAX_RESCHEDULES = 2;

    // Slots offered on the reschedule page
    public const DAY_START    = '09:00';
    public const DAY_END      = '17:00';
    public const SLOT_MINUTES = 15;

    public static function slotAt(Appointment $a): ?Carbon
    {
        $date = $a->getAttribute(self::DATE_COL);
        if (!$date) return null;

        if (self::TIME_COL === null) {
            return Carbon::parse($date);
        }

        $time = $a->getAttribute(self::TIME_COL);
        return Carbon::parse(Carbon::parse($date)->toDateString() . ' ' . ($time ? substr((string) $time, 0, 5) : '00:00'));
    }

    /** Column values to save for a new date-time */
    public static function slotAttributes(Carbon $at): array
    {
        if (self::TIME_COL === null) {
            return [self::DATE_COL => $at->format('Y-m-d H:i:s')];
        }
        return [self::DATE_COL => $at->toDateString(), self::TIME_COL => $at->format('H:i:s')];
    }

    /** Returns null if the patient may change it, otherwise the reason */
    public static function cannotChange(Appointment $a, bool $forReschedule = false): ?string
    {
        if (in_array($a->status, self::CLOSED, true)) {
            return __('This appointment can no longer be changed.');
        }

        $slot = self::slotAt($a);
        if ($slot && now()->diffInMinutes($slot, false) < self::CUTOFF_HOURS * 60) {
            return __('Changes are allowed until :h hours before the appointment.', ['h' => self::CUTOFF_HOURS]);
        }

        if (QueueEntry::where('appointment_id', $a->id)->whereIn('status', ['waiting', 'in_progress'])->exists()) {
            return __('You have already checked in.');
        }

        if ($forReschedule && (int) $a->reschedule_count >= self::MAX_RESCHEDULES) {
            return __('You can reschedule an appointment at most :n times.', ['n' => self::MAX_RESCHEDULES]);
        }

        return null;
    }

    /** ['09:00' => true (free), '09:15' => false (taken/past), ...] */
    public static function slots(Doctor $doctor, Carbon $date, ?int $ignoreAppointmentId = null): array
    {
        $booked = Appointment::where('doctor_id', $doctor->id)
            ->whereDate(self::DATE_COL, $date->toDateString())
            ->where('status', '!=', self::CANCELLED)
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->get()
            ->map(fn ($a) => self::slotAt($a)?->format('H:i'))
            ->filter()
            ->all();

        $earliest = now()->addHours(self::CUTOFF_HOURS);
        $t   = Carbon::parse($date->toDateString() . ' ' . self::DAY_START);
        $end = Carbon::parse($date->toDateString() . ' ' . self::DAY_END);

        $out = [];
        while ($t->lt($end)) {
            $key = $t->format('H:i');
            $out[$key] = !in_array($key, $booked, true) && $t->gt($earliest);
            $t->addMinutes(self::SLOT_MINUTES);
        }
        return $out;
    }
}