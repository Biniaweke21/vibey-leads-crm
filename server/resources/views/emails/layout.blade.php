<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6fb; font-family: 'Outfit', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
@isset($preheader)
<div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all;">{{ $preheader }}</div>
@endisset

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6fb;">
<tr>
<td align="center" style="padding: 40px 16px;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 480px;">
<!-- Brand header -->
<tr>
<td align="center" style="padding-bottom: 24px;">
    <span style="font-size:20px; font-weight:700; color:#0a1f44; letter-spacing:-0.01em;">{{ config('app.name') }}</span>
    <div style="height:3px; width:40px; background-color:#5ba7ff; border-radius:2px; margin:8px auto 0;"></div>
</td>
</tr>

<!-- Card -->
<tr>
<td style="background-color:#ffffff; border:1px solid #e5e7eb; border-radius:14px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(10,31,68,0.06);">
@yield('content')
</td>
</tr>

<!-- Footer -->
<tr>
<td align="center" style="padding-top: 24px;">
    <p style="margin:0; font-size:12px; color:#9ca3af; line-height:1.5;">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </p>
</td>
</tr>
</table>

</td>
</tr>
</table>
</body>
</html>
