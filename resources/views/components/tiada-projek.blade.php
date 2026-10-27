@props(['apa'])
{{-- Bayaran & perbelanjaan sentiasa milik satu projek — tanpa projek, butang rekod tidak wujud. --}}
<div class="card kosong-projek">
  <h2>Belum ada projek</h2>
  @if(auth()->user()->peranan()?->pengurusan())
    <p>Setiap {{ $apa }} direkod di bawah satu projek. Cipta projek dahulu — selepas itu butang <b>+ {{ ucfirst($apa) }}</b> muncul di sini dan dalam halaman projek.</p>
    <a class="btn p" href="{{ route('projek') }}" wire:navigate>+ Cipta projek</a>
  @else
    <p>Tiada projek ditugaskan kepada anda lagi. Minta pemilik atau pengurus tugaskan projek supaya anda boleh merekod {{ $apa }}.</p>
  @endif
</div>
