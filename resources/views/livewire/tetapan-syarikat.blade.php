<div>
  <div class="head">
    <div><h1>Tetapan Syarikat</h1><p>Jenama, penomboran projek, peringkat kerja dan kategori perbelanjaan.</p></div>
    @if($mesej)<div class="acts"><span class="tag" style="color:var(--biru);border-color:var(--biru)">{{ $mesej }}</span></div>@endif
  </div>

  <div class="row" style="grid-template-columns:1.4fr 1fr">
    <form class="borang" style="margin:0" wire:submit="simpanMaklumat">
      <div class="seclab" style="margin:14px 0 0"><b>Maklumat syarikat</b><span></span></div>
      <div class="g2">
        <div><label>Nama syarikat</label><input class="field" wire:model="maklumat.nama">@error('maklumat.nama')<div class="ralat">{{ $message }}</div>@enderror</div>
        <div><label>Sufiks <small style="color:var(--muted)">(warna emas, cth " SDN BHD")</small></label><input class="field" wire:model="maklumat.sufiks"></div>
        <div><label>Jabatan</label><input class="field" wire:model="maklumat.jabatan"></div>
        <div><label>Awalan kod projek</label><input class="field" wire:model="maklumat.kod_awalan" style="text-transform:uppercase">
          @error('maklumat.kod_awalan')<div class="ralat">{{ $message }}</div>@else<div class="hint" style="margin-top:6px">Projek baharu: {{ $maklumat['kod_awalan'] ?? '' }}01, {{ $maklumat['kod_awalan'] ?? '' }}02… Kod sedia ada tidak berubah.</div>@enderror</div>
        <div class="full"><label>Zon waktu</label>
          <select class="field" wire:model="maklumat.zon_waktu">
            @foreach(['Asia/Kuala_Lumpur' => 'Malaysia (Kuala Lumpur)', 'Asia/Kuching' => 'Malaysia (Kuching)', 'Asia/Singapore' => 'Singapura', 'Asia/Jakarta' => 'Indonesia (Jakarta)', 'Asia/Brunei' => 'Brunei'] as $z => $n)
              <option value="{{ $z }}">{{ $n }}</option>
            @endforeach
          </select></div>
      </div>
      <div class="acts"><button class="btn p">Simpan maklumat</button></div>
    </form>

    <div class="borang" style="margin:0">
      <div class="seclab" style="margin:14px 0 0"><b>Logo</b><span></span></div>
      <div style="display:flex;gap:18px;align-items:center;margin-top:16px">
        <div class="logo" style="padding:0"><i style="width:84px;height:84px;border-radius:16px;font-size:26px;overflow:hidden">
          @if($logo && method_exists($logo, 'temporaryUrl') && in_array($logo->extension(), ['png', 'jpg', 'jpeg', 'webp']))
            <img src="{{ $logo->temporaryUrl() }}" alt="" style="width:100%;height:100%;object-fit:contain">
          @elseif($syarikat->urlLogo())
            <img src="{{ $syarikat->urlLogo() }}" alt="" style="width:100%;height:100%;object-fit:contain">
          @else
            {{ $syarikat->inisial() }}
          @endif
        </i></div>
        <p class="hint" style="margin:0">PNG, JPG atau WebP, sekurang-kurangnya 64×64. Logo dikecilkan ke 256×256 dan dipapar di sidebar, skrin log masuk dan e-mel.</p>
      </div>
      <form wire:submit="simpanLogo">
        <input class="field" type="file" accept="image/png,image/jpeg,image/webp" wire:model="logo" style="margin-top:16px">
        <div wire:loading wire:target="logo" class="hint">Memuat naik…</div>
        @error('logo')<div class="ralat">{{ $message }}</div>@enderror
        <div class="acts">
          @if($syarikat->logo_path)<button type="button" class="btn" wire:click="buangLogo" wire:confirm="Buang logo dan guna huruf awal?">Buang</button>@endif
          <button class="btn p" @disabled(! $logo)>Simpan logo</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row" style="grid-template-columns:1fr 1fr;margin-top:16px">
    <div class="borang" style="margin:0">
      <div class="seclab" style="margin:14px 0 4px"><b>Peringkat kerja</b><span></span><small>kotak semak setiap projek</small></div>
      @foreach($peringkat as $i => $p)
        <div wire:key="pr-{{ $p->id }}" style="display:flex;gap:8px;align-items:center;margin-top:8px">
          <div style="display:flex;flex-direction:column;gap:2px">
            <button type="button" class="mini" style="height:18px" title="Naik" wire:click="alihPeringkat({{ $p->id }}, -1)" @disabled($i === 0)><svg viewBox="0 0 24 24"><path d="M6 15l6-6 6 6"/></svg></button>
            <button type="button" class="mini" style="height:18px" title="Turun" wire:click="alihPeringkat({{ $p->id }}, 1)" @disabled($loop->last)><svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></button>
          </div>
          <input class="field" wire:model="labelPeringkat.{{ $p->id }}" wire:change="simpanPeringkat({{ $p->id }})">
          <button type="button" class="mini" title="Padam" wire:click="padamPeringkat({{ $p->id }})" wire:confirm="Padam peringkat &quot;{{ $p->label }}&quot;? Tanda dalam projek sedia ada disimpan dan pulih jika ditambah semula."><svg viewBox="0 0 24 24"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button>
        </div>
        @error("labelPeringkat.$p->id")<div class="ralat">{{ $message }}</div>@enderror
      @endforeach
      <form wire:submit="tambahPeringkat" style="display:flex;gap:8px;margin-top:14px">
        <input class="field" wire:model="peringkatBaru" placeholder="Peringkat baharu, cth Pemasangan">
        <button class="btn">Tambah</button>
      </form>
      @error('peringkatBaru')<div class="ralat">{{ $message }}</div>@enderror
    </div>

    <div class="borang" style="margin:0">
      <div class="seclab" style="margin:14px 0 4px"><b>Kategori perbelanjaan</b><span></span><small>tukar nama mengemas kini rekod lama</small></div>
      @foreach($kategori as $i => $k)
        <div wire:key="kt-{{ $k->id }}" style="display:flex;gap:8px;align-items:center;margin-top:8px">
          <div style="display:flex;flex-direction:column;gap:2px">
            <button type="button" class="mini" style="height:18px" title="Naik" wire:click="alihKategori({{ $k->id }}, -1)" @disabled($i === 0)><svg viewBox="0 0 24 24"><path d="M6 15l6-6 6 6"/></svg></button>
            <button type="button" class="mini" style="height:18px" title="Turun" wire:click="alihKategori({{ $k->id }}, 1)" @disabled($loop->last)><svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></button>
          </div>
          <input class="field" wire:model="namaKategori.{{ $k->id }}" wire:change="simpanKategori({{ $k->id }})">
          <small style="color:var(--muted);white-space:nowrap;min-width:52px;text-align:right">{{ $penggunaan[$k->nama] ?? 0 }} rekod</small>
          <button type="button" class="mini" title="Padam" wire:click="padamKategori({{ $k->id }})" wire:confirm="Padam kategori &quot;{{ $k->nama }}&quot;?"><svg viewBox="0 0 24 24"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button>
        </div>
        @error("namaKategori.$k->id")<div class="ralat">{{ $message }}</div>@enderror
      @endforeach
      <form wire:submit="tambahKategori" style="display:flex;gap:8px;margin-top:14px">
        <input class="field" wire:model="kategoriBaru" placeholder="Kategori baharu, cth Elektrik">
        <button class="btn">Tambah</button>
      </form>
      @error('kategoriBaru')<div class="ralat">{{ $message }}</div>@enderror
    </div>
  </div>
</div>
