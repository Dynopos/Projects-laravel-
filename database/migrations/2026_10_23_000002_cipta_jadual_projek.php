<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projek', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->string('kod', 30);
            $table->string('nama');
            $table->string('klien')->default('');
            $table->string('lokasi')->default('');
            $table->string('telefon', 40)->default('');
            $table->string('inv', 60)->default('');
            $table->string('status', 40)->default('Baru');
            $table->decimal('nilai', 14, 2)->default(0);
            $table->date('mula')->nullable();
            $table->text('nota')->nullable();
            $table->json('aliran')->nullable();
            $table->foreignId('dicipta_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['syarikat_id', 'kod']);
            // Sasaran FK komposit di bawah: memastikan bayaran/belanja tak boleh
            // menunjuk ke projek syarikat lain walaupun syarikat_id tersalah isi.
            $table->unique(['syarikat_id', 'id']);
        });

        // Projek mana yang staf boleh nampak. Pemilik/pengurus/akauntan nampak semua.
        Schema::create('penugasan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->unsignedBigInteger('projek_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['projek_id', 'user_id']);
            $table->foreign(['syarikat_id', 'projek_id'])->references(['syarikat_id', 'id'])->on('projek')->cascadeOnDelete();
        });

        // Diikat pada projek_id (bukan teks kod seperti versi Supabase), jadi menukar
        // kod projek tidak lagi boleh meninggalkan bayaran yatim.
        Schema::create('bayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->unsignedBigInteger('projek_id');
            $table->string('jenis', 60);
            $table->decimal('amaun', 14, 2);
            $table->date('tarikh')->nullable();
            $table->string('kaedah', 60)->default('');
            $table->string('ruj', 80)->default('');
            // Diterima | Menunggu — hanya Diterima dikira dalam baki.
            $table->string('status', 20)->default('Menunggu');
            $table->foreignId('dicipta_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disahkan_pada')->nullable();
            $table->timestamps();

            $table->foreign(['syarikat_id', 'projek_id'])->references(['syarikat_id', 'id'])->on('projek')->cascadeOnDelete();
            $table->index(['syarikat_id', 'status']);
        });

        Schema::create('belanja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->unsignedBigInteger('projek_id');
            $table->string('kategori', 60);
            $table->decimal('amaun', 14, 2);
            $table->date('tarikh')->nullable();
            $table->string('penerima')->default('');
            $table->text('nota')->nullable();
            $table->string('resit_path')->nullable();
            $table->foreignId('dicipta_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign(['syarikat_id', 'projek_id'])->references(['syarikat_id', 'id'])->on('projek')->cascadeOnDelete();
        });

        // Insert & select sahaja. Model menolak update/delete; dalam PostgreSQL
        // trigger + RLS menolaknya juga (lihat migrasi rls).
        Schema::create('log_aktiviti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tindakan', 60);
            $table->text('butiran')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['syarikat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktiviti');
        Schema::dropIfExists('belanja');
        Schema::dropIfExists('bayaran');
        Schema::dropIfExists('penugasan');
        Schema::dropIfExists('projek');
    }
};
