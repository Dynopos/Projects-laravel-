<?php

namespace App\Support;

use App\Enums\Peranan;
use App\Http\Controllers\LamanController;
use App\Mail\AkaunSediaMail;
use App\Models\Jemputan;
use App\Models\Keahlian;
use App\Models\LogAktiviti;
use App\Models\PembayaranLangganan;
use App\Models\Syarikat;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Daftar sendiri + pembaharuan tahunan melalui CHIP.
 *
 *   /daftar  → ciptaPendaftaran() → CHIP checkout → callback / halaman selesai
 *            → selesaikan() → syarikat dicipta, aktif 1 tahun, jemputan pemilik
 *   /langganan → ciptaPembaharuan() → ... → selesaikan() → aktif_hingga + 1 tahun
 *
 * selesaikan() sentiasa membaca status dari API CHIP (bukan badan callback) dan
 * idempoten — callback berulang dan halaman selesai boleh berlumba tanpa kesan.
 */
class Langganan
{
    public function __construct(private Chip $chip, private SyarikatSemasa $semasa) {}

    /**
     * Cipta syarikat + tetapan lalai + jemputan pemilik. Dikongsi dengan dyno:syarikat-baru.
     *
     * @return array{0: Syarikat, 1: Jemputan, 2: string}
     */
    public function bukaAkaun(array $d, ?CarbonInterface $aktifHingga = null): array
    {
        return $this->semasa->tanpaSkop(function () use ($d, $aktifHingga) {
            $s = Syarikat::create([
                'nama' => $d['nama'], 'sufiks' => (string) ($d['sufiks'] ?? ''), 'subdomain' => $d['subdomain'],
                'domain' => $d['domain'] ?? null, 'kod_awalan' => $d['awalan'] ?? 'PRJ-', 'status' => 'aktif',
                'aktif_hingga' => $aktifHingga?->toDateString(),
            ]);
            $s->sediakanTetapanLalai();
            [$j, $token] = $this->semasa->sebagai($s, fn () => Jemputan::terbitkan($d['emel'], Peranan::Pemilik, null));

            return [$s, $j, $token];
        });
    }

    /** @return array{0: PembayaranLangganan, 1: string} rekod + checkout_url */
    public function ciptaPendaftaran(array $borang): array
    {
        $p = PembayaranLangganan::create([
            'jenis' => 'daftar', 'amaun_sen' => (int) config('dyno.harga.daftar'),
            'butiran' => [...$borang, 'persetujuan_pada' => now()->toIso8601String(), 'ip' => request()->ip()],
        ]);
        $domain = config('dyno.domain_utama');

        $purchase = $this->chip->ciptaPurchase([
            'reference' => $p->rujukan(),
            'client' => ['email' => $borang['emel'], 'full_name' => $borang['pemilik'], 'phone' => $borang['telefon'] ?? ''],
            'purchase' => ['currency' => 'MYR', 'products' => [[
                'name' => "Dyno Project OS — persediaan & langganan tahun pertama ({$borang['subdomain']}.{$domain})",
                'price' => $p->amaun_sen, 'quantity' => '1',
            ]]],
            'success_callback' => LamanController::url('/chip/callback'),
            'success_redirect' => LamanController::url("/daftar/selesai/{$p->id}/{$p->tanda()}"),
            'failure_redirect' => LamanController::url('/daftar?bayaran=gagal'),
            'cancel_redirect' => LamanController::url('/daftar?bayaran=batal'),
        ]);
        $p->update(['purchase_id' => $purchase['id']]);

        return [$p, $purchase['checkout_url']];
    }

    /** @return array{0: PembayaranLangganan, 1: string} */
    public function ciptaPembaharuan(Syarikat $s, User $oleh): array
    {
        $p = PembayaranLangganan::create([
            'syarikat_id' => $s->id, 'jenis' => 'pembaharuan', 'amaun_sen' => (int) config('dyno.harga.pembaharuan'),
            'butiran' => ['oleh' => $oleh->email],
        ]);

        $purchase = $this->chip->ciptaPurchase([
            'reference' => $p->rujukan(),
            'client' => ['email' => $oleh->email, 'full_name' => $oleh->name],
            'purchase' => ['currency' => 'MYR', 'products' => [[
                'name' => 'Dyno Project OS — pembaharuan langganan 1 tahun ('.$s->namaPenuh().')',
                'price' => $p->amaun_sen, 'quantity' => '1',
            ]]],
            'success_callback' => LamanController::url('/chip/callback'),
            'success_redirect' => $s->url('/langganan?bayaran=selesai&p='.$p->id),
            'failure_redirect' => $s->url('/langganan?bayaran=gagal'),
            'cancel_redirect' => $s->url('/langganan'),
        ]);
        $p->update(['purchase_id' => $purchase['id']]);

        return [$p, $purchase['checkout_url']];
    }

