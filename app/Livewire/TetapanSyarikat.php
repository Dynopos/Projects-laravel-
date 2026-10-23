<?php

namespace App\Livewire;

use App\Models\Belanja;
use App\Models\KategoriBelanja;
use App\Models\LogAktiviti;
use App\Models\PeringkatAliran;
use App\Models\Syarikat;
use App\Support\Logo;
use App\Support\SyarikatSemasa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Menggantikan config.js versi HTML: setiap pemilik ubah jenama, penomboran,
 * peringkat kerja dan kategori perbelanjaan syarikatnya sendiri.
 */
#[Layout('layouts::app')]
#[Title('Tetapan Syarikat')]
class TetapanSyarikat extends Component
{
    use WithFileUploads;

    public array $maklumat = [];

    public $logo;

    /** @var array<int, string> id => label */
    public array $labelPeringkat = [];

    /** @var array<int, string> id => nama */
    public array $namaKategori = [];

    public string $peringkatBaru = '';

    public string $kategoriBaru = '';

    public ?string $mesej = null;

    public function mount(): void
    {
        $this->pastikanBoleh();
        $this->maklumat = $this->syarikat()->only(['nama', 'sufiks', 'jabatan', 'kod_awalan', 'zon_waktu']);
        $this->muatSenarai();
    }

    // ---------- Maklumat ----------

    public function simpanMaklumat(): void
    {
        $this->pastikanBoleh();
        $data = $this->validate([
            'maklumat.nama' => ['required', 'string', 'max:80'],
            'maklumat.sufiks' => ['nullable', 'string', 'max:20'],
            'maklumat.jabatan' => ['nullable', 'string', 'max:60'],
            'maklumat.kod_awalan' => ['required', 'string', 'max:12', 'regex:/^[A-Z0-9]+-?$/'],
            'maklumat.zon_waktu' => ['required', 'timezone:all'],
        ], ['maklumat.kod_awalan.regex' => 'Huruf besar dan nombor sahaja, boleh diakhiri "-" (cth FC-).'])['maklumat'];

        $s = $this->syarikat();
        $s->update([...$data, 'sufiks' => (string) $data['sufiks'], 'jabatan' => (string) $data['jabatan']]);
        LogAktiviti::catat('Ubah tetapan syarikat', collect($s->getChanges())->except('updated_at')->keys()->implode(', '));
        $this->mesej = 'Maklumat syarikat disimpan.';
    }

    // ---------- Logo ----------

    public function simpanLogo(): void
    {
        $this->pastikanBoleh();
        $this->validate(['logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=64']],
            ['logo.mimes' => 'Guna PNG, JPG atau WebP.', 'logo.dimensions' => 'Logo sekurang-kurangnya 64×64 piksel.']);

