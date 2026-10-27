@props(['k', 'v', 'f' => '', 'href' => null, 'hero' => false])
<{{ $href ? 'a' : 'div' }} @if($href) href="{{ $href }}" wire:navigate @endif @class(['tile', 'hero' => $hero]) style="text-decoration:none;color:inherit;display:block">
  <div class="h"><span>{{ $k }}</span>@if($href)<div class="arw"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8"/></svg></div>@endif</div>
  <div @class(['v', 'long' => mb_strlen($v) > 11])>{{ $v }}</div>
  <div class="f">{{ $f }}</div>
</{{ $href ? 'a' : 'div' }}>
