<?php

namespace App\Console\Commands;

use App\Enums\Peranan;
use App\Mail\JemputanMail;
use App\Models\Jemputan;
use App\Models\Syarikat;
use App\Support\SyarikatSemasa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cipta syarikat pelanggan baharu + jemputan untuk pemiliknya.
 * Satu-satunya cara syarikat wujud di produksi (seeder demo hanya untuk tempatan).
 *
 *   php artisan dyno:syarikat-baru "FINE CABINETRY" finecabinetry pemilik@contoh.my --sufiks=".CO" --awalan=FC-
 */
class SyarikatBaru extends Command
{
    protected $signature = 'dyno:syarikat-baru
        {nama : Nama syarikat, cth "BINAAN MAJU"}
        {subdomain : cth binaanmaju → binaanmaju.<DYNO_DOMAIN>}
        {emel : E-mel pemilik — pautan jemputan dihantar ke sini}
        {--sufiks= : cth " SDN BHD" (warna emas)}
        {--awalan=PRJ- : Awalan kod projek}
        {--domain= : Domain sendiri pelanggan (pilihan), cth management.syarikat.my}
        {--tanpa-emel : Jangan hantar e-mel; papar pautan sahaja}';

    protected $description = 'Cipta syarikat pelanggan baharu dan jemput pemiliknya';

    public function handle(SyarikatSemasa $semasa): int
    {
        $data = [
            'nama' => trim($this->argument('nama')),
            'subdomain' => Str::lower(trim($this->argument('subdomain'))),
            'emel' => Str::lower(trim($this->argument('emel'))),
            'awalan' => $this->option('awalan'),
            'domain' => $this->option('domain') ? Str::lower($this->option('domain')) : null,
        ];

        $v = Validator::make($data, [
            'nama' => ['required', 'max:80'],
            'subdomain' => ['required', 'regex:/^[a-z0-9]([a-z0-9-]{0,40}[a-z0-9])?$/', 'not_in:www,admin,api,mail,app'],
            'emel' => ['required', 'email'],
            'awalan' => ['required', 'max:12', 'regex:/^[A-Z0-9]+-?$/'],
            'domain' => ['nullable', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/'],
        ]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        return $semasa->tanpaSkop(function () use ($data, $semasa) {
            if (Syarikat::where('subdomain', $data['subdomain'])->exists()) {
                $this->error("Subdomain \"{$data['subdomain']}\" sudah digunakan.");

                return self::FAILURE;
            }
            if ($data['domain'] && Syarikat::where('domain', $data['domain'])->exists()) {
                $this->error("Domain \"{$data['domain']}\" sudah digunakan.");

                return self::FAILURE;
            }

            $s = Syarikat::create([
                'nama' => $data['nama'], 'sufiks' => (string) $this->option('sufiks'), 'subdomain' => $data['subdomain'],
                'domain' => $data['domain'], 'kod_awalan' => $data['awalan'], 'status' => 'aktif',
            ]);
            $s->sediakanTetapanLalai();

            [$j, $token] = $semasa->sebagai($s, fn () => Jemputan::terbitkan($data['emel'], Peranan::Pemilik, null));
            $pautan = $s->url('/jemputan/'.$token);

            $this->info("Syarikat \"{$s->namaPenuh()}\" dicipta: ".$s->url('/'));

            if (! $this->option('tanpa-emel')) {
                try {
                    Mail::to($data['emel'])->send(new JemputanMail($j, $s, $pautan));
                    $this->info("Jemputan pemilik dihantar ke {$data['emel']}.");
                } catch (Throwable $e) {
                    $this->warn('E-mel gagal dihantar ('.$e->getMessage().'). Beri pautan di bawah kepada pemilik.');
                }
            }

            $this->line('');
            $this->line('Pautan jemputan pemilik (sah '.Jemputan::TEMPOH_HARI.' hari, sekali guna):');
            $this->line($pautan);

            return self::SUCCESS;
        });
    }
}
