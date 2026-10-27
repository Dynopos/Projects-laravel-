@php($rm = fn ($sen) => \App\Support\Kiraan::rm($sen))
<div>
  <div class="head">
    <div><h1>Perbelanjaan</h1><p>Setiap ringgit keluar, ikut projek dan kategori.</p></div>
    <div class="acts">
      <x-butang-eksport jenis="belanja" :tapisan="['projek' => $projek, 'kategori' => $kategori]" />
      @if($projekRekod->isNotEmpty())<button class="btn p" wire:click="bukaBorang">+ Perbelanjaan</button>@endif
    </div>
  </div>

  @if($borangBuka)
    <form class="borang" wire:submit="simpan">
      <div class="g2">
        <div class="full"><label>Projek</label>
          <select class="field" wire:model="bl.projek_id">
            <option value="">— Pilih projek —</option>
            @foreach($projekRekod as $p)<option value="{{ $p->id }}">{{ $p->kod }} — {{ $p->nama }}</option>@endforeach
          </select>@error('bl.projek_id')<div class="ralat">{{ $message }}</div>@enderror
        </div>
        <div><label>Kategori</label><select class="field" wire:model="bl.kategori">@foreach($kategoriSemua as $k)<option>{{ $k }}</option>@endforeach</select></div>
        <div><label>Amaun (RM)</label><input class="field" wire:model="bl.amaun" inputmode="decimal">@error('bl.amaun')<div class="ralat">{{ $message }}</div>@enderror</div>
        <div><label>Tarikh</label><input class="field" type="date" wire:model="bl.tarikh"></div>
        <div><label>Penerima</label><input class="field" wire:model="bl.penerima" placeholder="cth KEDAI BINAAN"></div>
        <div class="full"><label>Nota</label><input class="field" wire:model="bl.nota"></div>
      </div>
      <div class="acts"><button type="button" class="btn" wire:click="$set('borangBuka', false)">Batal</button><button class="btn p">Simpan perbelanjaan</button></div>
    </form>
  @endif

  @if($projekSemua->isEmpty())<x-tiada-projek apa="perbelanjaan" />@endif

  <x-baki-besar :masuk="$masuk" :keluar="$keluar" :nilai="$nilai" :label="$label" />

  <div class="tabs">
    <div class="seg" role="tablist" aria-label="Paparan">
      <button type="button" role="tab" aria-selected="{{ $paparan === 'senarai' ? 'true' : 'false' }}" @class(['on' => $paparan === 'senarai']) wire:click="$set('paparan', 'senarai')">Semua rekod</button>
      <button type="button" role="tab" aria-selected="{{ $paparan === 'projek' ? 'true' : 'false' }}" @class(['on' => $paparan === 'projek']) wire:click="$set('paparan', 'projek')">Ikut projek</button>
    </div>
    @if($paparan === 'senarai')
    <select class="field" style="max-width:340px" wire:model.live="projek">
      <option value="">Semua projek</option>
      @foreach($projekSemua as $p)<option value="{{ $p->id }}">{{ $p->kod }} — {{ $p->nama }}</option>@endforeach
    </select>
    @endif
    <select class="field" style="max-width:220px" wire:model.live="kategori">
      <option value="">Semua kategori</option>
      @foreach($kategoriSemua as $k)<option>{{ $k }}</option>@endforeach
    </select>
  </div>

  @if($paparan === 'projek')
    <div class="tbl"><table @class(['pnl-projek' => $bolehUntung])>
      <thead><tr><th>Projek</th><th class="num">Perbelanjaan{{ $kategori ? " · {$kategori}" : '' }}</th><th class="num">Rekod</th>
        @if($bolehUntung)<th class="num">Diterima</th><th class="num">Untung tunai</th><th class="num">Nilai kontrak</th><th class="num">Untung dijangka</th><th class="num">Margin</th>@endif
      </tr></thead>
      <tbody>
      @forelse($ikutProjek as $p)
        <tr wire:key="ip-{{ $p->id }}" class="baris-klik" wire:click="pilihProjek({{ $p->id }})" title="Lihat perbelanjaan {{ $p->kod }}">
          <td><b>{{ $p->kod }}</b><br><small style="color:var(--muted)">{{ $p->nama }}</small></td>
          <td class="num"><b>{{ $rm($p->belanja_tapis) }}</b></td>
          <td class="num">{{ $p->rekod }}</td>
          @if($bolehUntung)
            <td class="num">{{ $rm($p->diterima_sen) }}</td>
            <td @class(['num', 'rugi' => $p->untung_tunai < 0])>{{ $rm($p->untung_tunai) }}</td>
            <td class="num">{{ $rm($p->nilai_sen) }}</td>
            <td @class(['num', 'rugi' => $p->untung_jangka < 0])><b>{{ $rm($p->untung_jangka) }}</b></td>
            <td @class(['num', 'rugi' => ($p->margin ?? 0) < 0])>{{ \App\Support\UntungRugi::teksMargin($p->margin) }}</td>
          @endif
        </tr>
      @empty
        <tr><td colspan="{{ $bolehUntung ? 8 : 3 }}" class="empty">Tiada projek.</td></tr>
      @endforelse
      </tbody>
      @if($ikutProjek->isNotEmpty())
        <tfoot><tr><td><b>Jumlah · {{ $ikutProjek->count() }} projek</b></td>
          <td class="num"><b>{{ $rm($ikutProjek->sum('belanja_tapis')) }}</b></td><td class="num"><b>{{ $ikutProjek->sum('rekod') }}</b></td>
          @if($bolehUntung)<td colspan="5"></td>@endif</tr></tfoot>
      @endif
    </table></div>
    <p class="nota-pnl">Klik projek untuk lihat setiap rekod perbelanjaannya.@if($bolehUntung) Untung dikira dari semua kategori perbelanjaan projek. <a href="{{ route('pnl') }}" wire:navigate style="color:var(--biru)">Laporan Untung Rugi penuh →</a>@endif</p>
  @else

  @if($ikutKategori->isNotEmpty())
    <div class="card" style="margin-bottom:16px">
      <div class="ch"><h2>Ikut kategori</h2><b>{{ $rm($jumlahTapis) }}</b></div>
      <p class="cap">{{ $ikutKategori->sum('n') }} rekod{{ $kategori ? " · {$kategori}" : '' }}</p>
      @foreach($ikutKategori as $k)
        @php($pc = $jumlahTapis > 0 ? round($k['sen'] / $jumlahTapis * 100, 1) : 0)
        <div class="kat-baris">
          <button type="button" style="all:unset;cursor:pointer" wire:click="$set('kategori', @js($k['nama']))"><span class="tag">{{ $k['nama'] }}</span></button>
          <div class="prog kat-bar" style="min-width:0"><div class="b"><i style="width:{{ $pc }}%"></i></div></div>
          <span class="num" style="text-align:right;font-variant-numeric:tabular-nums">{{ $rm($k['sen']) }}</span>
          <small style="color:var(--muted);text-align:right">{{ $pc }}%</small>
        </div>
      @endforeach
    </div>
  @endif

  <div class="tbl"><table>
    <thead><tr><th>Tarikh</th><th>Projek</th><th>Kategori</th><th>Penerima</th><th>Direkod oleh</th><th class="num">Amaun</th><th></th></tr></thead>
    <tbody>
    @forelse($senarai as $x)
      <tr wire:key="bl-{{ $x->id }}">
        <td style="white-space:nowrap">{{ $x->tarikh?->format('d/m/Y') ?? '—' }}</td>
        <td><a href="{{ route('projek.butiran', $x->projek_id) }}" wire:navigate style="color:inherit"><b>{{ $x->projek?->kod }}</b></a><br><small style="color:var(--muted)">{{ $x->projek?->nama }}</small></td>
        <td><span class="tag">{{ $x->kategori }}</span></td>
        <td>{{ $x->penerima ?: '—' }}@if($x->nota)<br><small style="color:var(--muted)">{{ $x->nota }}</small>@endif</td>
        <td>{{ $x->perekod?->name ?? '—' }}</td>
        <td class="num">{{ $rm(\App\Support\Kiraan::sen($x->amaun)) }}</td>
        <td><div class="rowact">
          @can('delete', $x)<button class="mini" title="Padam" wire:click="padam({{ $x->id }})" wire:confirm="Padam perbelanjaan ini?"><svg viewBox="0 0 24 24"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button>@endcan
        </div></td>
      </tr>
    @empty
      <tr><td colspan="7" class="empty">Belum ada perbelanjaan{{ $projek || $kategori ? ' untuk tapisan ini' : '' }}.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  <div style="margin-top:14px">{{ $senarai->links() }}</div>
  @endif
</div>
