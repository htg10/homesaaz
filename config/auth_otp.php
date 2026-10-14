<?php

/*
|--------------------------------------------------------------------------
| OTP Login (phone number + SMS OTP, one active session per user)
|--------------------------------------------------------------------------
*/

return [

    // Column in Online_Tbl_Users that stores the login mobile number.
    'phone_column' => env('AUTH_PHONE_COLUMN', 'MobileNo'),

    // TESTING ONLY: show the OTP on the login page. Works only when APP_ENV=local,
    // so it can never show on the live (production) server.
    'debug_show_otp'    => env('AUTH_OTP_DEBUG_SHOW', false),

    'otp_length'        => 6,
    'otp_ttl_minutes'   => 5,     // OTP valid for this long
    'max_attempts'      => 3,     // wrong tries before OTP is burned
    'resend_seconds'    => 30,    // gap between two OTP sends to same number
    'send_limit'        => 5,     // max OTP sends per number ...
    'send_window_min'   => 15,    // ... within this many minutes

    /*
    | SMS gateway. driver:
    |   log  -> writes the OTP to the log (development / testing only)
    |   http -> GET request to SMS_API_URL with placeholders (see SmsSender)
    */
    'sms' => [
        'driver'      => env('SMS_DRIVER', 'log'),
        'url'         => env('SMS_API_URL'),          // e.g. provider send endpoint
        'api_key'     => env('SMS_API_KEY'),
        'sender_id'   => env('SMS_SENDER_ID'),        // 6-char DLT sender ID
        'template_id' => env('SMS_DLT_TEMPLATE_ID'),  // DLT template ID
        // Must match the approved DLT template text exactly, {otp} is the OTP.
        'template'    => 'Your OTP for login is {otp}. Do not share it with anyone.',
    ],
];