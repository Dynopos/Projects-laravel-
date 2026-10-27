<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Siapa yang membayar — projek payung ada pemilik berbeza untuk setiap kerja.
        // Kosong dalam borang = klien projek (Projek::rekodBayaran).
        Schema::table('bayaran', function (Blueprint $table) {
            $table->string('pembayar', 120)->default('')->after('jenis');
        });
    }

    public function down(): void
    {
        Schema::table('bayaran', fn (Blueprint $t) => $t->dropColumn('pembayar'));
    }
};
