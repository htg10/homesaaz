<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\OtpLoginService;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    /** Step 1: phone number -> OTP sent. */
    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $phone = OtpLoginService::normalizePhone($request->input('phone'));
        [$ok, $message, $debugOtp] = OtpLoginService::sendOtp($phone);

        if (!$ok) {
            return back()->withInput()->with('error', $message);
        }

        $request->session()->put('login_phone', $phone);

        $response = back()->with('success', $message);
        if ($debugOtp) {
            $response = $response->with('debug_otp', $debugOtp); // local testing only
        }

        return $response;
    }

    /** Step 2: OTP -> login, one session per user. */
    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:' . config('auth_otp.otp_length', 6)]);

        $phone = (string) $request->session()->get('login_phone', '');
        if ($phone === '') {
            return redirect()->route('login')->with('error', 'Please request an OTP first.');
        }

        [$user, $message] = OtpLoginService::verifyOtp($phone, (string) $request->input('otp'));

        if (!$user) {
            return back()->with('error', $message);
        }

        // Log in, rotate the session id (prevents session fixation) and kick out other sessions.
        Auth::login($user);
        $request->session()->regenerate();
        OtpLoginService::enforceSingleSession($user, $request->session()->getId());
        OtpLoginService::revokeApiTokens($user);
        $request->session()->forget('login_phone');

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login')->with('success', 'You have been logged out.');
    }

    public function showLinkRequestForm()
    {
        return view('auth.forgot');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink(
            $request->only('email')
        );
        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('success', __($status));
        } else {
            return back()->with('error', __($status));
        }
    }

    public function showResetForm($token)
    {
        return view('auth.reset', ['token' => $token]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
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
            return redirect()->route('login')->with('success', __($status));
        } else {
            return back()->with('error', __($status));
        }
    }

    public function passwordForm()
    {
        $title = 'Change Password';
        return view('auth.password', compact('title'));
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) {
                        $fail('The current password is incorrect.');
                    }
                },
            ],
            'new_password' => ['required', 'string', 'min:8', 'max:12', 'regex:/[A-Z]/', 'confirmed',],
            'new_password_confirmation' => 'required',
        ]);
        $user->password = Hash::make($request->new_password);
        $user->save();
        return redirect()->route('dashboard')->with('success', 'Password changed successfully.');
    }
}