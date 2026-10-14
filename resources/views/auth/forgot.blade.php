@extends('layouts.app')

@section('title', 'HomeSaaz | Forgot Password')
@section('body_class', 'hs-auth-page hs-auth-with-side-image')

@section('content')
<div class="hs-auth-container">
    {{-- Left side: image (hidden on mobile, visible on desktop) --}}
    <!-- <div class="hs-auth-image-side">
        <div class="hs-auth-image-wrap">
            <img src="{{ asset('assets/images/homesaaz-logo.png') }}" alt="HomeSaaz">
        </div>
    </div> -->

    {{-- Right side: form card --}}
    <div class="hs-auth-form-side">
        <div class="hs-card hs-auth-card">
            <div class="hs-logo">
                <img src="{{ asset('assets/images/homesaaz-logo.png') }}" alt="HomeSaaz">
            </div>

            <div class="hs-auth-box">
                <h2 class="hs-form-title">Forgot Password</h2>
                <p class="hs-form-subtitle">Enter your email to receive a password reset link</p>

                <form method="POST" action="{{ route('password.email') }}" autocomplete="off">
                    @csrf
                    <div class="hs-field">
                        <label>Email <sup>*</sup></label>
                        <input type="email" name="email" class="@error('email') hs-input-error @enderror" 
                               placeholder="your@email.com" required autocomplete="off">
                        @error('email')
                            <span class="hs-error-text">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="hs-submit">Send Reset Link</button>
                </form>

                <div class="hs-auth-links">
                    <p><a href="{{ route('login') }}">Back to Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection