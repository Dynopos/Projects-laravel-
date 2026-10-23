<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Menggantikan config.js — satu baris untuk setiap pelanggan.
        Schema::create('syarikat', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('sufiks')->default('');
            $table->string('jabatan')->default('Jabatan Projek');
            $table->string('logo_path')->nullable();
            $table->string('subdomain')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('kod_awalan', 12)->default('PRJ-');
            // percubaan | aktif | baca_sahaja | digantung
            $table->string('status', 20)->default('percubaan');
            $table->string('zon_waktu')->default('Asia/Kuala_Lumpur');
            $table->timestamps();
        });

        // Seorang pengguna boleh jadi ahli lebih dari satu syarikat.
        Schema::create('keahlian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // pemilik | pengurus | staf | akauntan
            $table->string('peranan', 20);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->unique(['syarikat_id', 'user_id']);
        });

        // Tiada daftar sendiri: pengurus jemput staf melalui e-mel.
        Schema::create('jemputan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->string('email');
            $table->string('peranan', 20);
            $table->string('token', 64)->unique();
            $table->foreignId('dijemput_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tamat_pada');
            $table->timestamp('diterima_pada')->nullable();
            $table->timestamps();
        });

        // Menggantikan TETAPAN.PERINGKAT. Kunci disimpan dalam projek.aliran — jangan
        // ditukar selepas data wujud; label boleh ditukar bila-bila masa.
        Schema::create('peringkat_aliran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->string('kunci', 40);
            $table->string('label');
            $table->unsignedSmallInteger('susunan')->default(0);
            $table->unique(['syarikat_id', 'kunci']);
        });

        // Menggantikan TETAPAN.KATEGORI_BELANJA.
        Schema::create('kategori_belanja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->string('nama');
            $table->unsignedSmallInteger('susunan')->default(0);
            $table->unique(['syarikat_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_belanja');
        Schema::dropIfExists('peringkat_aliran');
        Schema::dropIfExists('jemputan');
        Schema::dropIfExists('keahlian');
        Schema::dropIfExists('syarikat');
    }
};
