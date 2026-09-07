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
        // 1. elabel_boxes
        if (!Schema::hasTable('elabel_boxes')) {
            Schema::create('elabel_boxes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('created_by')->index();
                $table->string('box_code', 30);
                $table->string('location', 100)->nullable();
                $table->string('vehicle_type', 10)->default('mobil');
                $table->timestamps();

                $table->foreign('created_by', 'elabel_boxes_created_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
            });
        }

        // 2. elabel_box_years
        if (!Schema::hasTable('elabel_box_years')) {
            Schema::create('elabel_box_years', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('box_id')->index();
                $table->integer('year')->index();
                
                $table->unique(['box_id', 'year'], 'elabel_box_year_unique');

                $table->foreign('box_id', 'elabel_box_years_box_id_foreign')
                    ->references('id')
                    ->on('elabel_boxes')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
            });
        }

        // 3. elabel_bpkb
        if (!Schema::hasTable('elabel_bpkb')) {
            Schema::create('elabel_bpkb', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('box_id')->index();
                $table->integer('year')->nullable();
                $table->string('vehicle_type', 10)->default('mobil');
                $table->string('plate_number', 20);
                $table->string('no_bpkb', 50)->nullable();
                $table->string('nibar', 100)->nullable();
                $table->string('no_rangka', 50)->nullable();
                $table->string('no_mesin', 50)->nullable();
                $table->string('merek', 100)->nullable();
                $table->string('tipe', 100)->nullable();
                $table->string('isi_silinder', 50)->nullable();
                $table->string('warna', 100)->nullable();
                $table->string('pengguna', 100)->nullable();
                $table->string('status', 20)->default('Tersedia');
                $table->string('pdf_path', 255)->nullable();
                $table->unsignedInteger('sipat_opd_id')->nullable()->index();
                $table->unsignedBigInteger('input_by')->nullable()->index();
                $table->timestamps();
                $table->string('plate_number_key', 20)->nullable();

                $table->unique(['plate_number_key', 'year'], 'elabel_bpkb_plate_year_unique');

                $table->foreign('box_id', 'elabel_bpkb_box_id_foreign')
                    ->references('id')
                    ->on('elabel_boxes')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('input_by', 'elabel_bpkb_input_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('sipat_opd_id', 'elabel_bpkb_sipat_opd_id_foreign')
                    ->references('id')
                    ->on('opd')
                    ->onDelete('set null');
            });
        }

        // 4. elabel_bpkb_backup
        if (!Schema::hasTable('elabel_bpkb_backup')) {
            Schema::create('elabel_bpkb_backup', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('box_id')->index();
                $table->integer('year')->nullable();
                $table->string('vehicle_type', 10)->default('mobil');
                $table->string('plate_number', 20);
                $table->string('no_bpkb', 50)->nullable();
                $table->string('nibar', 100)->nullable();
                $table->string('no_rangka', 50)->nullable();
                $table->string('no_mesin', 50)->nullable();
                $table->string('merek', 100)->nullable();
                $table->string('tipe', 100)->nullable();
                $table->string('isi_silinder', 50)->nullable();
                $table->string('warna', 100)->nullable();
                $table->string('pengguna', 100)->nullable();
                $table->string('status', 20)->default('Tersedia');
                $table->string('pdf_path', 255)->nullable();
                $table->string('telegram_file_id', 255)->nullable();
                $table->unsignedInteger('sipat_opd_id')->nullable()->index();
                $table->unsignedBigInteger('input_by')->nullable()->index();
                $table->timestamps();
                $table->string('plate_number_key', 20)->nullable();

                $table->unique(['plate_number_key', 'year'], 'elabel_bpkb_backup_plate_year_unique');
            });
        }

        // 5. elabel_bpkb_deletes
        if (!Schema::hasTable('elabel_bpkb_deletes')) {
            Schema::create('elabel_bpkb_deletes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('bpkb_id')->index();
                $table->unsignedInteger('box_id')->nullable();
                $table->string('box_code', 30)->nullable();
                $table->integer('year')->nullable();
                $table->string('vehicle_type', 10)->nullable();
                $table->string('plate_number', 20)->nullable();
                $table->string('no_bpkb', 50)->nullable();
                $table->string('nibar', 100)->nullable();
                $table->string('no_rangka', 50)->nullable();
                $table->string('no_mesin', 50)->nullable();
                $table->string('merek', 100)->nullable();
                $table->string('tipe', 100)->nullable();
                $table->string('isi_silinder', 50)->nullable();
                $table->string('warna', 100)->nullable();
                $table->string('pengguna', 100)->nullable();
                $table->string('status', 20)->nullable();
                $table->string('pdf_path', 255)->nullable();
                $table->unsignedInteger('sipat_opd_id')->nullable()->index();
                $table->unsignedBigInteger('input_by')->nullable();
                $table->unsignedBigInteger('deleted_by')->index();
                $table->dateTime('deleted_at')->nullable();
                $table->string('reason', 50);
                $table->string('reason_detail', 255)->nullable();
                $table->string('support_doc_path', 255)->nullable();

                $table->foreign('deleted_by', 'elabel_bpkb_deletes_deleted_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('sipat_opd_id', 'elabel_bpkb_deletes_sipat_opd_id_foreign')
                    ->references('id')
                    ->on('opd')
                    ->onDelete('set null');
            });
        }

        // 6. elabel_loans
        if (!Schema::hasTable('elabel_loans')) {
            Schema::create('elabel_loans', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('bpkb_id')->index();
                $table->unsignedBigInteger('requester_id')->nullable()->index();
                $table->string('requester_name', 100)->nullable();
                $table->string('requester_phone', 30)->nullable();
                $table->string('requester_email', 150)->nullable();
                $table->string('requester_org', 150)->nullable();
                $table->unsignedInteger('sipat_opd_id')->nullable()->index();
                $table->string('requester_address', 255)->nullable();
                $table->string('requester_note', 255)->nullable();
                $table->dateTime('requested_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->dateTime('approved_at')->nullable();
                $table->string('status', 20)->default('Menunggu');
                $table->string('note', 255)->nullable();
                $table->timestamps();

                $table->foreign('bpkb_id', 'elabel_loans_bpkb_id_foreign')
                    ->references('id')
                    ->on('elabel_bpkb')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('requester_id', 'elabel_loans_requester_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('approved_by', 'elabel_loans_approved_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('sipat_opd_id', 'elabel_loans_sipat_opd_id_foreign')
                    ->references('id')
                    ->on('opd')
                    ->onDelete('set null');
            });
        }

        // 7. elabel_loan_histories
        if (!Schema::hasTable('elabel_loan_histories')) {
            Schema::create('elabel_loan_histories', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('loan_id')->index();
                $table->string('status', 20);
                $table->unsignedBigInteger('changed_by')->nullable()->index();
                $table->dateTime('changed_at')->nullable();
                $table->string('note', 255)->nullable();

                $table->foreign('loan_id', 'elabel_loan_histories_loan_id_foreign')
                    ->references('id')
                    ->on('elabel_loans')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');

                $table->foreign('changed_by', 'elabel_loan_histories_changed_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
            });
        }

        // 8. elabel_sertifikat_boxes
        if (!Schema::hasTable('elabel_sertifikat_boxes')) {
            Schema::create('elabel_sertifikat_boxes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('box_code', 30)->unique('elabel_sertifikat_boxes_code_unique');
                $table->string('lokasi', 255);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 9. elabel_sertifikat_tanah
        if (!Schema::hasTable('elabel_sertifikat_tanah')) {
            Schema::create('elabel_sertifikat_tanah', function (Blueprint $table) {
                $table->increments('id');
                $table->string('no_sertipikat', 100)->index();
                $table->string('nibar', 100)->nullable();
                $table->string('status_penggunaan', 100)->nullable();
                $table->string('spesifikasi', 255)->nullable();
                $table->decimal('luas', 18, 2)->nullable();
                $table->date('tanggal_perolehan')->nullable();
                $table->decimal('nilai_perolehan', 18, 2)->nullable();
                $table->string('nama_pemilik', 150)->nullable();
                $table->string('cara_perolehan', 150)->nullable();
                $table->string('alamat', 255)->nullable();
                $table->string('lokasi', 255)->nullable();
                $table->string('dinas', 150)->nullable();
                $table->enum('sync_status', ['synced', 'pending', 'failed'])->default('synced');
                $table->unsignedInteger('data_version')->default(1);
                $table->unsignedInteger('box_id')->nullable();
                $table->string('pdf_path', 255)->nullable();
                $table->unsignedInteger('sipat_opd_id')->nullable()->index();
                $table->string('no_sertipikat_key', 100)->nullable()->unique('elabel_sertifikat_no_sertipikat_unique');
                $table->string('nibar_key', 100)->nullable()->unique('elabel_sertifikat_nibar_unique');
                $table->timestamps();

                $table->foreign('sipat_opd_id', 'elabel_sertifikat_tanah_sipat_opd_id_foreign')
                    ->references('id')
                    ->on('opd')
                    ->onDelete('set null');
            });
        }

        // 10. elabel_surat_penyerahan_boxes
        if (!Schema::hasTable('elabel_surat_penyerahan_boxes')) {
            Schema::create('elabel_surat_penyerahan_boxes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('box_code', 30)->unique('elabel_surat_penyerahan_boxes_code_unique');
                $table->string('lokasi', 255);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // 11. elabel_surat_penyerahan
        if (!Schema::hasTable('elabel_surat_penyerahan')) {
            Schema::create('elabel_surat_penyerahan', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nibar', 100)->nullable()->index();
                $table->string('no_surat', 150)->index();
                $table->string('status_penggunaan', 150)->nullable();
                $table->string('spesifikasi', 255)->nullable();
                $table->string('jenis_penyerahan', 150)->nullable();
                $table->decimal('luas', 12, 2)->nullable();
                $table->date('tanggal_perolehan')->nullable();
                $table->string('alamat', 255)->nullable();
                $table->string('lokasi', 255)->nullable();
                $table->string('dinas', 150)->nullable();
                $table->string('pemberi_hibah', 150)->nullable();
                $table->string('pdf_path', 255)->nullable();
                $table->unsignedInteger('sipat_opd_id')->nullable()->index();
                $table->unsignedInteger('box_id')->nullable();
                $table->timestamps();

                $table->foreign('sipat_opd_id', 'elabel_surat_penyerahan_sipat_opd_id_foreign')
                    ->references('id')
                    ->on('opd')
                    ->onDelete('set null');
            });
        }

        // 12. elabel_activity_logs
        if (!Schema::hasTable('elabel_activity_logs')) {
            Schema::create('elabel_activity_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('action', 40)->index();
                $table->string('module', 80)->index();
                $table->string('description', 255);
                $table->longText('old_data')->nullable();
                $table->longText('new_data')->nullable();
                $table->string('reference_type', 80)->nullable();
                $table->unsignedInteger('reference_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->dateTime('created_at')->nullable()->index();

                $table->foreign('user_id', 'elabel_activity_logs_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->onDelete('restrict')
                    ->onUpdate('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elabel_activity_logs');
        Schema::dropIfExists('elabel_surat_penyerahan');
        Schema::dropIfExists('elabel_surat_penyerahan_boxes');
        Schema::dropIfExists('elabel_sertifikat_tanah');
        Schema::dropIfExists('elabel_sertifikat_boxes');
        Schema::dropIfExists('elabel_loan_histories');
        Schema::dropIfExists('elabel_loans');
        Schema::dropIfExists('elabel_bpkb_deletes');
        Schema::dropIfExists('elabel_bpkb_backup');
        Schema::dropIfExists('elabel_bpkb');
        Schema::dropIfExists('elabel_box_years');
        Schema::dropIfExists('elabel_boxes');
    }
};
