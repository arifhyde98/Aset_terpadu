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
        if (!Schema::hasColumn('proses_aset', 'tanggal_proses')) {
            Schema::table('proses_aset', function (Blueprint $table) {
                $table->date('tanggal_proses')->nullable()->after('id_status')->index();
            });
        }

        // Populasikan data tanggal_proses dari tgl_mulai / tgl_selesai / created_at jika belum terisi
        DB::statement("
            UPDATE proses_aset 
            SET tanggal_proses = COALESCE(tgl_mulai, tgl_selesai, DATE(created_at))
            WHERE tanggal_proses IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('proses_aset', 'tanggal_proses')) {
            Schema::table('proses_aset', function (Blueprint $table) {
                $table->dropColumn('tanggal_proses');
            });
        }
    }
};
