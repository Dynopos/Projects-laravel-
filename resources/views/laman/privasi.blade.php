@php($k = config('dyno.pengendali'))
<x-laman.halaman title="Notis Privasi · Dyno Project OS" laluan="/privasi"
  description="Data peribadi yang Dyno Project OS kumpul, sebab ia dikumpul, siapa yang menerimanya dan hak anda di bawah Akta Perlindungan Data Peribadi 2010.">
  <article class="dokumen">
    <p class="kening">Akta 709</p>
    <h1>Notis Privasi</h1>
    <p class="dikemas">Dikemas kini: {{ date('d/m/Y', filemtime(resource_path('views/laman/privasi.blade.php'))) }}</p>

    <p>Notis ini menerangkan data peribadi yang Dyno Project OS kumpul, sebab ia dikumpul, dan hak anda di bawah Akta Perlindungan Data Peribadi 2010 (Akta 709).</p>

    <h2>1. Siapa kami</h2>
    <p><b>{{ $k['nama'] }}</b>@if($k['ssm']) (No. Pendaftaran {{ $k['ssm'] }})@endif@if($k['alamat']), {{ $k['alamat'] }}@endif.
      @if($k['emel']) Hubungi: <a href="mailto:{{ $k['emel'] }}">{{ $k['emel'] }}</a>.@endif</p>
    <p>Bagi pengguna sistem dan pelawat laman ini, kami ialah <b>Pengguna Data</b>. Bagi data klien yang syarikat pelanggan rekodkan dalam projek mereka (nama, telefon, alamat klien), syarikat itu ialah Pengguna Data dan kami ialah <b>Pemproses Data</b> yang bertindak atas arahan mereka. Jika anda klien kepada syarikat sedemikian, hubungi syarikat itu dahulu.</p>

    <h2>2. Data yang kami kumpul dan tujuannya</h2>
    <div class="jadual-skrol"><table>
      <thead><tr><th>Data</th><th>Tujuan</th></tr></thead>
      <tbody>
        <tr><td>Nama syarikat, alamat sistem, nama pemilik, e-mel dan telefon (borang daftar)</td><td>Mencipta ruang syarikat, invois, resit dan urusan langganan</td></tr>
        <tr><td>Nama, e-mel, peranan pengguna dan kata laluan (disimpan sebagai hash)</td><td>Log masuk dan menentukan apa yang anda boleh lihat</td></tr>
        <tr><td>Alamat IP, jenis pelayar, masa log masuk</td><td>Keselamatan sesi dan mengesan akses mencurigakan</td></tr>
        <tr><td>Log aktiviti (siapa buat apa dan bila)</td><td>Jejak audit untuk syarikat anda — tidak boleh dipadam atau diubah</td></tr>
        <tr><td>Status dan rujukan bayaran (tiada nombor kad — kad diproses oleh CHIP)</td><td>Mengesahkan bayaran dan rekod perakaunan</td></tr>
      </tbody>
    </table></div>
    <p>Kami tidak menggunakan alat analitik pihak ketiga, piksel iklan atau kuki penjejakan. Hanya kuki yang diperlukan digunakan: sesi log masuk dan keselamatan borang (CSRF). Kami tidak menghantar pemasaran tanpa persetujuan berasingan.</p>

    <h2>3. Siapa yang menerima data</h2>
    <p>Kami tidak menjual atau menyewa data peribadi. Data didedahkan hanya kepada: pengguna lain dalam syarikat yang sama mengikut peranan; {{ $k['hosting'] }} (server dan sandaran, di {{ $k['lokasi_server'] }}); CHIP (gerbang pembayaran, Malaysia); penyedia e-mel untuk jemputan dan peringatan; dan pihak berkuasa apabila dikehendaki undang-undang.</p>
    <p>Server kami terletak di {{ $k['lokasi_server'] }}. Kami memilih penyedia dengan perlindungan setara — penyulitan dalam transit, kawalan akses dan sandaran — selaras dengan Seksyen 129 Akta 709.</p>

    <h2>4. Tempoh penyimpanan</h2>
    <ul>
      <li>Akaun pengguna: selagi syarikat anda melanggan. Jika anda dibuang, akses ditutup serta-merta; nama anda kekal dalam log aktiviti sebagai jejak audit.</li>
      <li>Data syarikat selepas langganan tamat: mod baca sahaja untuk eksport, kemudian dipadam atas permintaan atau selepas penamatan, termasuk daripada sandaran (maksimum 90 hari).</li>
      <li>Rekod bayaran dan invois: 7 tahun untuk keperluan cukai.</li>
    </ul>

    <h2>5. Keselamatan dan pelanggaran data</h2>
    <p>Semua sambungan menggunakan HTTPS. Data setiap syarikat diasingkan di peringkat aplikasi dan pangkalan data. Jika berlaku pelanggaran data yang berisiko kepada anda, kami akan memaklumkan Pesuruhjaya Perlindungan Data Peribadi dalam masa 72 jam dan memaklumkan anda tanpa kelewatan yang tidak wajar.</p>

    <h2>6. Hak anda</h2>
    <p>Anda berhak mengakses dan mendapatkan salinan data anda, membetulkan data yang tidak tepat, menarik balik persetujuan, menghalang pemprosesan yang menyebabkan kerosakan atau kesusahan, dan menolak pemasaran langsung.
      @if($k['emel']) Hantar permintaan ke <a href="mailto:{{ $k['emel'] }}">{{ $k['emel'] }}</a>.@endif
      Kami menjawab dalam masa 21 hari. Aduan boleh dibuat kepada Jabatan Perlindungan Data Peribadi di <a href="https://www.pdp.gov.my" rel="noopener" target="_blank">pdp.gov.my</a>.</p>

    <h2>7. Perubahan notis</h2>
    <p>Tarikh di atas dikemas kini setiap kali notis ini berubah. Perubahan besar dimaklumkan kepada pemilik syarikat melalui e-mel sekurang-kurangnya 14 hari lebih awal.</p>
  </article>
</x-laman.halaman>
