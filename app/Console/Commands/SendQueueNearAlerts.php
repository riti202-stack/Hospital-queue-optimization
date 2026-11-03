<?php

namespace App\Console\Commands;

use App\Models\QueueEntry;
use App\Models\User;
use App\Notifications\QueueNearNotification;
use App\Services\SchedulerService;
use Illuminate\Console\Command;

class SendQueueNearAlerts extends Command
{
    protected $signature = 'reminders:queue {--ahead=3 : alert when this many or fewer patients are ahead}';
    protected $description = 'Alert waiting patients whose turn is near';

    public function handle(SchedulerService $scheduler): int
    {
        $limit = (int) $this->option('ahead');

        $doctorIds = QueueEntry::where('status', 'waiting')
            ->whereDate('checked_in_at', today())
            ->whereNotNull('doctor_id')
            ->distinct()->pluck('doctor_id');

        $sent = 0;
        foreach ($doctorIds as $doctorId) {
            $queue = collect($scheduler->getOrderedQueue(null, $doctorId))->values();

            foreach ($queue as $ahead => $entry) {
                if ($ahead > $limit) break;
                if ($entry->near_alert_sent_at) continue;

                User::find($entry->patient_id)?->notify(new QueueNearNotification($entry, $ahead));
                $entry->forceFill(['near_alert_sent_at' => now()])->saveQuietly();
                $sent++;
            }
        }

        $this->info("Sent {$sent} queue alert(s).");
        return self::SUCCESS;
    }
}
