<?php

namespace App\Livewire;

use App\Models\Bayaran;
use App\Models\Projek;
use App\Support\Kiraan;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Semua bayaran merentas projek (tab Pembayaran versi HTML).
 * Kotak Dashboard "Deposit Payment" / "Received Payment" melompat ke sini dengan tapisan,
 * dan jumlah di sini mesti sama dengan kotak itu (Menunggu tidak dikira sebagai diterima).
 */
#[Layout('layouts::app')]
#[Title('Pembayaran')]
class SenaraiBayaran extends Component
{
    use WithPagination;

    #[Url]
    public string $projek = '';

    /** '' | Diterima | Menunggu */
    #[Url]
    public string $status = '';

    /** '' | deposit — padanan Kiraan::REGEX_DEPOSIT */
    #[Url]
    public string $jenis = '';

    public bool $borangBuka = false;

    public array $b = [];

    public function updated($medan): void
    {
        if (in_array($medan, ['projek', 'status', 'jenis'], true)) {
            $this->resetPage();
        }
    }

    public function kosongkan(): void
    {
        $this->reset('projek', 'status', 'jenis');
        $this->resetPage();
    }

    public function bukaBorang(): void
    {
        $this->b = [
            'projek_id' => $this->projek !== '' ? (int) $this->projek : '',
            'jenis' => 'Bayaran Progres', 'amaun' => '', 'tarikh' => now()->toDateString(),
            'kaedah' => 'Pindahan Bank', 'ruj' => '', 'status' => Bayaran::DITERIMA,
        ];
        $this->borangBuka = true;
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'b.projek_id' => ['required', 'integer'],
            'b.jenis' => ['required', 'string', 'max:60'],
            'b.amaun' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'b.tarikh' => ['nullable', 'date'],
            'b.kaedah' => ['nullable', 'string', 'max:60'],
            'b.ruj' => ['nullable', 'string', 'max:80'],
            'b.status' => ['required', 'in:Diterima,Menunggu'],
        ], ['b.projek_id.required' => 'Pilih projek.'])['b'];

        $p = Projek::findOrFail($data['projek_id']);   // scope syarikat — syarikat lain = 404
        $this->authorize('rekod', $p);
        $p->rekodBayaran(auth()->user(), $data);
        $this->borangBuka = false;
    }

    public function sahkan(int $id): void
    {
        $b = $this->cari($id);
        $this->authorize('sahkan', $b);
        $b->sahkan(auth()->user());
    }

    public function padam(int $id): void
    {
        $b = $this->cari($id);
        $this->authorize('delete', $b);
        $b->delete();
    }

    public function render()
    {
        $user = auth()->user();
        $projek = $this->projekDilihat()->orderBy('kod')->get(['id', 'kod', 'nama', 'syarikat_id']);

        $asas = Bayaran::query()->whereIn('projek_id', $projek->modelKeys())
            ->when($this->projek !== '', fn ($q) => $q->where('projek_id', (int) $this->projek));
        $tapis = (clone $asas)->when($this->status !== '', fn ($q) => $q->where('status', $this->status));

        // Jumlah ikut status & jenis, dikira dalam sen; regex deposit sama dengan Dashboard.
        $kumpulan = (clone $asas)->selectRaw('status, jenis, sum(amaun) as jumlah, count(*) as n')
            ->groupBy('status', 'jenis')->get();
        $diterima = $kumpulan->where('status', '!=', Bayaran::MENUNGGU);
        $menunggu = $kumpulan->where('status', Bayaran::MENUNGGU);
        $deposit = $diterima->filter(fn ($r) => preg_match(Kiraan::REGEX_DEPOSIT, (string) $r->jenis));

        $senarai = $tapis->with('projek:id,syarikat_id,kod,nama')->orderByRaw('tarikh is null, tarikh desc')->orderByDesc('id')->get();
        if ($this->jenis === 'deposit') {
            $senarai = $senarai->filter(fn ($b) => preg_match(Kiraan::REGEX_DEPOSIT, (string) $b->jenis))->values();
        }
        $halaman = $this->getPage();
        $paparan = new LengthAwarePaginator(
            $senarai->forPage($halaman, 30)->values(), $senarai->count(), 30, $halaman, ['pageName' => 'page']
        );

        return view('livewire.senarai-bayaran', [
            'senarai' => $paparan,
            'jumlahSenarai' => $senarai->sum(fn ($b) => Kiraan::sen($b->amaun)),
            'projekSemua' => $projek,
            'projekRekod' => $projek->filter(fn ($p) => $user->can('rekod', $p))->values(),
            'pengurusan' => (bool) $user->peranan()?->pengurusan(),
            'diterimaSen' => $diterima->sum(fn ($r) => Kiraan::sen($r->jumlah)),
            'diterimaN' => (int) $diterima->sum('n'),
            'menungguSen' => $menunggu->sum(fn ($r) => Kiraan::sen($r->jumlah)),
            'menungguN' => (int) $menunggu->sum('n'),
            'depositSen' => $deposit->sum(fn ($r) => Kiraan::sen($r->jumlah)),
            'depositN' => (int) $deposit->sum('n'),
        ]);
    }

    private function cari(int $id): Bayaran
    {
        return Bayaran::whereIn('projek_id', $this->projekDilihat()->select('id'))->findOrFail($id);
    }

    private function projekDilihat()
    {
        return Projek::query()->bolehDilihat(auth()->user());
    }
}
