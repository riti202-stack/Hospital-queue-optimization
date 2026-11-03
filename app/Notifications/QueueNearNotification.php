<?php

namespace App\Notifications;

use App\Models\QueueEntry;
use App\Notifications\Concerns\PatientChannels;
use Illuminate\Notifications\Notification;

class QueueNearNotification extends Notification
{
    use PatientChannels;

    public function __construct(public QueueEntry $entry, public int $ahead) {}

    public function via($notifiable): array
    {
        return $this->patientChannels($notifiable, mail: false);
    }

    private function text(): string
    {
        return $this->ahead === 0
            ? "Token {$this->entry->token_no}: you are next. Please stay near the doctor's room."
            : "Token {$this->entry->token_no}: {$this->ahead} patient(s) ahead of you. Please come to the waiting area.";
    }

    public function toSms($notifiable): string
    {
        return $this->text() . ' - ' . config('app.name');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'     => 'queue_near',
            'title'    => 'Your turn is near',
            'message'  => $this->text(),
            'entry_id' => $this->entry->id,
            'url'      => route('patient.queue-status'),
        ];
    }
}