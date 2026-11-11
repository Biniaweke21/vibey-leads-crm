@extends('emails.layout')

@php $preheader = "Your {$user->name} account on ".config('app.name')." is ready."; @endphp

@section('content')
<h1 style="margin:0 0 16px; font-size:19px; font-weight:700; color:#0a1f44;">Welcome, {{ $user->name }} 👋</h1>

<p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#374151;">
    An account has been created for you on <strong style="color:#0a1f44;">{{ config('app.name') }}</strong>.
    You can sign in with the credentials below.
</p>

@include('emails.partials.credentials', ['email' => $user->email, 'password' => $password])

@include('emails.partials.button', ['url' => $loginUrl, 'label' => 'Sign In to '.config('app.name')])

<p style="margin:20px 0 0; font-size:13px; line-height:1.6; color:#6b7280;">
    For your security, please change this password as soon as you sign in.
</p>

<p style="margin:24px 0 0; font-size:14px; color:#374151;">
    Best regards,<br>
    <strong style="color:#0a1f44;">{{ config('app.name') }} Team</strong>
</p>
@endsection
