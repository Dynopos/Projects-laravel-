<?php

use App\Enums\Peranan;
use App\Livewire\UrusLangganan;
use App\Mail\AkaunSediaMail;
use App\Mail\PeringatanLanggananMail;
use App\Models\Keahlian;
use App\Models\PembayaranLangganan;
use App\Models\Syarikat;
use Illuminate\Http\Client\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

/*
 * CHIP dipalsukan dengan Http::fake. Kunci RSA dijana setiap ujian supaya
 * pengesahan X-Signature diuji dengan kriptografi sebenar.
 */
beforeEach(function () {
    $kunci = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $this->kunciPeribadi = $kunci;
    config([
        'services.chip.key' => 'kunci-ujian', 'services.chip.brand_id' => 'brand-ujian',
        'services.chip.public_key' => openssl_pkey_get_details($kunci)['key'],
    ]);
    $this->purchase = [];     // id => status palsu di CHIP
    Http::fake(['gate.chip-in.asia/api/v1/*' => function (Request $r) {
        if ($r->method() === 'POST') {
            $id = 'pur-'.(count($this->purchase) + 1);
            $this->purchase[$id] = ['id' => $id, 'status' => 'created', 'reference' => $r['reference'],
                'purchase' => ['total' => $r['purchase']['products'][0]['price']]];

            return Http::response(['id' => $id, 'checkout_url' => "https://gate.chip-in.asia/p/{$id}/"], 201);
        }
        preg_match('#purchases/([^/]+)/#', $r->url(), $m);
        $p = $this->purchase[$m[1] ?? ''] ?? null;

        return Http::response($p ?? [], $p ? 200 : 404);
    }]);
    Mail::fake();
});

function urlLaman(string $laluan): string
{
    return 'http://'.config('dyno.domain_utama').$laluan;
}

function borangDaftar(array $ubah = []): array
{
    return [...['nama' => 'Binaan Maju', 'sufiks' => ' SDN BHD', 'subdomain' => 'binaanmaju', 'pemilik' => 'Ahmad Maju',
        'emel' => 'Ahmad@Maju.test', 'telefon' => '012-345 6789', 'setuju' => '1'], ...$ubah];
}

/** CHIP memanggil callback: badan ditandatangani dengan kunci peribadi ujian. */
function callbackChip($test, string $id, ?string $tandatangan = null)
{
    $badan = json_encode(['id' => $id, 'event_type' => 'purchase.paid', 'status' => 'paid']);
    openssl_sign($badan, $sig, $test->kunciPeribadi, OPENSSL_ALGO_SHA256);

    return $test->call('POST', urlLaman('/chip/callback'), [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $tandatangan ?? base64_encode($sig),
    ], $badan);
}

function pendaftaran($test): PembayaranLangganan
{
    $test->post(urlLaman('/daftar'), borangDaftar())->assertRedirect('https://gate.chip-in.asia/p/pur-1/');

    return PembayaranLangganan::sole();
}

it('borang daftar dipapar; ditutup jika CHIP belum dikonfigurasi', function () {
    $this->get(urlLaman('/daftar'))->assertOk()->assertSee('Teruskan ke bayaran RM99')->assertSee('href="/privasi"', false);

    config(['services.chip.key' => null]);
    $this->get(urlLaman('/daftar'))->assertOk()->assertSee('akan dibuka')->assertDontSee('Teruskan ke bayaran');
    $this->post(urlLaman('/daftar'), borangDaftar())->assertStatus(503);
});

it('daftar mencipta purchase CHIP RM99 dan tiada syarikat sebelum bayaran', function () {
    $p = pendaftaran($this);

    expect($p)->jenis->toBe('daftar')->status->toBe('menunggu')->purchase_id->toBe('pur-1')->amaun_sen->toBe(9900)
        ->and($p->butiran)->toMatchArray(['nama' => 'BINAAN MAJU', 'subdomain' => 'binaanmaju', 'emel' => 'ahmad@maju.test'])
        ->and(semasa()->tanpaSkop(fn () => Syarikat::where('subdomain', 'binaanmaju')->exists()))->toBeFalse();

    Http::assertSent(fn (Request $r) => $r->url() === 'https://gate.chip-in.asia/api/v1/purchases/'
        && $r->hasHeader('Authorization', 'Bearer kunci-ujian') && $r['brand_id'] === 'brand-ujian'
        && $r['reference'] === 'DAFTAR-'.$p->id && $r['purchase']['products'][0]['price'] === 9900
        && $r['success_callback'] === 'https://'.config('dyno.domain_utama').'/chip/callback'
        && str_contains($r['success_redirect'], "/daftar/selesai/{$p->id}/{$p->tanda()}"));
});

