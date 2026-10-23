@php($syarikat = app(\App\Support\SyarikatSemasa::class)->wajib())
@php($u = auth()->user())
<!DOCTYPE html>
<html lang="ms">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#0a0a09">
  <title>{{ $tajuk ?? 'Dashboard' }} · {{ $syarikat->namaPenuh() }}</title>
  <link rel="stylesheet" href="{{ asset('css/dyno.css') }}">
  <script>try{if(localStorage.getItem('tema')==='light')document.documentElement.dataset.t='light'}catch(e){}</script>
  @livewireStyles
</head>
<body>
<div class="shell"><div class="app">
  <div class="tabir" id="tabir" onclick="togolMenu(false)"></div>

  <nav class="side">
    <div class="logo"><i>{{ $syarikat->inisial() }}</i><span>{{ $syarikat->nama }}<em>{{ $syarikat->sufiks }}</em><small>{{ $syarikat->jabatan }}</small></span></div>
    <div class="navlab">MENU</div>
    <a href="{{ route('dashboard') }}" wire:navigate @class(['on' => request()->routeIs('dashboard')])><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/><rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/></svg> Dashboard</a>
    <a href="{{ route('projek') }}" wire:navigate @class(['on' => request()->routeIs('projek', 'projek.butiran')])><svg viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg> Projek</a>
    <a href="{{ route('arkib') }}" wire:navigate @class(['on' => request()->routeIs('arkib')])><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="4" rx="1.5"/><path d="M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/><path d="M10 12h4"/></svg> Projek Siap</a>
    @if($u->peranan() !== \App\Enums\Peranan::Staf)
      <div class="navlab" style="margin-top:16px">DATA</div>
      <a href="{{ route('log') }}" wire:navigate @class(['on' => request()->routeIs('log')])><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/></svg> Log Aktiviti</a>
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
