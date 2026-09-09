<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add opd_id column (nullable, no FK constraint yet)
        if (!Schema::hasColumn('vehicles', 'opd_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->unsignedBigInteger('opd_id')->nullable()->after('opd');
            });
        }

        // 2. Auto-fill opd_id by matching existing opd string to opds.nama
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                UPDATE vehicles v
                JOIN opds o ON TRIM(LOWER(v.opd)) = TRIM(LOWER(o.nama))
                SET v.opd_id = o.id
            ');
        } else {
            $opds = DB::table('opds')->get();
            foreach ($opds as $opd) {
                DB::table('vehicles')->whereRaw('TRIM(LOWER(opd)) = ?', [trim(strtolower($opd->nama))])->update(['opd_id' => $opd->id]);
            }
        }

        // 3. Now add the foreign key constraint
        try {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->foreign('opd_id')
                      ->references('id')
                      ->on('opds')
                      ->onDelete('set null');
            });
        } catch (\Throwable $e) {
            // FK might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['opd_id']);
            $table->dropColumn('opd_id');
        });
    }
};
