<?php

namespace App\Console\Commands;

use App\Enums\Peranan;
use App\Mail\PeringatanLanggananMail;
use App\Models\Keahlian;
use App\Models\Syarikat;
use App\Support\SyarikatSemasa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Dijadualkan setiap hari (routes/console.php).
 *  - Peringatan e-mel kepada pemilik: 30, 7, 1 hari sebelum tamat dan pada hari tamat.
 *  - Tamat + tempoh tangguh (dyno.tempoh_tangguh) → status baca_sahaja. Data tidak dipadam.
 * Syarikat tanpa aktif_hingga (diurus manual) tidak disentuh.
 */
class SemakLangganan extends Command
{
    protected $signature = 'dyno:semak-langganan';

    protected $description = 'Hantar peringatan pembaharuan dan tukar syarikat tamat ke mod baca sahaja';

    private const PERINGATAN = [0, 1, 7, 30];   // menaik: yang pertama ≥ baki ialah yang paling dekat

    public function handle(SyarikatSemasa $semasa): int
    {
        $semasa->tanpaSkop(function () {
            Syarikat::whereNotNull('aktif_hingga')->where('status', 'aktif')->each(function (Syarikat $s) {
                $baki = $s->bakiHari();

                if ($baki < -config('dyno.tempoh_tangguh')) {
                    $s->update(['status' => 'baca_sahaja']);
                    $this->line("{$s->subdomain}: baca sahaja (tamat {$s->aktif_hingga->format('d/m/Y')})");

                    return;
                }

                // Peringatan paling dekat yang sudah sampai tetapi belum dihantar.
                $tahap = collect(self::PERINGATAN)->first(fn ($h) => $baki <= $h && ($s->peringatan === null || $h < $s->peringatan));
                if ($tahap === null) {
                    return;
                }

                $emel = Keahlian::where('syarikat_id', $s->id)->where('peranan', Peranan::Pemilik)->where('aktif', true)
                    ->with('user:id,email')->get()->pluck('user.email')->filter();
                try {
                    foreach ($emel as $e) {
                        Mail::to($e)->send(new PeringatanLanggananMail($s, max($baki, 0)));
                    }
                    $s->update(['peringatan' => $tahap]);
                    $this->line("{$s->subdomain}: peringatan {$tahap} hari → ".$emel->implode(', '));
                } catch (Throwable $e) {
                    report($e);     // cuba lagi esok
                }
            });
        });

        return self::SUCCESS;
    }
}
