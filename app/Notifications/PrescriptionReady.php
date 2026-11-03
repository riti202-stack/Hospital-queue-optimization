<?php

namespace App\Notifications;

use App\Models\Prescription;
use Illuminate\Notifications\Notification;

class PrescriptionReady extends Notification
{
    public function __construct(public Prescription $rx) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'    => 'prescription_ready',
            'title'   => 'Prescription ready',
            'message' => $this->rx->doctor?->displayName() . ' has written your prescription (' . $this->rx->code() . ').',
            'url'     => route('patient.records.prescription', $this->rx),
        ];
    }
}