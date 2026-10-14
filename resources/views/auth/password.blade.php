@extends('layouts.app')

@section('title', 'HomeSaaz | Change Password')
@section('body_class', 'hs-auth-page')

@section('content')
<div class="hs-card hs-auth-card">
    <div class="hs-logo">
        <img src="{{ asset('assets/images/homesaaz-logo.png') }}" alt="HomeSaaz">
    </div>

    <div class="hs-auth-box">
        <h2 class="hs-form-title">Change Password</h2>
        <p class="hs-form-subtitle">Update your account password</p>

        <form method="POST" action="{{ route('password.change') }}" autocomplete="off">
            @csrf

            <div class="hs-field">
                <label>Current Password <sup>*</sup></label>
                <input type="password" name="current_password" 
                       class="@error('current_password') hs-input-error @enderror"
                       placeholder="••••••••" required autocomplete="current-password">
                @error('current_password')
                    <span class="hs-error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="hs-field">
                <label>New Password <sup>*</sup></label>
                <input type="password" name="new_password" 
                       class="@error('new_password') hs-input-error @enderror"
                       placeholder="••••••••" required autocomplete="new-password">
                @error('new_password')
                    <span class="hs-error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="hs-field">
                <label>Confirm New Password <sup>*</sup></label>
                <input type="password" name="new_password_confirmation" 
                       placeholder="••••••••" required autocomplete="new-password">
            </div>

            <button type="submit" class="hs-submit">Update Password</button>
        </form>

        <div class="hs-auth-links">
            <p><a href="{{ route('dashboard') }}">Back to Dashboard</a></p>
        </div>
    </div>
</div>
@endsection