<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\SmsChannel;

trait PatientChannels
{
    protected function patientChannels(object $n, bool $mail = true, bool $sms = true): array
    {
        $via = ['database'];

        if ($mail && ($n->notify_email ?? true) && !empty($n->email)) {
            $via[] = 'mail';
        }
        if ($sms && ($n->notify_sms ?? true) && !empty($n->phone)) {
            $via[] = SmsChannel::class;
        }
        return $via;
    }
}