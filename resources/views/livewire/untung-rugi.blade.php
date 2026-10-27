@php
  use App\Support\Kiraan;
  use App\Support\UntungRugi;
  $rm = fn ($sen) => Kiraan::rm($sen);
  $nmBulan = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogo', 'Sep', 'Okt', 'Nov', 'Dis'];
  $penuhBulan = ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'];

  // Carta: satu siri (untung tunai bulanan), bar naik/turun dari garis sifar.
  $W = 720; $H = 230; $kiri = 64; $atas = 14; $bawah = 26;
  $nilai = array_column($b['bulan'], 'bersih');
  $maks = max(1, max($nilai), 0); $min = min(0, min($nilai));
  $julat = $maks - $min ?: 1;
  $tinggi = $H - $atas - $bawah;
  $y = fn ($v) => $atas + ($maks - $v) / $julat * $tinggi;
  $slot = ($W - $kiri) / 12; $lebar = min(30, $slot * .56);
  $y0 = $y(0);
  // Bar dengan hujung data bulat (4px), pangkal rata di garis sifar.
  $bar = function ($x, $v) use ($y, $y0, $lebar) {
      $yv = $y($v); $h = abs($yv - $y0);
      if ($h < .5) return '';
      $r = min(4, $h, $lebar / 2); $x2 = $x + $lebar;
      return $v >= 0
          ? "M{$x},{$y0}V".($yv + $r)."Q{$x},{$yv} ".($x + $r).",{$yv}H".($x2 - $r)."Q{$x2},{$yv} {$x2},".($yv + $r)."V{$y0}Z"
          : "M{$x},{$y0}V".($yv - $r)."Q{$x},{$yv} ".($x + $r).",{$yv}H".($x2 - $r)."Q{$x2},{$yv} {$x2},".($yv - $r)."V{$y0}Z";
  };
  $ringkas = fn ($sen) => ($sen < 0 ? '−' : '').'RM'.(abs($sen) >= 100000000 ? number_format(abs($sen) / 100000000, 1).'j' : (abs($sen) >= 100000 ? number_format(abs($sen) / 100000, 0).'k' : number_format(abs($sen) / 100, 0)));
  $marginTunai = UntungRugi::margin($b['bersih'], $b['masuk']);
