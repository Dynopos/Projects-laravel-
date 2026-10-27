<?php

namespace App\Providers;

use App\Http\Middleware\PastikanAhli;
use App\Http\Middleware\TentukanSyarikat;
use App\Models\Bayaran;
use App\Models\Belanja;
use App\Models\BelanjaUmum;
use App\Models\Projek;
use App\Observers\CatatAktiviti;
use App\Support\SyarikatSemasa;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // scoped: dikosongkan antara permintaan (Octane) dan antara kerja queue.
        $this->app->scoped(SyarikatSemasa::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Projek::class, Bayaran::class, Belanja::class, BelanjaUmum::class] as $model) {
            $model::observe(CatatAktiviti::class);
        }

        // Permintaan Livewire (klik butang, hantar borang) mesti melalui syarikat yang
        // sama dengan halaman asal; tanpa ini scope memulangkan senarai kosong.
        Livewire::setUpdateRoute(fn ($handle, $path) => Route::post($path, $handle)->middleware(['web', TentukanSyarikat::class]));
        Livewire::addPersistentMiddleware([Authenticate::class, PastikanAhli::class]);

        RateLimiter::for('masuk', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
    }
}
