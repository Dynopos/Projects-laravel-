{{-- Dikongsi oleh SenaraiProjek (cipta) dan ButiranProjek (ubah). --}}
<form class="borang" wire:submit="simpan">
  <div class="g2">
    <div><label>Kod projek</label><input class="field" wire:model="form.kod">@error('form.kod')<div class="ralat">{{ $message }}</div>@enderror</div>
    <div><label>Status</label>
      <input class="field" wire:model="form.status" list="senarai-status">
      <datalist id="senarai-status"><option>Baru</option><option>Dalam progres</option><option>Tertunda</option><option>Siap</option></datalist>
      @error('form.status')<div class="ralat">{{ $message }}</div>@enderror
    </div>
    <div class="full"><label>Nama projek</label><input class="field" wire:model="form.nama">@error('form.nama')<div class="ralat">{{ $message }}</div>@enderror</div>
    <div><label>Klien</label><input class="field" wire:model="form.klien"></div>
    <div><label>Telefon</label><input class="field" wire:model="form.telefon" inputmode="tel"></div>
    <div class="full"><label>Lokasi</label><input class="field" wire:model="form.lokasi"></div>
    <div><label>Nilai kontrak (RM)</label><input class="field" wire:model="form.nilai" inputmode="decimal" @disabled($form->adaKerja)>
      @error('form.nilai')<div class="ralat">{{ $message }}</div>@else @if($form->adaKerja)<div class="hint" style="margin-top:6px;text-align:left">Jumlah semua kerja — ubah nilai dalam senarai Kerja.</div>@endif @enderror</div>
    <div><label>Tarikh mula</label><input class="field" type="date" wire:model="form.mula"></div>
    <div><label>No. invois</label><input class="field" wire:model="form.inv"></div>
    @unless($form->projek)
      <div><label>Deposit awal (RM)</label><input class="field" wire:model="form.deposit" inputmode="decimal" placeholder="0">@error('form.deposit')<div class="ralat">{{ $message }}</div>@enderror</div>
    @endunless
    <div class="full"><label>Aliran kerja</label>
      <div class="aliran">
        @foreach($peringkat as $pr)
          <label><input type="checkbox" wire:model="form.aliran.{{ $pr->kunci }}"><span>{{ $pr->label }}</span></label>
        @endforeach
      </div>
    </div>
    <div class="full"><label>Nota</label><textarea class="field" rows="2" wire:model="form.nota"></textarea></div>
  </div>
  <div class="acts">
    <button type="button" class="btn" wire:click="$set('borangBuka', false)">Batal</button>
    <button class="btn p">Simpan</button>
  </div>
</form>