@endphp
<div>
  <div class="head">
    <div><h1>Untung Rugi</h1><p>Kutipan tolak perbelanjaan projek dan perbelanjaan umum syarikat. Bayaran menunggu tidak dikira.</p></div>
  </div>

  <div class="tabs">
    <select class="field" style="max-width:160px" wire:model.live="tahun" aria-label="Tahun">
      @foreach($senaraiTahun as $t)<option value="{{ $t }}" @selected($t === $tahunDipilih)>{{ $t }}</option>@endforeach
    </select>
    <select class="field" style="max-width:220px" wire:model.live="kat" aria-label="Projek">
      <option value="">Semua projek</option>
      <option value="aktif">Projek aktif</option>
      <option value="siap">Projek siap</option>
    </select>
    <span class="eksport-kanan"><x-butang-eksport jenis="pnl-bulan" :tapisan="['tahun' => $tahunDipilih, 'kat' => $kat]" /></span>
  </div>

  <div class="seclab"><b>{{ $umumDikira ? 'Untung bersih' : 'Untung projek' }} · {{ $tahunDipilih }}</b><span></span><small>Ikut tarikh bayaran &amp; perbelanjaan</small></div>
  <div class="tiles">
    <x-tile k="Kutipan diterima" :v="$rm($b['masuk'])" f="Bayaran disahkan" :href="route('bayaran', ['status' => 'Diterima'])" />
    <x-tile k="Perbelanjaan projek" :v="$rm($b['keluar'])" :f="'Untung projek '.$rm($b['untung'])" :href="route('belanja')" />
    @if($umumDikira)
      <x-tile k="Perbelanjaan umum" :v="$rm($b['umum'])" f="Overhead syarikat" :href="route('belanja', ['paparan' => 'umum'])" />
      <x-tile k="Untung bersih" :v="$rm($b['bersih'])" :f="UntungRugi::teksMargin($marginTunai).' untung · selepas overhead'" hero />
    @else
      <x-tile k="Untung projek" :v="$rm($b['untung'])" f="Kutipan − perbelanjaan projek" hero />
      <x-tile k="% untung" :v="UntungRugi::teksMargin($marginTunai)" f="Untung ÷ kutipan" />
    @endif
  </div>
  @if(! $umumDikira)<p class="nota-pnl" style="margin-top:-6px">Perbelanjaan umum syarikat hanya ditolak bila "Semua projek" dipilih.</p>@endif

  <div class="carta-pnl">
    <div class="carta-kepala"><b>{{ $umumDikira ? 'Untung bersih' : 'Untung projek' }} bulanan, {{ $tahunDipilih }}</b><small>Bar di bawah garis = bulan rugi</small></div>
    <div class="carta-skrol"><svg viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Carta untung tunai bulanan {{ $tahunDipilih }}">
      @foreach(array_unique([$maks, 0, $min]) as $g)
        <line x1="{{ $kiri }}" x2="{{ $W }}" y1="{{ $y($g) }}" y2="{{ $y($g) }}" class="{{ $g === 0 ? 'paksi' : 'grid' }}" />
        <text x="{{ $kiri - 8 }}" y="{{ $y($g) + 4 }}" text-anchor="end" class="lbl">{{ $ringkas($g) }}</text>
      @endforeach
      @foreach($b['bulan'] as $i => $m)
        @php
          $x = $kiri + $i * $slot + ($slot - $lebar) / 2;
        @endphp
        <g class="lajur">
          <title>{{ $penuhBulan[$i] }}: untung {{ $rm($m['bersih']) }} (kutipan {{ $rm($m['masuk']) }}, belanja projek {{ $rm($m['keluar']) }}@if($umumDikira), umum {{ $rm($m['umum']) }}@endif)</title>
          <rect x="{{ $kiri + $i * $slot }}" y="{{ $atas }}" width="{{ $slot }}" height="{{ $tinggi }}" class="kena" />
          <path d="{{ $bar($x, $m['bersih']) }}" class="bar" />
          <text x="{{ $kiri + $i * $slot + $slot / 2 }}" y="{{ $H - 6 }}" text-anchor="middle" class="lbl">{{ $nmBulan[$i] }}</text>
        </g>
      @endforeach
    </svg></div>
  </div>

  <div class="tbl" style="margin-bottom:8px"><table class="ketat">
    <thead><tr><th>Bulan</th><th class="num">Kutipan</th><th class="num">Belanja projek</th><th class="num">Untung projek</th>
      @if($umumDikira)<th class="num">Belanja umum</th><th class="num">Untung bersih</th>@endif<th class="num">Kumulatif</th></tr></thead>
    <tbody>
    @foreach($b['bulan'] as $i => $m)
      <tr wire:key="bl-{{ $i }}" @class(['redup' => ! $m['masuk'] && ! $m['keluar'] && ! $m['umum']])>
        <td>{{ $penuhBulan[$i] }}</td>
        <td class="num">{{ $rm($m['masuk']) }}</td>
        <td class="num">{{ $rm($m['keluar']) }}</td>
        <td @class(['num', 'rugi' => $m['untung'] < 0])>@if($umumDikira){{ $rm($m['untung']) }}@else<b>{{ $rm($m['untung']) }}</b>@endif</td>
        @if($umumDikira)
          <td class="num">{{ $rm($m['umum']) }}</td>
          <td @class(['num', 'rugi' => $m['bersih'] < 0])><b>{{ $rm($m['bersih']) }}</b></td>
        @endif
        <td @class(['num', 'rugi' => $m['kumulatif'] < 0])>{{ $rm($m['kumulatif']) }}</td>
      </tr>
    @endforeach
    </tbody>
    <tfoot><tr><td><b>Jumlah {{ $tahunDipilih }}</b></td><td class="num"><b>{{ $rm($b['masuk']) }}</b></td><td class="num"><b>{{ $rm($b['keluar']) }}</b></td>
      <td @class(['num', 'rugi' => $b['untung'] < 0])><b>{{ $rm($b['untung']) }}</b></td>
      @if($umumDikira)<td class="num"><b>{{ $rm($b['umum']) }}</b></td><td @class(['num', 'rugi' => $b['bersih'] < 0])><b>{{ $rm($b['bersih']) }}</b></td>@endif
      <td></td></tr></tfoot>
  </table></div>
  @if($b['tanpa_tarikh'])
    <p class="nota-pnl">{{ $b['tanpa_tarikh'] }} rekod bayaran/perbelanjaan tanpa tarikh tidak dimasukkan dalam mana-mana bulan. Ia tetap dikira dalam jadual projek di bawah.</p>
  @endif

  <div class="seclab" style="margin-top:22px"><b>Ikut projek · keseluruhan</b><span></span></div>
  <div class="tabs">
    <select class="field" style="max-width:230px" wire:model.live="susun" aria-label="Susun">
      <option value="kod">Susun: kod projek</option>
      <option value="untung">Untung bila siap terendah</option>
      <option value="margin">% untung terendah</option>
      <option value="belanja">Perbelanjaan tertinggi</option>
    </select>
    <span class="eksport-kanan"><x-butang-eksport jenis="pnl" :tapisan="['kat' => $kat, 'susun' => $susun]" /></span>
  </div>
  <div class="tbl"><table class="pnl-projek">
    <thead><tr><th>Projek</th><th class="num">Nilai kontrak</th><th class="num">Diterima</th><th class="num">Perbelanjaan</th>
      <th class="num">Untung setakat ini</th><th class="num">Untung bila siap</th><th class="num">% untung</th></tr></thead>
    <tbody>
    @forelse($projek as $p)
      <tr wire:key="p-{{ $p->id }}">
        <td><a href="{{ route('projek.butiran', $p) }}" wire:navigate style="color:inherit"><b>{{ $p->kod }}</b></a><br><small style="color:var(--muted)">{{ $p->nama }}</small><br><x-status :s="$p->status" /></td>
        <td class="num">{{ $rm($p->nilai_sen) }}</td>
        <td class="num">{{ $rm($p->diterima_sen) }}</td>
        <td class="num">{{ $rm($p->belanja_sen) }}</td>
        <td @class(['num', 'rugi' => $p->untung_tunai < 0])>{{ $rm($p->untung_tunai) }}</td>
        <td @class(['num', 'rugi' => $p->untung_jangka < 0])><b>{{ $rm($p->untung_jangka) }}</b></td>
        <td @class(['num', 'rugi' => ($p->margin ?? 0) < 0])>{{ UntungRugi::teksMargin($p->margin) }}</td>
      </tr>
    @empty
      <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:28px">Tiada projek.</td></tr>
    @endforelse
    </tbody>
    @if($projek->isNotEmpty())
      @php
        $jn = $projek->sum('nilai_sen'); $jb = $projek->sum('belanja_sen'); $jd = $projek->sum('diterima_sen');
      @endphp
      <tfoot><tr><td><b>Jumlah · {{ $projek->count() }} projek</b></td>
        <td class="num"><b>{{ $rm($jn) }}</b></td><td class="num"><b>{{ $rm($jd) }}</b></td><td class="num"><b>{{ $rm($jb) }}</b></td>
        <td @class(['num', 'rugi' => $jd - $jb < 0])><b>{{ $rm($jd - $jb) }}</b></td>
        <td @class(['num', 'rugi' => $jn - $jb < 0])><b>{{ $rm($jn - $jb) }}</b></td>
        <td class="num"><b>{{ UntungRugi::teksMargin(UntungRugi::margin($jn - $jb, $jn)) }}</b></td></tr></tfoot>
    @endif
  </table></div>
  <p class="nota-pnl"><b>Untung setakat ini</b> = duit yang sudah diterima − perbelanjaan. <b>Untung bila siap</b> = nilai kontrak − perbelanjaan, iaitu untung jika klien bayar penuh; ia turun bila ada kos baharu. Perbelanjaan umum tidak diagihkan kepada projek.</p>
</div>
