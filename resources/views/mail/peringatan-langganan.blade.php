@php($harga = number_format(config('dyno.harga.pembaharuan') / 100, 2))
<x-mail.kerangka :syarikat="$syarikat">
  <h1 style="margin:0 0 12px;font-family:Georgia,serif;font-size:24px;color:#f6f2e8">
    @if($baki > 0) Langganan tamat dalam {{ $baki }} hari @else Langganan telah tamat @endif
  </h1>
  <p style="margin:0 0 10px;font-size:15px;line-height:1.55;color:#b6ae9c">
    Langganan {{ $syarikat->namaPenuh() }} {{ $baki > 0 ? 'aktif hingga' : 'tamat pada' }} <b style="color:#f6f2e8">{{ $syarikat->aktif_hingga->format('d/m/Y') }}</b>.
    Pembaharuan 1 tahun: <b style="color:#f6f2e8">RM{{ $harga }}</b>.
  </p>
  <p style="margin:0 0 22px;font-size:15px;line-height:1.55;color:#b6ae9c">
    Jika tidak diperbaharui dalam {{ config('dyno.tempoh_tangguh') }} hari selepas tamat, sistem bertukar ke mod baca sahaja. Data anda tidak dipadam dan boleh dieksport.
  </p>
  <a href="{{ $syarikat->url('/langganan') }}" style="display:inline-block;background:#d4a437;color:#17150e;text-decoration:none;font-weight:bold;font-size:15px;padding:13px 24px;border-radius:999px">Perbaharui RM{{ $harga }}</a>
</x-mail.kerangka>
