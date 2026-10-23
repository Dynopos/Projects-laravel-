<?php

namespace App\Models;

use App\Enums\Peranan;
use App\Support\SyarikatSemasa;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('syarikat')]
#[Fillable(['nama', 'sufiks', 'jabatan', 'logo_path', 'subdomain', 'domain', 'kod_awalan', 'status', 'zon_waktu'])]
class Syarikat extends Model
{
    use HasFactory;

    /** Sama dengan nilai lalai migrasi — supaya model yang baru dicipta tidak null. */
    protected $attributes = [
        'sufiks' => '', 'jabatan' => 'Jabatan Projek', 'kod_awalan' => 'PRJ-',
        'status' => 'percubaan', 'zon_waktu' => 'Asia/Kuala_Lumpur',
    ];

    public function keahlian(): HasMany
    {
        return $this->hasMany(Keahlian::class);
    }

    public function ahli(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'keahlian')->withPivot('peranan', 'aktif')->withTimestamps();
    }

    public function tambahAhli(User $user, Peranan $peranan): Keahlian
    {
        return $this->keahlian()->updateOrCreate(['user_id' => $user->id], ['peranan' => $peranan, 'aktif' => true]);
    }

    /** Peringkat aliran & kategori perbelanjaan lalai untuk syarikat baharu. */
    public function sediakanTetapanLalai(): void
    {
        app(SyarikatSemasa::class)->sebagai($this, function () {
            $i = 0;
            foreach (config('dyno.peringkat_lalai') as $kunci => $label) {
                PeringkatAliran::firstOrCreate(['kunci' => $kunci], ['label' => $label, 'susunan' => $i++]);
            }
            foreach (config('dyno.kategori_lalai') as $i => $nama) {
                KategoriBelanja::firstOrCreate(['nama' => $nama], ['susunan' => $i]);
            }
        });
    }

    /** Nama paparan, cth "FINE CABINETRY" + ".CO". */
    public function namaPenuh(): string
    {
        return $this->nama.$this->sufiks;
    }

    public function inisial(): string
    {
        return collect(preg_split('/\s+/', trim($this->nama)))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
    }

    /** URL logo dengan versi supaya cache browser dikemas kini bila logo ditukar. */
    public function urlLogo(): ?string
    {
        return $this->logo_path ? url('/logo').'?v='.substr(md5($this->logo_path), 0, 8) : null;
    }

    /** URL penuh ke laluan dalam ruang syarikat ini (untuk e-mel). */
    public function url(string $laluan = '/'): string
    {
        $skema = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $hos = $this->domain ?: $this->subdomain.'.'.config('dyno.domain_utama');

        return $skema.'://'.$hos.'/'.ltrim($laluan, '/');
    }

    public function bacaSahaja(): bool
    {
        return in_array($this->status, ['baca_sahaja', 'digantung'], true);
    }
}
