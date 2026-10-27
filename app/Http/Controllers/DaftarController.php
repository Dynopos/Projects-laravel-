<?php

namespace App\Http\Controllers;

use App\Models\PembayaranLangganan;
use App\Models\Syarikat;
use App\Support\Chip;
use App\Support\Langganan;
use App\Support\SyarikatSemasa;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Daftar sendiri di domain utama: borang → CHIP (RM99) → halaman selesai → jemputan pemilik.
 * Syarikat hanya dicipta selepas CHIP mengesahkan bayaran (Langganan::selesaikan).
 */
class DaftarController extends Controller
{
    /** Subdomain yang tidak boleh diambil pelanggan. */
    public const TERPELIHARA = ['www', 'admin', 'api', 'mail', 'app', 'ftp', 'daftar', 'demo', 'bantuan', 'status', 'dyno'];

    public function borang(Request $request)
    {
        return view('laman.daftar', [
            'dibuka' => Chip::dikonfigurasi(),
            'bayaran' => $request->query('bayaran'),
            'demo' => LamanController::pautanDemo(),
        ]);
    }

    public function simpan(Request $request, Langganan $langganan, SyarikatSemasa $semasa)
    {
        abort_unless(Chip::dikonfigurasi(), 503, 'Pendaftaran online belum dibuka.');
        // Medan perangkap bot — manusia tidak nampak medan ini.
        abort_if(filled($request->input('laman')), 422);

        $request->merge([
            'subdomain' => Str::lower(trim((string) $request->input('subdomain'))),
            'emel' => Str::lower(trim((string) $request->input('emel'))),
            'nama' => Str::upper(trim((string) $request->input('nama'))),
        ]);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:80'],
            'sufiks' => ['nullable', 'string', 'max:20'],
            'subdomain' => ['required', 'regex:/^[a-z0-9]([a-z0-9-]{0,40}[a-z0-9])?$/', 'not_in:'.implode(',', self::TERPELIHARA)],
            'pemilik' => ['required', 'string', 'max:255'],
            'emel' => ['required', 'email', 'max:255'],
            'telefon' => ['required', 'regex:/^\+?[0-9][0-9\s-]{7,16}$/'],
            'setuju' => ['accepted'],
        ], [
            'required' => 'Sila isi :attribute.',
            'max' => ':Attribute terlalu panjang.',
            'email' => 'Masukkan e-mel yang sah.',
            'subdomain.regex' => 'Huruf kecil, nombor dan sengkang sahaja, tanpa ruang.',
            'subdomain.not_in' => 'Alamat ini tidak boleh digunakan.',
            'telefon.regex' => 'Masukkan nombor telefon yang sah, cth 012-345 6789.',
            'setuju.accepted' => 'Sila bersetuju dengan Terma dan Notis Privasi.',
        ], ['nama' => 'nama syarikat', 'sufiks' => 'sufiks', 'subdomain' => 'alamat sistem', 'pemilik' => 'nama anda', 'emel' => 'e-mel', 'telefon' => 'no. telefon']);

        $sub = $data['subdomain'];
        $diambil = $semasa->tanpaSkop(fn () => Syarikat::where('subdomain', $sub)->exists())
            || PembayaranLangganan::where('jenis', 'daftar')->where('status', 'menunggu')
                ->where('created_at', '>', now()->subDay())->where('butiran->subdomain', $sub)
                ->where('butiran->emel', '!=', $data['emel'])->exists();
        if ($diambil) {
            throw ValidationException::withMessages(['subdomain' => 'Alamat ini sudah digunakan. Cuba nama lain.']);
        }

        unset($data['setuju']);
        // TrimStrings membuang ruang di depan; " SDN BHD" perlu ruang, ".CO" tidak.
        $sufiks = trim((string) ($data['sufiks'] ?? ''));
        $data['sufiks'] = $sufiks === '' || preg_match('/^[.,&]/', $sufiks) ? $sufiks : ' '.$sufiks;

        try {
            [, $checkout] = $langganan->ciptaPendaftaran($data);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['bayaran' => 'Sistem pembayaran tidak dapat dihubungi. Cuba lagi sebentar.']);
        }

        return redirect()->away($checkout);
    }

    public function selesai(PembayaranLangganan $pembayaran, string $tanda, Langganan $langganan)
    {
        $p = $this->sahkan($pembayaran, $tanda);
        $p = $langganan->segerakkan($p);

        return response()->view('laman.daftar-selesai', ['p' => $p, 'tanda' => $tanda])
            ->header('Cache-Control', 'no-store');
    }

    /** Butang "Tetapkan kata laluan": terbit jemputan pemilik (atau ke /masuk jika sudah ada pemilik). */
    public function masuk(PembayaranLangganan $pembayaran, string $tanda, Langganan $langganan)
    {
        $p = $this->sahkan($pembayaran, $tanda);
        abort_unless($p->dibayar() && $p->syarikat_id, 404);

        return redirect()->away($langganan->pautanPemilik($p));
    }

    private function sahkan(PembayaranLangganan $p, string $tanda): PembayaranLangganan
    {
        abort_unless($p->jenis === 'daftar' && hash_equals($p->tanda(), $tanda), 404);

        return $p;
    }
}