it('menolak alamat terpelihara, diambil, atau tanpa persetujuan', function () {
    syarikatBaru(['subdomain' => 'adaorang']);

    $this->post(urlLaman('/daftar'), borangDaftar(['subdomain' => 'www']))->assertSessionHasErrors('subdomain');
    $this->post(urlLaman('/daftar'), borangDaftar(['subdomain' => 'adaorang']))->assertSessionHasErrors('subdomain');
    $this->post(urlLaman('/daftar'), borangDaftar(['subdomain' => 'Ada Ruang']))->assertSessionHasErrors('subdomain');
    $this->post(urlLaman('/daftar'), borangDaftar(['setuju' => null]))->assertSessionHasErrors('setuju');
    $this->post(urlLaman('/daftar'), borangDaftar(['laman' => 'http://spam']))->assertStatus(422);

    // Alamat sedang menunggu bayaran orang lain (24 jam) tidak boleh diambil.
    $this->withoutMiddleware(ThrottleRequests::class);   // had 6 percubaan seminit
    pendaftaran($this);
    $this->post(urlLaman('/daftar'), borangDaftar(['emel' => 'lain@orang.test']))->assertSessionHasErrors('subdomain');
    expect(PembayaranLangganan::count())->toBe(1);
});

it('callback dengan tandatangan salah ditolak', function () {
    $p = pendaftaran($this);
    $this->purchase['pur-1']['status'] = 'paid';

    callbackChip($this, 'pur-1', base64_encode('palsu'))->assertStatus(401);
    expect($p->fresh()->status)->toBe('menunggu');
});

it('callback sah mencipta syarikat aktif setahun, sekali sahaja', function () {
    $p = pendaftaran($this);
    $this->purchase['pur-1']['status'] = 'paid';

    callbackChip($this, 'pur-1')->assertOk();
    callbackChip($this, 'pur-1')->assertOk();   // CHIP boleh menghantar dua kali

    $s = semasa()->tanpaSkop(fn () => Syarikat::where('subdomain', 'binaanmaju')->sole());
    expect($s)->nama->toBe('BINAAN MAJU')->sufiks->toBe(' SDN BHD')->status->toBe('aktif')
        ->and($s->aktif_hingga->toDateString())->toBe(now()->addYear()->toDateString())
        ->and($p->fresh())->status->toBe('dibayar')->syarikat_id->toBe($s->id);
    Mail::assertSent(AkaunSediaMail::class, 1);
    Mail::assertSent(AkaunSediaMail::class, fn ($m) => $m->hasTo('ahmad@maju.test') && str_contains($m->pautan, "/daftar/selesai/{$p->id}/"));
});

it('callback diabaikan jika CHIP belum paid atau amaun tidak sepadan', function () {
    $p = pendaftaran($this);

    callbackChip($this, 'pur-1')->assertOk();               // badan kata paid, API kata created
    $this->purchase['pur-1'] = [...$this->purchase['pur-1'], 'status' => 'paid', 'purchase' => ['total' => 100]];
    callbackChip($this, 'pur-1')->assertOk();

    expect($p->fresh()->status)->toBe('menunggu')
        ->and(semasa()->tanpaSkop(fn () => Syarikat::where('subdomain', 'binaanmaju')->exists()))->toBeFalse();
});

it('halaman selesai menyemak CHIP, kemudian pemilik tetapkan kata laluan dan masuk', function () {
    $p = pendaftaran($this);
    $selesai = urlLaman("/daftar/selesai/{$p->id}/{$p->tanda()}");

    $this->get($selesai)->assertOk()->assertSee('Menunggu pengesahan')->assertSee('http-equiv="refresh"', false);

    $this->purchase['pur-1']['status'] = 'paid';     // dibayar; callback belum tiba
    $this->get($selesai)->assertOk()->assertSee('sudah sedia')->assertSee('Tetapkan kata laluan');

    $s = semasa()->tanpaSkop(fn () => Syarikat::where('subdomain', 'binaanmaju')->sole());
    $jemputan = $this->get($selesai.'/masuk')->assertRedirect()->headers->get('Location');
    expect($jemputan)->toStartWith('http://binaanmaju.'.config('dyno.domain_utama').'/jemputan/');

    $this->post($jemputan, ['name' => 'Ahmad Maju', 'password' => 'rahsia-ahmad', 'password_confirmation' => 'rahsia-ahmad'])
        ->assertRedirect();
    $this->assertAuthenticated();
    expect(semasa()->tanpaSkop(fn () => Keahlian::where('syarikat_id', $s->id)->sole()->peranan))->toBe(Peranan::Pemilik);

    // Pemilik sudah wujud: pautan yang sama hanya membawa ke log masuk.
    auth()->logout();
    $this->get($selesai.'/masuk')->assertRedirect($s->url('/masuk'));
});

it('pautan selesai dengan tanda salah = 404', function () {
    $p = pendaftaran($this);
    $this->get(urlLaman("/daftar/selesai/{$p->id}/salah"))->assertNotFound();
    $this->get(urlLaman("/daftar/selesai/{$p->id}/salah/masuk"))->assertNotFound();
});

it('alamat diambil semasa bayaran berlangsung → nombor ditambah', function () {
    $p = pendaftaran($this);
    syarikatBaru(['subdomain' => 'binaanmaju']);
    $this->purchase['pur-1']['status'] = 'paid';

    callbackChip($this, 'pur-1')->assertOk();
    expect($p->fresh()->syarikat->subdomain)->toBe('binaanmaju-2');
});

