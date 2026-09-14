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
        if (!Schema::hasTable('aset_bangunan')) {
            Schema::create('aset_bangunan', function (Blueprint $table) {
                $table->id();
                $table->string('kode_bangunan', 50)->unique();
                $table->string('kode_barang', 50)->nullable()->index();
                $table->string('nama_bangunan', 255)->index();
                $table->string('nomor_register', 50)->nullable()->index();

                // Multi-Tenancy Instansi (terhubung ke tabel opd SIPAT)
                $table->foreignId('opd_id')->nullable()->constrained('opd')->nullOnDelete();
                $table->index('opd_id');

                // Pengamanan Hukum: Relasi ke Tanah KIB A (SIPAT)
                $table->foreignId('aset_tanah_id')->nullable()->constrained('aset_tanah')->nullOnDelete();
                $table->index('aset_tanah_id');
                $table->string('status_tanah_dasar', 100)->nullable();
                $table->decimal('luas_tanah_dasar', 12, 2)->nullable();

                // Standarisasi Klasifikasi (Permendagri No. 7/2024)
                $table->string('jenis_bangunan', 100)->default('Gedung Kantor')->index();
                $table->string('tipe_rumah_dinas', 50)->nullable();
                $table->string('nama_penghuni', 255)->nullable();

                // Karakteristik Fisik & Konstruksi (Standar KIB C & Permendagri No. 19/2016)
                $table->string('kondisi', 50)->default('Baik')->index();
                $table->boolean('konstruksi_tingkat')->default(false);
                $table->integer('jumlah_lantai')->default(1);
                $table->boolean('konstruksi_beton')->default(true);
                $table->string('tipe_konstruksi', 50)->default('Permanen');
                $table->decimal('luas_lantai', 12, 2)->default(0);
                $table->decimal('luas_dasar', 12, 2)->default(0);

                // Lokasi & Spasial
                $table->text('alamat')->nullable();
                $table->unsignedInteger('kecamatan_id')->nullable()->index();
                $table->unsignedInteger('desa_id')->nullable()->index();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->json('geojson')->nullable();

                // Pengamanan Administrasi: Dokumen & Perizinan
                $table->string('nomor_dokumen_pbg', 100)->nullable();
                $table->date('tanggal_dokumen_pbg')->nullable();
                $table->string('status_psp', 50)->default('Belum PSP');
                $table->string('foto_utama', 255)->nullable();
                $table->string('dokumen_pdf', 255)->nullable();

                // Akuntansi & Keuangan
                $table->string('asal_usul', 100)->default('APBD Kab');
                $table->unsignedSmallInteger('tahun_pengadaan')->nullable()->index();
                $table->decimal('harga_perolehan', 18, 2)->default(0);
                $table->decimal('nilai_buku', 18, 2)->nullable();
                $table->string('status_penggunaan', 100)->default('Digunakan Sendiri');

                $table->text('keterangan')->nullable();

                // Jejak Pengguna
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();

                // Foreign keys untuk wilayah jika tabelnya ada
                $table->foreign('kecamatan_id')->references('id')->on('kecamatan')->nullOnDelete();
                $table->foreign('desa_id')->references('id')->on('desa')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aset_bangunan');
    }
};
