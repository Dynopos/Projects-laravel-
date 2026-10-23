@props(['masuk', 'keluar', 'nilai', 'label' => 'semua projek'])
{{-- Angka paling penting dalam sistem, jadi paling besar. Sama seperti bakiBesar() versi HTML. --}}
@php($baki = $masuk - $keluar)
<div class="baki-besar">
  <div class="lab">Baki terkini · {{ $label }}</div>
  <div class="angka">{{ \App\Support\Kiraan::rm($baki) }}</div>
  <div class="cap">
    @if($baki < 0)<span class="awas">Perbelanjaan melebihi duit masuk</span>@else Duit masuk − perbelanjaan @endif
  </div>
  <div class="pecah">
    <div><small>Duit masuk</small><b>{{ \App\Support\Kiraan::rm($masuk) }}</b></div>
    <div><small>Perbelanjaan</small><b>{{ \App\Support\Kiraan::rm($keluar) }}</b></div>
    <div><small>Belum dikutip</small><b>{{ \App\Support\Kiraan::rm(max(0, $nilai - $masuk)) }}</b></div>
    <div><small>Untung kasar semasa</small><b>{{ \App\Support\Kiraan::rm($nilai - $keluar) }}</b></div>
  </div>
</div>
