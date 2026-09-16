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
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'admin_template')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('admin_template', 50)->default('classic')->after('email');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'admin_template')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('admin_template');
            });
        }
    }
};
