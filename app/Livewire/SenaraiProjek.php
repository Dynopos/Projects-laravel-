<?php

namespace App\Livewire;

use App\Livewire\Forms\ProjekForm;
use App\Models\PeringkatAliran;
use App\Models\Projek;
use App\Support\Kiraan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::app')]
class SenaraiProjek extends Component
{
    /** true = tab "Projek Siap" (arkib). Senarai utama kecualikan projek siap. */
    public bool $arkib = false;

    #[Url]
    public string $q = '';

    #[Url]
    public string $kat = '';

    #[Url]
    public string $susun = 'kod';

    public bool $borangBuka = false;

    public ProjekForm $form;

    public function mount(bool $arkib = false): void
    {
        $this->arkib = $arkib;
    }

    public function bukaBorang(): void
    {
        $this->authorize('create', Projek::class);
        $this->form->baharu();
        $this->borangBuka = true;
    }

    public function simpan(): void
    {
        $this->authorize('create', Projek::class);
        $p = $this->form->simpan();
        $this->borangBuka = false;
        $this->redirectRoute('projek.butiran', $p, navigate: true);
    }

    public function render()
    {
        $user = auth()->user();
        $q = mb_strtolower(trim($this->q));

        $semua = Kiraan::projekDenganJumlah(Projek::query()->bolehDilihat($user));
        $bilSiap = $semua->filter(fn ($p) => $p->jenisStatus() === 'siap')->count();

        $rows = $semua
            ->filter(fn ($p) => $this->arkib ? $p->jenisStatus() === 'siap' : $p->jenisStatus() !== 'siap')
            ->filter(fn ($p) => ! $this->kat || $p->jenisStatus() === $this->kat)
            ->filter(fn ($p) => $q === '' || collect([$p->kod, $p->nama, $p->klien, $p->lokasi, $p->telefon, $p->inv])
                ->contains(fn ($x) => str_contains(mb_strtolower((string) $x), $q)))
            ->sort(fn ($a, $b) => match ($this->susun) {
                'baki' => $b->baki_sen <=> $a->baki_sen,
                'nilai' => $b->nilai_sen <=> $a->nilai_sen,
                'pct' => $b->peratus <=> $a->peratus,
                default => strnatcmp($a->kod, $b->kod),
            })->values();

        return view('livewire.senarai-projek', [
            'rows' => $rows,
            'bilSiap' => $bilSiap,
            'bilAktif' => $semua->count() - $bilSiap,
            'peringkat' => PeringkatAliran::orderBy('susunan')->get(),
            'bolehCipta' => $user->can('create', Projek::class),
        ])->title($this->arkib ? 'Projek Siap' : 'Projek');
    }
}
