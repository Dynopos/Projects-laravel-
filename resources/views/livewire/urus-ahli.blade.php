@php($zon = app(\App\Support\SyarikatSemasa::class)->wajib()->zon_waktu)
<div>
  <div class="head">
    <div><h1>Ahli &amp; Jemputan</h1><p>Tiada daftar sendiri — staf hanya boleh masuk melalui jemputan e-mel.</p></div>
  </div>

  <form class="borang" wire:submit="jemput">
    <div class="g2">
      <div><label>E-mel staf</label><input class="field" type="email" wire:model="email" placeholder="nama@syarikat.com" autocomplete="off">@error('email')<div class="ralat">{{ $message }}</div>@enderror</div>
      <div><label>Peranan</label>
        <select class="field" wire:model="peranan">
          @foreach($pilihan as $p)<option value="{{ $p->value }}">{{ $p->label() }}</option>@endforeach
        </select>@error('peranan')<div class="ralat">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="acts" style="align-items:center">
      @if($mesej)<span style="margin-right:auto;color:var(--biru);font-size:13px">{{ $mesej }}</span>@endif
      <button class="btn p" wire:loading.attr="disabled">Hantar jemputan</button>
    </div>
  </form>

  @if($jemputan->isNotEmpty())
    <div class="seclab"><b>Jemputan belum diterima</b><span></span><small>{{ $jemputan->count() }}</small></div>
    <div class="tbl" style="margin-bottom:16px"><table>
      <thead><tr><th>E-mel</th><th>Peranan</th><th>Dijemput oleh</th><th>Tamat</th><th></th></tr></thead>
      <tbody>
      @foreach($jemputan as $j)
        <tr wire:key="j-{{ $j->id }}">
          <td><b>{{ $j->email }}</b></td>
          <td><span class="tag">{{ $j->peranan->label() }}</span></td>
          <td>{{ $j->penjemput?->name ?? '—' }}</td>
          <td>@if($j->sah()){{ $j->tamat_pada->timezone($zon)->format('d/m/Y') }}@else<span class="tag t-tertunda"><i></i>Tamat tempoh</span>@endif</td>
          <td><div class="rowact">
            @if($saya->bolehUrusPeranan($j->peranan))
              <button class="btn sm biru" wire:click="hantarSemula({{ $j->id }})">Hantar semula</button>
              <button class="btn sm" wire:click="batal({{ $j->id }})" wire:confirm="Batalkan jemputan untuk {{ $j->email }}?">Batal</button>
            @endif
          </div></td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  @endif

  <div class="seclab"><b>Ahli syarikat</b><span></span><small>{{ $ahli->where('aktif', true)->count() }} aktif</small></div>
  <div class="tbl"><table>
    <thead><tr><th>Nama</th><th>E-mel</th><th>Peranan</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($ahli as $k)
      @php($boleh = $k->user_id !== auth()->id() && $saya->bolehUrusPeranan($k->peranan))
      <tr wire:key="k-{{ $k->id }}" @unless($k->aktif) style="opacity:.55" @endunless>
        <td><b>{{ $k->user->name }}</b>@if($k->user_id === auth()->id()) <small style="color:var(--muted)">(anda)</small>@endif</td>
        <td>{{ $k->user->email }}</td>
        <td>
          @if($boleh && count($pilihan) > 1)
            <select class="field" style="padding:6px 10px;max-width:150px" wire:change="tukarPeranan({{ $k->id }}, $event.target.value)">
              @foreach($pilihan as $p)<option value="{{ $p->value }}" @selected($p === $k->peranan)>{{ $p->label() }}</option>@endforeach
            </select>
          @else
            <span class="tag">{{ $k->peranan->label() }}</span>
          @endif
        </td>
        <td>@if($k->aktif)<span class="tag t-diterima"><i></i>Aktif</span>@else<span class="tag t-tertunda"><i></i>Tidak aktif</span>@endif</td>
        <td><div class="rowact">
          @if($boleh)
            <button class="btn sm" wire:click="togolAktif({{ $k->id }})" @if($k->aktif) wire:confirm="Nyahaktif {{ $k->user->name }}? Dia tidak boleh log masuk ke syarikat ini lagi." @endif>{{ $k->aktif ? 'Nyahaktif' : 'Aktifkan' }}</button>
          @endif
        </div></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div>
