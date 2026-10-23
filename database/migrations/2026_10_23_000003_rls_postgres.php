<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Lapisan kedua pengasingan data: PostgreSQL Row Level Security.
 *
 * Lapisan pertama ialah global scope MilikSyarikat dalam Eloquent. Kalau satu
 * pertanyaan terlepas scope itu (DB::table, raw SQL, pakej pihak ketiga), pangkalan
 * data tetap hanya memulangkan baris syarikat dalam app.syarikat_id.
 *
 * Gagal tertutup: tanpa app.syarikat_id, tiada baris kelihatan. Panel pentadbir,
 * seeder dan arahan konsol mesti masuk melalui SyarikatSemasa::tanpaSkop(), yang
 * menetapkan app.pintas_rls = 'on' untuk tempoh panggilan itu sahaja.
 *
 * FORCE diperlukan kerana pengguna aplikasi juga pemilik jadual. Pengguna
 * pangkalan data aplikasi TIDAK boleh superuser — superuser memintas RLS.
 *
 * keahlian & jemputan sengaja tiada RLS: ia dibaca sebelum syarikat diketahui
 * (log masuk, terima jemputan) dan dilindungi oleh kod + ujian.
 */
return new class extends Migration
{
    private array $jadual = ['peringkat_aliran', 'kategori_belanja', 'projek', 'penugasan', 'bayaran', 'belanja'];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            create or replace function dyno_pintas() returns boolean language sql stable as $$
              select coalesce(current_setting('app.pintas_rls', true), '') = 'on'
            $$;
            create or replace function dyno_boleh(sid bigint) returns boolean language sql stable as $$
              select dyno_pintas() or sid = nullif(current_setting('app.syarikat_id', true), '')::bigint
            $$;
        SQL);

        foreach ($this->jadual as $t) {
            DB::unprepared("
                alter table {$t} enable row level security;
                alter table {$t} force row level security;
                create policy asing_syarikat on {$t} using (dyno_boleh(syarikat_id)) with check (dyno_boleh(syarikat_id));
            ");
        }

        // Log aktiviti: baca & tambah sahaja untuk syarikat. Tiada polisi update, jadi
        // update ditolak RLS; trigger menolaknya juga walaupun dalam mod pintas.
        // Padam hanya dalam mod pintas (penamatan langganan / permintaan PDPA).
        DB::unprepared(<<<'SQL'
            alter table log_aktiviti enable row level security;
            alter table log_aktiviti force row level security;
            create policy log_baca on log_aktiviti for select using (dyno_boleh(syarikat_id));
            create policy log_tambah on log_aktiviti for insert with check (dyno_boleh(syarikat_id));
            create policy log_padam_pentadbir on log_aktiviti for delete using (dyno_pintas());

            create or replace function kunci_log() returns trigger language plpgsql as $$
            begin
              if tg_op = 'UPDATE' then
                raise exception 'log_aktiviti tidak boleh diubah';
              end if;
              if tg_op = 'DELETE' and not dyno_pintas() then
                raise exception 'log_aktiviti tidak boleh dipadam';
              end if;
              return coalesce(old, new);
            end $$;
            create trigger kunci_log before update or delete on log_aktiviti
              for each row execute function kunci_log();
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared('drop trigger if exists kunci_log on log_aktiviti; drop function if exists kunci_log();');
        foreach ([...$this->jadual, 'log_aktiviti'] as $t) {
            DB::unprepared("alter table {$t} no force row level security; alter table {$t} disable row level security;");
        }
        DB::unprepared('drop policy if exists log_baca on log_aktiviti; drop policy if exists log_tambah on log_aktiviti; drop policy if exists log_padam_pentadbir on log_aktiviti;');
        foreach ($this->jadual as $t) {
            DB::unprepared("drop policy if exists asing_syarikat on {$t};");
        }
        DB::unprepared('drop function if exists dyno_boleh(bigint); drop function if exists dyno_pintas();');
    }
};