it('pemilik memperbaharui: setahun ditambah dari tarikh tamat', function () {
    $s = syarikatBaru(['aktif_hingga' => now()->addDays(10)->toDateString()]);
    semasa()->tetapkan($s);
    $pemilik = ahli($s, Peranan::Pemilik);

    Livewire::actingAs($pemilik)->test(UrusLangganan::class)->assertSee('10 hari lagi')
        ->call('bayar')->assertRedirect('https://gate.chip-in.asia/p/pur-1/');
    $p = PembayaranLangganan::sole();
    expect($p)->jenis->toBe('pembaharuan')->amaun_sen->toBe(3000)->syarikat_id->toBe($s->id);

    $this->purchase['pur-1']['status'] = 'paid';
    callbackChip($this, 'pur-1')->assertOk();

    expect($s->fresh()->aktif_hingga->toDateString())->toBe(now()->addDays(10)->addYear()->toDateString());
});

it('syarikat baca sahaja masih boleh bayar dan dibuka semula dari hari ini', function () {
    $s = syarikatBaru(['aktif_hingga' => now()->subDays(40)->toDateString(), 'status' => 'baca_sahaja', 'peringatan' => 0]);
    semasa()->tetapkan($s);
    $pemilik = ahli($s, Peranan::Pemilik);

    $this->actingAs($pemilik)->get(urlSyarikat($s, '/'))->assertSee('baca sahaja');
    semasa()->tetapkan($s);
    Livewire::actingAs($pemilik)->test(UrusLangganan::class)->call('bayar');
    $this->purchase['pur-1']['status'] = 'paid';
    callbackChip($this, 'pur-1')->assertOk();

    expect($s->fresh())->status->toBe('aktif')->peringatan->toBeNull()
        ->and($s->fresh()->aktif_hingga->toDateString())->toBe(now()->addYear()->toDateString());
});

it('hanya pemilik boleh buka halaman langganan', function (Peranan $peranan) {
    $s = syarikatBaru(['aktif_hingga' => now()->addMonth()->toDateString()]);
    semasa()->tetapkan($s);
    Livewire::actingAs(ahli($s, $peranan))->test(UrusLangganan::class)->assertForbidden();
})->with([Peranan::Pengurus, Peranan::Staf, Peranan::Akauntan]);

it('semak-langganan: peringatan sekali setiap tahap, kemudian baca sahaja selepas tempoh tangguh', function () {
    $s = syarikatBaru(['aktif_hingga' => now()->addDays(6)->toDateString()]);
    ahli($s, Peranan::Pemilik, ['email' => 'bos@ujian.test']);
    ahli($s, Peranan::Staf, ['email' => 'staf@ujian.test']);
    $manual = syarikatBaru(['aktif_hingga' => null]);

    $this->artisan('dyno:semak-langganan')->assertSuccessful();
    $this->artisan('dyno:semak-langganan')->assertSuccessful();
    Mail::assertSent(PeringatanLanggananMail::class, 1);
    Mail::assertSent(PeringatanLanggananMail::class, fn ($m) => $m->hasTo('bos@ujian.test') && $m->baki === 6);
    expect($s->fresh()->peringatan)->toBe(7);

    $this->travel(7)->days();     // sehari selepas tamat → peringatan "telah tamat"
    $this->artisan('dyno:semak-langganan');
    expect($s->fresh())->peringatan->toBe(0)->status->toBe('aktif');

    $this->travel(config('dyno.tempoh_tangguh'))->days();
    $this->artisan('dyno:semak-langganan');
    expect($s->fresh()->status)->toBe('baca_sahaja')
        ->and($manual->fresh()->status)->toBe('aktif');
});

it('jalur amaran dipapar kepada pemilik bila hampir tamat', function () {
    $s = syarikatBaru(['aktif_hingga' => now()->addDays(5)->toDateString()]);
    $pemilik = ahli($s, Peranan::Pemilik);
    $staf = ahli($s, Peranan::Staf);

    $this->actingAs($pemilik)->get(urlSyarikat($s, '/'))->assertSee('Langganan tamat dalam')->assertSee('Perbaharui');
    $this->actingAs($staf)->get(urlSyarikat($s, '/'))->assertDontSee('Langganan tamat dalam');
});

it('terma, privasi dan sitemap', function () {
    $this->get(urlLaman('/terma'))->assertOk()->assertSee('RM99.00')->assertSee('RM30.00 setahun');
    $this->get(urlLaman('/privasi'))->assertOk()->assertSee('Akta 709')->assertSee('72 jam');
    $this->get(urlLaman('/sitemap.xml'))->assertSee('/daftar</loc>', false)->assertSee('/privasi</loc>', false);
    $this->get(urlLaman('/'))->assertSee('href="/daftar"', false)->assertSee('"priceCurrency":"MYR"', false);
});
