@php($baki = $syarikat->bakiHari())
@php($harga = number_format(config('dyno.harga.pembaharuan') / 100, 2))
<div>
  <div class="head">
    <div><h1>Langganan</h1><p>Status langganan Dyno Project OS dan pembaharuan tahunan.</p></div>
  </div>

  @if($mesej)<div class="borang" style="padding:14px 20px;color:var(--biru)">{{ $mesej }}</div>@endif
  @if($ralat)<div class="ralat" style="margin:0 0 16px">{{ $ralat }}</div>@endif

  <div class="borang">
    <div class="seclab" style="margin:14px 0 0"><b>Status</b><span></span></div>
    @if($baki === null)
      <p style="margin:14px 0 0">Langganan diurus terus oleh Dyno — tiada tarikh tamat dalam sistem.</p>
    @else
      <p style="margin:14px 0 4px;font-size:15px">
        @if($baki >= 0)
          Aktif hingga <b>{{ $syarikat->aktif_hingga->format('d/m/Y') }}</b>
          <span class="tag" style="margin-left:6px">{{ $baki }} hari lagi</span>
        @else
          Tamat pada <b>{{ $syarikat->aktif_hingga->format('d/m/Y') }}</b>
          <span class="tag" style="margin-left:6px;color:var(--crit);border-color:var(--crit)">{{ $syarikat->bacaSahaja() ? 'Baca sahaja' : 'Tempoh tangguh' }}</span>
        @endif
      </p>
      <p style="margin:0;color:var(--muted);font-size:13px">
        Pembaharuan RM{{ $harga }} setahun. Bayaran ditambah dari tarikh tamat, jadi membayar awal tidak memendekkan langganan.
        @if($baki < 0 && ! $syarikat->bacaSahaja()) Sistem bertukar ke mod baca sahaja {{ config('dyno.tempoh_tangguh') }} hari selepas tamat. @endif
      </p>
      <div class="acts" style="margin-top:16px">
        <button class="btn p" wire:click="bayar" wire:loading.attr="disabled">Bayar RM{{ $harga }} · FPX / kad</button>
      </div>
    @endif
  </div>

  @if($sejarah->isNotEmpty())
    <div class="seclab"><b>Sejarah bayaran</b><span></span></div>
    <div class="tbl"><table>
      <thead><tr><th>Tarikh</th><th>Jenis</th><th>Rujukan</th><th style="text-align:right">Amaun</th></tr></thead>
      <tbody>
      @foreach($sejarah as $b)
        <tr wire:key="b-{{ $b->id }}">
          <td>{{ $b->dibayar_pada->timezone($syarikat->zon_waktu)->format('d/m/Y') }}</td>
          <td>{{ $b->jenis === 'daftar' ? 'Daftar + tahun pertama' : 'Pembaharuan 1 tahun' }}</td>
          <td>{{ $b->rujukan() }}</td>
          <td style="text-align:right">RM{{ number_format($b->amaun_sen / 100, 2) }}</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  @endif
</div>
