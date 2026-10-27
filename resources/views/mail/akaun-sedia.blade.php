<x-mail.kerangka :syarikat="$syarikat">
  <h1 style="margin:0 0 12px;font-family:Georgia,serif;font-size:24px;color:#f6f2e8">Terima kasih, {{ $nama }}. Ruang syarikat anda sudah sedia.</h1>
  <p style="margin:0 0 10px;font-size:15px;line-height:1.55;color:#b6ae9c">Bayaran RM{{ number_format(config('dyno.harga.daftar') / 100, 2) }} diterima. Langganan aktif hingga <b style="color:#f6f2e8">{{ $syarikat->aktif_hingga?->format('d/m/Y') }}</b>.</p>
  <p style="margin:0 0 22px;font-size:15px;line-height:1.55;color:#b6ae9c">Klik butang di bawah untuk tetapkan kata laluan pemilik dan mula guna sistem.</p>
  <a href="{{ $pautan }}" style="display:inline-block;background:#d4a437;color:#17150e;text-decoration:none;font-weight:bold;font-size:15px;padding:13px 24px;border-radius:999px">Tetapkan kata laluan</a>
  <p style="margin:22px 0 0;font-size:12.5px;line-height:1.5;color:#867f6d">Selepas itu, log masuk di {{ $syarikat->url('/') }}</p>
  <p style="margin:12px 0 0;font-size:12px;line-height:1.5;color:#867f6d;word-break:break-all">Jika butang tidak berfungsi, salin pautan ini:<br>{{ $pautan }}</p>
</x-mail.kerangka>
