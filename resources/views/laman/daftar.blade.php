@php
  $harga = number_format(config('dyno.harga.daftar') / 100, 0);
  $baharu = number_format(config('dyno.harga.pembaharuan') / 100, 0);
  $domain = config('dyno.domain_utama');
@endphp
<x-laman.halaman title="Daftar Dyno Project OS — RM{{ $harga }} tahun pertama" laluan="/daftar"
  description="Daftar ruang syarikat anda di Dyno Project OS. RM{{ $harga }} untuk persediaan dan tahun pertama, kemudian RM{{ $baharu }} setahun. Bayar dengan FPX atau kad.">
  <div class="daftar">
    <section>
      <p class="kening">Daftar syarikat</p>
      <h1>Ruang syarikat anda,<br><em>siap dalam 2 minit.</em></h1>

      @if($bayaran === 'gagal')
        <p class="notis ralat-notis" role="alert">Bayaran tidak berjaya dan tiada caj dikenakan. Semak butiran dan cuba lagi.</p>
      @elseif($bayaran === 'batal')
        <p class="notis" role="status">Bayaran dibatalkan. Anda boleh cuba lagi bila-bila masa.</p>
      @endif

      @if(! $dibuka)
        <p class="notis" role="status">Pendaftaran online akan dibuka tidak lama lagi. Hubungi kami untuk mula sekarang.</p>
        <a class="btn emas besar" href="{{ $demo }}" @if(str_starts_with($demo, 'http')) target="_blank" rel="noopener" @endif>Hubungi kami ↗</a>
      @else
        <form method="post" action="/daftar" class="borang-daftar" novalidate>
          @csrf
          @error('bayaran')<p class="notis ralat-notis" role="alert">{{ $message }}</p>@enderror
          <div class="perangkap" aria-hidden="true"><label>Laman web <input name="laman" tabindex="-1" autocomplete="off"></label></div>

          <div class="dua">
            <div>
              <label for="nama">Nama syarikat</label>
              <input id="nama" name="nama" value="{{ old('nama') }}" maxlength="80" required autocomplete="organization" placeholder="BINAAN MAJU" @error('nama') aria-invalid="true" @enderror>
              @error('nama')<p class="ralat">{{ $message }}</p>@enderror
            </div>
            <div>
              <label for="sufiks">Sufiks <small>(pilihan)</small></label>
              <input id="sufiks" name="sufiks" value="{{ old('sufiks') }}" maxlength="20" placeholder=" SDN BHD">
            </div>
          </div>

          <label for="subdomain">Alamat sistem anda</label>
          <div class="medan-sub"><input id="subdomain" name="subdomain" value="{{ old('subdomain') }}" required autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="binaanmaju" @error('subdomain') aria-invalid="true" @enderror><span>.{{ $domain }}</span></div>
          @error('subdomain')<p class="ralat">{{ $message }}</p>@else<p class="bantu">Huruf kecil dan nombor. Alamat ini tidak boleh ditukar kemudian.</p>@enderror

          <label for="pemilik">Nama anda (pemilik)</label>
          <input id="pemilik" name="pemilik" value="{{ old('pemilik') }}" required autocomplete="name" @error('pemilik') aria-invalid="true" @enderror>
          @error('pemilik')<p class="ralat">{{ $message }}</p>@enderror

          <div class="dua">
            <div>
              <label for="emel">E-mel</label>
              <input id="emel" name="emel" type="email" value="{{ old('emel') }}" required autocomplete="email" @error('emel') aria-invalid="true" @enderror>
              @error('emel')<p class="ralat">{{ $message }}</p>@enderror
            </div>
            <div>
              <label for="telefon">No. telefon</label>
              <input id="telefon" name="telefon" type="tel" value="{{ old('telefon') }}" required autocomplete="tel" placeholder="012-345 6789" @error('telefon') aria-invalid="true" @enderror>
              @error('telefon')<p class="ralat">{{ $message }}</p>@enderror
            </div>
          </div>

          <label class="setuju"><input type="checkbox" name="setuju" value="1" @checked(old('setuju')) required>
            <span>Saya bersetuju dengan <a href="/terma" target="_blank">Terma Perkhidmatan</a> dan <a href="/privasi" target="_blank">Notis Privasi</a>.</span></label>
          @error('setuju')<p class="ralat">{{ $message }}</p>@enderror

          <button class="btn emas besar" type="submit">Teruskan ke bayaran RM{{ $harga }} →</button>
          <p class="bantu">Anda akan dibawa ke halaman bayaran selamat CHIP (FPX, kad, e-dompet). Ruang syarikat dicipta sebaik bayaran disahkan.</p>
        </form>
      @endif
    </section>

    <aside class="kad harga-kad">
      <p class="kening">Harga</p>
      <p class="harga">RM{{ $harga }}<small>tahun pertama</small></p>
      <p class="harga-kecil">Kemudian RM{{ $baharu }} setahun</p>
      <ul>
        <li>Projek, kutipan, perbelanjaan dan baki terkini</li>
        <li>Pengguna untuk pemilik, pengurus, staf dan akauntan</li>
        <li>Import Excel dan eksport CSV / PDF</li>
        <li>Hosting, sandaran harian dan kemas kini</li>
        <li>Data anda kekal milik anda dan boleh dieksport bila-bila masa</li>
      </ul>
    </aside>
  </div>

  <script>
  // Cadang alamat dari nama syarikat sehingga pengguna menaip alamat sendiri.
  (() => {
    const n = document.getElementById('nama'), s = document.getElementById('subdomain');
    if (!n || !s) return;
    let sendiri = s.value !== '';
    s.addEventListener('input', () => { sendiri = s.value !== ''; s.value = s.value.toLowerCase().replace(/[^a-z0-9-]/g, ''); });
    n.addEventListener('input', () => { if (!sendiri) s.value = n.value.toLowerCase().replace(/sdn\.? ?bhd\.?|enterprise|[^a-z0-9]/g, '').slice(0, 30); });
  })();
  </script>
</x-laman.halaman>
