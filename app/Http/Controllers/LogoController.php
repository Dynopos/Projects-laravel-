<?php

namespace App\Http\Controllers;

use App\Support\SyarikatSemasa;
use Illuminate\Support\Facades\Storage;

/** Logo syarikat semasa — dipapar juga di skrin log masuk, jadi tidak perlu log masuk. */
class LogoController extends Controller
{
    public function __invoke(SyarikatSemasa $semasa)
    {
        $s = $semasa->wajib();
        $cakera = Storage::disk(config('dyno.cakera'));
        abort_unless($s->logo_path && $cakera->exists($s->logo_path), 404);

        return response($cakera->get($s->logo_path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=31536000, immutable',   // URL berubah (?v=) bila logo ditukar
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
