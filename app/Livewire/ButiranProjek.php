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

    /** 'bayaran' | 'belanja' | 'kerja' | null */
    public ?string $rekod = null;

    /** Borang kerja: tambah (kerjaId null) atau ubah. */
    public array $k = [];

    public ?int $kerjaId = null;

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
        if ($jenis === 'kerja') {
            $this->authorize('update', $this->projek);
            $this->kerjaId = null;
            $this->k = ['nama' => '', 'nilai' => '', 'tarikh' => now()->toDateString(), 'nota' => ''];
            $this->rekod = 'kerja';

            return;
        }
        // Lalai: kerja terbaru yang belum siap, supaya rekod masuk ke kerja yang sedang berjalan.
        $kerja = $this->projek->kerja()->orderBy('siap')->latest('id')->value('id');
        $this->b = ['kerja_id' => $kerja, 'pembayar' => $this->projek->klien, 'jenis' => 'Bayaran Progres', 'amaun' => '', 'tarikh' => now()->toDateString(), 'kaedah' => 'Pindahan Bank', 'ruj' => '', 'status' => Bayaran::DITERIMA];
        $this->bl = ['kerja_id' => $kerja, 'kategori' => KategoriBelanja::orderBy('susunan')->value('nama') ?? 'Lain-lain', 'amaun' => '', 'tarikh' => now()->toDateString(), 'penerima' => '', 'nota' => ''];
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
            'b.kerja_id' => ['nullable', 'integer'],
            'b.pembayar' => ['nullable', 'string', 'max:120'],
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
            'bl.kerja_id' => ['nullable', 'integer'],
        ])['bl'];

        $this->projek->belanja()->create([...$data, 'penerima' => (string) $data['penerima'],
            'kerja_id' => $this->projek->idKerja($data['kerja_id'] ?? null)]);
        $this->rekod = null;
    }

    // ---------- Kerja ----------

    public function ubahKerja(int $id): void
    {
        $this->authorize('update', $this->projek);
        $k = $this->projek->kerja()->findOrFail($id);
        $this->kerjaId = $k->id;
        $this->k = ['nama' => $k->nama, 'nilai' => (string) $k->nilai, 'tarikh' => (string) $k->tarikh?->toDateString(), 'nota' => (string) $k->nota];
        $this->rekod = 'kerja';
    }

    public function simpanKerja(): void
    {
        $this->authorize('update', $this->projek);
        $data = $this->validate([
            'k.nama' => ['required', 'string', 'max:255'],
            'k.nilai' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'k.tarikh' => ['nullable', 'date'],
            'k.nota' => ['nullable', 'string', 'max:2000'],
        ], [], ['k.nama' => 'nama kerja', 'k.nilai' => 'nilai'])['k'];
        $data['tarikh'] = $data['tarikh'] ?: null;

        if ($this->kerjaId) {
            $this->projek->kerja()->findOrFail($this->kerjaId)->update($data);
        } else {
            $this->projek->tambahKerja($data);
        }
        $this->projek->refresh();
        $this->rekod = null;
    }

    public function togolSiap(int $id): void
    {
        $this->authorize('update', $this->projek);
        $k = $this->projek->kerja()->findOrFail($id);
        $k->update(['siap' => ! $k->siap]);
    }

    public function padamKerja(int $id): void
    {
        $this->authorize('update', $this->projek);
        $this->projek->kerja()->findOrFail($id)->delete();
        $this->projek->refresh();
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
        $bayaran = $this->projek->bayaran()->with('kerja:id,nama')->orderByRaw('tarikh is null, tarikh desc')->orderByDesc('id')->get();
        $belanja = $this->projek->belanja()->with(['perekod', 'kerja:id,nama'])->orderByRaw('tarikh is null, tarikh desc')->orderByDesc('id')->get();

        // Untung setiap kerja dari rekod yang sudah dimuat (tiada pertanyaan tambahan).
        $diterima = $bayaran->where('status', '!=', Bayaran::MENUNGGU)->groupBy(fn ($x) => (int) $x->kerja_id)->map(fn ($g) => $g->sum(fn ($x) => Kiraan::sen($x->amaun)));
        $keluar = $belanja->groupBy(fn ($x) => (int) $x->kerja_id)->map(fn ($g) => $g->sum(fn ($x) => Kiraan::sen($x->amaun)));
        $kerja = $this->projek->kerja()->orderBy('tarikh')->orderBy('id')->get()->each(function ($k) use ($diterima, $keluar) {
            $k->nilai_sen = Kiraan::sen($k->nilai);
            $k->diterima_sen = $diterima[$k->id] ?? 0;
            $k->belanja_sen = $keluar[$k->id] ?? 0;
        });

        return view('livewire.butiran-projek', [
            'p' => $p,
            'bayaran' => $bayaran,
            'pembayarLalu' => $bayaran->pluck('pembayar')->push($this->projek->klien)->filter()->unique()->values(),
            'belanja' => $belanja,
            'kerja' => $kerja,
            'tanpaKerja' => ['diterima' => $diterima[0] ?? 0, 'belanja' => $keluar[0] ?? 0],
            'peringkat' => PeringkatAliran::orderBy('susunan')->get(),
            'kategori' => KategoriBelanja::orderBy('susunan')->pluck('nama'),
            'bolehUrus' => $user->can('update', $this->projek),
            'bolehRekod' => $user->can('rekod', $this->projek),
            'pengurusan' => (bool) $user->peranan()?->pengurusan(),
            'bolehUntung' => (bool) $user->peranan()?->bolehEksport(),
            'belanjaKategori' => $this->projek->belanja()->selectRaw('kategori, sum(amaun) as jumlah, count(*) as n')
                ->groupBy('kategori')->get()->map(fn ($r) => ['nama' => $r->kategori, 'sen' => Kiraan::sen($r->jumlah), 'n' => (int) $r->n])
                ->sortByDesc('sen')->values(),
        ])->title($this->projek->kod);
    }
}
