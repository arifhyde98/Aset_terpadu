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
        if (Schema::hasTable('elabel_activity_logs')) {
            Schema::table('elabel_activity_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('elabel_activity_logs', 'old_data')) {
                    $table->longText('old_data')->nullable()->after('description');
                }
                if (!Schema::hasColumn('elabel_activity_logs', 'new_data')) {
                    $table->longText('new_data')->nullable()->after('old_data');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('elabel_activity_logs')) {
            Schema::table('elabel_activity_logs', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('elabel_activity_logs', 'old_data')) {
                    $columns[] = 'old_data';
                }
                if (Schema::hasColumn('elabel_activity_logs', 'new_data')) {
                    $columns[] = 'new_data';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
