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
        if (!Schema::hasTable('kategori_proses')) {
            Schema::create('kategori_proses', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 50)->unique();
                $table->string('nama', 100);
                $table->text('deskripsi')->nullable();
                $table->boolean('exclude_target')->default(false)->comment('Apakah mengecualikan aset dalam target pensertifikatan');
                $table->boolean('includes_unprocessed')->default(false)->comment('Apakah menyertakan tanah yang belum ada riwayat proses');
                $table->string('warna', 30)->default('primary');
                $table->integer('urutan')->default(0);
                $table->boolean('is_system')->default(false)->comment('Kategori sistem bawaan yang tidak boleh dihapus');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kategori_status_pivot')) {
            Schema::create('kategori_status_pivot', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kategori_id');
                $table->unsignedInteger('status_id');
                $table->timestamps();

                $table->foreign('kategori_id')->references('id')->on('kategori_proses')->cascadeOnDelete();
                $table->foreign('status_id')->references('id_status')->on('status_proses')->cascadeOnDelete();
                $table->unique(['kategori_id', 'status_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_status_pivot');
        Schema::dropIfExists('kategori_proses');
    }
};
