@props(['title', 'description', 'canonical', 'image', 'imageAlt' => '', 'type' => 'website', 'noindex' => false])
@php($desc = \Illuminate\Support\Str::limit(strip_tags($description), 160, ''))
<title>{{ $title }}</title>
<meta name="description" content="{{ $desc }}">
<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="{{ $noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large' }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:locale" content="ms_MY">
<meta property="og:site_name" content="Dyno Project OS">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $desc }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $imageAlt }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $desc }}">
<meta name="twitter:image" content="{{ $image }}">
{{ $slot }}
