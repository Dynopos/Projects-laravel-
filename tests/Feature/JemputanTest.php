<?php

use App\Enums\Peranan;
use App\Livewire\UrusAhli;
use App\Mail\JemputanMail;
use App\Models\Jemputan;
use App\Models\Keahlian;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    $this->s = syarikatBaru(['nama' => 'BINAAN UJIAN']);
    semasa()->tetapkan($this->s);
    $this->pemilik = ahli($this->s, Peranan::Pemilik);
});

/** Hantar jemputan melalui skrin dan pulangkan pautan dari e-mel. */
function jemputDanAmbilPautan(User $oleh, string $email, string $peranan = 'staf'): string
{
    Livewire::actingAs($oleh)->test(UrusAhli::class)
        ->set('email', $email)->set('peranan', $peranan)->call('jemput')->assertHasNoErrors();

    $pautan = null;
    Mail::assertSent(JemputanMail::class, function (JemputanMail $m) use ($email, &$pautan) {
        $pautan = $m->pautan;

        return $m->hasTo(strtolower($email));
    });

    return $pautan;
}

it('pemilik menjemput staf dan e-mel mengandungi pautan ke subdomain syarikat', function () {
    $pautan = jemputDanAmbilPautan($this->pemilik, 'Ali@Contoh.test');

    expect($pautan)->toStartWith(urlSyarikat($this->s, '/jemputan/'));
    $j = Jemputan::sole();
    expect($j->email)->toBe('ali@contoh.test')
        ->and($j->peranan)->toBe(Peranan::Staf)
        ->and($j->token)->not->toContain(basename($pautan));   // hanya hash disimpan
});

it('staf baharu menerima jemputan, akaun dicipta dan terus log masuk', function () {
    $pautan = jemputDanAmbilPautan($this->pemilik, 'ali@contoh.test');
    auth()->logout();

    $this->get($pautan)->assertOk()->assertSee('Sertai BINAAN UJIAN')->assertSee('Nama penuh');
    $this->post($pautan, ['name' => 'Ali Staf', 'password' => 'rahsia-baru', 'password_confirmation' => 'rahsia-baru'])
        ->assertRedirect(urlSyarikat($this->s, ''));

    $u = User::where('email', 'ali@contoh.test')->sole();
    $this->assertAuthenticatedAs($u);
    semasa()->tetapkan($this->s);   // middleware mengosongkan syarikat selepas setiap permintaan
    expect($u->email_verified_at)->not->toBeNull()
        ->and($u->keahlianDi($this->s)->peranan)->toBe(Peranan::Staf)
        ->and(Jemputan::sole()->diterima_pada)->not->toBeNull();
});

it('pautan hanya boleh digunakan sekali', function () {
    $pautan = jemputDanAmbilPautan($this->pemilik, 'ali@contoh.test');
    auth()->logout();
    $this->post($pautan, ['name' => 'Ali', 'password' => 'rahsia-baru', 'password_confirmation' => 'rahsia-baru']);
    auth()->logout();

    $this->get($pautan)->assertOk()->assertSee('Jemputan tidak sah');
    $this->post($pautan, ['name' => 'Penceroboh', 'password' => 'xxxxxxxx', 'password_confirmation' => 'xxxxxxxx'])->assertStatus(410);
});

it('jemputan tamat tempoh ditolak', function () {
    $pautan = jemputDanAmbilPautan($this->pemilik, 'ali@contoh.test');
    auth()->logout();
    $this->travel(Jemputan::TEMPOH_HARI + 1)->days();

    $this->post($pautan, ['name' => 'Ali', 'password' => 'rahsia-baru', 'password_confirmation' => 'rahsia-baru'])->assertStatus(410);
    expect(User::where('email', 'ali@contoh.test')->exists())->toBeFalse();
});

it('token syarikat A tidak berfungsi pada subdomain syarikat B', function () {
    $pautan = jemputDanAmbilPautan($this->pemilik, 'ali@contoh.test');
    auth()->logout();
    $b = syarikatBaru();
    $pautanB = str_replace($this->s->subdomain.'.', $b->subdomain.'.', $pautan);

    $this->get($pautanB)->assertSee('Jemputan tidak sah');
    $this->post($pautanB, ['name' => 'Ali', 'password' => 'rahsia-baru', 'password_confirmation' => 'rahsia-baru'])->assertStatus(410);
});

