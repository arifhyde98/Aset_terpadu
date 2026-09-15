<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Konsolidasi master data OPD ke tabel tunggal `opds`:
     * 1. Menambahkan kolom sub_opd_id pada aset_tanah dan aset_bangunan.
     * 2. Melakukan remapping seluruh data aset_tanah, aset_bangunan, dan elabel_* dari tabel opd ke opds.
     * 3. Menghubungkan 4 kelurahan di Banawa ke OPD Induk Kecamatan Banawa (opd_id: 31) dan sub_opds masing-masing.
     * 4. Mengoreksi mapping Kecamatan Sindue ke opds ID 2066.
     * 5. Memperbarui foreign keys ke tabel opds dan sub_opds.
     * 6. Memensiunkan tabel opd_mappings dan opd (diarsipkan aman sebagai opd_legacy_backup & opd_mappings_legacy_backup).
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Tambah kolom sub_opd_id pada aset_tanah dan aset_bangunan jika belum ada
        if (Schema::hasTable('aset_tanah') && !Schema::hasColumn('aset_tanah', 'sub_opd_id')) {
            Schema::table('aset_tanah', function (Blueprint $table) {
                $table->unsignedBigInteger('sub_opd_id')->nullable()->after('opd_id');
            });
        }

        if (Schema::hasTable('aset_bangunan') && !Schema::hasColumn('aset_bangunan', 'sub_opd_id')) {
            Schema::table('aset_bangunan', function (Blueprint $table) {
                $table->unsignedBigInteger('sub_opd_id')->nullable()->after('opd_id');
            });
        }

        // 2. Koreksi mapping Sindue pada opd_mappings jika ada
        if (Schema::hasTable('opd_mappings')) {
            DB::table('opd_mappings')
                ->where('sipat_opd_id', 38)
                ->update(['erandis_opd_id' => 2066]);

            $mappings = DB::table('opd_mappings')->pluck('erandis_opd_id', 'sipat_opd_id')->toArray();
        } else {
            $mappings = [];
        }

        // Pastikan Sindue selalu terarah ke 2066
        $mappings[38] = 2066;

        // Pemetaan khusus 4 Kelurahan di Kecamatan Banawa ke sub_opds
        $kelurahanSubOpdMap = [
            30 => 10, // Kantor Kelurahan Ganti -> sub_opd_id 10 (Induk Kecamatan Banawa opds.id: 31)
            31 => 13, // Kantor Kelurahan Tanjung Batu -> sub_opd_id 13
            32 => 11, // Kantor Kelurahan Gunung Bale -> sub_opd_id 11
            33 => 12, // Kantor Kelurahan Kabonga Besar -> sub_opd_id 12
        ];

        // 3. Remapping aset_tanah
        if (Schema::hasTable('aset_tanah')) {
            // Aset milik 4 Kelurahan dialihkan ke Kecamatan Banawa (31) + sub_opd_id
            foreach ($kelurahanSubOpdMap as $sipatOpdId => $subOpdId) {
                DB::table('aset_tanah')
                    ->where('opd_id', $sipatOpdId)
                    ->update([
                        'opd_id' => 31,
                        'sub_opd_id' => $subOpdId,
                    ]);
            }

            // Aset lainnya dialihkan ke erandis_opd_id
            foreach ($mappings as $sipatId => $erandisId) {
                if (array_key_exists($sipatId, $kelurahanSubOpdMap)) {
                    continue;
                }
                DB::table('aset_tanah')
                    ->where('opd_id', $sipatId)
                    ->update(['opd_id' => $erandisId]);
            }
        }

        // 4. Remapping aset_bangunan
        if (Schema::hasTable('aset_bangunan')) {
            foreach ($kelurahanSubOpdMap as $sipatOpdId => $subOpdId) {
                DB::table('aset_bangunan')
                    ->where('opd_id', $sipatOpdId)
                    ->update([
                        'opd_id' => 31,
                        'sub_opd_id' => $subOpdId,
                    ]);
            }

            foreach ($mappings as $sipatId => $erandisId) {
                if (array_key_exists($sipatId, $kelurahanSubOpdMap)) {
                    continue;
                }
                DB::table('aset_bangunan')
                    ->where('opd_id', $sipatId)
                    ->update(['opd_id' => $erandisId]);
            }
        }

        // 5. Remapping tabel-tabel elabel_*
        $elabelTables = [
            'elabel_sertifikat_tanah',
            'elabel_bpkb',
            'elabel_bpkb_deletes',
            'elabel_surat_penyerahan',
            'elabel_loans',
        ];

        foreach ($elabelTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'sipat_opd_id')) {
                foreach ($mappings as $sipatId => $erandisId) {
                    DB::table($table)
                        ->where('sipat_opd_id', $sipatId)
                        ->update(['sipat_opd_id' => $erandisId]);
                }
            }
        }

        // 6. Lepaskan Foreign Keys lama yang merujuk ke tabel opd
        $oldFks = [
            'elabel_bpkb' => 'elabel_bpkb_sipat_opd_id_foreign',
            'elabel_bpkb_deletes' => 'elabel_bpkb_deletes_sipat_opd_id_foreign',
            'elabel_loans' => 'elabel_loans_sipat_opd_id_foreign',
            'elabel_sertifikat_tanah' => 'elabel_sertifikat_tanah_sipat_opd_id_foreign',
            'elabel_surat_penyerahan' => 'elabel_surat_penyerahan_sipat_opd_id_foreign',
            'opd_mappings' => 'opd_mappings_sipat_opd_id_foreign',
        ];

        foreach ($oldFks as $tbl => $fkName) {
            if (Schema::hasTable($tbl)) {
                try {
                    Schema::table($tbl, function (Blueprint $table) use ($fkName) {
                        $table->dropForeign($fkName);
                    });
                } catch (\Throwable $e) {
                    // Abaikan jika foreign key sudah tidak ada
                }
            }
        }

        // 7. Ubah tipe kolom sipat_opd_id di tabel elabel_* menjadi unsignedBigInteger
        foreach ($elabelTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'sipat_opd_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('sipat_opd_id')->nullable()->change();
                });
            }
        }

        // 8. Pasang Foreign Keys baru yang merujuk ke opds dan sub_opds
        if (Schema::hasTable('aset_tanah')) {
            Schema::table('aset_tanah', function (Blueprint $table) {
                $table->foreign('opd_id')->references('id')->on('opds')->onDelete('restrict');
                $table->foreign('sub_opd_id')->references('id')->on('sub_opds')->onDelete('set null');
            });
        }

        if (Schema::hasTable('aset_bangunan')) {
            Schema::table('aset_bangunan', function (Blueprint $table) {
                $table->foreign('opd_id')->references('id')->on('opds')->onDelete('restrict');
                $table->foreign('sub_opd_id')->references('id')->on('sub_opds')->onDelete('set null');
            });
        }

        foreach ($elabelTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'sipat_opd_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreign('sipat_opd_id')->references('id')->on('opds')->onDelete('set null');
                });
            }
        }

        // 9. Pensiunkan tabel opd dan opd_mappings secara aman (arsip backup di database)
        if (Schema::hasTable('opd_mappings')) {
            Schema::dropIfExists('opd_mappings_legacy_backup');
            Schema::rename('opd_mappings', 'opd_mappings_legacy_backup');
        }

        if (Schema::hasTable('opd')) {
            Schema::dropIfExists('opd_legacy_backup');
            Schema::rename('opd', 'opd_legacy_backup');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop foreign keys baru
        if (Schema::hasTable('aset_tanah')) {
            Schema::table('aset_tanah', function (Blueprint $table) {
                $table->dropForeign(['opd_id']);
                $table->dropForeign(['sub_opd_id']);
                $table->dropColumn('sub_opd_id');
            });
        }

        if (Schema::hasTable('aset_bangunan')) {
            Schema::table('aset_bangunan', function (Blueprint $table) {
                $table->dropForeign(['opd_id']);
                $table->dropForeign(['sub_opd_id']);
                $table->dropColumn('sub_opd_id');
            });
        }

        $elabelTables = [
            'elabel_sertifikat_tanah',
            'elabel_bpkb',
            'elabel_bpkb_deletes',
            'elabel_surat_penyerahan',
            'elabel_loans',
        ];

        foreach ($elabelTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'sipat_opd_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropForeign(['sipat_opd_id']);
                });
            }
        }

        // Kembalikan tabel dari arsip jika ada
        if (Schema::hasTable('opd_legacy_backup') && !Schema::hasTable('opd')) {
            Schema::rename('opd_legacy_backup', 'opd');
        }

        if (Schema::hasTable('opd_mappings_legacy_backup') && !Schema::hasTable('opd_mappings')) {
            Schema::rename('opd_mappings_legacy_backup', 'opd_mappings');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
