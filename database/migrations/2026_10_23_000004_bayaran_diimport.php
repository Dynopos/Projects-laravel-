<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bezakan bayaran dari import Excel dengan bayaran yang staf masukkan sendiri,
        // supaya import semula boleh beri amaran sebelum memadam rekod manual.
        Schema::table('bayaran', function (Blueprint $table) {
            $table->boolean('diimport')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bayaran', fn (Blueprint $table) => $table->dropColumn('diimport'));
    }
};
