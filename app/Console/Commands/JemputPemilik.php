<?php

namespace App\Console\Commands;

use App\Enums\Peranan;
use App\Models\Jemputan;
use App\Models\Keahlian;
use App\Models\Syarikat;
use App\Support\SyarikatSemasa;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Terbit semula pautan jemputan pemilik untuk syarikat sedia ada — bila pautan dari
 * dyno:syarikat-baru hilang atau tamat. Jemputan lama untuk e-mel yang sama terbatal.
 *
 *   php artisan dyno:jemput-pemilik faizsupian
 *   php artisan dyno:jemput-pemilik faizsupian --emel=lain@contoh.my --setahun
 */
class JemputPemilik extends Command
{
    protected $signature = 'dyno:jemput-pemilik
        {subdomain : Subdomain syarikat}
        {--emel= : E-mel pemilik (lalai: e-mel jemputan pemilik terakhir)}
        {--setahun : Tetapkan langganan aktif 12 bulan jika belum ada tarikh tamat}';

    protected $description = 'Terbit semula pautan jemputan pemilik untuk syarikat sedia ada';

    public function handle(SyarikatSemasa $semasa): int
    {
        return $semasa->tanpaSkop(function () use ($semasa) {
            $s = Syarikat::where('subdomain', Str::lower($this->argument('subdomain')))->first();
            if (! $s) {
                $this->error('Syarikat tidak dijumpai.');

                return self::FAILURE;
            }

            if ($this->option('setahun') && ! $s->aktif_hingga) {
                $s->update(['aktif_hingga' => now()->addYear()->toDateString()]);
            }
            $this->info("Syarikat: {$s->namaPenuh()} · {$s->url('/')}");
            $this->line('Langganan: '.($s->aktif_hingga ? 'aktif hingga '.$s->aktif_hingga->format('d/m/Y') : 'tiada tarikh tamat'));

            $pemilik = Keahlian::where('syarikat_id', $s->id)->where('peranan', Peranan::Pemilik)->with('user:id,email')->get();
            if ($pemilik->isNotEmpty() && ! $this->option('emel')) {
                $this->warn('Pemilik sudah mendaftar: '.$pemilik->pluck('user.email')->implode(', '));
                $this->line('Log masuk di '.$s->url('/masuk').' (guna --emel= untuk jemput pemilik tambahan).');

                return self::SUCCESS;
            }

            $emel = Str::lower(trim((string) ($this->option('emel')
                ?: Jemputan::where('syarikat_id', $s->id)->where('peranan', Peranan::Pemilik)->latest('id')->value('email'))));
            if (! filter_var($emel, FILTER_VALIDATE_EMAIL)) {
                $this->error('Tiada e-mel pemilik. Gunakan --emel=alamat@contoh.my');

                return self::FAILURE;
            }

            [, $token] = $semasa->sebagai($s, fn () => Jemputan::terbitkan($emel, Peranan::Pemilik, null));
            $this->line('');
            $this->line("Pautan jemputan pemilik untuk {$emel} (sah ".Jemputan::TEMPOH_HARI.' hari, sekali guna):');
            $this->line($s->url('/jemputan/'.$token));

            return self::SUCCESS;
        });
    }
}
