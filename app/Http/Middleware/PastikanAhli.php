<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Pengguna yang log masuk mesti ahli aktif syarikat dalam URL ini. */
class PastikanAhli
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->ahliSyarikatSemasa()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('masuk')->withErrors(['email' => 'Akaun ini bukan ahli syarikat ini.']);
        }

        return $next($request);
    }
}
