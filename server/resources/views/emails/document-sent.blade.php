@extends('emails.layout')

@php $preheader = "Your {$document->title} from ".config('app.name'); @endphp

@section('content')
<h1 style="margin:0 0 16px; font-size:19px; font-weight:700; color:#0a1f44;">
    {{ $document->type->label() }} from {{ config('app.name') }}
</h1>

<p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#374151;">
    Hi {{ $document->client->name ?? 'there' }},
</p>

<p style="margin:0 0 14px; font-size:14px; line-height:1.6; color:#374151;">
    Please find attached your {{ strtolower($document->type->label()) }}
    @if($document->number)<strong style="color:#0a1f44;">{{ $document->number }}</strong>@endif
    &mdash; <strong style="color:#0a1f44;">{{ $document->title }}</strong>. Let us know if you have any questions.
</p>

@include('emails.partials.button', ['url' => $portalUrl, 'label' => 'View in Client Portal'])

<p style="margin:24px 0 0; font-size:14px; color:#374151;">
    Best regards,<br>
    <strong style="color:#0a1f44;">{{ config('app.name') }} Team</strong>
</p>
@endsection