        $s = $this->syarikat();
        $s->update(['logo_path' => Logo::simpan($s, $this->logo)]);
        $this->reset('logo');
        LogAktiviti::catat('Tukar logo syarikat');
        $this->mesej = 'Logo dikemas kini.';
    }

    public function buangLogo(): void
    {
        $this->pastikanBoleh();
        $s = $this->syarikat();
        Logo::padam($s);
        $s->update(['logo_path' => null]);
        LogAktiviti::catat('Buang logo syarikat');
    }

    // ---------- Peringkat aliran ----------

    public function tambahPeringkat(): void
    {
        $this->pastikanBoleh();
        $label = trim($this->validate(['peringkatBaru' => ['required', 'string', 'max:60']])['peringkatBaru']);

        // Kunci kekal walaupun label ditukar kemudian — ia disimpan dalam projek.aliran.
        $asas = Str::limit(Str::camel(Str::ascii($label)), 34, '') ?: 'peringkat';
        $kunci = $asas;
        for ($i = 2; PeringkatAliran::where('kunci', $kunci)->exists(); $i++) {
            $kunci = $asas.$i;
        }

        PeringkatAliran::create(['kunci' => $kunci, 'label' => $label, 'susunan' => (int) PeringkatAliran::max('susunan') + 1]);
        LogAktiviti::catat('Tambah peringkat aliran', $label);
        $this->reset('peringkatBaru');
        $this->muatSenarai();
    }

    public function simpanPeringkat(int $id): void
    {
        $this->pastikanBoleh();
        $this->validate(["labelPeringkat.$id" => ['required', 'string', 'max:60']], [], ["labelPeringkat.$id" => 'label']);
        $p = PeringkatAliran::findOrFail($id);
        $p->update(['label' => trim($this->labelPeringkat[$id])]);
    }

    public function padamPeringkat(int $id): void
    {
        $this->pastikanBoleh();
        $p = PeringkatAliran::findOrFail($id);
        // Tanda dalam projek.aliran dibiarkan — tambah semula kunci yang sama memulihkannya.
        $p->delete();
        LogAktiviti::catat('Padam peringkat aliran', $p->label);
        $this->muatSenarai();
    }

    public function alihPeringkat(int $id, int $arah): void
    {
        $this->pastikanBoleh();
        $this->alih(PeringkatAliran::class, $id, $arah);
    }

    // ---------- Kategori perbelanjaan ----------

    public function tambahKategori(): void
    {
        $this->pastikanBoleh();
        $nama = trim($this->validate(['kategoriBaru' => ['required', 'string', 'max:60',
            Rule::unique('kategori_belanja', 'nama')->where('syarikat_id', $this->syarikat()->id)]],
            ['kategoriBaru.unique' => 'Kategori ini sudah wujud.'])['kategoriBaru']);

        KategoriBelanja::create(['nama' => $nama, 'susunan' => (int) KategoriBelanja::max('susunan') + 1]);
        LogAktiviti::catat('Tambah kategori perbelanjaan', $nama);
        $this->reset('kategoriBaru');
        $this->muatSenarai();
    }

    /** Tukar nama kategori — rekod perbelanjaan lama ikut sekali supaya laporan tak berpecah. */
    public function simpanKategori(int $id): void
    {
        $this->pastikanBoleh();
        $k = KategoriBelanja::findOrFail($id);
        $baru = trim((string) ($this->namaKategori[$id] ?? ''));
        $this->validate(["namaKategori.$id" => ['required', 'string', 'max:60',
            Rule::unique('kategori_belanja', 'nama')->where('syarikat_id', $this->syarikat()->id)->ignore($id)]],
            ["namaKategori.$id.unique" => 'Kategori ini sudah wujud.'], ["namaKategori.$id" => 'nama']);

        if ($baru === $k->nama) {
            return;
        }

        DB::transaction(function () use ($k, $baru) {
            $lama = $k->nama;
            Belanja::where('kategori', $lama)->update(['kategori' => $baru]);
            $k->update(['nama' => $baru]);
            LogAktiviti::catat('Tukar nama kategori', "{$lama} → {$baru}");
        });
    }

    public function padamKategori(int $id): void
    {
        $this->pastikanBoleh();
        $k = KategoriBelanja::findOrFail($id);
        $guna = Belanja::where('kategori', $k->nama)->count();
        if ($guna > 0) {
            $this->addError("namaKategori.$id", "Masih digunakan oleh {$guna} rekod perbelanjaan — tukar namanya, bukan padam.");

            return;
        }
        $k->delete();
        LogAktiviti::catat('Padam kategori perbelanjaan', $k->nama);
        $this->muatSenarai();
    }

    public function alihKategori(int $id, int $arah): void
    {
        $this->pastikanBoleh();
        $this->alih(KategoriBelanja::class, $id, $arah);
    }

    public function render()
    {
        return view('livewire.tetapan-syarikat', [
            'syarikat' => $this->syarikat(),
            'peringkat' => PeringkatAliran::orderBy('susunan')->orderBy('id')->get(),
            'kategori' => KategoriBelanja::orderBy('susunan')->orderBy('id')->get(),
            'penggunaan' => Belanja::selectRaw('kategori, count(*) as n')->groupBy('kategori')->pluck('n', 'kategori'),
        ]);
    }

    // ---------- Dalaman ----------

    /** Tukar tempat dengan jiran di atas (-1) atau di bawah (+1), kemudian nombor semula 0..n. */
    private function alih(string $model, int $id, int $arah): void
    {
        $senarai = $model::orderBy('susunan')->orderBy('id')->get()->values();
        $i = $senarai->search(fn ($x) => $x->id === $id);
        $j = $i === false ? false : $i + ($arah < 0 ? -1 : 1);
        if ($i === false || $j < 0 || $j >= $senarai->count()) {
            return;
        }

        $tertib = $senarai->all();
        [$tertib[$i], $tertib[$j]] = [$tertib[$j], $tertib[$i]];
        DB::transaction(function () use ($tertib) {
            foreach ($tertib as $n => $x) {
                $x->update(['susunan' => $n]);
            }
        });
        $this->muatSenarai();
    }

    private function muatSenarai(): void
    {
        $this->labelPeringkat = PeringkatAliran::orderBy('susunan')->pluck('label', 'id')->all();
        $this->namaKategori = KategoriBelanja::orderBy('susunan')->pluck('nama', 'id')->all();
    }

    private function pastikanBoleh(): void
    {
        abort_unless(auth()->user()->peranan()?->bolehTetapan(), 403);
        abort_if($this->syarikat()->bacaSahaja(), 403, 'Syarikat dalam mod baca sahaja.');
    }

    private function syarikat(): Syarikat
    {
        return app(SyarikatSemasa::class)->wajib();
    }
}