    /** Tanya CHIP terus (untuk halaman selesai — callback mungkin belum tiba). */
    public function segerakkan(PembayaranLangganan $p): PembayaranLangganan
    {
        if ($p->status === 'menunggu' && $p->purchase_id) {
            try {
                $this->selesaikan($p->purchase_id);
            } catch (Throwable $e) {
                Log::warning('Semakan CHIP gagal', ['p' => $p->id, 'ralat' => $e->getMessage()]);
            }
        }

        return $p->fresh();
    }

    /** Proses satu purchase CHIP. Status dibaca dari API, bukan dari pemanggil. */
    public function selesaikan(string $purchaseId): ?PembayaranLangganan
    {
        $purchase = $this->chip->purchase($purchaseId);

        return $this->semasa->tanpaSkop(function () use ($purchase, $purchaseId) {
            $p = PembayaranLangganan::where('purchase_id', $purchaseId)->first();
            if (! $p) {
                return null;
            }

            $status = $purchase['status'] ?? '';
            if (in_array($status, ['cancelled', 'expired'], true) && $p->status === 'menunggu') {
                $p->update(['status' => 'gagal']);
            }
            $jumlah = (int) ($purchase['purchase']['total'] ?? $purchase['payment']['amount'] ?? 0);
            if ($status !== 'paid' || ($purchase['reference'] ?? '') !== $p->rujukan() || $jumlah !== $p->amaun_sen) {
                return $p;
            }

            $mel = null;
            DB::transaction(function () use ($p, &$mel) {
                $p = PembayaranLangganan::whereKey($p->id)->lockForUpdate()->first();
                if ($p->dibayar()) {
                    return;     // callback berulang
                }
                $p->update(['status' => 'dibayar', 'dibayar_pada' => now()]);

                if ($p->jenis === 'daftar') {
                    $mel = $this->bukaAkaunDibayar($p);
                } else {
                    $this->panjangkan($p->syarikat);
                }
            });
            $mel?->__invoke();

            return $p->fresh();
        });
    }

    /** Syarikat dicipta selepas bayaran daftar. Pulangkan penghantar e-mel (dijalankan selepas commit). */
    private function bukaAkaunDibayar(PembayaranLangganan $p): callable
    {
        $b = $p->butiran;
        [$s] = $this->bukaAkaun([
            'nama' => $b['nama'], 'sufiks' => $b['sufiks'] ?? '', 'subdomain' => $this->subdomainBebas($b['subdomain']),
            'emel' => $b['emel'],
        ], now()->addYear());
        $p->update(['syarikat_id' => $s->id]);
        $this->semasa->sebagai($s, fn () => LogAktiviti::catat('Daftar langganan', 'Bayaran CHIP '.$p->rujukan().' · RM'.number_format($p->amaun_sen / 100, 2)));

        $pautan = LamanController::url("/daftar/selesai/{$p->id}/{$p->tanda()}");

        return function () use ($b, $s, $pautan) {
            try {
                Mail::to($b['emel'])->send(new AkaunSediaMail($s, $b['pemilik'], $pautan));
            } catch (Throwable $e) {
                Log::warning('E-mel akaun sedia gagal', ['syarikat' => $s->id, 'ralat' => $e->getMessage()]);
            }
        };
    }

    /** Tambah setahun dari tarikh tamat (atau dari hari ini jika sudah tamat) dan buka semula. */
    private function panjangkan(Syarikat $s): void
    {
        $asas = $s->aktif_hingga && $s->aktif_hingga->isFuture() ? $s->aktif_hingga : now()->startOfDay();
        $s->update(['aktif_hingga' => $asas->copy()->addYear()->toDateString(), 'status' => 'aktif', 'peringatan' => null]);
        $this->semasa->sebagai($s, fn () => LogAktiviti::catat('Langganan diperbaharui', 'Aktif hingga '.$s->aktif_hingga->format('d/m/Y')));
    }

    /** Subdomain boleh diambil orang lain semasa pembayaran berlangsung — tambah nombor. */
    private function subdomainBebas(string $sub): string
    {
        $calon = $sub;
        for ($i = 2; Syarikat::where('subdomain', $calon)->exists(); $i++) {
            $calon = $sub.'-'.$i;
        }

        return $calon;
    }

    /** Pemilik belum terima jemputan → terbit jemputan baharu (yang lama terbatal). */
    public function pautanPemilik(PembayaranLangganan $p): string
    {
        $s = $p->syarikat;

        return $this->semasa->tanpaSkop(function () use ($s, $p) {
            $adaPemilik = Keahlian::where('syarikat_id', $s->id)->where('peranan', Peranan::Pemilik)->exists();
            if ($adaPemilik) {
                return $s->url('/masuk');
            }
            [, $token] = $this->semasa->sebagai($s, fn () => Jemputan::terbitkan($p->butiran['emel'], Peranan::Pemilik, null));

            return $s->url('/jemputan/'.$token);
        });
    }
}
