<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Perbelanjaan umum (overhead): sewa, gaji pejabat, utiliti — tiada projek.
        // Ditolak dalam Untung Rugi sebagai untung bersih; tidak mengubah Baki Terkini projek.
        Schema::create('belanja_umum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->string('kategori', 60);
            $table->decimal('amaun', 14, 2);
            $table->date('tarikh')->nullable();
            $table->string('penerima')->default('');
            $table->text('nota')->nullable();
            $table->foreignId('dicipta_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['syarikat_id', 'tarikh']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('
                alter table belanja_umum enable row level security;
                alter table belanja_umum force row level security;
                create policy asing_syarikat on belanja_umum using (dyno_boleh(syarikat_id)) with check (dyno_boleh(syarikat_id));
            ');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('belanja_umum');
    }
};
