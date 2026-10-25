<?php

namespace App\Http\Controllers;

/**
 * Laman awam di domain utama (dynoproject.my): landing page, robots.txt, sitemap.
 * Ruang syarikat (subdomain) tidak melalui pengawal ini.
 */
class LamanController extends Controller
{
    public function utama()
    {
        return view('laman.utama', ['demo' => self::pautanDemo()]);
    }

    /** Domain utama boleh diindeks; setiap ruang syarikat (subdomain/domain sendiri) disekat. */
    public function robots(bool $utama)
    {
        $isi = $utama
            ? "User-agent: *\nAllow: /\n\nSitemap: ".self::url('/sitemap.xml')."\n"
            : "User-agent: *\nDisallow: /\n";

        return response($isi, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap()
    {
        $urls = [['loc' => self::url('/'), 'priority' => '1.0', 'lastmod' => date('Y-m-d', filemtime(resource_path('views/laman/utama.blade.php')))]];

        return response()->view('laman.sitemap', compact('urls'))->header('Content-Type', 'application/xml');
    }

    /** URL mutlak https pada domain utama — untuk canonical, OG dan sitemap. */
    public static function url(string $laluan = '/'): string
    {
        return 'https://'.config('dyno.domain_utama').'/'.ltrim($laluan, '/');
    }

    /** Butang "Minta demo": WhatsApp, kemudian e-mel, kemudian tatal ke #mula. */
    public static function pautanDemo(): string
    {
        $wa = preg_replace('/\D/', '', (string) config('dyno.hubungi.whatsapp'));
        if ($wa !== '') {
            return 'https://wa.me/'.$wa.'?text='.rawurlencode('Salam, saya berminat dengan demo Dyno Project OS.');
        }
        if ($e = config('dyno.hubungi.emel')) {
            return 'mailto:'.$e.'?subject='.rawurlencode('Demo Dyno Project OS');
        }

        return '#mula';
    }
}
