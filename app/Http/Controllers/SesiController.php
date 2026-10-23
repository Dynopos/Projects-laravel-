<?php

namespace App\Http\Controllers;

use App\Models\LogAktiviti;
use App\Support\SyarikatSemasa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SesiController extends Controller
{
    public function borang(SyarikatSemasa $semasa)
    {
        return view('auth.masuk', ['syarikat' => $semasa->wajib()]);
    }

    public function masuk(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Kata laluan betul tapi bukan ahli syarikat ini = mesej yang sama dengan
        // kata laluan salah, supaya senarai ahli tak boleh diteka.
        if (! Auth::attempt($data, $request->boolean('ingat')) || ! $request->user()->ahliSyarikatSemasa()) {
            Auth::guard('web')->logout();
            throw ValidationException::withMessages(['email' => 'E-mel atau kata laluan salah.']);
        }

        $request->session()->regenerate();
        LogAktiviti::catat('Log masuk', $request->user()->email);

        return redirect()->intended(route('dashboard'));
    }

    public function keluar(Request $request)
    {
        LogAktiviti::catat('Log keluar', $request->user()->email);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('masuk');
    }
}
