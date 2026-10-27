Terima kasih, {{ $nama }}. Ruang {{ $syarikat->namaPenuh() }} di Dyno Project OS sudah sedia.

Bayaran RM{{ number_format(config('dyno.harga.daftar') / 100, 2) }} diterima. Langganan aktif hingga {{ $syarikat->aktif_hingga?->format('d/m/Y') }}.

Tetapkan kata laluan pemilik:
{{ $pautan }}

Selepas itu, log masuk di {{ $syarikat->url('/') }}
