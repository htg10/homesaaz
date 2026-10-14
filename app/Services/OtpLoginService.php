<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Phone + OTP login helper used by the web and API AuthController.
 *
 *  - sendOtp()   : finds the active user by phone, stores a hashed OTP in cache, sends SMS
 *  - verifyOtp() : checks the OTP (attempt limit, expiry), returns the User on success
 *  - enforceSingleSession() : one login at a time (kicks out older web sessions and API tokens)
 */
class OtpLoginService
{
    /** Show OTP on screen only in local development with the flag on. */
    public static function debugOtpAllowed(): bool
    {
        return app()->environment('local') && (bool) config('auth_otp.debug_show_otp');
    }

    /** Normalise to the last 10 digits ("+91 98765-43210" -> "9876543210"). */
    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    public static function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^[6-9]\d{9}$/', $phone);
    }

    public static function findActiveUser(string $phone): ?User
    {
        $column = config('auth_otp.phone_column');

        // Stored numbers may carry +91 / spaces; compare on the last 10 digits.
        return User::query()
            ->where('IsActive', 1)
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(ISNULL(CAST([$column] AS VARCHAR(30)), ''), ' ', ''), '-', ''), '+', ''), 10) = ?", [$phone])
            ->first();
    }

    /** True when another user (not $exceptId) already has this number. */
    public static function phoneTaken(string $phone, ?int $exceptId = null): bool
    {
        $column = config('auth_otp.phone_column');

        $query = User::query()
            ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(ISNULL(CAST([$column] AS VARCHAR(30)), ''), ' ', ''), '-', ''), '+', ''), 10) = ?", [$phone]);

        if ($exceptId) {
            $query->where('ID', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Returns [ok(bool), message(string)].
     * The response is the same whether or not the number is registered, so the
     * form cannot be used to discover which numbers have accounts.
     */
    public static function sendOtp(string $phone): array
    {
        if (!self::isValidPhone($phone)) {
            return [false, 'Enter a valid 10-digit mobile number.'];
        }

        $rateKey = 'otp-send:' . $phone;
        if (RateLimiter::tooManyAttempts($rateKey, config('auth_otp.send_limit'))) {
            return [false, 'Too many OTP requests. Try again after some time.'];
        }

        $cooldownKey = 'otp-cooldown:' . $phone;
        if (Cache::has($cooldownKey)) {
            return [false, 'Please wait ' . config('auth_otp.resend_seconds') . ' seconds before requesting another OTP.'];
        }

        $user = self::findActiveUser($phone);
        Cache::put($cooldownKey, true, config('auth_otp.resend_seconds'));
        RateLimiter::hit($rateKey, config('auth_otp.send_window_min') * 60);

        if (!$user) {
            return [true, 'If this number is registered, an OTP has been sent.', null];
        }

        $length = (int) config('auth_otp.otp_length', 6);
        $otp = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        Cache::put('otp-login:' . $phone, [
            'hash'     => Hash::make($otp),
            'user_id'  => $user->ID,
            'attempts' => 0,
        ], now()->addMinutes((int) config('auth_otp.otp_ttl_minutes', 5)));

        try {
            SmsSender::send($phone, $otp);
        } catch (\Throwable $e) {
            Cache::forget('otp-login:' . $phone);
            Log::error('OTP SMS failed', ['phone' => substr($phone, -4), 'error' => $e->getMessage()]);
            return [false, 'Could not send OTP right now. Please try again.'];
        }

        $debugOtp = self::debugOtpAllowed() ? $otp : null;

        return [true, 'OTP sent to your mobile number.', $debugOtp];
    }

    /**
     * Returns [User|null, message(string)].
     */
    public static function verifyOtp(string $phone, string $otp): array
    {
        $key = 'otp-login:' . $phone;
        $data = Cache::get($key);

        if (!$data) {
            return [null, 'OTP expired or not requested. Request a new OTP.'];
        }

        $data['attempts']++;
        if ($data['attempts'] > (int) config('auth_otp.max_attempts', 3)) {
            Cache::forget($key);
            return [null, 'Too many wrong attempts. Request a new OTP.'];
        }

        if (!Hash::check(trim($otp), $data['hash'])) {
            Cache::put($key, $data, now()->addMinutes((int) config('auth_otp.otp_ttl_minutes', 5)));
            return [null, 'Invalid OTP.'];
        }

        // Success: OTP is single use.
        Cache::forget($key);
        Cache::forget('otp-cooldown:' . $phone);
        RateLimiter::clear('otp-send:' . $phone);

        $user = User::where('ID', $data['user_id'])->where('IsActive', 1)->first();
        if (!$user) {
            return [null, 'User not found or inactive.'];
        }

        return [$user, 'OK'];
    }

    /**
     * Only one active login per user, across web AND API.
     *  - Web: deletes every other web session of this user (keeps $keepSessionId).
     *  - API: deletes all web sessions (pass null) and revokes old tokens, so the
     *         new device is the only one logged in.
     * Call AFTER the new web session is regenerated.
     */
    public static function enforceSingleSession(User $user, ?string $keepSessionId = null): void
    {
        $query = DB::table('sessions')->where('user_id', $user->ID);

        if ($keepSessionId !== null) {
            $query->where('id', '!=', $keepSessionId);
        }

        $query->delete();
    }

    public static function revokeApiTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}