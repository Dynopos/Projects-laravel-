<?php

namespace App\Livewire;

use App\Livewire\Forms\ProjekForm;
use App\Models\Bayaran;
use App\Models\Belanja;
use App\Models\KategoriBelanja;
use App\Models\PeringkatAliran;
use App\Models\Projek;
use App\Support\Kiraan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts::app')]
class ButiranProjek extends Component
{
    #[Locked]
    public Projek $projek;

    public bool $borangBuka = false;

    public ProjekForm $form;

    /** 'bayaran' | 'belanja' | null */
    public ?string $rekod = null;

    public array $b = [];

    public array $bl = [];

    public function mount(Projek $projek): void
    {
        $this->authorize('view', $projek);
        $this->projek = $projek;
    }

    public function bukaBorang(): void
    {
        $this->authorize('update', $this->projek);
        $this->form->isi($this->projek);
        $this->borangBuka = true;
    }

    public function simpan(): void
    {
        $this->authorize('update', $this->projek);
        $this->projek = $this->form->simpan();
        $this->borangBuka = false;
    }

    public function padamProjek(): void
    {
        $this->authorize('delete', $this->projek);
        $this->projek->delete();   // bayaran & belanja dipadam oleh FK cascade
        $this->redirectRoute('projek', navigate: true);
    }

    public function bukaRekod(string $jenis): void
    {
        $this->authorize('rekod', $this->projek);
        $this->rekod = $jenis;
        $this->b = ['jenis' => 'Bayaran Progres', 'amaun' => '', 'tarikh' => now()->toDateString(), 'kaedah' => 'Pindahan Bank', 'ruj' => '', 'status' => Bayaran::DITERIMA];
        $this->bl = ['kategori' => KategoriBelanja::orderBy('susunan')->value('nama') ?? 'Lain-lain', 'amaun' => '', 'tarikh' => now()->toDateString(), 'penerima' => '', 'nota' => ''];
    }

    public function simpanBayaran(): void
    {
        $this->authorize('rekod', $this->projek);
        $data = $this->validate([
            'b.jenis' => ['required', 'string', 'max:60'],
            'b.amaun' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'b.tarikh' => ['nullable', 'date'],
            'b.kaedah' => ['nullable', 'string', 'max:60'],
            'b.ruj' => ['nullable', 'string', 'max:80'],
            'b.status' => ['required', 'in:Diterima,Menunggu'],
        ])['b'];

        // Staf dipaksa Menunggu di pelayan (Projek::rekodBayaran), bukan sekadar disorok dalam borang.
        $this->projek->rekodBayaran(auth()->user(), $data);
        $this->rekod = null;
    }

    public function simpanBelanja(): void
    {
        $this->authorize('rekod', $this->projek);
        $data = $this->validate([
            'bl.kategori' => ['required', 'string', 'max:60'],
            'bl.amaun' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'bl.tarikh' => ['nullable', 'date'],
            'bl.penerima' => ['nullable', 'string', 'max:255'],
            'bl.nota' => ['nullable', 'string', 'max:2000'],
        ])['bl'];

        $this->projek->belanja()->create([...$data, 'penerima' => (string) $data['penerima']]);
        $this->rekod = null;
    }

    public function sahkan(int $id): void
    {
        $b = $this->projek->bayaran()->findOrFail($id);
        $this->authorize('sahkan', $b);
        $b->sahkan(auth()->user());
    }

    public function padamBayaran(int $id): void
    {
        $b = $this->projek->bayaran()->findOrFail($id);
        $this->authorize('delete', $b);
        $b->delete();
    }

    public function padamBelanja(int $id): void
    {
        $x = $this->projek->belanja()->findOrFail($id);
        $this->authorize('delete', $x);
        $x->delete();
    }

    public function render()
    {
        $p = Kiraan::projekDenganJumlah(Projek::whereKey($this->projek->id))->first();
        $user = auth()->user();

        return view('livewire.butiran-projek', [
            'p' => $p,
            'bayaran' => $this->projek->bayaran()->orderByDesc('tarikh')->orderByDesc('id')->get(),
            'belanja' => $this->projek->belanja()->with('perekod')->orderByDesc('tarikh')->orderByDesc('id')->get(),
            'peringkat' => PeringkatAliran::orderBy('susunan')->get(),
            'kategori' => KategoriBelanja::orderBy('susunan')->pluck('nama'),
            'bolehUrus' => $user->can('update', $this->projek),
            'bolehRekod' => $user->can('rekod', $this->projek),
            'pengurusan' => (bool) $user->peranan()?->pengurusan(),
        ])->title($this->projek->kod);
    }
}
