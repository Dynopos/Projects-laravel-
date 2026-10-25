<?php

use App\Http\Controllers\JemputanController;
use App\Http\Controllers\LogoController;
use App\Http\Controllers\SesiController;
use App\Livewire\ButiranProjek;
use App\Livewire\Dashboard;
use App\Livewire\ImportExcel;
use App\Livewire\LogAktivitiSenarai;
use App\Livewire\SenaraiBelanja;
use App\Livewire\SenaraiProjek;
use App\Livewire\TetapanSyarikat;
use App\Livewire\UrusAhli;
use Illuminate\Support\Facades\Route;

// Semua laluan app berada di bawah satu syarikat (dari subdomain / domain pelanggan).
Route::middleware('syarikat')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/masuk', [SesiController::class, 'borang'])->name('masuk');
        Route::post('/masuk', [SesiController::class, 'masuk'])->middleware('throttle:masuk');
    });

    Route::get('/logo', LogoController::class)->name('logo');

    // Pautan dari e-mel jemputan — dibuka sebelum akaun wujud.
    Route::get('/jemputan/{token}', [JemputanController::class, 'borang'])->name('jemputan');
    Route::post('/jemputan/{token}', [JemputanController::class, 'terima'])->middleware('throttle:masuk');

    Route::middleware(['auth', 'ahli'])->group(function () {
        Route::post('/keluar', [SesiController::class, 'keluar'])->name('keluar');

        Route::livewire('/', Dashboard::class)->name('dashboard');
        Route::livewire('/projek', SenaraiProjek::class)->name('projek');
        Route::livewire('/projek-siap', SenaraiProjek::class)->name('arkib')->defaults('arkib', true);
        Route::livewire('/projek/{projek}', ButiranProjek::class)->name('projek.butiran');
        Route::livewire('/perbelanjaan', SenaraiBelanja::class)->name('belanja');
        Route::livewire('/log', LogAktivitiSenarai::class)->name('log');
        Route::livewire('/ahli', UrusAhli::class)->name('ahli');
        Route::livewire('/tetapan', TetapanSyarikat::class)->name('tetapan');
        Route::livewire('/import', ImportExcel::class)->name('import');
    });
});
