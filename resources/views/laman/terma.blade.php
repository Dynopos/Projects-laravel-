@php
  $k = config('dyno.pengendali');
  $daftar = number_format(config('dyno.harga.daftar') / 100, 2);
  $baharu = number_format(config('dyno.harga.pembaharuan') / 100, 2);
@endphp
<x-laman.halaman title="Terma Perkhidmatan · Dyno Project OS" laluan="/terma"
  description="Terma langganan Dyno Project OS: harga, pembaharuan, pemilikan data, penamatan dan had liabiliti.">
  <article class="dokumen">
    <p class="kening">Langganan</p>
    <h1>Terma Perkhidmatan</h1>
    <p class="dikemas">Dikemas kini: {{ date('d/m/Y', filemtime(resource_path('views/laman/terma.blade.php'))) }}</p>

    <p>Dengan mendaftar, syarikat anda ("Pelanggan") bersetuju dengan terma ini bersama {{ $k['nama'] }}@if($k['ssm']) (No. Pendaftaran {{ $k['ssm'] }})@endif ("Dyno"). Pelanggan yang menandatangani perjanjian bertulis tertakluk kepada perjanjian itu.</p>

    <h2>1. Perkhidmatan</h2>
    <p>Dyno memberi hak menggunakan Dyno Project OS untuk urusan dalaman perniagaan Pelanggan, termasuk hosting, sijil SSL, sandaran harian, kemas kini dan sokongan. Fungsi teras (projek, bayaran, perbelanjaan, laporan dan eksport) tidak akan dibuang sepanjang tempoh langganan.</p>

    <h2>2. Harga dan pembaharuan</h2>
    <ul>
      <li><b>RM{{ $daftar }}</b> sekali bayar: persediaan dan langganan tahun pertama.</li>
      <li><b>RM{{ $baharu }} setahun</b> bermula tahun kedua, dibayar dalam sistem (halaman Langganan). Pembaharuan ditambah dari tarikh tamat.</li>
      <li>Peringatan dihantar 30, 7 dan 1 hari sebelum tamat. Jika tidak diperbaharui dalam {{ config('dyno.tempoh_tangguh') }} hari selepas tamat, sistem bertukar ke mod baca sahaja; data tidak dipadam dan boleh dieksport.</li>
      <li>Bayaran tidak dikembalikan kecuali Dyno menamatkan perkhidmatan tanpa kesalahan Pelanggan (pro rata). Harga pembaharuan boleh diubah dengan notis 60 hari sebelum tarikh pembaharuan.</li>
    </ul>

    <h2>3. Data Pelanggan</h2>
    <p>Semua data yang dimasukkan kekal milik Pelanggan. Dyno tidak menjual atau menggunakannya untuk pengiklanan, dan memprosesnya hanya untuk menyediakan perkhidmatan. Bagi data peribadi klien Pelanggan, Pelanggan ialah Pengguna Data dan Dyno ialah Pemproses Data di bawah Akta 709. Dyno akan memaklumkan Pelanggan dalam masa 24 jam jika berlaku pelanggaran data yang melibatkan Data Pelanggan. Butiran lanjut dalam <a href="/privasi">Notis Privasi</a>.</p>

    <h2>4. Tanggungjawab Pelanggan</h2>
    <p>Menjaga kata laluan pengguna, membatalkan akses staf yang berhenti, memastikan ketepatan data yang dimasukkan, dan tidak menggunakan sistem untuk tujuan menyalahi undang-undang atau cuba mengakses data syarikat lain.</p>

    <h2>5. Had liabiliti</h2>
    <p>Liabiliti keseluruhan Dyno terhad kepada jumlah yuran yang dibayar oleh Pelanggan dalam 12 bulan sebelum tuntutan. Dyno tidak bertanggungjawab atas kerugian tidak langsung atau gangguan di luar kawalan munasabahnya. Had ini tidak terpakai bagi kecuaian melampau atau penipuan.</p>

    <h2>6. Penamatan</h2>
    <p>Pelanggan boleh berhenti dengan tidak membuat pembaharuan. Selepas tamat, data boleh dieksport dalam mod baca sahaja, kemudian dipadam atas permintaan atau selepas penamatan (termasuk sandaran, maksimum 90 hari). Dyno boleh menamatkan perkhidmatan dengan notis 60 hari dan pemulangan pro rata.</p>

    <h2>7. Am</h2>
    <p>Terma ini tertakluk kepada undang-undang Malaysia.@if($k['emel']) Notis dan pertanyaan: <a href="mailto:{{ $k['emel'] }}">{{ $k['emel'] }}</a>.@endif</p>
  </article>
</x-laman.halaman>
