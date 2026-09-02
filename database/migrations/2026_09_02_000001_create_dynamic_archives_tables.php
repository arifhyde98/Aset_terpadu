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
        // 1. archive_types
        Schema::create('archive_types', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama', 150);
            $table->text('deskripsi')->nullable();
            $table->string('icon', 100)->default('bi-folder2');
            $table->string('warna_badge', 50)->default('primary');
            $table->json('schema_fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. archive_boxes
        Schema::create('archive_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_type_id')->constrained('archive_types')->onDelete('cascade');
            $table->string('nomor_box', 100);
            $table->string('barcode_code', 100)->nullable();
            $table->string('lokasi_rak', 255)->nullable();
            $table->smallInteger('tahun')->nullable();
            $table->unsignedInteger('kapasitas_maksimal')->default(100);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['archive_type_id', 'nomor_box']);
        });

        // 3. archive_items
        Schema::create('archive_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_type_id')->constrained('archive_types')->onDelete('cascade');
            $table->foreignId('archive_box_id')->nullable()->constrained('archive_boxes')->onDelete('set null');
            $table->foreignId('opd_id')->nullable()->constrained('opds')->onDelete('set null');
            $table->string('nomor_dokumen', 150)->index();
            $table->string('nama_dokumen', 255);
            $table->smallInteger('tahun_dokumen')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->string('file_scan_pdf', 255)->nullable();
            $table->string('status', 50)->default('Tersedia')->index();
            $table->text('keterangan')->nullable();
            $table->foreignId('input_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['archive_type_id', 'archive_box_id']);
        });

        // 4. archive_attachments
        Schema::create('archive_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_item_id')->constrained('archive_items')->onDelete('cascade');
            $table->string('field_name', 100)->nullable();
            $table->string('file_title', 255);
            $table->string('file_path', 255);
            $table->string('file_type', 50)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });

        // 5. archive_loans
        Schema::create('archive_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_item_id')->constrained('archive_items')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('opd_id')->nullable()->constrained('opds')->onDelete('set null');
            $table->string('requester_name', 150)->nullable();
            $table->string('requester_phone', 50)->nullable();
            $table->string('requester_email', 150)->nullable();
            $table->string('requester_org', 150)->nullable();
            $table->enum('jenis_layanan', ['scan_digital', 'pinjam_fisik'])->default('scan_digital');
            $table->date('tanggal_pinjam')->nullable();
            $table->date('tanggal_kembali')->nullable();
            $table->enum('status_persetujuan', ['pending', 'approved', 'rejected', 'returned'])->default('pending')->index();
            $table->text('keperluan')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_loans');
        Schema::dropIfExists('archive_attachments');
        Schema::dropIfExists('archive_items');
        Schema::dropIfExists('archive_boxes');
        Schema::dropIfExists('archive_types');
    }
};
