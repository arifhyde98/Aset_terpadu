<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. elabel_boxes
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_boxes` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `created_by` bigint unsigned NOT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              `box_code` varchar(30) NOT NULL,
              `location` varchar(100) DEFAULT NULL,
              `vehicle_type` varchar(10) DEFAULT 'mobil',
              PRIMARY KEY (`id`),
              KEY `created_by` (`created_by`),
              CONSTRAINT `elabel_boxes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        // 2. elabel_box_years
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_box_years` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `box_id` int unsigned NOT NULL,
              `year` int NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `elabel_box_year_unique` (`box_id`,`year`),
              KEY `box_id` (`box_id`),
              KEY `year` (`year`),
              CONSTRAINT `elabel_box_years_box_id_foreign` FOREIGN KEY (`box_id`) REFERENCES `elabel_boxes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        // 3. elabel_bpkb
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_bpkb` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `box_id` int unsigned NOT NULL,
              `year` int DEFAULT NULL,
              `vehicle_type` varchar(10) DEFAULT 'mobil',
              `plate_number` varchar(20) NOT NULL,
              `no_bpkb` varchar(50) DEFAULT NULL,
              `nibar` varchar(100) DEFAULT NULL,
              `no_rangka` varchar(50) DEFAULT NULL,
              `no_mesin` varchar(50) DEFAULT NULL,
              `merek` varchar(100) DEFAULT NULL,
              `tipe` varchar(100) DEFAULT NULL,
              `isi_silinder` varchar(50) DEFAULT NULL,
              `warna` varchar(100) DEFAULT NULL,
              `pengguna` varchar(100) DEFAULT NULL,
              `status` varchar(20) NOT NULL DEFAULT 'Tersedia',
              `pdf_path` varchar(255) DEFAULT NULL,
              `input_by` bigint unsigned NOT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              `plate_number_key` varchar(20) GENERATED ALWAYS AS (upper(trim(`plate_number`))) STORED,
              PRIMARY KEY (`id`),
              UNIQUE KEY `elabel_bpkb_plate_year_unique` (`plate_number_key`,`year`),
              KEY `box_id` (`box_id`),
              KEY `input_by` (`input_by`),
              CONSTRAINT `elabel_bpkb_box_id_foreign` FOREIGN KEY (`box_id`) REFERENCES `elabel_boxes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
              CONSTRAINT `elabel_bpkb_input_by_foreign` FOREIGN KEY (`input_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        // 4. elabel_bpkb_deletes
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_bpkb_deletes` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `bpkb_id` int unsigned NOT NULL,
              `box_id` int unsigned DEFAULT NULL,
              `box_code` varchar(30) DEFAULT NULL,
              `year` int DEFAULT NULL,
              `vehicle_type` varchar(10) DEFAULT NULL,
              `plate_number` varchar(20) DEFAULT NULL,
              `no_bpkb` varchar(50) DEFAULT NULL,
              `nibar` varchar(100) DEFAULT NULL,
              `no_rangka` varchar(50) DEFAULT NULL,
              `no_mesin` varchar(50) DEFAULT NULL,
              `merek` varchar(100) DEFAULT NULL,
              `tipe` varchar(100) DEFAULT NULL,
              `isi_silinder` varchar(50) DEFAULT NULL,
              `warna` varchar(100) DEFAULT NULL,
              `pengguna` varchar(100) DEFAULT NULL,
              `status` varchar(20) DEFAULT NULL,
              `pdf_path` varchar(255) DEFAULT NULL,
              `input_by` bigint unsigned DEFAULT NULL,
              `deleted_by` bigint unsigned NOT NULL,
              `deleted_at` datetime DEFAULT NULL,
              `reason` varchar(50) NOT NULL,
              `reason_detail` varchar(255) DEFAULT NULL,
              `support_doc_path` varchar(255) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `bpkb_id` (`bpkb_id`),
              KEY `deleted_by` (`deleted_by`),
              CONSTRAINT `elabel_bpkb_deletes_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        // 5. elabel_sertifikat_boxes
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_sertifikat_boxes` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `box_code` varchar(30) NOT NULL,
              `lokasi` varchar(255) NOT NULL,
              `created_by` bigint unsigned DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `elabel_sertifikat_boxes_code_unique` (`box_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        // 6. elabel_sertifikat_tanah
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_sertifikat_tanah` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `no_sertipikat` varchar(100) NOT NULL,
              `nibar` varchar(100) DEFAULT NULL,
              `status_penggunaan` varchar(100) DEFAULT NULL,
              `spesifikasi` varchar(255) DEFAULT NULL,
              `luas` decimal(18,2) DEFAULT NULL,
              `tanggal_perolehan` date DEFAULT NULL,
              `nilai_perolehan` decimal(18,2) DEFAULT NULL,
              `nama_pemilik` varchar(150) DEFAULT NULL,
              `cara_perolehan` varchar(150) DEFAULT NULL,
              `alamat` varchar(255) DEFAULT NULL,
              `lokasi` varchar(255) DEFAULT NULL,
              `dinas` varchar(150) DEFAULT NULL,
              `sync_status` enum('synced','pending','failed') NOT NULL DEFAULT 'synced',
              `data_version` int unsigned NOT NULL DEFAULT '1',
              `box_id` int unsigned DEFAULT NULL,
              `pdf_path` varchar(255) DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              `no_sertipikat_key` varchar(100) GENERATED ALWAYS AS ((case when ((`no_sertipikat` is null) or (trim(`no_sertipikat`) = '')) then NULL else upper(trim(`no_sertipikat`)) end)) STORED,
              `nibar_key` varchar(100) GENERATED ALWAYS AS ((case when ((`nibar` is null) or (trim(`nibar`) = '')) then NULL else upper(trim(`nibar`)) end)) STORED,
              PRIMARY KEY (`id`),
              UNIQUE KEY `elabel_sertifikat_no_sertipikat_unique` (`no_sertipikat_key`),
              UNIQUE KEY `elabel_sertifikat_nibar_unique` (`nibar_key`),
              KEY `no_sertipikat` (`no_sertipikat`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        // 7. elabel_surat_penyerahan_boxes
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_surat_penyerahan_boxes` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `box_code` varchar(30) NOT NULL,
              `lokasi` varchar(255) NOT NULL,
              `created_by` bigint unsigned DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `elabel_surat_penyerahan_boxes_code_unique` (`box_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        // 8. elabel_surat_penyerahan
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_surat_penyerahan` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `nibar` varchar(100) DEFAULT NULL,
              `no_surat` varchar(150) NOT NULL,
              `status_penggunaan` varchar(150) DEFAULT NULL,
              `spesifikasi` varchar(255) DEFAULT NULL,
              `jenis_penyerahan` varchar(150) DEFAULT NULL,
              `luas` decimal(12,2) DEFAULT NULL,
              `tanggal_perolehan` date DEFAULT NULL,
              `alamat` varchar(255) DEFAULT NULL,
              `lokasi` varchar(255) DEFAULT NULL,
              `dinas` varchar(150) DEFAULT NULL,
              `pemberi_hibah` varchar(150) DEFAULT NULL,
              `pdf_path` varchar(255) DEFAULT NULL,
              `box_id` int unsigned DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `no_surat` (`no_surat`),
              KEY `nibar` (`nibar`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        // 9. elabel_loans
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_loans` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `bpkb_id` int unsigned NOT NULL,
              `requester_id` bigint unsigned DEFAULT NULL,
              `requester_name` varchar(100) DEFAULT NULL,
              `requester_phone` varchar(30) DEFAULT NULL,
              `requester_email` varchar(150) DEFAULT NULL,
              `requester_org` varchar(150) DEFAULT NULL,
              `requester_address` varchar(255) DEFAULT NULL,
              `requester_note` varchar(255) DEFAULT NULL,
              `requested_at` datetime DEFAULT NULL,
              `approved_by` bigint unsigned DEFAULT NULL,
              `approved_at` datetime DEFAULT NULL,
              `status` varchar(20) NOT NULL DEFAULT 'Menunggu',
              `note` varchar(255) DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `bpkb_id` (`bpkb_id`),
              KEY `requester_id` (`requester_id`),
              KEY `approved_by` (`approved_by`),
              CONSTRAINT `elabel_loans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
              CONSTRAINT `elabel_loans_bpkb_id_foreign` FOREIGN KEY (`bpkb_id`) REFERENCES `elabel_bpkb` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
              CONSTRAINT `elabel_loans_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        // 10. elabel_loan_histories
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_loan_histories` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `loan_id` int unsigned NOT NULL,
              `status` varchar(20) NOT NULL,
              `changed_by` bigint unsigned DEFAULT NULL,
              `changed_at` datetime DEFAULT NULL,
              `note` varchar(255) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `loan_id` (`loan_id`),
              KEY `changed_by` (`changed_by`),
              CONSTRAINT `elabel_loan_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
              CONSTRAINT `elabel_loan_histories_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `elabel_loans` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");

        // 11. elabel_activity_logs
        DB::statement("
            CREATE TABLE IF NOT EXISTS `elabel_activity_logs` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `user_id` bigint unsigned DEFAULT NULL,
              `action` VARCHAR(40) NOT NULL,
              `module` VARCHAR(80) NOT NULL,
              `description` VARCHAR(255) NOT NULL,
              `reference_type` VARCHAR(80) DEFAULT NULL,
              `reference_id` int unsigned DEFAULT NULL,
              `ip_address` VARCHAR(45) DEFAULT NULL,
              `user_agent` VARCHAR(255) DEFAULT NULL,
              `created_at` DATETIME DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `action` (`action`),
              KEY `module` (`module`),
              KEY `created_at` (`created_at`),
              CONSTRAINT `elabel_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elabel_activity_logs');
        Schema::dropIfExists('elabel_loan_histories');
        Schema::dropIfExists('elabel_loans');
        Schema::dropIfExists('elabel_surat_penyerahan');
        Schema::dropIfExists('elabel_surat_penyerahan_boxes');
        Schema::dropIfExists('elabel_sertifikat_tanah');
        Schema::dropIfExists('elabel_sertifikat_boxes');
        Schema::dropIfExists('elabel_bpkb_deletes');
        Schema::dropIfExists('elabel_bpkb');
        Schema::dropIfExists('elabel_box_years');
        Schema::dropIfExists('elabel_boxes');
    }
};
