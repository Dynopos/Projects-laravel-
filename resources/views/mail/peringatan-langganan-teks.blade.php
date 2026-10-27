Langganan {{ $syarikat->namaPenuh() }} di Dyno Project OS {{ $baki > 0 ? "tamat dalam {$baki} hari" : 'telah tamat' }} ({{ $syarikat->aktif_hingga->format('d/m/Y') }}).

Pembaharuan 1 tahun: RM{{ number_format(config('dyno.harga.pembaharuan') / 100, 2) }}
{{ $syarikat->url('/langganan') }}

Jika tidak diperbaharui dalam {{ config('dyno.tempoh_tangguh') }} hari selepas tamat, sistem bertukar ke mod baca sahaja. Data tidak dipadam dan boleh dieksport.
