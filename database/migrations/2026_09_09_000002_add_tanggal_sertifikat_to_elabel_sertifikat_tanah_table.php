<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('elabel_sertifikat_tanah')) {
            Schema::table('elabel_sertifikat_tanah', function (Blueprint $table) {
                if (!Schema::hasColumn('elabel_sertifikat_tanah', 'tanggal_sertifikat')) {
                    $table->date('tanggal_sertifikat')->nullable()->after('no_sertipikat');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('elabel_sertifikat_tanah')) {
            Schema::table('elabel_sertifikat_tanah', function (Blueprint $table) {
                if (Schema::hasColumn('elabel_sertifikat_tanah', 'tanggal_sertifikat')) {
                    $table->dropColumn('tanggal_sertifikat');
                }
            });
        }
    }
};
