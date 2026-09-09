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
        // 1. master_pengamanan_items
        if (!Schema::hasTable('master_pengamanan_items')) {
            Schema::create('master_pengamanan_items', function (Blueprint $table) {
                $table->increments('id');
                $table->string('label', 255);
                $table->boolean('is_active')->default(true);
            });
        }

        // 2. pengamanan_fisik
        if (!Schema::hasTable('pengamanan_fisik')) {
            Schema::create('pengamanan_fisik', function (Blueprint $table) {
                $table->increments('id_pengamanan');
                $table->unsignedInteger('id_aset')->unique('id_aset');
                $table->boolean('sertifikat_ada')->default(false);
                $table->boolean('pagar')->default(false);
                $table->boolean('papan_nama')->default(false);
                $table->boolean('dikuasai_pihak_lain')->default(false);
                $table->text('catatan')->nullable();
                $table->date('tgl_cek')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();

                $table->foreign('id_aset', 'pengamanan_fisik_id_aset_foreign')
                    ->references('id_aset')
                    ->on('aset_tanah')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }

        // 3. pengamanan_fisik_values
        if (!Schema::hasTable('pengamanan_fisik_values')) {
            Schema::create('pengamanan_fisik_values', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('id_pengamanan');
                $table->unsignedInteger('id_item');
                $table->boolean('is_checked')->default(false);

                $table->foreign('id_pengamanan', 'pengamanan_fisik_values_id_pengamanan_foreign')
                    ->references('id_pengamanan')
                    ->on('pengamanan_fisik')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreign('id_item', 'pengamanan_fisik_values_id_item_foreign')
                    ->references('id')
                    ->on('master_pengamanan_items')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengamanan_fisik_values');
        Schema::dropIfExists('pengamanan_fisik');
        Schema::dropIfExists('master_pengamanan_items');
    }
};

