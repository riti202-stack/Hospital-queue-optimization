<?php

namespace App\Notifications;

use App\Models\QueueEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConsultationCompleted extends Notification
{
    use Queueable;

    public function __construct(public QueueEntry $entry) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $doctor = $this->entry->doctor?->user?->name ?? 'your doctor';
        $dept   = $this->entry->department?->name;

        return [
            'type'           => 'consultation_completed',
            'title'          => 'Consultation completed',
            'message'        => "Your visit with {$doctor}" . ($dept ? " ({$dept})" : '') . ' is complete. Thank you for visiting.',
            'queue_entry_id' => $this->entry->id,
            'appointment_id' => $this->entry->appointment_id,
            'token_no'       => $this->entry->token_no,
        ];
    }
}