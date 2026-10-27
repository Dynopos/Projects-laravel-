@php($syarikat = app(\App\Support\SyarikatSemasa::class)->wajib())
@php($u = auth()->user())
@php($bakiHari = $syarikat->bakiHari())
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0a0a09">
  <title>{{ $tajuk ?? 'Dashboard' }} · {{ $syarikat->namaPenuh() }}</title>
  <link rel="stylesheet" href="{{ asset('css/dyno.css') }}">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="apple-touch-icon" href="/img/apple-touch-icon.png">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black">
  <meta name="apple-mobile-web-app-title" content="Dyno">
  <meta name="robots" content="noindex, nofollow">
  <script>try{if(localStorage.getItem('tema')==='light')document.documentElement.dataset.t='light'}catch(e){}</script>
  @livewireStyles
</head>
<body>
<div class="shell"><div class="app">
  <div class="tabir" id="tabir" onclick="togolMenu(false)"></div>

  <nav class="side">
    <div class="logo">@if($syarikat->urlLogo())<i style="background:none;box-shadow:none;overflow:hidden"><img src="{{ $syarikat->urlLogo() }}" alt="" style="width:100%;height:100%;object-fit:contain"></i>@else<i>{{ $syarikat->inisial() }}</i>@endif<span>{{ $syarikat->nama }}<em>{{ $syarikat->sufiks }}</em><small>{{ $syarikat->jabatan }}</small></span></div>
    <div class="navlab">MENU</div>
    <a href="{{ route('dashboard') }}" wire:navigate @class(['on' => request()->routeIs('dashboard')])><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/></svg> Dashboard</a>
    <a href="{{ route('projek') }}" wire:navigate @class(['on' => request()->routeIs('projek', 'projek.butiran')])><svg viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Projek</a>
    <a href="{{ route('arkib') }}" wire:navigate @class(['on' => request()->routeIs('arkib')])><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="4" rx="1.5"/><path d="M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/><path d="M10 12h4"/></svg> Projek Siap</a>
    <a href="{{ route('bayaran') }}" wire:navigate @class(['on' => request()->routeIs('bayaran')])><svg viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="3"/><path d="M2 10h20"/></svg> Pembayaran</a>
    <a href="{{ route('belanja') }}" wire:navigate @class(['on' => request()->routeIs('belanja')])><svg viewBox="0 0 24 24"><path d="M4 7a2 2 0 012-2h11v4"/><path d="M4 7v11a2 2 0 002 2h13V9H6a2 2 0 01-2-2z"/><circle cx="16" cy="14.5" r="1.3"/></svg> Perbelanjaan</a>
    @if($u->peranan() !== \App\Enums\Peranan::Staf)
      <div class="navlab" style="margin-top:16px">DATA</div>
      @if($u->peranan()?->pengurusan())
        <a href="{{ route('import') }}" wire:navigate @class(['on' => request()->routeIs('import')])><svg viewBox="0 0 24 24"><path d="M12 16V4m0 0L8 8m4-4l4 4"/><path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/></svg> Import Excel</a>
      @endif
      <a href="{{ route('log') }}" wire:navigate @class(['on' => request()->routeIs('log')])><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg> Log Aktiviti</a>
      @if($u->peranan()?->bolehTetapan())
        <a href="{{ route('tetapan') }}" wire:navigate @class(['on' => request()->routeIs('tetapan')])><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1.1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1.1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9a1.7 1.7 0 001.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/></svg> Tetapan</a>
        <a href="{{ route('langganan') }}" wire:navigate @class(['on' => request()->routeIs('langganan')])><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="15" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4"/></svg> Langganan</a>
      @endif
      @if($u->peranan()?->bolehUrus())
        <a href="{{ route('ahli') }}" wire:navigate @class(['on' => request()->routeIs('ahli')])><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0113 0"/><path d="M19 8v6M16 11h6"/></svg> Ahli &amp; Jemputan</a>
      @endif
    @endif
    <div class="promo">
      <b>{{ $u->name }}</b>
      <p>{{ $u->peranan()?->label() }} · {{ $syarikat->namaPenuh() }}</p>
      <form method="post" action="{{ route('keluar') }}">@csrf<button type="submit">Log keluar</button></form>
    </div>
  </nav>

  <div class="main">
    <div class="topbar">
      <button class="iconbtn bmenu" title="Menu" aria-label="Menu" onclick="togolMenu()"><svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
      <div class="me">
        <button class="iconbtn" title="Tukar tema gelap / cerah" onclick="tukarTema()"><svg viewBox="0 0 24 24"><path d="M12 3a9 9 0 109 9 7 7 0 01-9-9z"/></svg></button>
        <div class="av">{{ collect(explode(' ', $u->name))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</div>
        <div><b>{{ $u->name }}</b><small>{{ $u->email }}</small></div>
      </div>
    </div>
    <div class="body">
      @if($syarikat->bacaSahaja() || ($bakiHari !== null && $bakiHari <= 30 && $u->peranan()?->bolehTetapan()))
        <div class="jalur-langganan" role="status">
          @if($syarikat->bacaSahaja())
            <span>Langganan tamat — sistem dalam mod <b>baca sahaja</b>. Data boleh dilihat dan dieksport.</span>
          @elseif($bakiHari < 0)
            <span>Langganan tamat pada {{ $syarikat->aktif_hingga->format('d/m/Y') }}. Perbaharui dalam {{ config('dyno.tempoh_tangguh') + $bakiHari }} hari untuk elak mod baca sahaja.</span>
          @else
            <span>Langganan tamat dalam <b>{{ $bakiHari }} hari</b> ({{ $syarikat->aktif_hingga->format('d/m/Y') }}).</span>
          @endif
          @if($u->peranan()?->bolehTetapan())<a class="btn sm p" href="{{ route('langganan') }}" wire:navigate>Perbaharui</a>@else<span>Hubungi pemilik syarikat.</span>@endif
        </div>
      @endif
      {{ $slot }}
    </div>
  </div>
</div></div>
<script>
function togolMenu(buka){
  const sd=document.querySelector('.side'), tb=document.getElementById('tabir');
  const nak = buka===undefined ? !sd.classList.contains('buka') : buka;
  sd.classList.toggle('buka',nak); tb.classList.toggle('on',nak);
}
function tukarTema(){
  const r=document.documentElement, cerah=r.dataset.t!=='light';
  if(cerah) r.dataset.t='light'; else delete r.dataset.t;
  try{localStorage.setItem('tema',cerah?'light':'dark')}catch(e){}
}
document.addEventListener('livewire:navigated',()=>togolMenu(false));
</script>
@livewireScripts
</body>
</html>
