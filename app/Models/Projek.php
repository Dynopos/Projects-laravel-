<?php

namespace App\Models;

use App\Models\Concerns\DirekodOleh;
use App\Models\Concerns\MilikSyarikat;
use App\Observers\CatatAktiviti;
use App\Support\Kiraan;
use App\Support\SyarikatSemasa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Table('projek')]
#[Fillable(['kod', 'nama', 'klien', 'lokasi', 'telefon', 'inv', 'status', 'nilai', 'mula', 'nota', 'aliran'])]
class Projek extends Model
{
    use DirekodOleh, HasFactory, MilikSyarikat;

    protected static function booted(): void
    {
        static::updating(function (self $p) {
            $p->diubah_oleh = auth()->id() ?? $p->diubah_oleh;
        });
    }

    protected function casts(): array
    {
        return ['nilai' => 'decimal:2', 'mula' => 'date', 'aliran' => 'array'];
    }

    public function bayaran(): HasMany
    {
        return $this->hasMany(Bayaran::class);
    }

    public function belanja(): HasMany
    {
        return $this->hasMany(Belanja::class);
    }

    public function kerja(): HasMany
    {
        return $this->hasMany(Kerja::class);
    }

    /**
     * Tambah kerja baharu. Kerja pertama pada projek yang sudah ada nilai/rekod: nilai asal
     * dan semua rekod sedia ada dijadikan kerja "{nama projek}" dahulu supaya tiada yang hilang.
     * Projek Siap dibuka semula (kerja baharu belum siap).
     */
    public function tambahKerja(array $data): Kerja
    {
        return DB::transaction(function () use ($data) {
            if (! $this->kerja()->exists() && ((float) $this->nilai > 0 || $this->bayaran()->exists() || $this->belanja()->exists())) {
                $asal = $this->kerja()->create(['nama' => $this->nama, 'nilai' => $this->nilai,
                    'tarikh' => $this->mula, 'siap' => $this->jenisStatus() === 'siap']);
                $this->bayaran()->whereNull('kerja_id')->update(['kerja_id' => $asal->id]);
                $this->belanja()->whereNull('kerja_id')->update(['kerja_id' => $asal->id]);
            }
            $k = $this->kerja()->create([...$data, 'siap' => false]);
            if ($this->jenisStatus() === 'siap') {
                $this->update(['status' => 'Dalam Progres']);
            }

            return $k;
        });
    }

    /** projek.nilai = Σ kerja.nilai bila projek ada kerja. Kiraan & laporan terus guna projek.nilai. */
    public function kemasKiniNilai(): void
    {
        if (! $this->kerja()->exists()) {
            return;
        }
        $jumlah = number_format((float) $this->kerja()->sum('nilai'), 2, '.', '');
        if ((string) $this->nilai !== $jumlah) {
            CatatAktiviti::senyap(fn () => $this->forceFill(['nilai' => $jumlah])->save());
        }
    }

    public function staf(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'penugasan');
    }

    public function tugaskan(User $user): void
    {
        $this->staf()->syncWithoutDetaching([$user->id => ['syarikat_id' => $this->syarikat_id]]);
    }

    /**
     * Rekod bayaran oleh $oleh. Staf (bukan pengurusan) SENTIASA direkod sebagai Menunggu —
     * pengurus sahkan kemudian. Dikuatkuasa di sini supaya setiap borang ikut peraturan sama.
     *
     * @param  array{jenis: string, amaun: mixed, tarikh?: ?string, kaedah?: ?string, ruj?: ?string, status?: string}  $data
     */
    public function rekodBayaran(User $oleh, array $data): Bayaran
    {
        $status = $oleh->peranan()?->pengurusan() ? ($data['status'] ?? Bayaran::DITERIMA) : Bayaran::MENUNGGU;

        return $this->bayaran()->create([
            'jenis' => $data['jenis'], 'amaun' => $data['amaun'], 'tarikh' => ($data['tarikh'] ?? null) ?: null,
            'kaedah' => (string) ($data['kaedah'] ?? ''), 'ruj' => (string) ($data['ruj'] ?? ''), 'status' => $status,
            'kerja_id' => $this->idKerja($data['kerja_id'] ?? null),
        ]);
    }

    /** kerja_id dari borang — mesti kerja projek INI (bukan projek / syarikat lain). */
    public function idKerja(mixed $id): ?int
    {
        return $id ? $this->kerja()->whereKey((int) $id)->value('id') : null;
    }

    /** "siap" | "jalan" | "pending" — lihat Kiraan::jenisStatus(). */
    public function jenisStatus(): string
    {
        return Kiraan::jenisStatus($this->status);
    }

    /** Projek yang pengguna ini boleh nampak dalam syarikat semasa. */
    public function scopeBolehDilihat(Builder $q, User $user): void
    {
        if (! $user->peranan()?->nampakSemua()) {
            $q->whereHas('staf', fn ($s) => $s->whereKey($user->id));
        }
    }

    /** Kod seterusnya: awalan syarikat + nombor terbesar sedia ada + 1, cth PRJ-09. */
    public static function kodSeterusnya(): string
    {
        $awalan = app(SyarikatSemasa::class)->wajib()->kod_awalan;
        $max = static::query()->where('kod', 'like', $awalan.'%')->pluck('kod')
            ->map(fn ($k) => (int) preg_replace('/\D/', '', substr($k, strlen($awalan))))
            ->max() ?? 0;

        return $awalan.str_pad((string) ($max + 1), 2, '0', STR_PAD_LEFT);
    }
}
