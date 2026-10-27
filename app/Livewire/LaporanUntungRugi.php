<?php

namespace App\Livewire;

use App\Models\Projek;
use App\Support\UntungRugi;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Untung rugi ikut projek: tunai bulanan + dijangka per projek. Bukan untuk staf. */
#[Layout('layouts::app')]
#[Title('Untung Rugi')]
class LaporanUntungRugi extends Component
{
    #[Url]
    public string $tahun = '';

    #[Url]
    public string $kat = '';      // '' | aktif | siap

    #[Url]
    public string $susun = 'kod';

    public function mount(): void
    {
        abort_unless(auth()->user()->peranan()?->bolehEksport(), 403);
    }

    public function render()
    {
        $senaraiTahun = UntungRugi::senaraiTahun($this->projek());
        $tahun = in_array((int) $this->tahun, $senaraiTahun, true) ? (int) $this->tahun : $senaraiTahun[0];

        return view('livewire.untung-rugi', [
            'senaraiTahun' => $senaraiTahun,
            'tahunDipilih' => $tahun,
            'b' => UntungRugi::bulanan($this->projek(), $tahun),
            'projek' => UntungRugi::perProjek($this->projek(), $this->susun),
        ]);
    }

    /** @return Builder<Projek> */
    private function projek(): Builder
    {
        return self::tapis(Projek::query()->bolehDilihat(auth()->user()), $this->kat);
    }

    /** Dikongsi dengan eksport supaya fail = skrin. */
    public static function tapis(Builder $q, string $kat): Builder
    {
        $siap = Projek::query()->get(['id', 'status'])->filter(fn ($p) => $p->jenisStatus() === 'siap')->modelKeys();

        return match ($kat) {
            'aktif' => $q->whereNotIn('id', $siap),
            'siap' => $q->whereIn('id', $siap),
            default => $q,
        };
    }
}
