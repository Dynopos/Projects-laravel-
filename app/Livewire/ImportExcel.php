<?php

namespace App\Livewire;

use App\Import\BacaFail;
use App\Import\HasilImport;
use App\Import\HuraiFlow;
use App\Import\HuraiGenerik;
use App\Import\SimpanImport;
use App\Models\Projek;
use App\Support\SyarikatSemasa;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/**
 * Import Excel: format PROJECT FLOW dicuba dahulu (tidak akan tersalah padan kerana ia
 * perlukan tajuk "No." dan "Total Project"), kemudian penghurai generik ikut helaian.
 * Fail dihurai semula semasa import — keadaan komponen hanya simpan fail sementara.
 */
#[Layout('layouts::app')]
#[Title('Import Excel')]
class ImportExcel extends Component
{
    use WithFileUploads;

    public $fail;

    public ?string $helaian = null;

    /** @var array{projek: int, bayaran: int}|null */
    public ?array $selesai = null;

    public ?string $ralat = null;

    public function mount(): void
    {
        $this->pastikanBoleh();
    }

    public function updatedFail(): void
    {
        $this->reset('helaian', 'selesai', 'ralat');
        $this->validate(['fail' => ['required', 'file', 'max:10240', 'extensions:xls,xlsx,csv']],
            ['fail.extensions' => 'Guna fail .xls, .xlsx atau .csv.', 'fail.max' => 'Fail melebihi 10 MB.']);
    }

    public function importSemua(): void
    {
        $this->jalankan(false);
    }

    public function importBaharu(): void
    {
        $this->jalankan(true);
    }

    public function render()
    {
        $hasil = null;
        $helaianSemua = [];
        $semak = null;

        if ($this->fail && ! $this->selesai) {
            try {
                [$hasil, $helaianSemua] = $this->hurai();
                $semak = $hasil ? SimpanImport::semak($hasil) : null;
            } catch (Throwable $e) {
                report($e);
                $this->ralat = 'Fail tidak dapat dibaca. Pastikan ia fail Excel yang sah.';
            }
        }

        return view('livewire.import-excel', [
            'hasil' => $hasil,
            'helaianSemua' => $helaianSemua,
            'semak' => $semak,
            'hariIni' => now(app(SyarikatSemasa::class)->wajib()->zon_waktu)->toDateString(),
        ]);
    }

    private function jalankan(bool $hanyaBaharu): void
    {
        $this->pastikanBoleh();
        [$hasil] = $this->hurai();
        abort_unless($hasil, 422);
        $this->selesai = SimpanImport::simpan($hasil, $hanyaBaharu);
        $this->reset('fail');
    }

    /** @return array{0: ?HasilImport, 1: list<string>} */
    private function hurai(): array
    {
        $buku = BacaFail::helaian($this->fail->getRealPath());
        $awalan = app(SyarikatSemasa::class)->wajib()->kod_awalan;

        foreach ($buku as $nama => $baris) {
            if ($h = HuraiFlow::hurai($baris, $awalan, $nama)) {
                return [$h, array_keys($buku)];
            }
        }

        $berisi = array_keys(array_filter($buku, fn ($b) => count($b) > 1));
        $this->helaian = in_array($this->helaian, $berisi, true) ? $this->helaian : ($berisi[0] ?? null);
        if (! $this->helaian) {
            return [null, $berisi];
        }

        // Baris tanpa kod: sambung dari kod terbesar sedia ada — jangan timpa projek yang wujud.
        $mula = (int) preg_replace('/\D/', '', substr(Projek::kodSeterusnya(), strlen($awalan)));
        $kod = fn (int $n) => $awalan.str_pad((string) ($mula + $n - 1), 2, '0', STR_PAD_LEFT);

        return [HuraiGenerik::hurai($buku[$this->helaian], $kod, $this->helaian), $berisi];
    }

    private function pastikanBoleh(): void
    {
        abort_unless(auth()->user()->peranan()?->pengurusan(), 403);
        abort_if(app(SyarikatSemasa::class)->wajib()->bacaSahaja(), 403);
    }
}
