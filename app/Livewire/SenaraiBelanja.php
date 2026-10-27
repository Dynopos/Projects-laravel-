<?php

namespace App\Livewire;

use App\Models\Belanja;
use App\Models\BelanjaUmum;
use App\Models\KategoriBelanja;
use App\Models\Projek;
use App\Support\Kiraan;
use App\Support\SyarikatSemasa;
use App\Support\UntungRugi;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Semua perbelanjaan merentas projek (tab Perbelanjaan versi HTML).
 * Staf hanya nampak perbelanjaan projek yang ditugaskan kepadanya.
 */
#[Layout('layouts::app')]
#[Title('Perbelanjaan')]
class SenaraiBelanja extends Component
{
    use WithPagination;

    #[Url]
    public string $projek = '';

    #[Url]
    public string $kategori = '';

    /** senarai | projek | umum — "Ikut projek" meringkaskan setiap projek; "Umum" = overhead syarikat. */
    #[Url]
    public string $paparan = 'senarai';

    public bool $borangBuka = false;

    public bool $umumBuka = false;

    /** Borang perbelanjaan umum (overhead). */
    public array $bu = [];

    public array $bl = [];

    public function updated($medan): void
    {
        if ($medan === 'paparan') {
            $this->projek = '';     // ikut projek / umum sentiasa merangkumi semua
            $this->kategori = '';   // kategori umum ≠ kategori projek
            $this->borangBuka = $this->umumBuka = false;
        }
        if (in_array($medan, ['projek', 'kategori', 'paparan'], true)) {
            $this->resetPage();
        }
    }

