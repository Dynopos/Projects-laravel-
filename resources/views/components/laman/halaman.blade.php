{{-- Kerangka halaman awam selain landing (daftar, terma, privasi). --}}
@props(['title', 'description', 'laluan', 'noindex' => false])
@php($u = fn ($p) => \App\Http\Controllers\LamanController::url($p))
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0a0a09">
<x-seo.meta :title="$title" :description="$description" :canonical="$u($laluan)" :image="$u('/img/laman/og.jpg')"
  image-alt="Dyno Project OS — sistem pengurusan projek kontraktor" :noindex="$noindex" />
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="apple-touch-icon" href="/img/apple-touch-icon.png">
<link rel="stylesheet" href="/css/laman.css?v={{ filemtime(public_path('css/laman.css')) }}">
{{ $kepala ?? '' }}
</head>
<body>
<a class="langkau" href="#utama">Langkau ke kandungan</a>
<header class="nav bungkus">
  <a class="jenama" href="/" aria-label="Dyno Project OS — laman utama">
    <img src="/img/laman/dyno-logo.webp" alt="" width="44" height="44">
    <span>DYNO<em>PROJECT OS</em></span>
  </a>
  <div class="nav-butang"><a class="btn hantu" href="/">← Laman utama</a></div>
</header>
<main id="utama" class="halaman bungkus">
  {{ $slot }}
</main>
<footer class="kaki bungkus">
  <span>© {{ date('Y') }} {{ config('dyno.pengendali.nama') }}</span>
  <span class="pautan-kaki"><a href="/daftar">Daftar</a><a href="/terma">Terma</a><a href="/privasi">Notis Privasi</a></span>
</footer>
</body>
</html>
