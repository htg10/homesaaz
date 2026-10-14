<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\OtpLoginService;

class AuthController extends Controller
{
    /**
     * Step 1: send OTP to mobile number.
     * POST /auth/otp/send  { "phone": "9876543210" }
     */
    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $phone = OtpLoginService::normalizePhone($request->input('phone'));
        [$ok, $message, $debugOtp] = OtpLoginService::sendOtp($phone);

        $body = ['success' => $ok, 'message' => $message];
        if ($ok && $debugOtp) {
            $body['debug_otp'] = $debugOtp; // local testing only
        }

        return response()->json($body, $ok ? 200 : 422);
    }

    /**
     * Step 2: verify OTP, return Bearer token. Older tokens of this user are revoked,
     * so only one login is active at a time.
     * POST /auth/otp/verify  { "phone": "9876543210", "otp": "123456" }
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'otp'   => 'required|digits:' . config('auth_otp.otp_length', 6),
        ]);

        $phone = OtpLoginService::normalizePhone($request->input('phone'));
        [$user, $message] = OtpLoginService::verifyOtp($phone, (string) $request->input('otp'));

        if (!$user) {
            return response()->json(['success' => false, 'message' => $message], 401);
        }

        // Only this device stays logged in: kill web sessions and older tokens.
        OtpLoginService::enforceSingleSession($user);
        OtpLoginService::revokeApiTokens($user);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id'        => $user->ID,
                'firstname' => $user->FirstName,
                'lastname'  => $user->LastName ?? null,
                'email'     => $user->EmailID ?? null,
                'role'      => $user->user_role ?? null,
            ],
        ]);
    }

    /**
     * API Logout - Revoke Token
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Current User
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ], 200);
    }

    /**
     * Send Password Reset Link
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to email'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to send password reset link'
        ], 400);
    }

    /**
     * Reset Password with Token
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Password reset failed'
        ], 400);
    }

    /**
     * Change Password (Authenticated)
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password ?? $user->Pwd)) {
                        $fail('The current password is incorrect.');
                    }
                },
            ],
            'new_password' => ['required', 'string', 'min:8', 'max:12', 'confirmed'],
            'new_password_confirmation' => 'required',
        ]);

        try {
            $user->update([
                'password' => Hash::make($request->new_password)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password: ' . $e->getMessage()
            ], 500);
        }
    }
}