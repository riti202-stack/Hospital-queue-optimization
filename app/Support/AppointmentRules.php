<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\QueueEntry;
use Carbon\Carbon;

final class AppointmentRules
{
    // Your appointments table: one datetime column + status 'booked'
    public const DATE_COL = 'scheduled_time';
    public const TIME_COL = null;

    public const CANCELLED       = 'cancelled';
    public const CLOSED          = ['cancelled', 'completed', 'checked_in', 'in_progress', 'no_show'];
    public const CUTOFF_HOURS    = 2;   // can change until 2 hours before
    public const MAX_RESCHEDULES = 2;

    // Same slots as the booking page: 9:00–1:00 and 2:00–5:00, every 15 minutes
    public const SESSIONS     = [[9, 13], [14, 17]];
    public const SLOT_MINUTES = 15;

    /** Date-time of the appointment */
    public static function slotAt(Appointment $a): ?Carbon
    {
        $value = $a->getAttribute(self::DATE_COL);
        return $value ? Carbon::parse($value) : null;
    }

    /** Column values to save for a new date-time */
    public static function slotAttributes(Carbon $at): array
    {
        return [self::DATE_COL => $at->format('Y-m-d H:i:s')];
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

    /** ['09:00' => true (free), '09:15' => false (taken or too soon), ...] */
    public static function slots(Doctor $doctor, Carbon $date, ?int $ignoreAppointmentId = null): array
    {
        $booked = Appointment::where('doctor_id', $doctor->id)
            ->whereDate(self::DATE_COL, $date->toDateString())
            ->whereIn('status', ['booked', 'completed'])
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '!=', $ignoreAppointmentId))
            ->pluck(self::DATE_COL)
            ->map(fn ($t) => Carbon::parse($t)->format('H:i'))
            ->all();

        $earliest = now()->addHours(self::CUTOFF_HOURS);
        $out = [];

        foreach (self::SESSIONS as [$from, $to]) {
            $t   = $date->copy()->setTime($from, 0);
            $end = $date->copy()->setTime($to, 0);
            while ($t->lt($end)) {
                $key = $t->format('H:i');
                $out[$key] = ! in_array($key, $booked, true) && $t->gt($earliest);
                $t->addMinutes(self::SLOT_MINUTES);
            }
        }

        return $out;
    }
}