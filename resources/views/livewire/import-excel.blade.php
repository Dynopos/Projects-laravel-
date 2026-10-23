@php($rm = fn ($x) => \App\Support\Kiraan::rm(\App\Support\Kiraan::sen($x)))
<div>
  <div class="head">
    <div><h1>Import Excel</h1><p>Masukkan senarai projek sedia ada sekali gus. Semak pratonton sebelum import.</p></div>
  </div>

  @if($selesai)
    <div class="note"><b>{{ $selesai['projek'] }}</b> projek dan <b>{{ $selesai['bayaran'] }}</b> rekod bayaran berjaya dimasukkan.</div>
    <div style="display:flex;gap:9px;flex-wrap:wrap">
      <a class="btn p" href="{{ route('dashboard') }}" wire:navigate style="text-decoration:none">Lihat Dashboard</a>
      <a class="btn" href="{{ route('projek') }}" wire:navigate style="text-decoration:none">Lihat Projek</a>
      <button class="btn" wire:click="$set('selesai', null)">Import fail lain</button>
    </div>
  @else
    <div class="borang">
      <label style="display:block;margin:16px 0 8px;color:var(--ink2);font-size:12.5px">Fail Excel (.xls, .xlsx) atau CSV, maksimum 10 MB</label>
      <input class="field" type="file" wire:model="fail" accept=".xls,.xlsx,.csv">
      <div wire:loading wire:target="fail" class="hint">Membaca fail…</div>
      @error('fail')<div class="ralat">{{ $message }}</div>@enderror
      @if($ralat)<div class="ralat">{{ $ralat }}</div>@endif
      <p class="hint" style="margin:12px 0 0">Format dikenali: <b>PROJECT FLOW</b> (berbilang baris setiap projek), atau mana-mana jadual
        dengan tajuk lajur seperti Projek, Klien, Nilai, Deposit, Status, Tarikh.</p>
    </div>

    @if($fail && ! $hasil && ! $ralat && ! $errors->has('fail'))
      <div class="note warn">Sistem tidak jumpa lajur nama projek{{ $helaian ? " dalam helaian \"{$helaian}\"" : '' }}. Pastikan baris pertama ialah tajuk lajur.</div>
    @endif

    @if($hasil)
      @if($hasil->format === 'flow')
        <div class="note"><b>Format PROJECT FLOW dikenali</b> — helaian <b>{{ $hasil->helaian }}</b>. Sistem baca {{ count($hasil->projek) }} projek
          dan {{ count($hasil->bayaran) }} rekod bayaran, termasuk baris butiran di bawah setiap projek.</div>
      @elseif(count($helaianSemua) > 1)
        <div class="tabs">
          @foreach($helaianSemua as $h)
            <button class="btn sm {{ $h === $helaian ? 'p' : '' }}" wire:click="$set('helaian', @js($h))">{{ $h }}</button>
          @endforeach
        </div>
      @endif

      <div class="row" style="grid-template-columns:1fr 1fr">
        <div class="card">
          <h2>Semakan jumlah</h2><p class="cap">{{ $hasil->format === 'flow' ? 'Bandingkan dengan baris TOTAL dalam fail anda' : 'Seperti yang sistem faham' }}</p>
          <dl class="butiran" style="grid-template-columns:1fr 1fr">
            <div><dt>Jumlah nilai kontrak</dt><dd>{{ $rm($hasil->jumlahNilai()) }}</dd></div>
            <div><dt>Jumlah diterima</dt><dd>{{ $rm($hasil->jumlahDiterima()) }}</dd></div>
            <div><dt>Belum dikutip</dt><dd>{{ $rm($hasil->jumlahNilai() - $hasil->jumlahDiterima()) }}</dd></div>
            <div><dt>Projek · Bayaran</dt><dd>{{ count($hasil->projek) }} · {{ count($hasil->bayaran) }}</dd></div>
          </dl>
          @if($hasil->peta)
            <div class="aliran" style="margin-top:14px">
              @foreach(array_keys(\App\Import\HuraiGenerik::KAMUS) as $m)
                <span class="tag" @isset($hasil->peta[$m]) style="color:var(--biru);border-color:var(--biru)" @endisset>
                  {{ isset($hasil->peta[$m]) ? '✓' : '–' }} {{ $m }}{{ isset($hasil->peta[$m]) ? ' ← '.$hasil->peta[$m] : '' }}</span>
              @endforeach
            </div>
          @endif
          <div style="display:flex;gap:9px;flex-wrap:wrap;margin-top:16px">
            <button class="btn p" wire:click="importSemua" wire:loading.attr="disabled"
              @if($semak['wujud']) wire:confirm="{{ count($semak['wujud']) }} projek sudah wujud dan akan DIGANTIKAN{{ $semak['bayaranDipadam'] ? ", termasuk {$semak['bayaranDipadam']} rekod bayarannya" : '' }}{{ $semak['manualDipadam'] ? " ({$semak['manualDipadam']} dimasukkan sendiri oleh staf — akan HILANG)" : '' }}. Teruskan?" @endif>
              ✓ Import semua ({{ count($hasil->projek) }})</button>
            @if($semak['wujud'])
              <button class="btn" wire:click="importBaharu" wire:loading.attr="disabled" @disabled(! $semak['baharu'])>Hanya projek baharu ({{ count($semak['baharu']) }})</button>
            @endif
          </div>
        </div>

        <div class="card">
          <h2>Perkara perlu disemak</h2><p class="cap">Dijumpai semasa membaca fail</p>
          @php($ada = false)
          @if($semak['wujud'])
            @php($ada = true)
            <div class="note warn" style="margin-top:0"><b>{{ count($semak['wujud']) }} kod projek sudah wujud</b> ({{ implode(', ', array_slice($semak['wujud'], 0, 5)) }}{{ count($semak['wujud']) > 5 ? '…' : '' }}).
              "Import semua" menggantikan butiran dan {{ $semak['bayaranDipadam'] }} rekod bayarannya.
              @if($semak['manualDipadam'])<b>{{ $semak['manualDipadam'] }} bayaran dimasukkan sendiri oleh staf akan hilang</b> — pilih "Hanya projek baharu" untuk mengelak.@endif
              Perbelanjaan tidak disentuh.</div>
          @endif
          @if($inv = $hasil->invoisBerulang())
            @php($ada = true)
            <div class="note warn"><b>No. invois berulang:</b> {{ implode(', ', $inv) }} — dipakai lebih daripada satu projek. Sistem beri kod unik sendiri; elok dibetulkan dalam fail asal.</div>
          @endif
          @if($tanpa = collect($hasil->projek)->where('nilai', 0)->count())
            @php($ada = true)
            <div class="note warn">{{ $tanpa }} projek tiada nilai kontrak.</div>
          @endif
          @if($depan = $hasil->bayaranMasaDepan($hariIni))
            @php($ada = true)
            <div class="note warn"><b>{{ count($depan) }} tarikh bayaran jatuh selepas hari ini</b>
              ({{ collect($depan)->take(3)->map(fn ($b) => $b['kod'].' · '.$b['tarikh'])->implode(', ') }}{{ count($depan) > 3 ? '…' : '' }}).
              @if($hasil->format === 'flow')Teks tarikh dalam format ini dibaca sebagai <b>bulan/hari/tahun</b>: <code>10/08/26</code> jadi 8 Oktober, bukan 10 Ogos.@endif
              Semak dan betulkan selepas import.</div>
          @endif
          @if(collect($hasil->projek)->contains(fn ($p) => preg_match('/^x+$/i', $p['nama'])))
            @php($ada = true)
            <div class="note warn">Ada projek bernama sementara (cth "xxx") — kekal sehingga anda namakan.</div>
          @endif
          @unless($ada)<div class="note" style="margin-top:0">Tiada masalah dikesan.</div>@endunless
        </div>
      </div>

      <div class="seclab" style="margin-top:16px"><b>Pratonton</b><span></span><small>seperti yang sistem faham</small></div>
      <div class="tbl"><table>
        <thead><tr><th>Kod</th><th>Projek / Klien</th><th>Lokasi</th><th>Telefon</th><th>Status</th><th class="num">Kontrak</th><th class="num">Diterima</th></tr></thead>
        <tbody>
        @foreach($hasil->projek as $p)
          <tr>
            <td><b>{{ $p['kod'] }}</b>@if(in_array($p['kod'], $semak['wujud'], true))<br><small style="color:var(--warn)">sudah wujud</small>@endif</td>
            <td>{{ $p['nama'] }}@if($p['klien'] && $p['klien'] !== $p['nama'])<br><small style="color:var(--muted)">{{ $p['klien'] }}</small>@endif</td>
            <td>{{ $p['lokasi'] ?: '—' }}</td><td>{{ $p['telefon'] ?: '—' }}</td>
            <td><x-status :s="$p['status']" /></td>
            <td class="num">{{ $rm($p['nilai']) }}</td><td class="num">{{ $rm($hasil->diterimaUntuk($p['kod'])) }}</td>
          </tr>
        @endforeach
        </tbody>
      </table></div>
    @endif
  @endif
</div>
