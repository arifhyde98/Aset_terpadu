<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedTinyInteger('module_id')->default(1)->after('user_id');
            $table->string('module_key', 32)->default('erandis')->after('module_id');
            $table->index(['module_id', 'created_at']);
            $table->index(['module_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['module_id', 'created_at']);
            $table->dropIndex(['module_key', 'created_at']);
            $table->dropColumn(['module_id', 'module_key']);
        });
    }
};
