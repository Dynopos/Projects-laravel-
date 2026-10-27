<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('syarikat', function (Blueprint $table) {
            // null = tiada tarikh tamat (syarikat dicipta manual melalui dyno:syarikat-baru).
            $table->date('aktif_hingga')->nullable()->after('status');
            // Peringatan terakhir yang dihantar (hari sebelum tamat: 30, 7, 1, 0) — elak e-mel berulang.
            $table->smallInteger('peringatan')->nullable()->after('aktif_hingga');
        });

        // Bayaran kepada Dyno melalui CHIP (daftar RM99 / pembaharuan RM30).
        // Jadual platform: tiada RLS, dibaca melalui SyarikatSemasa::tanpaSkop().
        Schema::create('pembayaran_langganan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->nullable()->constrained('syarikat')->nullOnDelete();
            $table->string('jenis', 20);                 // daftar | pembaharuan
            $table->string('purchase_id')->nullable()->unique();
            $table->unsignedInteger('amaun_sen');
            $table->string('status', 20)->default('menunggu');   // menunggu | dibayar | gagal
            $table->json('butiran')->nullable();         // borang daftar (nama, subdomain, pemilik, persetujuan)
            $table->timestamp('dibayar_pada')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_langganan');
        Schema::table('syarikat', fn (Blueprint $t) => $t->dropColumn(['aktif_hingga', 'peringatan']));
    }
};
