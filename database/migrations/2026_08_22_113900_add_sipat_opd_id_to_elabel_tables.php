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
        // 1. elabel_bpkb
        if (Schema::hasTable('elabel_bpkb')) {
            Schema::table('elabel_bpkb', function (Blueprint $table) {
                $table->unsignedInteger('sipat_opd_id')->nullable()->after('pdf_path');
                $table->foreign('sipat_opd_id')->references('id')->on('opd')->onDelete('set null');
            });
        }

        // 2. elabel_bpkb_deletes
        if (Schema::hasTable('elabel_bpkb_deletes')) {
            Schema::table('elabel_bpkb_deletes', function (Blueprint $table) {
                $table->unsignedInteger('sipat_opd_id')->nullable()->after('pdf_path');
                $table->foreign('sipat_opd_id')->references('id')->on('opd')->onDelete('set null');
            });
        }

        // 3. elabel_sertifikat_tanah
        if (Schema::hasTable('elabel_sertifikat_tanah')) {
            Schema::table('elabel_sertifikat_tanah', function (Blueprint $table) {
                $table->unsignedInteger('sipat_opd_id')->nullable()->after('pdf_path');
                $table->foreign('sipat_opd_id')->references('id')->on('opd')->onDelete('set null');
            });
        }

        // 4. elabel_surat_penyerahan
        if (Schema::hasTable('elabel_surat_penyerahan')) {
            Schema::table('elabel_surat_penyerahan', function (Blueprint $table) {
                $table->unsignedInteger('sipat_opd_id')->nullable()->after('pdf_path');
                $table->foreign('sipat_opd_id')->references('id')->on('opd')->onDelete('set null');
            });
        }

        // 5. elabel_loans
        if (Schema::hasTable('elabel_loans')) {
            Schema::table('elabel_loans', function (Blueprint $table) {
                $table->unsignedInteger('sipat_opd_id')->nullable()->after('requester_org');
                $table->foreign('sipat_opd_id', 'elabel_loans_sipat_opd_id_foreign')->references('id')->on('opd')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 5. elabel_loans
        if (Schema::hasTable('elabel_loans')) {
            Schema::table('elabel_loans', function (Blueprint $table) {
                $table->dropForeign('elabel_loans_sipat_opd_id_foreign');
                $table->dropColumn('sipat_opd_id');
            });
        }

        // 4. elabel_surat_penyerahan
        if (Schema::hasTable('elabel_surat_penyerahan')) {
            Schema::table('elabel_surat_penyerahan', function (Blueprint $table) {
                $table->dropForeign(['sipat_opd_id']);
                $table->dropColumn('sipat_opd_id');
            });
        }

        // 3. elabel_sertifikat_tanah
        if (Schema::hasTable('elabel_sertifikat_tanah')) {
            Schema::table('elabel_sertifikat_tanah', function (Blueprint $table) {
                $table->dropForeign(['sipat_opd_id']);
                $table->dropColumn('sipat_opd_id');
            });
        }

        // 2. elabel_bpkb_deletes
        if (Schema::hasTable('elabel_bpkb_deletes')) {
            Schema::table('elabel_bpkb_deletes', function (Blueprint $table) {
                $table->dropForeign(['sipat_opd_id']);
                $table->dropColumn('sipat_opd_id');
            });
        }

        // 1. elabel_bpkb
        if (Schema::hasTable('elabel_bpkb')) {
            Schema::table('elabel_bpkb', function (Blueprint $table) {
                $table->dropForeign(['sipat_opd_id']);
                $table->dropColumn('sipat_opd_id');
            });
        }
    }
};
