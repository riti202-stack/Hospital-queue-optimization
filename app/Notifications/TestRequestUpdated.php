<?php

namespace App\Notifications;

use App\Models\TestRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TestRequestUpdated extends Notification
{
    use Queueable;

    public function __construct(public TestRequest $req, public string $kind) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $r = $this->req;

        [$title, $message] = match ($this->kind) {
            'scheduled' => [
                'Your tests are booked',
                "{$r->code}: " . $r->scheduled_at->format('D, d M Y \a\t h:i A') . ", Room {$r->room_no}, with {$r->conducted_by}.",
            ],
            'suggested' => [
                'Sorry — please check a new time',
                "{$r->code}: " . \Illuminate\Support\Str::limit($r->admin_message, 250),
            ],
            default => ['Test request updated', "{$r->code} was updated."],
        };

        return [
            'type'            => 'test_update',
            'title'           => $title,
            'message'         => $message,
            'url'             => route('patient.tests.requests.show', $r),
            'test_request_id' => $r->id,
        ];
    }
}