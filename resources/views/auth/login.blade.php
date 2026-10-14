@extends('layouts.app')

@section('title', 'HomeSaaz | Login')
@section('body_class', 'hs-login-page')

@section('content')
<div class="hs-card">
    <div class="hs-logo">
        <img src="{{ asset('assets/images/homesaaz-logo.png') }}" alt="HomeSaaz">
    </div>

    {{-- ================= ENTRY BUTTONS ================= --}}
    <div class="hs-actions" id="hsActions">
        <button type="button" class="hs-btn hs-btn--login" id="hsLoginBtn">Login</button>

        {{-- TODO: point this to the real supplier request route once built --}}
        <a href="#" class="hs-btn hs-btn--supplier" data-todo="supplier">Supplier Request</a>

        {{-- TODO: point this to the real customer request route once built --}}
        <a href="#" class="hs-btn hs-btn--customer" data-todo="customer">Customer Request</a>

        {{-- Reserved / blank button (green) — label + image to be provided later --}}
        <button type="button" class="hs-btn hs-btn--extra" disabled><span>Coming soon</span></button>
    </div>

    {{-- ================= LOGIN FORM (inline, hidden by default) ================= --}}
    <div class="hs-login-form" id="hsLoginForm">
        <div class="hs-form-title">Login</div>
        {{-- autocomplete off so the browser does NOT prefill saved email/password --}}
        @if(session()->has('login_phone'))
            {{-- Step 2: enter the OTP that was sent --}}
            <p class="hs-otp-note">OTP sent to ******{{ substr(session('login_phone'), -4) }}</p>
            @if(session('debug_otp'))
                <div style="background:#fff3cd;border:1px solid #ffe08a;padding:8px;border-radius:6px;margin-bottom:10px;font-size:13px;">
                    <strong>TEST MODE (local only):</strong> OTP = <strong>{{ session('debug_otp') }}</strong>
                </div>
            @endif
            <form method="POST" action="{{ route('login.otp.verify') }}" autocomplete="off">
                @csrf
                <div class="hs-field">
                    <label>OTP <sup>*</sup></label>
                    <input type="text" name="otp" inputmode="numeric" pattern="[0-9]*"
                           maxlength="{{ config('auth_otp.otp_length', 6) }}" autocomplete="one-time-code" required>
                </div>
                <div class="hs-row">
                    <a href="{{ route('login') }}">Change number</a>
                    <a href="{{ route('login') }}" onclick="event.preventDefault(); document.getElementById('hsResendForm').submit();">Resend OTP</a>
                </div>
                <button type="submit" class="hs-submit">VERIFY &amp; LOGIN</button>
            </form>
            <form method="POST" action="{{ route('login.otp.send') }}" id="hsResendForm" style="display:none">
                @csrf
                <input type="hidden" name="phone" value="{{ session('login_phone') }}">
            </form>
        @else
            {{-- Step 1: enter mobile number --}}
            <form method="POST" action="{{ route('login.otp.send') }}" autocomplete="off">
                @csrf
                <div class="hs-field">
                    <label>Mobile Number <sup>*</sup></label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" inputmode="numeric"
                           maxlength="15" autocomplete="off" required>
                </div>
                <button type="submit" class="hs-submit">SEND OTP</button>
            </form>
        @endif
        <button type="button" class="hs-back" id="hsBackBtn">&larr; Back</button>
    </div>
</div>
@endsection