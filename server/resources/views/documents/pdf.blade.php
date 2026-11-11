<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 3px solid {{ $company['primary_color'] ?? '#5ba7ff' }}; padding-bottom: 12px; }
    .company { font-size: 20px; font-weight: bold; color: {{ $company['primary_color'] ?? '#5ba7ff' }}; }
    .muted { color: #6b7280; }
    h2 { font-size: 14px; margin: 18px 0 6px; color: {{ $company['primary_color'] ?? '#5ba7ff' }}; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
    th { background: #f3f4f6; }
    .text-right { text-align: right; }
    .total-row td { font-weight: bold; border-top: 2px solid #1f2937; }
    ul { margin: 4px 0; padding-left: 18px; }
    .box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 14px; margin-top: 6px; }
    .footer { margin-top: 32px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 10px; color: #9ca3af; text-align: center; }
</style>
</head>
<body>
    <div class="header">
        <div>
            @if(!empty($company['logo_url']))
                <img src="{{ $company['logo_url'] }}" alt="{{ $company['name'] ?? 'Vibey Worlds' }}" style="height:36px; margin-bottom:6px;">
            @else
                <div class="company">{{ $company['name'] ?? 'Vibey Worlds' }}</div>
            @endif
            <div class="muted">{{ $company['address'] ?? '' }}</div>
            <div class="muted">{{ $company['email'] ?? '' }} @if(!empty($company['phone'])) &middot; {{ $company['phone'] }} @endif</div>
        </div>
        <div style="text-align:right">
            <div style="font-size:16px;font-weight:bold">{{ $document->type->label() }}</div>
            @if($document->number)<div class="muted">#{{ $document->number }}</div>@endif
            <div class="muted">{{ $document->created_at->format('M j, Y') }}</div>
        </div>
    </div>

    <h2>{{ $document->title }}</h2>
    <p class="muted">Client: {{ $document->client->name ?? 'N/A' }}
        @if($document->project) &middot; Project: {{ $document->project->name }} @endif
        &middot; Prepared by {{ $company['name'] ?? 'Vibey Worlds' }}
    </p>

    @if($document->type->value === 'proposal')
        <h2>Executive Summary</h2>
        <p style="white-space:pre-line">{{ $document->body['executive_summary'] ?? '' }}</p>
        <h2>Scope of Work</h2>
        <p>{{ $document->body['scope_of_work'] ?? '' }}</p>
        @if(!empty($document->body['deliverables']))
            <h2>Deliverables</h2>
            <ul>@foreach($document->body['deliverables'] as $item)<li>{{ $item }}</li>@endforeach</ul>
        @endif

        @if(!empty($document->body['tech_stack']))
            <h2>Suggested Technology Stack</h2>
            <table>
                <thead><tr><th>Layer</th><th>Choice</th><th>Why</th></tr></thead>
                <tbody>
                    @foreach($document->body['tech_stack'] as $layer => $item)
                        <tr>
                            <td style="text-transform:capitalize">{{ $layer }}</td>
                            <td>{{ $item['choice'] ?? '' }}</td>
                            <td>{{ $item['why'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(!empty($document->body['timeline']))
            <h2>Estimated Timeline</h2>
            <p><strong>Total: {{ $document->body['timeline']['total_duration'] ?? '' }}</strong></p>
            @if(!empty($document->body['timeline']['phases']))
                <table>
                    <thead><tr><th>Phase</th><th>Duration</th></tr></thead>
                    <tbody>
                        @foreach($document->body['timeline']['phases'] as $phase)
                            <tr><td>{{ $phase['phase'] ?? '' }}</td><td>{{ $phase['duration'] ?? '' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    @elseif($document->type->value === 'contract')
        <h2>Contract Terms</h2>
        <p>{{ $document->body['contract_terms'] ?? '' }}</p>
        @if(!empty($document->body['deliverables']))
            <h2>Deliverables</h2>
            <ul>@foreach($document->body['deliverables'] as $item)<li>{{ $item }}</li>@endforeach</ul>
        @endif
        <p class="muted">Signature status: {{ $document->signature_status?->label() ?? 'Unsigned' }}</p>
    @endif

    @if($document->lineItems->count() > 0)
        <h2>Pricing</h2>
        <table>
            <thead>
                <tr><th>Description</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total</th></tr>
            </thead>
            <tbody>
                @foreach($document->lineItems as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-right">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3">Total ({{ $document->currency }})</td>
                    <td class="text-right">{{ number_format($document->total, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if(!empty($document->body['quotation']))
        <div class="box">
            <strong>Estimated Price Range:</strong>
            {{ number_format($document->body['quotation']['range_low'] ?? 0) }}–{{ number_format($document->body['quotation']['range_high'] ?? 0) }}
            {{ $document->body['quotation']['currency'] ?? $document->currency }}
            @if(!empty($document->body['quotation']['reasoning']))
                <p class="muted" style="margin:6px 0 0;">Reasoning:</p>
                <ul>@foreach($document->body['quotation']['reasoning'] as $line)<li>{{ $line }}</li>@endforeach</ul>
            @endif
        </div>
    @endif

    @if(!empty($document->body['payment_terms']))
        <h2>Payment Terms</h2>
        <ul>
            @foreach($document->body['payment_terms'] as $term)
                <li>{{ $term['percentage'] ?? '' }}% &mdash; {{ $term['label'] ?? '' }}</li>
            @endforeach
        </ul>
    @endif

    @if(!empty($document->body['assumptions']))
        <h2>Assumptions</h2>
        <ul>@foreach($document->body['assumptions'] as $item)<li>{{ $item }}</li>@endforeach</ul>
    @endif

    @if($document->type->value === 'invoice')
        <p class="muted">Due date: {{ $document->due_date?->format('M j, Y') ?? 'N/A' }}</p>
        <p class="muted">Amount paid: {{ number_format($document->amountPaid, 2) }} {{ $document->currency }}</p>
        <p class="muted">Balance due: {{ number_format($document->balanceDue, 2) }} {{ $document->currency }}</p>
    @endif

    @if($document->type->value === 'proposal' && !empty($document->body['terms']))
        <h2>Terms</h2>
        <p>{{ $document->body['terms'] }}</p>
    @endif

    @if(!empty($document->body['call_to_action']))
        <p style="margin-top:20px;">{{ $document->body['call_to_action'] }}</p>
    @endif

    @if(($document->include_stamp && !empty($company['stamp_url'])) || ($document->include_signature && $document->signer?->signature_url))
        <div style="margin-top:28px; display:flex; gap:24px; align-items:flex-end;">
            @if($document->include_stamp && !empty($company['stamp_url']))
                <img src="{{ $company['stamp_url'] }}" alt="Company stamp" style="height:80px;">
            @endif
            @if($document->include_signature && $document->signer?->signature_url)
                <div>
                    <img src="{{ $document->signer->signature_url }}" alt="Signature" style="height:50px;">
                    <p class="muted" style="margin:2px 0 0; border-top:1px solid #d1d5db; padding-top:2px;">{{ $document->signer->name }}</p>
                </div>
            @endif
        </div>
    @endif

    <div class="footer">
        {{ $company['name'] ?? 'Vibey Worlds' }} &middot; {{ $company['website'] ?? '' }} &middot; Generated {{ now()->format('M j, Y') }}
    </div>
</body>
</html>
