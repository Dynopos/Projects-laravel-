@php($rm = fn ($sen) => \App\Support\Kiraan::rm($sen))
<div>
  <div class="head">
    <div><h1>Dashboard</h1><p>Semua projek dan pembayaran dalam satu pandangan.</p></div>
  </div>

  <x-baki-besar :masuk="$r['diterima']" :keluar="$r['belanja']" :nilai="$r['jumlah_nilai']" :href="route('belanja')" />

  <div class="seclab"><b>Projek</b><span></span></div>
  <div class="tiles">
    <x-tile k="Total Project" :v="(string) $r['projek']" f="Semua projek" :href="route('projek')" hero />
    <x-tile k="Ended Project" :v="(string) $r['siap']" f="Siap — dalam arkib" :href="route('arkib')" />
    <x-tile k="Running Project" :v="(string) $r['jalan']" f="Dalam progres" :href="route('projek', ['kat' => 'jalan'])" />
    <x-tile k="Pending Project" :v="(string) $r['pending']" f="Baru / tertunda" :href="route('projek', ['kat' => 'pending'])" />
  </div>

  <div class="seclab"><b>Pembayaran</b><span></span></div>
  <div class="tiles">
    <x-tile k="Total Payment" :v="$rm($r['jumlah_nilai'])" f="Nilai kontrak" :href="route('projek')" hero />
    <x-tile k="Deposit Payment" :v="$rm($r['deposit'])" f="Deposit diterima" :href="route('projek')" />
    <x-tile k="Pending Payment" :v="$rm($r['tertunggak'])" f="Belum dikutip" :href="route('projek', ['susun' => 'baki'])" />
    <x-tile k="Received Payment" :v="$rm($r['diterima'])" f="Telah diterima" :href="route('projek')" />
  </div>

  @if($menunggu->isNotEmpty())
    <div class="seclab"><b>Bayaran menunggu pengesahan</b><span></span><small>{{ $r['bayaran_menunggu'] }} rekod · tidak dikira dalam baki</small></div>
    <div class="tbl"><table>
      <thead><tr><th>Projek</th><th>Jenis</th><th>Tarikh</th><th class="num">Amaun</th><th></th></tr></thead>
      <tbody>
      @foreach($menunggu as $b)
        <tr wire:key="m-{{ $b->id }}">
          <td><a href="{{ route('projek.butiran', $b->projek) }}" wire:navigate style="color:inherit"><b>{{ $b->projek->kod }}</b></a><br><small style="color:var(--muted)">{{ $b->projek->nama }}</small></td>
          <td>{{ $b->jenis }}</td>
          <td>{{ $b->tarikh?->format('d/m/Y') ?? '—' }}</td>
          <td class="num">{{ $rm(\App\Support\Kiraan::sen($b->amaun)) }}</td>
          <td>@if($bolehSahkan)<button class="btn sm biru" wire:click="sahkan({{ $b->id }})">Sahkan diterima</button>@else<span class="tag t-menunggu"><i></i>Menunggu</span>@endif</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  @endif
</div>
