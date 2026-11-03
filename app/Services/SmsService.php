<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(?string $phone, string $message): bool
    {
        $to = $this->normalize($phone);
        if (!$to) return false;

        $cfg = config('services.sms');

        // Default: no real SMS, just log it (good for development / demo)
        if (($cfg['driver'] ?? 'log') !== 'http' || empty($cfg['url'])) {
            Log::info("[SMS to {$to}] {$message}");
            return true;
        }

        try {
            // Parameter names differ between providers — match your provider's API docs.
            $response = Http::timeout(10)->asForm()->post($cfg['url'], [
                'api_key'  => $cfg['api_key'],
                'senderid' => $cfg['sender_id'],
                'number'   => $to,
                'message'  => $message,
                'type'     => 'text',
            ]);

            if ($response->failed()) {
                Log::warning('SMS failed', ['to' => $to, 'body' => $response->body()]);
            }
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('SMS error: ' . $e->getMessage());
            return false;
        }
    }

    /** 01XXXXXXXXX → 8801XXXXXXXXX */
    private function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        if (preg_match('/^01\d{9}$/', $digits))   return '88' . $digits;
        if (preg_match('/^8801\d{9}$/', $digits)) return $digits;
        return null;
    }
}