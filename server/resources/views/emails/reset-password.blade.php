@extends('emails.layout')

@php $preheader = 'Reset your '.config('app.name').' password'; @endphp

@section('content')
<h1 style="margin:0 0 16px; font-size:19px; font-weight:700; color:#0a1f44;">Reset Your Password</h1>

<p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#374151;">
    Hi {{ $user->name }},
</p>

<p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#374151;">
    We received a request to reset the password for your {{ config('app.name') }} account.
    Click the button below to choose a new one.
</p>

@include('emails.partials.button', ['url' => $resetUrl, 'label' => 'Reset Password'])

<p style="margin:20px 0 0; font-size:13px; line-height:1.6; color:#6b7280;">
    This link will expire in {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes.
    If you didn't request a password reset, no further action is required.
</p>

<p style="margin:24px 0 0; font-size:14px; color:#374151;">
    Best regards,<br>
    <strong style="color:#0a1f44;">{{ config('app.name') }} Team</strong>
</p>
@endsection
