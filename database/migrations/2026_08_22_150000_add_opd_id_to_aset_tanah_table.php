<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset_tanah', function (Blueprint $table) {
            $table->foreignId('opd_id')->nullable()->after('opd');
        });

        DB::statement('
            UPDATE aset_tanah a
            LEFT JOIN opd o ON TRIM(LOWER(a.opd)) = TRIM(LOWER(o.nama))
            SET a.opd_id = o.id
        ');

        Schema::table('aset_tanah', function (Blueprint $table) {
            $table->foreign('opd_id')
                ->references('id')
                ->on('opd')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aset_tanah', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
            $table->dropColumn('opd_id');
        });
    }
};
