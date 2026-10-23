<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>Log masuk · {{ $syarikat->namaPenuh() }}</title>
  <link rel="stylesheet" href="{{ asset('css/dyno.css') }}">
  <script>try{if(localStorage.getItem('tema')==='light')document.documentElement.dataset.t='light'}catch(e){}</script>
</head>
<body>
<div class="gate">
  <form class="gatebox" method="post" action="{{ url('/masuk') }}">
    @csrf
    <div class="logo" style="padding-bottom:18px">@if($syarikat->urlLogo())<i style="background:none;box-shadow:none;overflow:hidden"><img src="{{ $syarikat->urlLogo() }}" alt="" style="width:100%;height:100%;object-fit:contain"></i>@else<i>{{ $syarikat->inisial() }}</i>@endif<span>{{ $syarikat->nama }}<em>{{ $syarikat->sufiks }}</em><small>{{ $syarikat->jabatan }}</small></span></div>
    <p style="margin:0 0 4px;color:var(--ink2)">Log masuk untuk teruskan.</p>
    <label for="email">E-mel</label>
    <input class="field" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="nama@syarikat.com">
    <label for="password">Kata laluan</label>
    <input class="field" id="password" name="password" type="password" autocomplete="current-password" required>
    <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="ingat" value="1"> Ingat saya</label>
    @error('email')<div class="ralat">{{ $message }}</div>@enderror
    <button class="btn p" style="width:100%;margin-top:18px;justify-content:center">Log Masuk</button>
    <p class="hint">Tiada akaun? Minta pengurus syarikat anda hantar jemputan.</p>
  </form>
</div>
</body>
</html>
