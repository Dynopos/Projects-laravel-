<x-laman.halaman title="Status pendaftaran · Dyno Project OS" laluan="/daftar" description="Status pendaftaran Dyno Project OS." :noindex="true">
  <x-slot:kepala>
    @if($p->status === 'menunggu')
      <meta http-equiv="refresh" content="5">
    @endif
  </x-slot:kepala>
  <div class="selesai kad">
    @if($p->dibayar() && $p->syarikat)
      <p class="kening">Bayaran diterima</p>
      <h1>Ruang {{ $p->syarikat->namaPenuh() }} <em>sudah sedia.</em></h1>
      <p>Langganan aktif hingga <b>{{ $p->syarikat->aktif_hingga?->format('d/m/Y') }}</b>. Resit dihantar oleh CHIP ke {{ $p->butiran['emel'] }}.</p>
      <p>Langkah terakhir: tetapkan kata laluan pemilik.</p>
      <a class="btn emas besar" href="{{ route('daftar.masuk', [$p, $tanda]) }}">Tetapkan kata laluan →</a>
      <p class="bantu">Alamat sistem anda: <b>{{ $p->syarikat->url('/') }}</b> — simpan alamat ini. Pautan yang sama juga dihantar ke e-mel anda.</p>
    @elseif($p->status === 'gagal')
      <p class="kening">Bayaran tidak berjaya</p>
      <h1>Tiada caj dikenakan.</h1>
      <p>Bayaran dibatalkan atau tamat tempoh. Anda boleh mendaftar semula.</p>
      <a class="btn emas besar" href="/daftar">Cuba lagi</a>
    @else
      <p class="kening">Menunggu pengesahan</p>
      <h1>Kami sedang menunggu pengesahan bayaran.</h1>
      <p>Halaman ini dimuat semula secara automatik. Biasanya mengambil beberapa saat.</p>
      <p class="bantu">Jika anda belum membayar, kembali ke halaman bayaran CHIP atau <a href="/daftar">daftar semula</a>.</p>
    @endif
  </div>
</x-laman.halaman>
