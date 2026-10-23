@props(['s'])
{{-- k-* = kategori (jaring), t-* = padanan tepat; t-* diisytihar kemudian dalam CSS supaya menang. --}}
<span class="tag k-{{ \App\Support\Kiraan::jenisStatus($s) }} t-{{ \Illuminate\Support\Str::slug((string) $s) }}"><i></i>{{ $s ?: '—' }}</span>
