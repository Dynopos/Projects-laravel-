<?php

use App\Http\Middleware\PastikanAhli;
use App\Http\Middleware\TentukanSyarikat;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'syarikat' => TentukanSyarikat::class,
            'ahli' => PastikanAhli::class,
        ]);
        // Syarikat mesti ditetapkan SEBELUM route model binding — jika tidak, {projek}
        // dicari tanpa syarikat, scope gagal tertutup dan setiap halaman butiran jadi 404.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: TentukanSyarikat::class);
        $middleware->redirectGuestsTo(fn () => route('masuk'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
