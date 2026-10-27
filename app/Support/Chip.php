<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien CHIP Collect (gate.chip-in.asia). Test atau live ditentukan oleh kunci API.
 * Amaun dalam sen: 9900 = RM99.00.
 */
class Chip
{
    private const ASAS = 'https://gate.chip-in.asia/api/v1/';

    public static function dikonfigurasi(): bool
    {
        return filled(config('services.chip.key')) && filled(config('services.chip.brand_id'));
    }

    /** @return array{id: string, checkout_url: string} */
    public function ciptaPurchase(array $data): array
    {
        $res = $this->http()->post('purchases/', ['brand_id' => config('services.chip.brand_id'), ...$data]);
        if (! $res->successful() || ! $res->json('checkout_url')) {
            throw new RuntimeException('CHIP gagal mencipta purchase: '.$res->status().' '.$res->body());
        }

        return $res->json();
    }

    public function purchase(string $id): array
    {
        return $this->http()->get('purchases/'.rawurlencode($id).'/')->throw()->json();
    }

    /** X-Signature = RSA PKCS#1 v1.5 + SHA-256 ke atas badan mentah, base64. */
    public function tandatanganSah(string $badan, ?string $tandatangan): bool
    {
        $sig = base64_decode((string) $tandatangan, true);
        $kunci = openssl_pkey_get_public($this->kunciAwam());

        return $sig !== false && $sig !== '' && $kunci !== false
            && openssl_verify($badan, $sig, $kunci, OPENSSL_ALGO_SHA256) === 1;
    }

    private function kunciAwam(): string
    {
        return config('services.chip.public_key') ?: Cache::remember('chip.kunci_awam', now()->addDay(), function () {
            $badan = $this->http()->get('public_key/')->throw()->body();
            $d = json_decode($badan, true);

            return is_string($d) ? $d : ($d['public_key'] ?? trim($badan, "\" \n\r\t"));
        });
    }

    private function http()
    {
        return Http::baseUrl(self::ASAS)->withToken((string) config('services.chip.key'))->acceptJson()->timeout(20);
    }
}
