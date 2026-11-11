@extends('emails.layout')

@php $preheader = "Meeting cancelled: {$meeting->title}"; @endphp

@section('content')
<h1 style="margin:0 0 16px; font-size:19px; font-weight:700; color:#0a1f44;">Meeting Cancelled</h1>

<p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#374151;">
    Hi {{ $recipientName }}, the following meeting has been cancelled:
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6fb; border:1px solid #e5e7eb; border-radius:10px; margin: 20px 0;">
<tr>
<td style="padding: 16px 20px;">
    <p style="margin:0 0 6px; font-size:15px; font-weight:700; color:#0a1f44; text-decoration:line-through;">{{ $meeting->title }}</p>
    <p style="margin:0; font-size:13px; color:#374151;">
        {{ $meeting->starts_at->format('l, M j, Y \a\t g:i A') }}
        @if($meeting->ends_at) &ndash; {{ $meeting->ends_at->format('g:i A') }} @endif
        @if($meeting->timezone) ({{ $meeting->timezone }}) @endif
    </p>
    @if($meeting->location)
        <p style="margin:8px 0 0; font-size:13px; color:#6b7280;">📍 {{ $meeting->location }}</p>
    @endif
</td>
</tr>
</table>

@include('emails.partials.button', ['url' => $portalUrl, 'label' => 'View in Portal'])

<p style="margin:20px 0 0; font-size:14px; line-height:1.6; color:#374151;">
    No action is needed on your end. If you have questions, please reach out to us.
</p>

<p style="margin:24px 0 0; font-size:14px; color:#374151;">
    Best regards,<br>
    <strong style="color:#0a1f44;">{{ config('app.name') }} Team</strong>
</p>
@endsection
