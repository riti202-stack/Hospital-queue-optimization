<?php

namespace App\Notifications;

use App\Models\QueueEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PatientCalled extends Notification
{
    use Queueable;

    public function __construct(public QueueEntry $entry) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $room   = $this->entry->doctor?->room_no;
        $doctor = $this->entry->doctor?->user?->name ?? 'the doctor';

        return [
            'type'           => 'patient_called',
            'title'          => "It's your turn",
            'message'        => "Queue no. #{$this->entry->token_no}: please go to "
                                . ($room ? "Room {$room}" : "the doctor's room") . " now ({$doctor}).",
            'queue_entry_id' => $this->entry->id,
            'token_no'       => $this->entry->token_no,
            'room'           => $room,
            'doctor'         => $doctor,
        ];
    }
}