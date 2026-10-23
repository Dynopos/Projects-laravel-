@props(['k', 'v', 'f' => '', 'href' => null, 'hero' => false])
<a href="{{ $href }}" wire:navigate @class(['tile', 'hero' => $hero]) style="text-decoration:none;color:inherit;display:block">
  <div class="h"><span>{{ $k }}</span><div class="arw"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8"/></svg></div></div>
  <div @class(['v', 'long' => mb_strlen($v) > 11])>{{ $v }}</div>
  <div class="f">{{ $f }}</div>
</a>
