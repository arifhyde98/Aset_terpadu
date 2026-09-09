<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('aset_tanah', 'opd_id')) {
            Schema::table('aset_tanah', function (Blueprint $table) {
                $table->foreignId('opd_id')->nullable()->after('opd');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE aset_tanah a
                LEFT JOIN opd o ON TRIM(LOWER(a.opd)) = TRIM(LOWER(o.nama))
                SET a.opd_id = o.id
            ');
        } else {
            $opds = DB::table('opd')->get();
            foreach ($opds as $opd) {
                DB::table('aset_tanah')->whereRaw('TRIM(LOWER(opd)) = ?', [trim(strtolower($opd->nama))])->update(['opd_id' => $opd->id]);
            }
        }

        try {
            Schema::table('aset_tanah', function (Blueprint $table) {
                $table->foreign('opd_id')
                    ->references('id')
                    ->on('opd')
                    ->nullOnDelete();
            });
        } catch (\Throwable $e) {
            // Foreign key might already exist
        }
    }

    public function down(): void
    {
        Schema::table('aset_tanah', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
            $table->dropColumn('opd_id');
        });
    }
};