    public function bukaBorang(): void
    {
        $this->bl = [
            'projek_id' => $this->projek !== '' ? (int) $this->projek : '',
            'kategori' => $this->kategori ?: (KategoriBelanja::orderBy('susunan')->value('nama') ?? 'Lain-lain'),
            'amaun' => '', 'tarikh' => now()->toDateString(), 'penerima' => '', 'nota' => '',
        ];
        $this->borangBuka = true;
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'bl.projek_id' => ['required', 'integer'],
            'bl.kategori' => ['required', 'string', 'max:60'],
            'bl.amaun' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'bl.tarikh' => ['nullable', 'date'],
            'bl.penerima' => ['nullable', 'string', 'max:255'],
            'bl.nota' => ['nullable', 'string', 'max:2000'],
        ], ['bl.projek_id.required' => 'Pilih projek.'])['bl'];

        // findOrFail ikut scope syarikat — id projek syarikat lain = 404.
        $p = Projek::findOrFail($data['projek_id']);
        $this->authorize('rekod', $p);

        $p->belanja()->create([
            'kategori' => $data['kategori'], 'amaun' => $data['amaun'], 'tarikh' => $data['tarikh'] ?: null,
            'penerima' => (string) $data['penerima'], 'nota' => $data['nota'] ?: null,
        ]);
        $this->borangBuka = false;
    }

    /** Klik projek dalam paparan "Ikut projek" → senarai perbelanjaan projek itu. */
    public function pilihProjek(int $id): void
    {
        $this->projek = (string) $id;
        $this->paparan = 'senarai';
        $this->resetPage();
    }

    // ---------- Perbelanjaan umum (overhead) ----------

    public function bukaUmum(): void
    {
        abort_unless($this->bolehTulisUmum(), 403);
        $this->bu = ['kategori' => $this->kategori ?: config('dyno.kategori_umum')[0], 'amaun' => '',
            'tarikh' => now()->toDateString(), 'penerima' => '', 'nota' => ''];
        $this->umumBuka = true;
    }

    public function simpanUmum(): void
    {
        abort_unless($this->bolehTulisUmum(), 403);
        $data = $this->validate([
            'bu.kategori' => ['required', 'string', 'max:60'],
            'bu.amaun' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'bu.tarikh' => ['nullable', 'date'],
            'bu.penerima' => ['nullable', 'string', 'max:255'],
            'bu.nota' => ['nullable', 'string', 'max:2000'],
        ], [], ['bu.kategori' => 'kategori', 'bu.amaun' => 'amaun'])['bu'];

        BelanjaUmum::create([
            'kategori' => trim($data['kategori']), 'amaun' => $data['amaun'], 'tarikh' => $data['tarikh'] ?: null,
            'penerima' => (string) $data['penerima'], 'nota' => $data['nota'] ?: null,
        ]);
        $this->umumBuka = false;
    }

    public function padamUmum(int $id): void
    {
        abort_unless($this->bolehTulisUmum(), 403);
        BelanjaUmum::findOrFail($id)->delete();
    }

    /** Overhead: pemilik & pengurus rekod/padam; akauntan lihat; staf tiada akses. */
    private function bolehTulisUmum(): bool
    {
        return (bool) auth()->user()->peranan()?->pengurusan() && ! app(SyarikatSemasa::class)->wajib()->bacaSahaja();
    }

    public function padam(int $id): void
    {
        $x = Belanja::whereIn('projek_id', $this->projekDilihat()->select('id'))->findOrFail($id);
        $this->authorize('delete', $x);
        $x->delete();
    }

    public function render()
    {
        $user = auth()->user();
        if ($this->paparan === 'umum' && ! $user->peranan()?->bolehEksport()) {
            $this->paparan = 'senarai';
        }
        $semuaProjek = $this->projekDilihat()->orderBy('kod')->get(['id', 'kod', 'nama', 'syarikat_id']);

        $q = Belanja::query()->whereIn('projek_id', $semuaProjek->modelKeys())
            ->when($this->projek !== '', fn ($q) => $q->where('projek_id', (int) $this->projek))
            ->when($this->kategori !== '', fn ($q) => $q->where('kategori', $this->kategori));

        // Kad Baki Terkini ikut projek yang ditapis (atau semua projek yang boleh dilihat).
        $projekBaki = $this->projekDilihat()->when($this->projek !== '', fn ($q) => $q->whereKey((int) $this->projek));
        $jumlah = Kiraan::projekDenganJumlah($projekBaki);

        $ikutKategori = (clone $q)->selectRaw('kategori, sum(amaun) as jumlah, count(*) as n')
            ->groupBy('kategori')->get()
            ->map(fn ($r) => ['nama' => $r->kategori, 'sen' => Kiraan::sen($r->jumlah), 'n' => (int) $r->n])
            ->sortByDesc('sen')->values();

        $pilihan = $this->projek !== '' ? $semuaProjek->firstWhere('id', (int) $this->projek) : null;

        // Ikut projek: kategori yang ditapis menyempitkan perbelanjaan; untung sentiasa ikut semua kategori.
        $ikutProjek = collect();
        if ($this->paparan === 'projek') {
            $perKategori = (clone $q)->selectRaw('projek_id, sum(amaun) as jumlah, count(*) as n')->groupBy('projek_id')->get()->keyBy('projek_id');
            $ikutProjek = UntungRugi::perProjek($this->projekDilihat(), 'belanja')
                ->each(function ($p) use ($perKategori) {
                    $p->belanja_tapis = Kiraan::sen($perKategori[$p->id]->jumlah ?? 0);
                    $p->rekod = (int) ($perKategori[$p->id]->n ?? 0);
                })
                ->sortByDesc('belanja_tapis')->values();
        }

        return view('livewire.senarai-belanja', [
            'senarai' => (clone $q)->with(['projek:id,syarikat_id,kod,nama', 'perekod:id,name'])->orderByRaw('tarikh is null, tarikh desc')->orderByDesc('id')->paginate(30),
            'projekSemua' => $semuaProjek,
            'projekRekod' => $semuaProjek->filter(fn ($p) => $user->can('rekod', $p))->values(),
            'kategoriSemua' => KategoriBelanja::orderBy('susunan')->pluck('nama'),
            'ikutKategori' => $ikutKategori,
            'jumlahTapis' => $ikutKategori->sum('sen'),
            'masuk' => $jumlah->sum('diterima_sen'),
            'keluar' => $jumlah->sum('belanja_sen'),
            'nilai' => $jumlah->sum('nilai_sen'),
            'label' => $pilihan ? "{$pilihan->kod} — {$pilihan->nama}" : 'semua projek',
            'ikutProjek' => $ikutProjek,
            ...($this->paparan === 'umum' ? $this->dataUmum() : []),
            'bolehTulisUmum' => $this->bolehTulisUmum(),
            'bolehUntung' => (bool) $user->peranan()?->bolehEksport(),
        ]);
    }

    private function dataUmum(): array
    {
        $q = BelanjaUmum::query()->when($this->kategori !== '', fn ($q) => $q->where('kategori', $this->kategori));
        $ikutKategori = BelanjaUmum::query()->selectRaw('kategori, sum(amaun) as jumlah, count(*) as n')->groupBy('kategori')->get()
            ->map(fn ($r) => ['nama' => $r->kategori, 'sen' => Kiraan::sen($r->jumlah), 'n' => (int) $r->n])->sortByDesc('sen')->values();
        $tahunIni = BelanjaUmum::query()->whereNotNull('tarikh')->get(['tarikh', 'amaun'])
            ->filter(fn ($x) => $x->tarikh->year === now()->year)->sum(fn ($x) => Kiraan::sen($x->amaun));

        return [
            'umum' => (clone $q)->with('perekod:id,name')->orderByRaw('tarikh is null, tarikh desc')->orderByDesc('id')->paginate(30),
            'umumKategori' => $ikutKategori,
            'umumJumlah' => $ikutKategori->sum('sen'),
            'umumTahunIni' => $tahunIni,
            'kategoriUmum' => collect(config('dyno.kategori_umum'))->merge($ikutKategori->pluck('nama'))->unique()->values(),
        ];
    }

    private function projekDilihat()
    {
        return Projek::query()->bolehDilihat(auth()->user());
    }
}
