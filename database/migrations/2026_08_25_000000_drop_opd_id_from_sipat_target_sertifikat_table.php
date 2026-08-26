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
        Schema::table('sipat_target_sertifikat', function (Blueprint $table) {
            if (Schema::hasColumn('sipat_target_sertifikat', 'opd_id')) {
                $table->dropForeign(['opd_id']);
                $table->dropColumn('opd_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sipat_target_sertifikat', function (Blueprint $table) {
            $table->unsignedInteger('opd_id')->nullable()->after('aset_tanah_id');
            $table->foreign('opd_id')
                ->references('id')
                ->on('opd')
                ->onDelete('set null');
        });
    }
};

