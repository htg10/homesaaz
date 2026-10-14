<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Sends the OTP SMS. Provider details come from config/auth_otp.php (sms.*).
 *
 * http driver: GET {SMS_API_URL} with these placeholders replaced in the URL:
 *   {mobile} {message} {sender} {template_id} {api_key}
 * Adjust the parameter names to your gateway's API docs (MSG91, Textlocal,
 * Fast2SMS and others all take a similar GET/POST call).
 */
class SmsSender
{
    public static function send(string $phone, string $otp): void
    {
        $cfg = config('auth_otp.sms');
        $message = str_replace('{otp}', $otp, $cfg['template']);

        if (($cfg['driver'] ?? 'log') === 'log') {
            // Dev only: never use the log driver in production.
            Log::info('[OTP-LOG-DRIVER] phone=' . substr($phone, -4) . ' otp=' . $otp);
            return;
        }

        if (empty($cfg['url'])) {
            throw new RuntimeException('SMS_API_URL is not set.');
        }

        $url = strtr($cfg['url'], [
            '{mobile}'      => '91' . $phone,
            '{message}'     => rawurlencode($message),
            '{sender}'      => rawurlencode((string) $cfg['sender_id']),
            '{template_id}' => rawurlencode((string) $cfg['template_id']),
            '{api_key}'     => rawurlencode((string) $cfg['api_key']),
        ]);

        $response = Http::timeout(10)->get($url);

        if ($response->failed()) {
            throw new RuntimeException('SMS gateway returned HTTP ' . $response->status());
        }
    }
}