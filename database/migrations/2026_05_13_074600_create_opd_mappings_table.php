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
        Schema::create('opd_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('sipat_opd_id');
            $table->unsignedBigInteger('erandis_opd_id');
            $table->enum('status_verifikasi', ['matched', 'pending'])->default('matched');
            $table->timestamps();

            $table->unique(['sipat_opd_id', 'erandis_opd_id'], 'opd_mappings_sipat_opd_id_erandis_opd_id_unique');

            $table->foreign('sipat_opd_id', 'opd_mappings_sipat_opd_id_foreign')
                ->references('id')
                ->on('opd')
                ->onDelete('cascade');

            $table->foreign('erandis_opd_id', 'opd_mappings_erandis_opd_id_foreign')
                ->references('id')
                ->on('opds')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opd_mappings');
    }
};

