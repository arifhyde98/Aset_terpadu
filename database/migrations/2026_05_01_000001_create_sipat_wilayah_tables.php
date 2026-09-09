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
        // 1. kecamatan
        if (!Schema::hasTable('kecamatan')) {
            Schema::create('kecamatan', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama', 150);
                $table->timestamps();
            });
        }

        // 2. desa
        if (!Schema::hasTable('desa')) {
            Schema::create('desa', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('kecamatan_id')->nullable()->index('fk_desa_kecamatan');
                $table->string('nama', 150);
                $table->string('jenis', 20)->default('Kelurahan');
                $table->timestamps();

                $table->foreign('kecamatan_id', 'fk_desa_kecamatan')
                    ->references('id')
                    ->on('kecamatan')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
            });
        }

        // 3. camat
        if (!Schema::hasTable('camat')) {
            Schema::create('camat', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('kecamatan_id')->nullable()->index('fk_camat_kecamatan');
                $table->string('nama', 150);
                $table->string('nip', 50)->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();

                $table->foreign('kecamatan_id', 'fk_camat_kecamatan')
                    ->references('id')
                    ->on('kecamatan')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
            });
        }

        // 4. kepala_desa
        if (!Schema::hasTable('kepala_desa')) {
            Schema::create('kepala_desa', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('desa_id')->index('desa_id');
                $table->string('nama', 150);
                $table->string('nip', 50)->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();

                $table->foreign('desa_id', 'fk_kepala_desa_desa')
                    ->references('id')
                    ->on('desa')
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
        Schema::dropIfExists('kepala_desa');
        Schema::dropIfExists('camat');
        Schema::dropIfExists('desa');
        Schema::dropIfExists('kecamatan');
    }
};

