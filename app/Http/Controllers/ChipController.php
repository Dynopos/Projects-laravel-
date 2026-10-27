<?php

namespace App\Http\Controllers;

use App\Support\Chip;
use App\Support\Langganan;
use Illuminate\Http\Request;

/**
 * success_callback CHIP (pelayan ke pelayan). Tandatangan disahkan dengan badan mentah,
 * kemudian status dibaca semula dari API — badan callback tidak dipercayai.
 * Ralat = 5xx supaya CHIP mencuba semula; selesaikan() idempoten.
 */
class ChipController extends Controller
{
    public function callback(Request $request, Chip $chip, Langganan $langganan)
    {
        $badan = $request->getContent();
        abort_unless($chip->tandatanganSah($badan, $request->header('X-Signature')), 401, 'Tandatangan tidak sah.');

        $id = json_decode($badan, true)['id'] ?? null;
        abort_unless(is_string($id) && $id !== '', 422);

        $langganan->selesaikan($id);

        return response('OK');
    }
}
