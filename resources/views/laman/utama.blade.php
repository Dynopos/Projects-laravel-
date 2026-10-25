@php
  $u = fn ($p) => \App\Http\Controllers\LamanController::url($p);
  $domain = config('dyno.domain_utama');
  // Soalan lazim — dipapar DAN dihantar sebagai FAQPage JSON-LD, jadi teksnya mesti sama.
  $soalan = [
    ['Siapa yang sesuai guna Dyno Project OS?', 'Kontraktor dan pemaju pembinaan yang perlu urus projek, kutipan, deposit dan perbelanjaan bersama pasukan dalam satu sistem — bukan lagi Excel yang dikongsi.'],
    ['Perlu muat turun aplikasi?', 'Tidak. Sistem dibuka melalui pelayar web. Pada iPhone dan Android, pasang ke skrin utama terus dari pelayar tanpa melalui App Store.'],
    ['Boleh bawa masuk data Excel sedia ada?', 'Ya. Import Excel memadankan lajur secara automatik, kemudian menunjukkan pratonton dan semakan jumlah sebelum apa-apa disimpan.'],
    ['Boleh staf sahkan bayaran sendiri?', 'Tidak. Bayaran yang staf rekod masuk sebagai Menunggu. Pemilik atau pengurus mesti menyemak dan mengesahkannya sebelum baki terkini berubah.'],
    ['Adakah data syarikat saya selamat?', 'Data setiap syarikat dikunci dalam kod aplikasi dan dalam pangkalan data, dan diuji secara automatik supaya tidak boleh dibaca oleh syarikat lain. Setiap tindakan direkod dalam log aktiviti yang tidak boleh dipadam.'],
    ['Adakah invois dan notifikasi WhatsApp tersedia?', 'Belum. Invois PDF, notifikasi WhatsApp, pembantu bil AI dan lampiran gambar resit ialah ciri dalam perancangan. Eksport laporan CSV dan PDF sudah tersedia.'],
  ];
  // Kunci "@..." dibina dengan "\x40" — Blade menganggap '@context' dalam templat sebagai arahan @context.
  $at = "\x40";
  $org = $u('/').'#org';
  $jsonLd = json_encode([$at.'context' => 'https://schema.org', $at.'graph' => [
    [$at.'type' => 'Organization', $at.'id' => $org, 'name' => 'Dyno Project OS', 'url' => $u('/'), 'logo' => $u('/img/ikon-512.png')],
    [$at.'type' => 'SoftwareApplication', 'name' => 'Dyno Project OS', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web, iOS, Android',
      'inLanguage' => 'ms', 'url' => $u('/'), 'image' => $u('/img/laman/dashboard.webp'), 'publisher' => [$at.'id' => $org],
      'description' => 'Sistem pengurusan projek, kutipan dan perbelanjaan untuk kontraktor dan pemaju pembinaan di Malaysia.'],
    [$at.'type' => 'FAQPage', 'mainEntity' => array_map(fn ($q) => [$at.'type' => 'Question', 'name' => $q[0],
      'acceptedAnswer' => [$at.'type' => 'Answer', 'text' => $q[1]]], $soalan)],
  ]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
@endphp
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0a0a09">
<x-seo.meta
  title="Sistem Pengurusan Projek Kontraktor | Dyno Project OS"
  description="Urus projek, kutipan, perbelanjaan dan baki terkini dalam satu sistem untuk kontraktor & pemaju di Malaysia. Staf rekod, pengurus sahkan. Web & telefon."
  :canonical="$u('/')"
  :image="$u('/img/laman/og.jpg')"
  image-alt="Dyno Project OS — sistem pengurusan projek kontraktor"
>
<script type="application/ld+json">{!! $jsonLd !!}</script>
</x-seo.meta>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="apple-touch-icon" href="/img/apple-touch-icon.png">
<link rel="preload" href="/fonts/dmsans.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/playfair.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/img/laman/dashboard.webp" as="image" fetchpriority="high">
<link rel="stylesheet" href="/css/laman.css?v={{ filemtime(public_path('css/laman.css')) }}">
</head>
<body>
<a class="langkau" href="#utama">Langkau ke kandungan</a>

<header class="nav bungkus">
  <a class="jenama" href="/" aria-label="Dyno Project OS — laman utama">
    <img src="/img/laman/dyno-logo.webp" alt="" width="44" height="44">
    <span>DYNO<em>PROJECT OS</em></span>
  </a>
  <nav class="pautan" aria-label="Navigasi utama">
    <a href="#ciri">Ciri sistem</a><a href="#pratonton">Pratonton</a><a href="#pasukan">Untuk pasukan</a><a href="#mula">Cara mula</a><a href="#soalan">Soalan</a>
  </nav>
  <div class="nav-butang">
    <button class="btn hantu" type="button" data-buka="dialog-masuk">Log masuk</button>
    <a class="btn emas" href="{{ $demo }}" @if(str_starts_with($demo, 'http')) target="_blank" rel="noopener" @endif>Minta demo</a>
  </div>
</header>

<main id="utama">
  {{-- ============ HERO ============ --}}
  <section class="hero bungkus">
    <div class="hero-teks">
      <p class="kening"><span class="titik"></span> Untuk kontraktor &amp; pemaju</p>
      <h1><span class="h1-kata-kunci">Sistem pengurusan projek kontraktor</span> Setiap projek. Setiap ringgit. <em>Dalam kawalan.</em></h1>
      <p class="lead">Dyno Project OS menghimpunkan projek, kutipan dan perbelanjaan dalam satu sistem — tahu baki sebenar setiap projek tanpa selongkar Excel satu per satu.</p>
      <div class="tindakan">
        <a class="btn emas besar" href="{{ $demo }}" @if(str_starts_with($demo, 'http')) target="_blank" rel="noopener" @endif>Minta demo percuma <span aria-hidden="true">↗</span></a>
        <a class="btn hantu besar" href="#pratonton">Lihat sistem <span aria-hidden="true">↓</span></a>
      </div>
      <p class="mikro"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg> Di pejabat atau di tapak. Web, iPhone &amp; Android.</p>
    </div>

    <div class="pentas" aria-label="Tangkapan skrin Dyno Project OS">
      <figure class="pelayar">
        <div class="bar" aria-hidden="true"><i></i><i></i><i></i><b>syarikatanda.{{ $domain }}</b></div>
        <img src="/img/laman/dashboard.webp" width="1600" height="1000" fetchpriority="high"
             alt="Dashboard Dyno Project OS: kad Baki Terkini RM227,400 dan kotak projek serta pembayaran">
      </figure>
      <div class="telefon" aria-hidden="true"><img src="/img/laman/telefon.webp" width="585" height="1266" alt="" loading="lazy" decoding="async"></div>
      <div class="kad-apung lulus" aria-hidden="true">
        <small>Kawalan bayaran</small>
        <div><span class="tanda">✓</span><p><b>Bayaran disahkan</b><br>Disemak oleh pengurus</p><span class="lencana-biru">Diterima</span></div>
      </div>
      <div class="kad-apung angka" aria-hidden="true"><small>Baki projek naik</small><b>+RM144,000</b></div>
      <p class="kapsyen">Tangkapan skrin sebenar · data demo</p>
    </div>
  </section>

  <div class="jalur bungkus" role="list">
    <small role="listitem">SATU RUANG KERJA.<br>SEMUA ANGKA SELARI.</small>
    <span role="listitem">Pengurusan projek</span><span role="listitem">Kutipan &amp; deposit</span><span role="listitem">Perbelanjaan</span><span role="listitem">Jejak audit</span><span role="listitem">Laporan CSV &amp; PDF</span>
  </div>

  {{-- ============ CIRI ============ --}}
  <section id="ciri" class="bahagian bungkus">
    <div class="kepala">
      <div><p class="kening">01 / Kejelasan kewangan</p><h2>Angka yang sama.<br>Keputusan yang lebih jelas.</h2></div>
      <p>Daripada Excel yang berbeza versi kepada satu pandangan untuk seluruh pengurusan.</p>
    </div>
    <div class="bento">
      <article class="kad">
        <p class="ikon" aria-hidden="true">↗</p>
        <h3>Baki sebenar.<br>Di depan mata.</h3>
        <p>Duit masuk ditolak perbelanjaan — angka paling besar di dashboard. Lihat semua projek atau tapis satu projek.</p>
        <div class="baki-mini"><small>Baki terkini · demo</small><b>RM227,400</b></div>
      </article>
      <article class="kad emas-kad">
        <p class="ikon" aria-hidden="true">✓</p>
        <h3>Staf rekod.<br>Pengurus sahkan.</h3>
        <p>Baki hanya berubah selepas bayaran disahkan. Pengurusan kekal pegang kawalan.</p>
        <ol class="aliran">
          <li><span>01</span> Staf rekod bayaran <em class="menunggu">Menunggu</em></li>
          <li><span>02</span> Pengurus semak <em>Disahkan ✓</em></li>
          <li><span>03</span> Baki dikemas kini <em>Automatik</em></li>
        </ol>
      </article>
      <article class="kad">
        <p class="ikon" aria-hidden="true">▤</p>
        <h3>Tahu ke mana<br>duit projek pergi.</h3>
        <p>Material, upah dan subkon. Setiap rekod ada kategori dan nama perekod.</p>
        <div class="kos">
          <div><span>Material</span><span>49%</span><i style="--w:49%"></i></div>
          <div><span>Upah pekerja</span><span>27%</span><i style="--w:27%"></i></div>
          <div><span>Subkontraktor</span><span>19%</span><i style="--w:19%"></i></div>
        </div>
        <p class="nota-kecil">Pecahan dari data demo.</p>
      </article>
    </div>
  </section>

  {{-- ============ PRATONTON ============ --}}
  <section id="pratonton" class="bahagian bungkus">
    <div class="kepala">
      <div><p class="kening">02 / Lihat lebih dekat</p><h2>Satu sistem.<br>Gambaran keseluruhan.</h2></div>
      <p>Tangkapan skrin sebenar dari Dyno Project OS. Semua angka dan nama projek ialah data demonstrasi.</p>
    </div>
    <div class="demo">
      <div class="tab" role="tablist" aria-label="Paparan sistem">
        @foreach([['dashboard', 'Dashboard'], ['projek', 'Projek'], ['sahkan', 'Sahkan bayaran'], ['belanja', 'Perbelanjaan'], ['import', 'Import Excel']] as $i => [$k, $label])
          <button role="tab" id="tab-{{ $k }}" aria-controls="panel-{{ $k }}" aria-selected="{{ $i ? 'false' : 'true' }}" @if($i) tabindex="-1" @endif>{{ $label }}</button>
        @endforeach
      </div>
      @foreach([
        ['dashboard', 'Dashboard dengan kad Baki Terkini dan 8 kotak projek serta pembayaran'],
        ['projek', 'Senarai projek aktif dengan nilai kontrak, diterima, baki dan peratus kutipan'],
        ['sahkan', 'Butiran projek dengan bayaran berstatus Menunggu dan butang Sahkan'],
        ['belanja', 'Perbelanjaan merentas projek dengan ringkasan ikut kategori'],
        ['import', 'Pratonton Import Excel dengan padanan lajur automatik dan semakan jumlah'],
      ] as $i => [$k, $alt])
        <figure id="panel-{{ $k }}" role="tabpanel" aria-labelledby="tab-{{ $k }}" @if($i) hidden @endif>
          <img src="/img/laman/{{ $k }}.webp" width="1600" height="1000" alt="{{ $alt }}" loading="lazy" decoding="async">
        </figure>
      @endforeach
    </div>
    <div class="butiran">
      <article><span>01</span><div><h3>Excel lama masuk. Laporan keluar.</h3><p>Import Excel dengan padanan lajur dan semakan sebelum simpan. Eksport CSV atau PDF ikut tapisan untuk akauntan.</p></div></article>
      <article><span>02</span><div><h3>Aliran kerja ikut cara syarikat anda.</h3><p>Tetapkan logo, peringkat projek dan kategori perbelanjaan sendiri. Projek siap diarkibkan supaya senarai utama kekal kemas.</p></div></article>
      <article><span>03</span><div><h3>Di poket, bukan hanya di pejabat.</h3><p>Pasang ke skrin utama iPhone atau Android tanpa App Store. Rekod bayaran dan belanja terus dari tapak.</p></div></article>
      <article><span>04</span><div><h3>Setiap tindakan ada jejaknya.</h3><p>Siapa, bila dan apa yang direkod. Log aktiviti dan nama perekod dikunci untuk jejak audit pengurusan.</p></div></article>
    </div>
  </section>

  {{-- ============ PASUKAN ============ --}}
  <section id="pasukan" class="bahagian latar-dalam">
    <div class="bungkus">
      <div class="kepala">
        <div><p class="kening">03 / Kawalan pasukan</p><h2>Empat peranan.<br>Tanggungjawab yang jelas.</h2></div>
        <p>Staf masuk melalui jemputan e-mel. Setiap orang mendapat akses ikut peranan — dikuatkuasa di pelayan, bukan sekadar menyorok butang.</p>
      </div>
      <div class="jadual-skrol" tabindex="0" aria-label="Jadual peranan, boleh ditatal ke tepi">
        <table class="peranan">
          <thead><tr><th scope="col">Akses &amp; tindakan</th><th scope="col">Pemilik</th><th scope="col">Pengurus</th><th scope="col">Staf</th><th scope="col">Akauntan</th></tr></thead>
          <tbody>
            <tr><th scope="row">Lihat dashboard &amp; baki</th><td>✓</td><td>✓</td><td class="biru">Projek ditugaskan</td><td>✓</td></tr>
            <tr><th scope="row">Rekod bayaran &amp; belanja</th><td>✓</td><td>✓</td><td class="biru">Sebagai Menunggu</td><td class="tiada">—</td></tr>
            <tr><th scope="row">Sahkan bayaran</th><td>✓</td><td>✓</td><td class="tiada">—</td><td class="tiada">—</td></tr>
            <tr><th scope="row">Cipta, ubah, padam projek</th><td>✓</td><td>✓</td><td class="tiada">—</td><td class="tiada">—</td></tr>
            <tr><th scope="row">Eksport CSV &amp; PDF</th><td>✓</td><td>✓</td><td class="tiada">—</td><td>✓</td></tr>
            <tr><th scope="row">Jemput &amp; urus pasukan</th><td>Semua peranan</td><td class="biru">Staf sahaja</td><td class="tiada">—</td><td class="tiada">—</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  {{-- ============ CARA MULA ============ --}}
  <section id="mula" class="bahagian bungkus">
    <div class="kepala">
      <div><p class="kening">04 / Cara bermula</p><h2>Dari data berselerak<br>ke ruang kerja tersusun.</h2></div>
      <p>Kami urus hos, sandaran dan kemas kini. Anda fokus pada projek dan pasukan.</p>
    </div>
    <ol class="langkah">
      <li><span>01</span><h3>Ruang syarikat</h3><p>Alamat syarikat anda disediakan dan jemputan dihantar kepada pemilik.</p></li>
      <li><span>02</span><h3>Ikut identiti anda</h3><p>Tetapkan logo, kod projek, kategori dan peringkat kerja syarikat.</p></li>
      <li><span>03</span><h3>Jemput pasukan</h3><p>Masukkan e-mel dan pilih peranan. Staf tetapkan kata laluan sendiri.</p></li>
      <li><span>04</span><h3>Bawa data lama</h3><p>Import Excel sedia ada. Semak pratonton sebelum data disimpan.</p></li>
    </ol>
  </section>

  {{-- ============ SOALAN ============ --}}
  <section id="soalan" class="bahagian bungkus soalan">
    <div><p class="kening">Sebelum bermula</p><h2>Soalan lazim tentang Dyno Project OS</h2></div>
    <div>
      @foreach($soalan as $i => [$q, $a])
        <details @if(! $i) open @endif><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
      @endforeach
    </div>
  </section>

  <section class="bungkus">
    <div class="cta-akhir">
      <img src="/img/laman/dyno-logo.webp" width="160" height="160" alt="" loading="lazy" decoding="async">
      <div><h2>Projek bergerak.<br>Angka mesti ikut sekali.</h2><p>Lihat bagaimana kutipan, perbelanjaan dan baki bertemu dalam satu ruang — dengan data syarikat anda sendiri.</p></div>
      <a class="btn emas besar" href="{{ $demo }}" @if(str_starts_with($demo, 'http')) target="_blank" rel="noopener" @endif>Minta demo percuma <span aria-hidden="true">↗</span></a>
    </div>
  </section>
</main>

<footer class="kaki bungkus">
  <a class="jenama" href="/"><img src="/img/laman/dyno-logo.webp" alt="" width="32" height="32" loading="lazy"><span>DYNO<em>PROJECT OS</em></span></a>
  <span>© {{ date('Y') }} Dyno Project OS · Sistem pengurusan projek untuk kontraktor &amp; pemaju di Malaysia</span>
  <a href="#utama">Kembali ke atas ↑</a>
</footer>

{{-- Pelanggan sedia ada: setiap syarikat ada alamat sendiri, jadi tanya alamatnya dahulu. --}}
<dialog class="dialog" id="dialog-masuk" aria-labelledby="masuk-tajuk">
  <form method="dialog" class="tutup-borang"><button class="tutup" aria-label="Tutup">×</button></form>
  <p class="kening">Pelanggan Dyno</p>
  <h2 id="masuk-tajuk">Log masuk ke ruang syarikat</h2>
  <form id="borang-masuk" novalidate>
    <label for="sub">Alamat syarikat anda</label>
    <div class="medan-sub"><input id="sub" name="sub" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="namasyarikat" required><span>.{{ $domain }}</span></div>
    <p class="ralat" id="ralat-sub" hidden>Masukkan alamat seperti yang ada dalam e-mel jemputan anda.</p>
    <button class="btn emas besar" type="submit">Teruskan ke log masuk ↗</button>
    <p class="nota-kecil">Belum ada akaun? Staf masuk melalui jemputan daripada pengurus syarikat anda.</p>
  </form>
</dialog>

<script>
// Tab pratonton (papan kekunci: ← → Home End)
(() => {
  const tabs = [...document.querySelectorAll('[role=tab]')];
  const pilih = t => tabs.forEach(b => {
    const ya = b === t; b.setAttribute('aria-selected', ya); b.tabIndex = ya ? 0 : -1;
    document.getElementById(b.getAttribute('aria-controls')).hidden = !ya;
  });
  tabs.forEach((b, i) => {
    b.addEventListener('click', () => pilih(b));
    b.addEventListener('keydown', e => {
      const n = { ArrowRight: (i + 1) % tabs.length, ArrowLeft: (i - 1 + tabs.length) % tabs.length, Home: 0, End: tabs.length - 1 }[e.key];
      if (n !== undefined) { e.preventDefault(); tabs[n].focus(); pilih(tabs[n]); }
    });
  });
})();
// Dialog log masuk → https://<alamat>.<domain>/masuk
(() => {
  const d = document.getElementById('dialog-masuk'), f = document.getElementById('borang-masuk'), i = document.getElementById('sub'), r = document.getElementById('ralat-sub');
  document.querySelectorAll('[data-buka="dialog-masuk"]').forEach(b => b.addEventListener('click', () => { d.showModal(); i.focus(); }));
  d.addEventListener('click', e => { if (e.target === d) d.close(); });
  f.addEventListener('submit', e => {
    e.preventDefault();
    const s = i.value.trim().toLowerCase().replace(/\.{{ str_replace('.', '\\.', $domain) }}.*$/, '').replace(/^https?:\/\//, '');
    if (!/^[a-z0-9]([a-z0-9-]{0,40}[a-z0-9])?$/.test(s)) { r.hidden = false; i.setAttribute('aria-invalid', 'true'); return; }
    location.href = 'https://' + s + '.{{ $domain }}/masuk';
  });
})();
</script>
</body>
</html>
