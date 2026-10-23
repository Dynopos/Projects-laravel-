<!DOCTYPE html>
<html lang="ms">
<body style="margin:0;padding:0;background:#0a0a09;font-family:Arial,Helvetica,sans-serif;color:#f6f2e8">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a09;padding:32px 12px">
  <tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#141310;border:1px solid #26241d;border-radius:18px">
      <tr><td style="padding:28px 28px 8px">
        @if($syarikat->logo_path)<img src="{{ $syarikat->url('logo') }}" alt="" width="56" height="56" style="display:block;width:56px;height:56px;object-fit:contain;margin:0 0 12px;border-radius:10px">@endif
        <p style="margin:0;font-family:Georgia,serif;font-size:15px;font-weight:bold;letter-spacing:.04em;color:#f6f2e8">{{ $syarikat->nama }}<span style="color:#d4a437">{{ $syarikat->sufiks }}</span></p>
        <p style="margin:2px 0 0;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#867f6d">{{ $syarikat->jabatan }}</p>
      </td></tr>
      <tr><td style="padding:16px 28px 4px">
        <h1 style="margin:0 0 12px;font-family:Georgia,serif;font-size:24px;color:#f6f2e8">Anda dijemput menyertai sistem projek kami</h1>
        <p style="margin:0 0 10px;font-size:15px;line-height:1.55;color:#b6ae9c">
          {{ $jemputan->penjemput?->name ?? 'Pengurusan' }} menjemput anda sebagai <b style="color:#f6f2e8">{{ $jemputan->peranan->label() }}</b>
          di {{ $syarikat->namaPenuh() }}.
        </p>
        <p style="margin:0 0 22px;font-size:15px;line-height:1.55;color:#b6ae9c">Klik butang di bawah untuk tetapkan kata laluan dan mula guna sistem.</p>
        <a href="{{ $pautan }}" style="display:inline-block;background:#d4a437;color:#17150e;text-decoration:none;font-weight:bold;font-size:15px;padding:13px 24px;border-radius:999px">Terima jemputan</a>
        <p style="margin:22px 0 0;font-size:12.5px;line-height:1.5;color:#867f6d">
          Pautan ini sah selama {{ \App\Models\Jemputan::TEMPOH_HARI }} hari dan hanya untuk {{ $jemputan->email }}.
          Jika anda tidak menjangka e-mel ini, abaikan sahaja.
        </p>
        <p style="margin:12px 0 24px;font-size:12px;line-height:1.5;color:#867f6d;word-break:break-all">Jika butang tidak berfungsi, salin pautan ini:<br>{{ $pautan }}</p>
      </td></tr>
    </table>
    <p style="margin:16px 0 0;font-size:11px;letter-spacing:.14em;color:#6f6858">DYNO PROJECT OS</p>
  </td></tr>
</table>
</body>
</html>
