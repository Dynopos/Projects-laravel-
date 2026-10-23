<?php

namespace App\Http\Controllers;

use App\Models\Jemputan;
use App\Models\LogAktiviti;
use App\Models\User;
use App\Support\SyarikatSemasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * Terima jemputan dari pautan e-mel.
 *  - E-mel baharu: tetapkan nama + kata laluan, akaun dicipta.
 *  - Akaun sedia ada (ahli syarikat lain): sahkan dengan kata laluan sedia ada.
 * Jemputan dicari dalam syarikat semasa sahaja (scope), jadi token syarikat A
 * tidak berfungsi pada subdomain syarikat B.
 */
class JemputanController extends Controller
{
    public function borang(Request $request, SyarikatSemasa $semasa, string $token)
    {
        $j = Jemputan::cariToken($token);

        return view('auth.jemputan', [
            'syarikat' => $semasa->wajib(),
            'jemputan' => $j?->sah() ? $j : null,
            'token' => $token,
            'akaunWujud' => $j && User::where('email', $j->email)->exists(),
            'lainPengguna' => $request->user() && $j && strcasecmp($request->user()->email, $j->email) !== 0,
        ]);
    }

    public function terima(Request $request, SyarikatSemasa $semasa, string $token)
    {
        $j = Jemputan::cariToken($token);
        abort_unless($j?->sah(), 410, 'Jemputan ini tidak sah atau sudah tamat tempoh.');

        if ($request->user() && strcasecmp($request->user()->email, $j->email) !== 0) {
            abort(403, 'Log keluar dahulu untuk menerima jemputan ini.');
        }

        $user = User::where('email', $j->email)->first();

        if ($user) {
            $request->validate(['password' => ['required', 'string']]);
            if (! Hash::check($request->input('password'), $user->password)) {
                throw ValidationException::withMessages(['password' => 'Kata laluan salah.']);
            }
        } else {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'confirmed', Password::min(8)],
            ]);
        }

        $user = DB::transaction(function () use ($j, $user, $semasa) {
            // Kunci baris supaya dua klik serentak tidak mencipta dua akaun.
            $j = Jemputan::whereKey($j->id)->lockForUpdate()->first();
            abort_unless($j?->sah(), 410);

            if (! $user) {
                $user = User::create(['name' => request('name'), 'email' => $j->email, 'password' => request('password')]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $semasa->wajib()->tambahAhli($user, $j->peranan);
            $j->forceFill(['diterima_pada' => now()])->save();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        LogAktiviti::catat('Terima jemputan', "{$user->email} · {$j->peranan->label()}");

        return redirect()->route('dashboard');
    }
}
