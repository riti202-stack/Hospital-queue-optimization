<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Support\AppointmentRules;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'reminders:appointments {--date= : Y-m-d (default: tomorrow)}';
    protected $description = 'Send reminders for appointments on the given day';

    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : today()->addDay();

        $appointments = Appointment::with(['doctor', 'dependent'])
            ->whereDate(AppointmentRules::DATE_COL, $date->toDateString())
            ->whereNotIn('status', AppointmentRules::CLOSED)
            ->whereNull('reminder_sent_at')
            ->get();

        $sent = 0;
        foreach ($appointments as $a) {
            $patient = User::find($a->patient_id);
            if (!$patient) continue;

            $patient->notify(new AppointmentReminder($a));
            $a->forceFill(['reminder_sent_at' => now()])->saveQuietly();
            $sent++;
        }

        $this->info("Sent {$sent} reminder(s) for {$date->toDateString()}.");
        return self::SUCCESS;
    }
}