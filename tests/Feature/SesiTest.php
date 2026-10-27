<?php

use App\Enums\Peranan;
use App\Mail\JemputanMail;
use App\Models\LogAktiviti;
use App\Models\PeringkatAliran;
use App\Models\Syarikat;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->s = syarikatBaru(['nama' => 'BINAAN UJIAN']);
});

it('ahli boleh log masuk dan nampak Baki Terkini', function () {
    ahli($this->s, Peranan::Pemilik, ['email' => 'pemilik@ujian.test', 'password' => 'rahsia-123']);

    $this->get(urlSyarikat($this->s, '/masuk'))->assertOk()->assertSee('BINAAN UJIAN');
    $this->post(urlSyarikat($this->s, '/masuk'), ['email' => 'pemilik@ujian.test', 'password' => 'rahsia-123'])
        ->assertRedirect(urlSyarikat($this->s, ''));
    $this->get(urlSyarikat($this->s))->assertOk()->assertSee('Baki terkini')->assertSee('Total Payment');
});

it('bukan ahli ditolak dengan mesej yang sama seperti kata laluan salah', function () {
    $lain = syarikatBaru();
    ahli($lain, Peranan::Pemilik, ['email' => 'luar@ujian.test', 'password' => 'rahsia-123']);

    $this->post(urlSyarikat($this->s, '/masuk'), ['email' => 'luar@ujian.test', 'password' => 'rahsia-123'])
        ->assertSessionHasErrors(['email' => 'E-mel atau kata laluan salah.']);
    $this->assertGuest();
});

it('tetamu dihantar ke skrin log masuk', function () {
    $this->get(urlSyarikat($this->s))->assertRedirect(urlSyarikat($this->s, '/masuk'));
});

it('log aktiviti tidak boleh diubah atau dipadam melalui model', function () {
    semasa()->tetapkan($this->s);
    $log = LogAktiviti::catat('Ujian', 'asal');

    expect(fn () => $log->update(['butiran' => 'diubah']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);
});

it('log aktiviti tidak boleh diubah walaupun dengan SQL terus', function () {
    semasa()->tetapkan($this->s);
    $log = LogAktiviti::catat('Ujian', 'asal');

    // RLS tiada polisi update: baris tak kelihatan untuk diubah, jadi 0 baris terjejas.
    expect(DB::table('log_aktiviti')->where('id', $log->id)->update(['butiran' => 'x']))->toBe(0);
    // Mod pintas pun tiada polisi update. Trigger kunci_log ialah lapisan ketiga untuk
    // sambungan yang memintas RLS sepenuhnya (superuser / pemilik tanpa FORCE).
    expect(semasa()->tanpaSkop(fn () => DB::table('log_aktiviti')->where('id', $log->id)->update(['butiran' => 'x'])))->toBe(0);
    expect(DB::table('log_aktiviti')->where('id', $log->id)->value('butiran'))->toBe('asal');
})->skip(fn () => ! pgsql(), 'Trigger hanya dalam PostgreSQL');

it('arahan dyno:syarikat-baru mencipta syarikat dan jemputan pemilik yang berfungsi', function () {
    Mail::fake();

    $this->artisan('dyno:syarikat-baru', ['nama' => 'BINAAN MAJU', 'subdomain' => 'binaanmaju', 'emel' => 'Bos@Maju.test', '--awalan' => 'BM-'])
        ->expectsOutputToContain('dicipta')->assertSuccessful();

    $s = semasa()->tanpaSkop(fn () => Syarikat::where('subdomain', 'binaanmaju')->sole());
    expect($s)->kod_awalan->toBe('BM-')->status->toBe('aktif');

    $pautan = null;
    Mail::assertSent(JemputanMail::class, function ($m) use (&$pautan) {
        $pautan = $m->pautan;

        return $m->hasTo('bos@maju.test');
    });
    $this->post($pautan, ['name' => 'Bos Maju', 'password' => 'rahsia-bos', 'password_confirmation' => 'rahsia-bos'])->assertRedirect();
    expect(User::where('email', 'bos@maju.test')->sole()->keahlianDi($s)->peranan)->toBe(Peranan::Pemilik);

    semasa()->tetapkan($s);
    expect(PeringkatAliran::count())->toBeGreaterThan(0);
});

it('arahan dyno:syarikat-baru --setahun menetapkan tarikh tamat langganan', function () {
    $this->artisan('dyno:syarikat-baru', ['nama' => 'FAIZ', 'subdomain' => 'faiz', 'emel' => 'f@f.test', '--tanpa-emel' => true, '--setahun' => true])
        ->expectsOutputToContain('aktif hingga')->assertSuccessful();
    $s = semasa()->tanpaSkop(fn () => Syarikat::where('subdomain', 'faiz')->sole());
    expect($s->aktif_hingga->toDateString())->toBe(now()->addYear()->toDateString());
});

it('arahan dyno:syarikat-baru menolak subdomain pendua atau tidak sah', function () {
    $this->artisan('dyno:syarikat-baru', ['nama' => 'X', 'subdomain' => $this->s->subdomain, 'emel' => 'a@b.test', '--tanpa-emel' => true])->assertFailed();
    $this->artisan('dyno:syarikat-baru', ['nama' => 'X', 'subdomain' => 'Bukan Sah!', 'emel' => 'a@b.test', '--tanpa-emel' => true])->assertFailed();
    $this->artisan('dyno:syarikat-baru', ['nama' => 'X', 'subdomain' => 'www', 'emel' => 'a@b.test', '--tanpa-emel' => true])->assertFailed();
});

it('arahan dyno:jemput-pemilik terbit semula pautan, kemudian beritahu bila pemilik sudah ada', function () {
    $this->artisan('dyno:syarikat-baru', ['nama' => 'FAIZ', 'subdomain' => 'faiz', 'emel' => 'f@f.test', '--tanpa-emel' => true])->assertSuccessful();

    $this->artisan('dyno:jemput-pemilik', ['subdomain' => 'faiz', '--setahun' => true])
        ->expectsOutputToContain('aktif hingga')->expectsOutputToContain('f@f.test')->assertSuccessful();
    $s = semasa()->tanpaSkop(fn () => Syarikat::where('subdomain', 'faiz')->sole());
    expect($s->aktif_hingga)->not->toBeNull();

    Artisan::call('dyno:jemput-pemilik', ['subdomain' => 'faiz']);
    preg_match('#http\S+/jemputan/\S+#', Artisan::output(), $m);
    $this->post($m[0], ['name' => 'Faiz', 'password' => 'rahsia-faiz', 'password_confirmation' => 'rahsia-faiz'])->assertRedirect();

    $this->artisan('dyno:jemput-pemilik', ['subdomain' => 'faiz'])->expectsOutputToContain('Pemilik sudah mendaftar')->assertSuccessful();
    $this->artisan('dyno:jemput-pemilik', ['subdomain' => 'tiada'])->assertFailed();
});
