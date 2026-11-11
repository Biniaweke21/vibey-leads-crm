{{-- Params: $email, $password, $passwordLabel (optional) --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6fb; border:1px solid #e5e7eb; border-radius:10px; margin: 20px 0;">
<tr>
<td style="padding: 16px 20px;">
    <p style="margin:0 0 4px; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.06em;">Email</p>
    <p style="margin:0 0 14px; font-size:14px; color:#0a1f44; font-weight:600;">{{ $email }}</p>
    <p style="margin:0 0 4px; font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.06em;">{{ $passwordLabel ?? 'Temporary Password' }}</p>
    <p style="margin:0; font-size:16px; font-family: 'Courier New', Courier, monospace; color:#0a1f44; font-weight:700; letter-spacing:0.04em;">{{ $password }}</p>
</td>
</tr>
</table>
