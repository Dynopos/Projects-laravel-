<?php

namespace App\Http\Middleware;

use App\Models\Syarikat;
use App\Support\SyarikatSemasa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pilih syarikat dari hos: <subdomain>.<domain_utama> atau domain sendiri pelanggan.
 * Hos yang tak dikenali = 404, bukan "syarikat pertama" — jangan sesekali teka.
 */
class TentukanSyarikat
{
    public function __construct(private SyarikatSemasa $semasa) {}

    public function handle(Request $request, Closure $next): Response
    {
        $syarikat = $this->cari($request->getHost());
        abort_unless($syarikat, 404, 'Syarikat tidak dijumpai.');

        $this->semasa->tetapkan($syarikat);

        try {
            return $next($request);
        } finally {
            // Sambungan DB boleh diguna semula (Octane/queue) — jangan bawa syarikat ke permintaan lain.
            $this->semasa->tetapkan(null);
        }
    }

    private function cari(string $hos): ?Syarikat
    {
        $hos = strtolower($hos);
        $utama = strtolower((string) config('dyno.domain_utama'));

        if ($utama && str_ends_with($hos, '.'.$utama)) {
            $sub = substr($hos, 0, -strlen('.'.$utama));

            return Syarikat::where('subdomain', $sub)->first();
        }

        if ($s = Syarikat::where('domain', $hos)->first()) {
            return $s;
        }

        $lalai = config('dyno.syarikat_lalai');

        return $lalai && app()->environment('local', 'testing')
            ? Syarikat::where('subdomain', $lalai)->first()
            : null;
    }
}
