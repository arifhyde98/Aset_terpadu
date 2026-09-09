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
        // 1. audit_logs
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id')->nullable()->index('user_id');
                $table->string('action', 50);
                $table->string('entity', 50);
                $table->integer('entity_id')->nullable();
                $table->text('old_data')->nullable();
                $table->text('new_data')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->dateTime('created_at')->nullable();
            });
        }

        // 2. integration_audit_logs
        if (!Schema::hasTable('integration_audit_logs')) {
            Schema::create('integration_audit_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('event_id', 36)->index('idx_event_id');
                $table->string('correlation_id', 36)->nullable();
                $table->string('nibar', 100)->index('idx_nibar');
                $table->string('event_name', 60);
                $table->string('source_system', 20);
                $table->string('direction', 10);
                $table->text('changes')->nullable();
                $table->string('reason', 500)->nullable();
                $table->enum('sync_status', ['SUCCESS', 'FAILED', 'PENDING'])->default('PENDING')->index('idx_sync_status');
                $table->text('error_message')->nullable();
                $table->unsignedInteger('data_version')->default(1);
                $table->string('created_by', 100)->nullable();
                $table->dateTime('created_at')->nullable()->index('idx_created_at');
            });
        }

        // 3. report_titles
        if (!Schema::hasTable('report_titles')) {
            Schema::create('report_titles', function (Blueprint $table) {
                $table->increments('id');
                $table->string('judul', 255);
                $table->string('kategori_status', 50)->nullable();
                $table->boolean('aktif')->default(true);
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_titles');
        Schema::dropIfExists('integration_audit_logs');
        Schema::dropIfExists('audit_logs');
    }
};

