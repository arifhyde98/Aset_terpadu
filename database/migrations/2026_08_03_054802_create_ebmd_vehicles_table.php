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
        Schema::create('ebmd_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('no_polisi')->index();
            $table->string('nomor_register')->nullable()->index();
            $table->string('merk')->nullable();
            $table->string('tipe')->nullable();
            $table->string('jenis')->nullable();
            $table->unsignedBigInteger('vehicle_type_id')->nullable();
            $table->integer('tahun_pembuatan')->nullable();
            $table->date('tgl_perolehan')->nullable();
            $table->decimal('nilai_perolehan', 15, 2)->nullable();
            $table->string('stnk_ada')->default('Tidak');
            $table->string('bpkb_ada')->default('Tidak');
            $table->string('no_rangka')->nullable()->index();
            $table->string('no_mesin')->nullable()->index();
            $table->string('warna')->nullable();
            $table->date('tgl_stnk')->nullable();
            $table->string('opd')->nullable();
            $table->unsignedBigInteger('opd_id')->nullable();
            $table->string('pemegang')->nullable();
            $table->string('status')->default('available');
            $table->string('kondisi')->default('B');
            $table->json('foto_kendaraan')->nullable();
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            
            $table->boolean('is_synced')->default(false); // Flag for e-BMD sync status

            $table->timestamps();

            // Foreign keys
            $table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->nullOnDelete();
            $table->foreign('opd_id')->references('id')->on('opds')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebmd_vehicles');
    }
};
