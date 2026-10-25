@props(['jenis', 'tapisan' => []])
{{-- Pautan biasa (bukan wire:click) supaya browser memuat turun fail terus. --}}
@if(auth()->user()->peranan()?->bolehEksport())
  @php($q = array_filter($tapisan, fn ($v) => $v !== '' && $v !== null))
  <a class="btn" style="text-decoration:none" href="{{ route('eksport', ['jenis' => $jenis, 'format' => 'csv'] + $q) }}" title="Muat turun CSV (Excel)">⬇ CSV</a>
  <a class="btn" style="text-decoration:none" href="{{ route('eksport', ['jenis' => $jenis, 'format' => 'pdf'] + $q) }}" title="Muat turun PDF">⬇ PDF</a>
@endif
