<?php

namespace App\Livewire\Forms;

use App\Models\Bayaran;
use App\Models\Projek;
use App\Support\SyarikatSemasa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ProjekForm extends Form
{
    public ?Projek $projek = null;

    public string $kod = '';

    public string $nama = '';

    public string $klien = '';

    public string $lokasi = '';

    public string $telefon = '';

    public string $inv = '';

    public string $status = 'Baru';

    public string $nilai = '';

    public string $mula = '';

    public string $nota = '';

    /** @var array<string, bool> */
    public array $aliran = [];

    /** Projek baharu sahaja: deposit awal jadi rekod bayaran Diterima. */
    public string $deposit = '';

    public function baharu(): void
    {
        $this->reset();
        $this->kod = Projek::kodSeterusnya();
        $this->mula = now()->toDateString();
    }

    public function isi(Projek $p): void
    {
        $this->projek = $p;
        $this->fill($p->only(['kod', 'nama', 'klien', 'lokasi', 'telefon', 'inv', 'status', 'nota']));
        $this->nota = (string) $p->nota;
        $this->nilai = (string) $p->nilai;
        $this->mula = (string) $p->mula?->toDateString();
        $this->aliran = $p->aliran ?? [];
    }

    protected function rules(): array
    {
        return [
            'kod' => ['required', 'string', 'max:30', Rule::unique('projek', 'kod')
                ->where('syarikat_id', app(SyarikatSemasa::class)->id())
                ->ignore($this->projek?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'klien' => ['nullable', 'string', 'max:255'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'telefon' => ['nullable', 'string', 'max:40'],
            'inv' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'string', 'max:40'],
            'nilai' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'mula' => ['nullable', 'date'],
            'nota' => ['nullable', 'string', 'max:5000'],
            'aliran' => ['array'],
            'aliran.*' => ['boolean'],
            'deposit' => ['nullable', 'numeric', 'min:0', 'lte:nilai'],
        ];
    }

    protected function messages(): array
    {
        return ['kod.unique' => 'Kod projek ini sudah digunakan.', 'deposit.lte' => 'Deposit tidak boleh melebihi nilai kontrak.'];
    }

    public function simpan(): Projek
    {
        $data = $this->validate();
        $medan = collect($data)->only(['kod', 'nama', 'klien', 'lokasi', 'telefon', 'inv', 'status', 'nilai', 'mula', 'nota', 'aliran'])
            ->map(fn ($v, $k) => in_array($k, ['klien', 'lokasi', 'telefon', 'inv'], true) ? (string) $v : $v)
            ->put('mula', $data['mula'] ?: null)
            ->all();

        return DB::transaction(function () use ($medan, $data) {
            if ($this->projek) {
                $this->projek->update($medan);

                return $this->projek;
            }

            $p = Projek::create($medan);
            if ((float) ($data['deposit'] ?? 0) > 0) {
                $p->bayaran()->create([
                    'jenis' => 'Deposit', 'amaun' => $data['deposit'], 'tarikh' => $p->mula ?? now(),
                    'status' => Bayaran::DITERIMA,
                ]);
            }

            return $p;
        });
    }
}
