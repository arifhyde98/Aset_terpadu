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
        if (!Schema::hasTable('master_nama_aset')) {
            Schema::create('master_nama_aset', function (Blueprint $table) {
                $table->id();
                $table->string('kode_barang', 50)->nullable()->index();
                $table->string('nama', 200)->unique();
                $table->string('kelompok', 100)->nullable();
                $table->text('deskripsi')->nullable();
                $table->integer('urutan')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('aset_tanah')) {
            DB::statement("SET SESSION sql_mode = ''");
            DB::statement("UPDATE aset_tanah SET tanggal_perolehan = NULL WHERE tanggal_perolehan = '0000-00-00'");

            if (!Schema::hasColumn('aset_tanah', 'nama_aset_id')) {
                Schema::table('aset_tanah', function (Blueprint $table) {
                    $table->unsignedBigInteger('nama_aset_id')->nullable()->index()->after('nama_aset');
                });
            }

            $fkExists = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aset_tanah' AND CONSTRAINT_NAME = 'aset_tanah_nama_aset_id_foreign'"))->isNotEmpty();
            if (!$fkExists) {
                Schema::table('aset_tanah', function (Blueprint $table) {
                    $table->foreign('nama_aset_id')->references('id')->on('master_nama_aset')->nullOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('aset_tanah') && Schema::hasColumn('aset_tanah', 'nama_aset_id')) {
            $fkExists = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'aset_tanah' AND CONSTRAINT_NAME = 'aset_tanah_nama_aset_id_foreign'"))->isNotEmpty();
            if ($fkExists) {
                Schema::table('aset_tanah', function (Blueprint $table) {
                    $table->dropForeign('aset_tanah_nama_aset_id_foreign');
                });
            }

            Schema::table('aset_tanah', function (Blueprint $table) {
                $table->dropColumn('nama_aset_id');
            });
        }

        Schema::dropIfExists('master_nama_aset');
    }
};
