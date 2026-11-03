<?php

namespace App\Notifications;

use App\Models\TestRequest;
use App\Notifications\Concerns\PatientChannels;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

class LabReportReady extends Notification
{
    use PatientChannels;

    public function __construct(public TestRequest $tr) {}

    public function via($notifiable): array
    {
        return $this->patientChannels($notifiable, mail: false);
    }

    private function code(): string
    {
        return 'LT-' . str_pad($this->tr->id, 5, '0', STR_PAD_LEFT);
    }

    public function toSms($notifiable): string
    {
        return 'Your lab report ' . $this->code() . ' is ready. Download it from your account. - ' . config('app.name');
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'test_update',
            'title'           => 'Lab report ready',
            'message'         => 'Your lab report for request #' . $this->code() . ' has been uploaded.',
            'test_request_id' => $this->tr->id,
            'url'             => Route::has('patient.tests.show')
                ? route('patient.tests.show', $this->tr)
                : route('lab-reports.download', $this->tr),
        ];
    }
}