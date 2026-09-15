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
        if (!Schema::hasTable('sub_opds')) {
            Schema::create('sub_opds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('opd_id')->constrained('opds')->onDelete('cascade');
                $table->string('nama');
                $table->string('kode_sub')->nullable();
                $table->string('jenis')->default('uptd'); // puskesmas, uptd, bagian, sekolah, rsud, lainnya
                $table->text('alamat')->nullable();
                $table->string('nama_pimpinan')->nullable();
                $table->string('nip_pimpinan')->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();

                $table->index(['opd_id', 'aktif']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_opds');
    }
};
