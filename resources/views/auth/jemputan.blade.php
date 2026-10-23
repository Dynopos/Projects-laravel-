<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="referrer" content="no-referrer">
  <title>Jemputan · {{ $syarikat->namaPenuh() }}</title>
  <link rel="stylesheet" href="{{ asset('css/dyno.css') }}">
  <script>try{if(localStorage.getItem('tema')==='light')document.documentElement.dataset.t='light'}catch(e){}</script>
</head>
<body>
<div class="gate">
  <div class="gatebox">
    <div class="logo" style="padding-bottom:18px"><i>{{ $syarikat->inisial() }}</i><span>{{ $syarikat->nama }}<em>{{ $syarikat->sufiks }}</em><small>{{ $syarikat->jabatan }}</small></span></div>

    @if(! $jemputan)
      <h2 style="font-family:var(--serif);margin:0 0 8px">Jemputan tidak sah</h2>
      <p style="color:var(--ink2);margin:0">Pautan ini sudah digunakan, dibatalkan atau tamat tempoh. Minta pengurus anda hantar jemputan baharu.</p>
      <a class="btn" href="{{ url('/masuk') }}" style="margin-top:18px;text-decoration:none">Ke skrin log masuk</a>
    @elseif($lainPengguna)
      <h2 style="font-family:var(--serif);margin:0 0 8px">Log keluar dahulu</h2>
      <p style="color:var(--ink2);margin:0">Jemputan ini untuk <b>{{ $jemputan->email }}</b>, tetapi anda sedang log masuk sebagai akaun lain.</p>
      <form method="post" action="{{ route('keluar') }}" style="margin-top:18px">@csrf<button class="btn">Log keluar</button></form>
    @else
      <h2 style="font-family:var(--serif);margin:0 0 6px">Sertai {{ $syarikat->namaPenuh() }}</h2>
      <p style="color:var(--ink2);margin:0 0 4px">Anda dijemput sebagai <b>{{ $jemputan->peranan->label() }}</b>.</p>
      <form method="post" action="{{ url('/jemputan/'.$token) }}">
        @csrf
        <label>E-mel</label>
        <input class="field" type="email" value="{{ $jemputan->email }}" disabled>
        @if($akaunWujud)
          <p class="hint" style="margin:12px 0 0">Anda sudah ada akaun Dyno. Masukkan kata laluan sedia ada untuk sahkan.</p>
          <label for="password">Kata laluan</label>
          <input class="field" id="password" name="password" type="password" autocomplete="current-password" required autofocus>
        @else
          <label for="name">Nama penuh</label>
          <input class="field" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>
          <label for="password">Kata laluan baharu</label>
          <input class="field" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
          <label for="password_confirmation">Ulang kata laluan</label>
          <input class="field" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        @endif
        @foreach($errors->all() as $e)<div class="ralat">{{ $e }}</div>@endforeach
        <button class="btn p" style="width:100%;margin-top:18px;justify-content:center">Terima jemputan</button>
      </form>
    @endif
  </div>
</div>
</body>
</html>
