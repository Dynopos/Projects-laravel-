<?php

namespace App\Support;

use App\Models\Syarikat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Logo syarikat dilukis semula oleh GD ke PNG maksimum 256×256. Fail asal tidak
 * pernah disimpan: metadata EXIF, skrip tersembunyi dan "polyglot" hilang, dan
 * saiz kekal kecil. SVG tidak diterima (boleh membawa skrip).
 */
class Logo
{
    public const SAIZ = 256;

    public static function simpan(Syarikat $s, UploadedFile $fail): string
    {
        $asal = @imagecreatefromstring((string) file_get_contents($fail->getRealPath()));
        if (! $asal) {
            throw new RuntimeException('Fail bukan imej yang sah.');
        }

        [$w, $h] = [imagesx($asal), imagesy($asal)];
        $skala = min(1, self::SAIZ / max($w, $h));
        [$nw, $nh] = [max(1, (int) round($w * $skala)), max(1, (int) round($h * $skala))];

        $baru = imagecreatetruecolor($nw, $nh);
        imagealphablending($baru, false);
        imagesavealpha($baru, true);
        imagefill($baru, 0, 0, imagecolorallocatealpha($baru, 0, 0, 0, 127));
        imagecopyresampled($baru, $asal, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagepng($baru, null, 9);
        $png = (string) ob_get_clean();

        $laluan = "syarikat/{$s->id}/logo-".Str::lower(Str::random(10)).'.png';
        Storage::disk(config('dyno.cakera'))->put($laluan, $png);

        if ($s->logo_path) {
            Storage::disk(config('dyno.cakera'))->delete($s->logo_path);
        }

        return $laluan;
    }

    public static function padam(Syarikat $s): void
    {
        if ($s->logo_path) {
            Storage::disk(config('dyno.cakera'))->delete($s->logo_path);
        }
    }
}
