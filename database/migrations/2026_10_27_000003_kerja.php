<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kerja kecil di bawah satu projek (cth "Pasang alumglass RM400", kemudian "RM250").
        // Bila projek ada kerja, projek.nilai = Σ kerja.nilai (diselenggara oleh Kerja).
        Schema::create('kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('syarikat_id')->constrained('syarikat')->cascadeOnDelete();
            $table->unsignedBigInteger('projek_id');
            $table->string('nama');
            $table->decimal('nilai', 14, 2)->default(0);
            $table->date('tarikh')->nullable();
            $table->boolean('siap')->default(false);
            $table->text('nota')->nullable();
            $table->foreignId('dicipta_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign(['syarikat_id', 'projek_id'])->references(['syarikat_id', 'id'])->on('projek')->cascadeOnDelete();
        });

        // Bayaran & perbelanjaan boleh diikat pada satu kerja (pilihan). Padam kerja → null.
        foreach (['bayaran', 'belanja'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->unsignedBigInteger('kerja_id')->nullable()->index();
            });
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('
                alter table bayaran add constraint bayaran_kerja_fk foreign key (kerja_id) references kerja(id) on delete set null;
                alter table belanja add constraint belanja_kerja_fk foreign key (kerja_id) references kerja(id) on delete set null;
                alter table kerja enable row level security;
                alter table kerja force row level security;
                create policy asing_syarikat on kerja using (dyno_boleh(syarikat_id)) with check (dyno_boleh(syarikat_id));
            ');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('alter table bayaran drop constraint if exists bayaran_kerja_fk; alter table belanja drop constraint if exists belanja_kerja_fk;');
        }
        foreach (['bayaran', 'belanja'] as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->dropColumn('kerja_id'));
        }
        Schema::dropIfExists('kerja');
    }
};
