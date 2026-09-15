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
        // 1. Kolom sub_opd_id pada tabel vehicles
        if (Schema::hasTable('vehicles') && !Schema::hasColumn('vehicles', 'sub_opd_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->foreignId('sub_opd_id')->nullable()->after('opd_id')
                    ->constrained('sub_opds')->nullOnDelete();
            });
        }

        // 2. Kolom sub_opd_id pada tabel ebmd_vehicles
        if (Schema::hasTable('ebmd_vehicles') && !Schema::hasColumn('ebmd_vehicles', 'sub_opd_id')) {
            Schema::table('ebmd_vehicles', function (Blueprint $table) {
                $table->foreignId('sub_opd_id')->nullable()->after('opd_id')
                    ->constrained('sub_opds')->nullOnDelete();
            });
        }

        // 3. Kolom sub_opd_id pada tabel users
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'sub_opd_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('sub_opd_id')->nullable()->after('opd_id')
                    ->constrained('sub_opds')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'sub_opd_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropForeign(['sub_opd_id']);
                $table->dropColumn('sub_opd_id');
            });
        }

        if (Schema::hasTable('ebmd_vehicles') && Schema::hasColumn('ebmd_vehicles', 'sub_opd_id')) {
            Schema::table('ebmd_vehicles', function (Blueprint $table) {
                $table->dropForeign(['sub_opd_id']);
                $table->dropColumn('sub_opd_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'sub_opd_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['sub_opd_id']);
                $table->dropColumn('sub_opd_id');
            });
        }
    }
};
