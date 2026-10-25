@php($rm = fn ($sen) => \App\Support\Kiraan::rm($sen))
<div>
  <div class="head">
    <div>
      <h1>{{ $arkib ? 'Projek Siap' : 'Projek' }}</h1>
      <p>{{ $arkib ? 'Projek yang sudah siap, disimpan berasingan.' : 'Projek aktif — baru, dalam progres dan tertunda.' }}</p>
    </div>
    <div class="acts">
      <x-butang-eksport :jenis="$arkib ? 'arkib' : 'projek'" :tapisan="['q' => $q, 'kat' => $kat, 'susun' => $susun]" />
      @if($bolehCipta && ! $arkib)<button class="btn p" wire:click="bukaBorang">+ Projek baharu</button>@endif
    </div>
  </div>

  @if($borangBuka)
    @include('livewire.borang-projek')
  @endif

  @if(! $arkib && $bilSiap)
    <div class="note" style="margin:0 0 14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
      <span><b>{{ $bilSiap }} projek siap</b> disimpan berasingan supaya senarai ini kekal bersih.</span>
      <a class="btn sm" style="margin-left:auto;text-decoration:none" href="{{ route('arkib') }}" wire:navigate>Buka Projek Siap →</a>
    </div>
  @endif

  <div class="tabs">
    <input class="field" style="max-width:320px" placeholder="Cari projek, klien atau kod…" wire:model.live.debounce.300ms="q">
    <select class="field" style="max-width:200px" wire:model.live="susun">
      <option value="kod">Susun: kod</option><option value="baki">Baki tertinggi</option>
      <option value="nilai">Nilai tertinggi</option><option value="pct">% dikutip</option>
    </select>
    @if($kat)
      <button class="tapis" wire:click="$set('kat', '')">{{ ['jalan' => 'Dalam progres', 'pending' => 'Baru / tertunda', 'siap' => 'Siap'][$kat] ?? $kat }} <b>×</b></button>
    @endif
  </div>

  <div class="tbl"><table>
    <thead><tr><th>Kod</th><th>Projek</th><th>Lokasi</th><th>Telefon</th><th>Status</th>
      <th class="num">Nilai</th><th class="num">Diterima</th><th class="num">Baki</th><th>Kutipan</th></tr></thead>
    <tbody>
    @forelse($rows as $p)
      <tr wire:key="p-{{ $p->id }}">
        <td><a href="{{ route('projek.butiran', $p) }}" wire:navigate style="color:inherit"><b>{{ $p->kod }}</b></a>@if($p->inv)<br><small style="color:var(--muted)">{{ $p->inv }}</small>@endif</td>
        <td class="wnama"><a href="{{ route('projek.butiran', $p) }}" wire:navigate style="color:inherit;text-decoration:none"><b>{{ $p->nama }}</b></a><br><small style="color:var(--muted)">{{ $p->klien }}</small></td>
        <td>{{ $p->lokasi ?: '—' }}</td>
        <td>@if($p->telefon)<a href="tel:{{ preg_replace('/\s/', '', $p->telefon) }}" style="color:var(--biru);text-decoration:none">{{ $p->telefon }}</a>@else — @endif</td>
        <td><x-status :s="$p->status" /></td>
        <td class="num">{{ $rm($p->nilai_sen) }}</td>
        <td class="num">{{ $rm($p->diterima_sen) }}</td>
        <td class="num" @if($p->baki_sen <= 0) style="color:var(--gold);font-weight:600" @endif>{{ $rm($p->baki_sen) }}</td>
        <td><div class="prog"><div class="b"><i style="width:{{ $p->peratus }}%"></i></div><small>{{ $p->peratus }}%</small></div></td>
      </tr>
    @empty
      <tr><td colspan="9" class="empty">Tiada rekod padan.</td></tr>
    @endforelse
    </tbody>
    @if($rows->isNotEmpty())
      <tfoot><tr style="background:var(--hover);font-weight:700">
        <td colspan="5" style="padding:13px 16px">JUMLAH · {{ $rows->count() }} projek</td>
        <td class="num">{{ $rm($rows->sum('nilai_sen')) }}</td>
        <td class="num">{{ $rm($rows->sum('diterima_sen')) }}</td>
        <td class="num">{{ $rm($rows->sum('baki_sen')) }}</td><td></td>
      </tr></tfoot>
    @endif
  </table></div>
</div>
