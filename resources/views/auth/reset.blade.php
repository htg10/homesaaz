@extends('layouts.app')

@section('title', 'HomeSaaz | Reset Password')
@section('body_class', 'hs-auth-page')

@section('content')
<div class="hs-card hs-auth-card">
    <div class="hs-logo">
        <img src="{{ asset('assets/images/homesaaz-logo.png') }}" alt="HomeSaaz">
    </div>

    <div class="hs-auth-box">
        <h2 class="hs-form-title">Reset Password</h2>
        <p class="hs-form-subtitle">Enter your new password below</p>

        <form method="POST" action="{{ route('password.update') }}" autocomplete="off">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="hs-field">
                <label>Email <sup>*</sup></label>
                <input type="email" name="email" value="{{ old('email') }}" 
                       class="@error('email') hs-input-error @enderror"
                       placeholder="your@email.com" required autocomplete="off">
                @error('email')
                    <span class="hs-error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="hs-field">
                <label>New Password <sup>*</sup></label>
                <input type="password" name="password" 
                       class="@error('password') hs-input-error @enderror"
                       placeholder="••••••••" required autocomplete="new-password">
                @error('password')
                    <span class="hs-error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="hs-field">
                <label>Confirm Password <sup>*</sup></label>
                <input type="password" name="password_confirmation" 
                       placeholder="••••••••" required autocomplete="new-password">
            </div>

            <button type="submit" class="hs-submit">Reset Password</button>
        </form>

        <div class="hs-auth-links">
            <p><a href="{{ route('login') }}">Back to Login</a></p>
        </div>
    </div>
</div>
@endsection