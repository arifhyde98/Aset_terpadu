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
        if (!Schema::hasTable('kendaraan_pinjam_pakai')) {
            Schema::create('kendaraan_pinjam_pakai', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->foreignId('opd_id')->nullable()->constrained('opds')->nullOnDelete();
                $table->index('vehicle_id');
                $table->index('opd_id');

                $table->string('kategori_peminjam', 50)->default('Instansi Vertikal')->index(); // Instansi Vertikal, Antar OPD, Lainnya
                $table->string('nama_instansi_peminjam', 255)->index(); // Kejaksaan Negeri, Polres, KPU, Bawaslu, Kodim, dll
                $table->string('nama_penanggung_jawab', 255);
                $table->string('nip_penanggung_jawab', 50)->nullable();
                $table->string('jabatan_penanggung_jawab', 150)->nullable();
                $table->string('kontak_penanggung_jawab', 50)->nullable();

                $table->string('nomor_nppp_bast', 100)->index(); // No Naskah Perjanjian Pinjam Pakai / BAST
                $table->date('tanggal_nppp_bast')->nullable();
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai');
                $table->string('status_perjanjian', 50)->default('Aktif')->index(); // Aktif, Akan Jatuh Tempo, Selesai, Diperpanjang

                $table->string('file_dokumen_pdf', 255)->nullable();
                $table->text('keterangan')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kendaraan_pinjam_pakai');
    }
};