it('akaun sedia ada mesti sahkan dengan kata laluan sendiri', function () {
    $lain = syarikatBaru();
    $u = ahli($lain, Peranan::Staf, ['email' => 'siti@contoh.test', 'password' => 'kata-asal']);
    $pautan = jemputDanAmbilPautan($this->pemilik, 'siti@contoh.test', 'akauntan');
    auth()->logout();

    $this->get($pautan)->assertSee('sudah ada akaun');
    $this->post($pautan, ['password' => 'salah'])->assertSessionHasErrors('password');
    expect($u->fresh()->keahlianDi($this->s))->toBeNull();

    $this->post($pautan, ['password' => 'kata-asal'])->assertRedirect();
    expect($u->fresh()->keahlianDi($this->s)->peranan)->toBe(Peranan::Akauntan)
        ->and($u->fresh()->keahlianDi($lain)->peranan)->toBe(Peranan::Staf);
});

it('pengurus hanya boleh menjemput staf', function () {
    $pengurus = ahli($this->s, Peranan::Pengurus);

    Livewire::actingAs($pengurus)->test(UrusAhli::class)
        ->set('email', 'x@contoh.test')->set('peranan', 'pemilik')->call('jemput')
        ->assertHasErrors('peranan');
    Mail::assertNothingSent();
});

it('staf dan akauntan tidak boleh buka halaman ahli', function (Peranan $p) {
    Livewire::actingAs(ahli($this->s, $p))->test(UrusAhli::class)->assertForbidden();
})->with([Peranan::Staf, Peranan::Akauntan]);

it('menolak jemputan untuk e-mel yang sudah ahli', function () {
    ahli($this->s, Peranan::Staf, ['email' => 'ada@contoh.test']);

    Livewire::actingAs($this->pemilik)->test(UrusAhli::class)
        ->set('email', 'ADA@contoh.test')->call('jemput')->assertHasErrors('email');
});

it('hantar semula membatalkan pautan lama', function () {
    $lama = jemputDanAmbilPautan($this->pemilik, 'ali@contoh.test');
    Livewire::actingAs($this->pemilik)->test(UrusAhli::class)->call('hantarSemula', Jemputan::sole()->id);
    auth()->logout();

    $this->get($lama)->assertSee('Jemputan tidak sah');
    semasa()->tetapkan($this->s);
    expect(Jemputan::count())->toBe(1);
});

it('nyahaktif ahli menghalang log masuk; tidak boleh ubah diri sendiri', function () {
    $staf = ahli($this->s, Peranan::Staf, ['email' => 'staf@contoh.test', 'password' => 'rahsia-123']);
    $k = Keahlian::where('user_id', $staf->id)->sole();

    Livewire::actingAs($this->pemilik)->test(UrusAhli::class)->call('togolAktif', $k->id);
    expect($k->fresh()->aktif)->toBeFalse();

    $milik = Keahlian::where('user_id', $this->pemilik->id)->sole();
    Livewire::actingAs($this->pemilik)->test(UrusAhli::class)->call('togolAktif', $milik->id)->assertForbidden();
    Livewire::actingAs($this->pemilik)->test(UrusAhli::class)->call('tukarPeranan', $milik->id, 'staf')->assertForbidden();

    auth()->logout();
    $this->post(urlSyarikat($this->s, '/masuk'), ['email' => 'staf@contoh.test', 'password' => 'rahsia-123'])
        ->assertSessionHasErrors('email');
});

it('pengurus tidak boleh mengurus pemilik atau pengurus lain', function () {
    $pengurus = ahli($this->s, Peranan::Pengurus);
    $lain = ahli($this->s, Peranan::Pengurus);
    $k = Keahlian::where('user_id', $lain->id)->sole();

    Livewire::actingAs($pengurus)->test(UrusAhli::class)->call('togolAktif', $k->id)->assertForbidden();
    Livewire::actingAs($pengurus)->test(UrusAhli::class)->call('tukarPeranan', $k->id, 'staf')->assertForbidden();
});
