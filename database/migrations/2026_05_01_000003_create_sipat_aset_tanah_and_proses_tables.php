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
        // 1. status_proses
        if (!Schema::hasTable('status_proses')) {
            Schema::create('status_proses', function (Blueprint $table) {
                $table->increments('id_status');
                $table->string('nama_status', 100);
                $table->integer('urutan');
                $table->string('warna', 30)->nullable();
                $table->string('kategori', 255)->nullable();
            });
        }

        // 2. aset_tanah
        if (!Schema::hasTable('aset_tanah')) {
            Schema::create('aset_tanah', function (Blueprint $table) {
                $table->increments('id_aset');
                $table->string('kode_aset', 50)->unique('aset_tanah_kode_aset_unique');
                $table->enum('status_pencatatan', ['TERCATAT_KIB_A', 'USULAN_BELUM_TERCATAT'])->default('TERCATAT_KIB_A')->index('aset_tanah_status_pencatatan_idx');
                $table->string('nama_aset', 150);
                $table->string('peruntukan', 150)->nullable();
                $table->decimal('luas', 15, 2)->nullable();
                $table->text('alamat')->nullable();
                $table->decimal('lat', 18, 12)->nullable();
                $table->decimal('lng', 18, 12)->nullable();
                $table->longText('geojson')->nullable();
                $table->string('opd', 150)->nullable();
                $table->unsignedBigInteger('opd_id')->nullable();
                $table->unsignedBigInteger('kecamatan_id')->nullable()->index('idx_aset_tanah_kecamatan_id');
                $table->unsignedBigInteger('desa_id')->nullable()->index('idx_aset_tanah_desa_id');
                $table->string('dasar_perolehan', 150)->nullable();
                $table->decimal('harga_perolehan', 18, 2)->nullable();
                $table->date('tanggal_perolehan')->nullable();
                $table->text('keterangan')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        // 3. pemohon
        if (!Schema::hasTable('pemohon')) {
            Schema::create('pemohon', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama', 150);
                $table->string('nik', 30)->nullable();
                $table->string('ttl', 150)->nullable();
                $table->string('umur', 50)->nullable();
                $table->string('jenis_kelamin', 20)->nullable();
                $table->string('warga_negara', 50)->nullable();
                $table->string('agama', 50)->nullable();
                $table->string('pekerjaan', 100)->nullable();
                $table->string('jabatan', 150)->nullable();
                $table->text('alamat')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        // 4. proses_aset
        if (!Schema::hasTable('proses_aset')) {
            Schema::create('proses_aset', function (Blueprint $table) {
                $table->increments('id_proses');
                $table->unsignedInteger('id_aset')->index('proses_aset_id_aset_index');
                $table->unsignedInteger('id_status')->index('proses_aset_id_status_index');
                $table->date('tanggal_proses')->nullable()->index('proses_aset_tanggal_proses_index');
                $table->date('tgl_mulai')->nullable();
                $table->date('tgl_selesai')->nullable();
                $table->text('keterangan')->nullable();
                $table->integer('durasi_hari')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->foreign('id_aset', 'proses_aset_id_aset_foreign')
                    ->references('id_aset')
                    ->on('aset_tanah')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreign('id_status', 'proses_aset_id_status_foreign')
                    ->references('id_status')
                    ->on('status_proses')
                    ->onDelete('cascade')
                    ->onUpdate('restrict');
            });
        }

        // 5. dokumen_aset
        if (!Schema::hasTable('dokumen_aset')) {
            Schema::create('dokumen_aset', function (Blueprint $table) {
                $table->increments('id_dokumen');
                $table->unsignedInteger('id_aset')->index('dokumen_aset_id_aset_index');
                $table->unsignedInteger('id_proses')->nullable()->index('dokumen_aset_id_proses_index');
                $table->string('jenis_dokumen', 120);
                $table->text('file_path')->nullable();
                $table->string('status_dokumen', 50)->nullable();
                $table->dateTime('uploaded_at')->nullable();

                $table->foreign('id_aset', 'dokumen_aset_id_aset_foreign')
                    ->references('id_aset')
                    ->on('aset_tanah')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreign('id_proses', 'dokumen_aset_id_proses_foreign')
                    ->references('id_proses')
                    ->on('proses_aset')
                    ->onDelete('cascade')
                    ->onUpdate('set null');
            });
        }

        // 6. surat_skpt
        if (!Schema::hasTable('surat_skpt')) {
            Schema::create('surat_skpt', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nomor_surat', 100)->nullable();
                $table->string('alamat_kantor', 255)->nullable();
                $table->unsignedInteger('desa_id')->nullable()->index('fk_skpt_desa');
                $table->unsignedInteger('kepala_desa_id')->nullable()->index('fk_skpt_kepala_desa');
                $table->unsignedInteger('camat_id')->nullable()->index('fk_skpt_camat');
                $table->unsignedInteger('pemohon_id')->nullable()->index('fk_skpt_pemohon');
                $table->text('lokasi_tanah')->nullable();
                $table->string('jenis_tanah', 150)->nullable();
                $table->string('status_tanah', 255)->nullable();
                $table->text('asal_tanah')->nullable();
                $table->text('pernyataan_tanah')->nullable();
                $table->decimal('luas_tanah', 15, 2)->nullable();
                $table->string('dasar_perolehan', 150)->nullable();
                $table->string('batas_utara', 150)->nullable();
                $table->string('batas_timur', 150)->nullable();
                $table->string('batas_selatan', 150)->nullable();
                $table->string('batas_barat', 150)->nullable();
                $table->text('keterangan')->nullable();
                $table->date('tanggal_surat')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->foreign('desa_id', 'fk_skpt_desa')
                    ->references('id')
                    ->on('desa')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('kepala_desa_id', 'fk_skpt_kepala_desa')
                    ->references('id')
                    ->on('kepala_desa')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('camat_id', 'fk_skpt_camat')
                    ->references('id')
                    ->on('camat')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('pemohon_id', 'fk_skpt_pemohon')
                    ->references('id')
                    ->on('pemohon')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_skpt');
        Schema::dropIfExists('dokumen_aset');
        Schema::dropIfExists('proses_aset');
        Schema::dropIfExists('pemohon');
        Schema::dropIfExists('aset_tanah');
        Schema::dropIfExists('status_proses');
    }
};

