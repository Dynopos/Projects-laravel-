{{-- Kerangka e-mel Dyno (jadual + gaya sebaris untuk klien e-mel). Isi melalui $slot. --}}
@props(['syarikat'])
<!DOCTYPE html>
<html lang="ms">
<body style="margin:0;padding:0;background:#0a0a09;font-family:Arial,Helvetica,sans-serif;color:#f6f2e8">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a09;padding:32px 12px">
  <tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#141310;border:1px solid #26241d;border-radius:18px">
      <tr><td style="padding:28px 28px 8px">
        <p style="margin:0;font-family:Georgia,serif;font-size:15px;font-weight:bold;letter-spacing:.04em;color:#f6f2e8">{{ $syarikat->nama }}<span style="color:#d4a437">{{ $syarikat->sufiks }}</span></p>
        <p style="margin:2px 0 0;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#867f6d">{{ $syarikat->subdomain }}.{{ config('dyno.domain_utama') }}</p>
      </td></tr>
      <tr><td style="padding:16px 28px 24px">
        {{ $slot }}
      </td></tr>
    </table>
    <p style="margin:16px 0 0;font-size:11px;letter-spacing:.14em;color:#6f6858">DYNO PROJECT OS</p>
  </td></tr>
</table>
</body>
</html>
