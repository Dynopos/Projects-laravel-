@php($rm = fn ($sen) => \App\Support\Kiraan::rm($sen))
@php($sen = fn ($x) => \App\Support\Kiraan::sen($x))
<div>
  <div class="head">
    <div>
      <p style="margin:0 0 4px"><a href="{{ $p->jenisStatus() === 'siap' ? route('arkib') : route('projek') }}" wire:navigate style="color:var(--biru);text-decoration:none">← Senarai projek</a></p>
      <h1>{{ $p->kod }} — {{ $p->nama }}</h1>
      <p>{{ $p->klien ?: 'Tiada nama klien' }} · <x-status :s="$p->status" /></p>
    </div>
    <div class="acts">
      @if($bolehRekod)
        <button class="btn" wire:click="bukaRekod('bayaran')">+ Bayaran</button>
        <button class="btn" wire:click="bukaRekod('belanja')">+ Perbelanjaan</button>
      @endif
      @if($bolehUrus)
        <button class="btn" wire:click="bukaRekod('kerja')">+ Kerja</button>
        <button class="btn" wire:click="bukaBorang">Ubah</button>
        <button class="btn" wire:click="padamProjek" wire:confirm="Padam {{ $p->kod }}? Semua bayaran dan perbelanjaan projek ini turut dipadam. Tindakan ini tidak boleh dibatalkan.">Padam</button>
      @endif
    </div>
  </div>

  @if($borangBuka)
    @include('livewire.borang-projek')
  @endif

  @if($rekod === 'bayaran')
    <form class="borang" wire:submit="simpanBayaran">
      <div class="g2">
        @if($kerja->isNotEmpty())
          <div class="full"><label>Kerja</label><select class="field" wire:model="b.kerja_id"><option value="">— Tidak ditetapkan —</option>@foreach($kerja as $kj)<option value="{{ $kj->id }}">{{ $kj->nama }}{{ $kj->siap ? ' (siap)' : '' }}</option>@endforeach</select></div>
        @endif
        <div><label>Jenis</label><input class="field" wire:model="b.jenis" list="jenis-bayaran">
          <datalist id="jenis-bayaran"><option>Deposit</option><option>Bayaran Progres</option><option>Bayaran Akhir</option></datalist></div>
        <div><label>Amaun (RM)</label><input class="field" wire:model="b.amaun" inputmode="decimal">@error('b.amaun')<div class="ralat">{{ $message }}</div>@enderror</div>
        <div><label>Tarikh</label><input class="field" type="date" wire:model="b.tarikh"></div>
        <div><label>Kaedah</label><input class="field" wire:model="b.kaedah"></div>
        <div><label>Rujukan</label><input class="field" wire:model="b.ruj"></div>
        @if($pengurusan)
          <div><label>Status</label><select class="field" wire:model="b.status"><option>Diterima</option><option>Menunggu</option></select></div>
        @else
          <div class="full"><div class="note" style="margin:14px 0 0">Bayaran direkod sebagai <b>Menunggu</b> dan hanya dikira dalam baki selepas pengurus mengesahkannya.</div></div>
        @endif
      </div>
      <div class="acts"><button type="button" class="btn" wire:click="$set('rekod', null)">Batal</button><button class="btn p">Simpan bayaran</button></div>
    </form>
  @elseif($rekod === 'belanja')
    <form class="borang" wire:submit="simpanBelanja">
      <div class="g2">
        @if($kerja->isNotEmpty())
          <div class="full"><label>Kerja</label><select class="field" wire:model="bl.kerja_id"><option value="">— Tidak ditetapkan —</option>@foreach($kerja as $kj)<option value="{{ $kj->id }}">{{ $kj->nama }}{{ $kj->siap ? ' (siap)' : '' }}</option>@endforeach</select></div>
        @endif
        <div><label>Kategori</label><select class="field" wire:model="bl.kategori">@foreach($kategori as $k)<option>{{ $k }}</option>@endforeach</select></div>
        <div><label>Amaun (RM)</label><input class="field" wire:model="bl.amaun" inputmode="decimal">@error('bl.amaun')<div class="ralat">{{ $message }}</div>@enderror</div>
        <div><label>Tarikh</label><input class="field" type="date" wire:model="bl.tarikh"></div>
        <div><label>Penerima</label><input class="field" wire:model="bl.penerima"></div>
        <div class="full"><label>Nota</label><input class="field" wire:model="bl.nota"></div>
      </div>
      <div class="acts"><button type="button" class="btn" wire:click="$set('rekod', null)">Batal</button><button class="btn p">Simpan perbelanjaan</button></div>
    </form>
  @elseif($rekod === 'kerja')
    <form class="borang" wire:submit="simpanKerja">
      @if(! $kerjaId && $kerja->isEmpty() && $p->nilai_sen > 0)
        <div class="note" style="margin:14px 0 0">Kerja pertama: nilai asal projek ({{ $rm($p->nilai_sen) }}) dan semua rekod sedia ada akan disimpan sebagai kerja "{{ $p->nama }}". Nilai kontrak projek jadi jumlah semua kerja.</div>
      @endif
      <div class="g2">
        <div class="full"><label>Nama kerja</label><input class="field" wire:model="k.nama" placeholder="cth Pasang alumglass bilik air">@error('k.nama')<div class="ralat">{{ $message }}</div>@enderror</div>
        <div><label>Nilai (RM)</label><input class="field" wire:model="k.nilai" inputmode="decimal">@error('k.nilai')<div class="ralat">{{ $message }}</div>@enderror</div>
        <div><label>Tarikh</label><input class="field" type="date" wire:model="k.tarikh"></div>
        <div class="full"><label>Nota</label><input class="field" wire:model="k.nota"></div>
      </div>
      <div class="acts"><button type="button" class="btn" wire:click="$set('rekod', null)">Batal</button><button class="btn p">{{ $kerjaId ? 'Simpan perubahan' : 'Tambah kerja' }}</button></div>
    </form>
  @endif

  <x-baki-besar :masuk="$p->diterima_sen" :keluar="$p->belanja_sen" :nilai="$p->nilai_sen" :label="$p->kod" />

  <div class="card" style="margin-bottom:16px">
    <dl class="butiran">
      <div><dt>Nilai kontrak</dt><dd>{{ $rm($p->nilai_sen) }}</dd></div>
      <div><dt>Diterima</dt><dd>{{ $rm($p->diterima_sen) }} ({{ $p->peratus }}%)</dd></div>
      <div><dt>Baki kontrak</dt><dd>{{ $rm($p->baki_sen) }}</dd></div>
      <div><dt>Lokasi</dt><dd>{{ $p->lokasi ?: '—' }}</dd></div>
      <div><dt>Telefon</dt><dd>@if($p->telefon)<a href="tel:{{ preg_replace('/\s/', '', $p->telefon) }}" style="color:var(--biru)">{{ $p->telefon }}</a>@else — @endif</dd></div>
      <div><dt>Mula · Invois</dt><dd>{{ $p->mula?->format('d/m/Y') ?? '—' }} · {{ $p->inv ?: '—' }}</dd></div>
    </dl>
    <div class="aliran" style="margin-top:16px">
      @foreach($peringkat as $pr)
        <span class="tag" @if($p->aliran[$pr->kunci] ?? false) style="color:var(--biru);border-color:var(--biru)" @endif>{{ ($p->aliran[$pr->kunci] ?? false) ? '✓' : '○' }} {{ $pr->label }}</span>
      @endforeach
    </div>
    @if($p->nota)<p style="margin:14px 0 0;color:var(--ink2)">{{ $p->nota }}</p>@endif
  </div>


  @if($kerja->isNotEmpty())
    <div class="seclab"><b>Kerja dalam projek ini</b><span></span><small>{{ $kerja->count() }} kerja · {{ $kerja->where('siap', true)->count() }} siap</small></div>
    <div class="tbl" style="margin-bottom:16px"><table class="pnl-projek">
      <thead><tr><th>Kerja</th><th class="num">Nilai</th><th class="num">Diterima</th><th class="num">Perbelanjaan</th>
        @if($bolehUntung)<th class="num">Untung setakat ini</th><th class="num">Untung bila siap</th>@endif<th></th></tr></thead>
      <tbody>
      @foreach($kerja as $kj)
        <tr wire:key="kj-{{ $kj->id }}">
          <td><b>{{ $kj->nama }}</b><br><small style="color:var(--muted)">{{ $kj->tarikh?->format('d/m/Y') ?? '—' }}</small>
            @if($bolehUrus)<button type="button" class="tag kerja-status" wire:click="togolSiap({{ $kj->id }})" title="Tukar status">{{ $kj->siap ? '✓ Siap' : '○ Dalam progres' }}</button>
            @else<span class="tag">{{ $kj->siap ? '✓ Siap' : '○ Dalam progres' }}</span>@endif</td>
          <td class="num">{{ $rm($kj->nilai_sen) }}</td>
          <td class="num">{{ $rm($kj->diterima_sen) }}</td>
          <td class="num">{{ $rm($kj->belanja_sen) }}</td>
          @if($bolehUntung)
            <td @class(['num', 'rugi' => $kj->diterima_sen - $kj->belanja_sen < 0])>{{ $rm($kj->diterima_sen - $kj->belanja_sen) }}</td>
            <td @class(['num', 'rugi' => $kj->nilai_sen - $kj->belanja_sen < 0])><b>{{ $rm($kj->nilai_sen - $kj->belanja_sen) }}</b></td>
          @endif
          <td><div class="rowact">
            @if($bolehUrus)
              <button class="mini" title="Ubah" wire:click="ubahKerja({{ $kj->id }})"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4z"/></svg></button>
              <button class="mini" title="Padam" wire:click="padamKerja({{ $kj->id }})" wire:confirm="Padam kerja {{ $kj->nama }}? Bayaran dan perbelanjaannya TIDAK dipadam — ia jadi 'Tidak ditetapkan'."><svg viewBox="0 0 24 24"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button>
            @endif
          </div></td>
        </tr>
      @endforeach
      @if($tanpaKerja['diterima'] || $tanpaKerja['belanja'])
        <tr class="redup"><td><i>Tidak ditetapkan</i><br><small>Rekod tanpa kerja</small></td><td class="num">—</td>
          <td class="num">{{ $rm($tanpaKerja['diterima']) }}</td><td class="num">{{ $rm($tanpaKerja['belanja']) }}</td>
          @if($bolehUntung)<td class="num">{{ $rm($tanpaKerja['diterima'] - $tanpaKerja['belanja']) }}</td><td></td>@endif<td></td></tr>
      @endif
      </tbody>
      <tfoot><tr><td><b>Jumlah projek</b></td><td class="num"><b>{{ $rm($p->nilai_sen) }}</b></td><td class="num"><b>{{ $rm($p->diterima_sen) }}</b></td>
        <td class="num"><b>{{ $rm($p->belanja_sen) }}</b></td>
        @if($bolehUntung)<td @class(['num', 'rugi' => $p->diterima_sen - $p->belanja_sen < 0])><b>{{ $rm($p->diterima_sen - $p->belanja_sen) }}</b></td>
          <td @class(['num', 'rugi' => $p->nilai_sen - $p->belanja_sen < 0])><b>{{ $rm($p->nilai_sen - $p->belanja_sen) }}</b></td>@endif<td></td></tr></tfoot>
    </table></div>
  @elseif($bolehUrus)
    <p class="nota-pnl" style="margin-top:-6px">Projek kecil berulang (cth pasang alumglass RM400, kemudian RM250)? Tekan <b>+ Kerja</b> untuk tambah kerja baharu dalam projek ini — setiap kerja ada nilai, bayaran dan perbelanjaan sendiri.</p>
  @endif

  @if($bolehUntung)
    @php($untungTunai = $p->diterima_sen - $p->belanja_sen)
    @php($untungJangka = $p->nilai_sen - $p->belanja_sen)
    @php($margin = \App\Support\UntungRugi::margin($untungJangka, $p->nilai_sen))
    @php($jumlahBelanja = $belanjaKategori->sum('sen'))
    <div class="seclab"><b>Untung rugi projek</b><span></span><a href="{{ route('pnl') }}" wire:navigate style="font-size:12px;color:var(--biru)">Semua projek →</a></div>
    <div class="tiles tiga">
      <x-tile k="Untung setakat ini" :v="\App\Support\Kiraan::rm($untungTunai)" :f="$untungTunai < 0 ? 'Belanja lebih dari duit diterima' : 'Duit diterima − perbelanjaan'" />
      <x-tile k="Untung bila siap" :v="\App\Support\Kiraan::rm($untungJangka)" f="Jika klien bayar penuh: kontrak − perbelanjaan" hero />
      <x-tile k="% untung bila siap" :v="\App\Support\UntungRugi::teksMargin($margin)" :f="$untungJangka < 0 ? 'Projek ini rugi' : 'Untung bila siap ÷ nilai kontrak'" />
    </div>
    @if($belanjaKategori->isNotEmpty())
      <div class="card" style="margin-bottom:16px">
        <div class="ch"><h2>Perbelanjaan ikut kategori</h2><b>{{ $rm($jumlahBelanja) }}</b></div>
        <p class="cap">{{ $belanjaKategori->sum('n') }} rekod · {{ $p->nilai_sen > 0 ? round($jumlahBelanja / $p->nilai_sen * 100, 1) : 0 }}% daripada nilai kontrak</p>
        @foreach($belanjaKategori as $k)
          @php($pc = $jumlahBelanja > 0 ? round($k['sen'] / $jumlahBelanja * 100, 1) : 0)
          <div class="kat-baris">
            <span class="tag">{{ $k['nama'] }}</span>
            <div class="prog kat-bar" style="min-width:0"><div class="b"><i style="width:{{ $pc }}%"></i></div></div>
            <span class="num" style="text-align:right">{{ $rm($k['sen']) }}</span>
            <small style="color:var(--muted);text-align:right">{{ $pc }}%</small>
          </div>
        @endforeach
      </div>
    @endif
  @endif

  <div class="seclab"><b>Bayaran</b><span></span><small>{{ $bayaran->count() }} rekod</small></div>
  <div class="tbl" style="margin-bottom:16px"><table>
    <thead><tr><th>Tarikh</th><th>Jenis</th><th>Kaedah · Rujukan</th><th>Status</th><th class="num">Amaun</th><th></th></tr></thead>
    <tbody>
    @forelse($bayaran as $b)
      <tr wire:key="b-{{ $b->id }}">
        <td>{{ $b->tarikh?->format('d/m/Y') ?? '—' }}</td>
        <td>{{ $b->jenis }}@if($b->kerja)<br><small style="color:var(--muted)">{{ $b->kerja->nama }}</small>@endif</td>
        <td>{{ $b->kaedah ?: '—' }}@if($b->ruj)<br><small style="color:var(--muted)">{{ $b->ruj }}</small>@endif</td>
        <td><span class="tag t-{{ \Illuminate\Support\Str::slug($b->status) }}"><i></i>{{ $b->status }}</span></td>
        <td class="num">{{ $rm($sen($b->amaun)) }}</td>
        <td><div class="rowact">
          @can('sahkan', $b)<button class="btn sm biru" wire:click="sahkan({{ $b->id }})">Sahkan</button>@endcan
          @can('delete', $b)<button class="mini" title="Padam" wire:click="padamBayaran({{ $b->id }})" wire:confirm="Padam bayaran ini?"><svg viewBox="0 0 24 24"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button>@endcan
        </div></td>
      </tr>
    @empty
      <tr><td colspan="6" class="empty">Belum ada bayaran.</td></tr>
    @endforelse
    </tbody>
  </table></div>

  <div class="seclab"><b>Perbelanjaan</b><span></span><small>{{ $belanja->count() }} rekod</small></div>
  <div class="tbl"><table>
    <thead><tr><th>Tarikh</th><th>Kategori</th><th>Penerima</th><th>Direkod oleh</th><th class="num">Amaun</th><th></th></tr></thead>
    <tbody>
    @forelse($belanja as $x)
      <tr wire:key="bl-{{ $x->id }}">
        <td>{{ $x->tarikh?->format('d/m/Y') ?? '—' }}</td>
        <td><span class="tag">{{ $x->kategori }}</span>@if($x->kerja)<br><small style="color:var(--muted)">{{ $x->kerja->nama }}</small>@endif</td>
        <td>{{ $x->penerima ?: '—' }}@if($x->nota)<br><small style="color:var(--muted)">{{ $x->nota }}</small>@endif</td>
        <td>{{ $x->perekod?->name ?? '—' }}</td>
        <td class="num">{{ $rm($sen($x->amaun)) }}</td>
        <td><div class="rowact">
          @can('delete', $x)<button class="mini" title="Padam" wire:click="padamBelanja({{ $x->id }})" wire:confirm="Padam perbelanjaan ini?"><svg viewBox="0 0 24 24"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button>@endcan
        </div></td>
      </tr>
    @empty
      <tr><td colspan="6" class="empty">Belum ada perbelanjaan.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
