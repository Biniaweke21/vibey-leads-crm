@extends('emails.layout')

@php $preheader = "Payment reminder for invoice {$invoice->number}"; @endphp

@section('content')
<h1 style="margin:0 0 16px; font-size:19px; font-weight:700; color:#0a1f44;">Payment Reminder</h1>

<p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#374151;">
    Hi {{ $invoice->client->name ?? 'there' }},
</p>

<p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#374151;">
    This is a reminder that invoice <strong style="color:#0a1f44;">{{ $invoice->number }}</strong>
    ({{ $invoice->title }}) is still outstanding{{ $invoice->due_date ? ', due '.$invoice->due_date->format('M j, Y') : '' }}.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fff7ed; border:1px solid #fed7aa; border-radius:10px; margin: 20px 0;">
<tr>
<td style="padding: 16px 20px;">
    <p style="margin:0 0 4px; font-size:11px; font-weight:600; color:#9a5b13; text-transform:uppercase; letter-spacing:0.06em;">Balance Due</p>
    <p style="margin:0; font-size:22px; color:#0a1f44; font-weight:700;">{{ number_format($invoice->balanceDue, 2) }} {{ $invoice->currency }}</p>
</td>
</tr>
</table>

@include('emails.partials.button', ['url' => $portalUrl, 'label' => 'Pay in Client Portal'])

<p style="margin:0; font-size:14px; line-height:1.6; color:#374151;">
    Please let us know if you have any questions.
</p>

<p style="margin:24px 0 0; font-size:14px; color:#374151;">
    Best regards,<br>
    <strong style="color:#0a1f44;">{{ config('app.name') }} Team</strong>
</p>
@endsection
