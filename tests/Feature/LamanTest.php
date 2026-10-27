<?php

use App\Enums\Peranan;
use App\Models\Syarikat;

function urlUtama(string $laluan = '/'): string
{
    return 'http://'.config('dyno.domain_utama').$laluan;
}

it('landing page di domain utama: satu h1 dengan kata kunci, meta, canonical, JSON-LD', function () {
    $html = $this->get(urlUtama())->assertOk()->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('Sistem pengurusan projek kontraktor')
        ->toContain('<title>Sistem Pengurusan Projek Kontraktor | Dyno Project OS</title>')
        ->toContain('<link rel="canonical" href="https://'.config('dyno.domain_utama').'/">')
        ->toContain('property="og:image"')
        ->toContain('name="twitter:card"');

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);
    $ld = json_decode($m[1], true);
    expect($ld['@context'])->toBe('https://schema.org')
        ->and(array_column($ld['@graph'], '@type'))->toBe(['Organization', 'SoftwareApplication', 'FAQPage'])
        ->and($ld['@graph'][2]['mainEntity'])->toHaveCount(7);
});

it('setiap imej landing ada lebar, tinggi dan alt', function () {
    $html = $this->get(urlUtama())->getContent();
    preg_match_all('/<img\b[^>]*>/', $html, $m);
    foreach ($m[0] as $img) {
        expect($img)->toContain('width=')->toContain('height=')->toContain('alt=');
    }
});

it('butang Minta demo guna WhatsApp, kemudian e-mel, kemudian #mula', function () {
    config(['dyno.hubungi.whatsapp' => '+60 12-345 6789', 'dyno.hubungi.emel' => null]);
    $this->get(urlUtama())->assertSee('https://wa.me/60123456789?text=', false);

    config(['dyno.hubungi.whatsapp' => null, 'dyno.hubungi.emel' => 'jual@dyno.test']);
    $this->get(urlUtama())->assertSee('mailto:jual@dyno.test', false);

    config(['dyno.hubungi.emel' => null]);
    $this->get(urlUtama())->assertSee('href="#mula"', false);
});

it('www diubah hala 301 ke domain utama', function () {
    $this->get('http://www.'.config('dyno.domain_utama').'/')->assertStatus(301)
        ->assertRedirect('https://'.config('dyno.domain_utama').'/');
});

it('robots.txt: domain utama boleh diindeks, ruang syarikat disekat', function () {
    $this->get(urlUtama('/robots.txt'))->assertOk()->assertSee('Allow: /')->assertSee('Sitemap: https://');
    $s = syarikatBaru();
    $this->get(urlSyarikat($s, '/robots.txt'))->assertOk()->assertSee('Disallow: /');
});

it('sitemap.xml menyenaraikan landing page', function () {
    $this->get(urlUtama('/sitemap.xml'))->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<loc>https://'.config('dyno.domain_utama').'/</loc>', false);
});

it('subdomain syarikat masih app, bukan landing; halaman app noindex', function () {
    $s = syarikatBaru();
    $this->get(urlSyarikat($s))->assertRedirect(urlSyarikat($s, '/masuk'));
    $this->get(urlSyarikat($s, '/masuk'))->assertOk()->assertSee('noindex, nofollow', false)
        ->assertSee('manifest.webmanifest', false)->assertDontSee('Sistem Pengurusan Projek Kontraktor');
    ahli($s, Peranan::Pemilik);
});

it('landing page tidak memerlukan pangkalan data syarikat (tiada syarikat langsung)', function () {
    expect(Syarikat::query()->count())->toBe(0);
    $this->get(urlUtama())->assertOk();
});

it('fail statik PWA & SEO wujud', function () {
    foreach (['manifest.webmanifest', 'img/ikon-192.png', 'img/ikon-512.png', 'img/apple-touch-icon.png', 'img/laman/og.jpg', 'css/laman.css'] as $f) {
        expect(public_path($f))->toBeFile();
    }
    expect(json_decode(file_get_contents(public_path('manifest.webmanifest')), true))->name->toBe('Dyno Project OS');
});
