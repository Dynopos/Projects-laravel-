<?php

namespace App\Livewire;

use App\Models\PembayaranLangganan;
use App\Support\Chip;
use App\Support\Langganan;
use App\Support\SyarikatSemasa;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Status langganan + bayar pembaharuan (pemilik sahaja). Sengaja TIDAK disekat oleh
 * bacaSahaja() — syarikat yang sudah tamat mesti masih boleh membayar.
 */
#[Layout('layouts::app')]
#[Title('Langganan')]
class UrusLangganan extends Component
{
    public ?string $mesej = null;

    public ?string $ralat = null;

    public function mount(Langganan $langganan): void
    {
        abort_unless(auth()->user()->peranan()?->bolehTetapan(), 403);

        // Kembali dari CHIP: semak terus supaya tarikh baharu terus kelihatan walaupun callback lambat.
        $id = (int) request()->query('p');
        if (request()->query('bayaran') === 'selesai' && $id) {
            $p = PembayaranLangganan::where('syarikat_id', $this->syarikat()->id)->find($id);
            if ($p && $langganan->segerakkan($p)->dibayar()) {
                $this->mesej = 'Terima kasih. Langganan telah diperbaharui.';
            } elseif ($p) {
                $this->mesej = 'Bayaran sedang disahkan. Muat semula halaman ini sebentar lagi.';
            }
        } elseif (request()->query('bayaran') === 'gagal') {
            $this->ralat = 'Bayaran tidak berjaya. Tiada caj dikenakan — sila cuba lagi.';
        }
    }

    public function bayar(Langganan $langganan)
    {
        abort_unless(auth()->user()->peranan()?->bolehTetapan(), 403);
        if (! Chip::dikonfigurasi()) {
            $this->ralat = 'Bayaran online belum dibuka. Sila hubungi Dyno untuk pembaharuan.';

            return null;
        }

        try {
            [, $url] = $langganan->ciptaPembaharuan($this->syarikat(), auth()->user());
        } catch (Throwable $e) {
            report($e);
            $this->ralat = 'Sistem pembayaran tidak dapat dihubungi. Cuba lagi sebentar.';

            return null;
        }

        return $this->redirect($url);
    }

    public function render()
    {
        return view('livewire.urus-langganan', [
            'syarikat' => $this->syarikat(),
            'sejarah' => PembayaranLangganan::where('syarikat_id', $this->syarikat()->id)->where('status', 'dibayar')
                ->latest('dibayar_pada')->limit(10)->get(),
        ]);
    }

    private function syarikat()
    {
        return app(SyarikatSemasa::class)->wajib();
    }
}
