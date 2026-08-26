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
        if (!Schema::hasColumn('aset_tanah', 'status_pencatatan')) {
            Schema::table('aset_tanah', function (Blueprint $table) {
                $table->enum('status_pencatatan', ['TERCATAT_KIB_A', 'USULAN_BELUM_TERCATAT'])
                      ->default('TERCATAT_KIB_A')
                      ->after('kode_aset')
                      ->index();
            });
        }

        // Set status_pencatatan = USULAN_BELUM_TERCATAT untuk 18 aset draft/belum tercatat yang ada saat ini
        DB::table('aset_tanah')
            ->where('kode_aset', 'LIKE', 'DRAFT-%')
            ->orWhere('kode_aset', 'LIKE', 'BELUM-%')
            ->orWhereNull('kode_aset')
            ->orWhere('kode_aset', '')
            ->orWhere('kode_aset', '-')
            ->update(['status_pencatatan' => 'USULAN_BELUM_TERCATAT']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('aset_tanah', 'status_pencatatan')) {
            Schema::table('aset_tanah', function (Blueprint $table) {
                $table->dropColumn('status_pencatatan');
            });
        }
    }
};
