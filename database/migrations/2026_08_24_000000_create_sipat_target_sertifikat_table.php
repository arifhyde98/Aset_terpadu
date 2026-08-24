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
        Schema::create('sipat_target_sertifikat', function (Blueprint $table) {
            $table->id();
            $table->integer('tahun')->index();
            $table->unsignedInteger('aset_tanah_id');
            $table->unsignedInteger('opd_id')->nullable();
            $table->integer('target_jumlah')->default(1);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('aset_tanah_id')
                ->references('id_aset')
                ->on('aset_tanah')
                ->onDelete('cascade');

            $table->foreign('opd_id')
                ->references('id')
                ->on('opd')
                ->onDelete('set null');

            $table->unique(['tahun', 'aset_tanah_id'], 'unique_tahun_aset_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sipat_target_sertifikat');
    }
};
