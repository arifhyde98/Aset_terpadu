-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: db_sipat_terpadu
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.22.04.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `module_id` tinyint unsigned NOT NULL DEFAULT '1',
  `module_key` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'erandis',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `old_data` longtext COLLATE utf8mb4_unicode_ci,
  `new_data` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activities_user_id_foreign` (`user_id`),
  KEY `activities_module_id_created_at_index` (`module_id`,`created_at`),
  KEY `activities_module_key_created_at_index` (`module_key`,`created_at`),
  CONSTRAINT `activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `aset_tanah`
--

DROP TABLE IF EXISTS `aset_tanah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `aset_tanah` (
  `id_aset` int unsigned NOT NULL AUTO_INCREMENT,
  `kode_aset` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_aset` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `peruntukan` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `luas` decimal(15,2) DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `opd` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opd_id` bigint unsigned DEFAULT NULL,
  `dasar_perolehan` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `harga_perolehan` decimal(18,2) DEFAULT NULL,
  `tanggal_perolehan` date DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_aset`),
  UNIQUE KEY `kode_aset` (`kode_aset`)
) ENGINE=InnoDB AUTO_INCREMENT=1196 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `entity` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `entity_id` int DEFAULT NULL,
  `old_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `new_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `camat`
--

DROP TABLE IF EXISTS `camat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `camat` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kecamatan_id` int unsigned DEFAULT NULL,
  `nama` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_camat_kecamatan` (`kecamatan_id`),
  CONSTRAINT `fk_camat_kecamatan` FOREIGN KEY (`kecamatan_id`) REFERENCES `kecamatan` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `desa`
--

DROP TABLE IF EXISTS `desa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `desa` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kecamatan_id` int unsigned DEFAULT NULL,
  `nama` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jenis` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Kelurahan',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_desa_kecamatan` (`kecamatan_id`),
  CONSTRAINT `fk_desa_kecamatan` FOREIGN KEY (`kecamatan_id`) REFERENCES `kecamatan` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dokumen_aset`
--

DROP TABLE IF EXISTS `dokumen_aset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dokumen_aset` (
  `id_dokumen` int unsigned NOT NULL AUTO_INCREMENT,
  `id_aset` int unsigned NOT NULL,
  `id_proses` int unsigned DEFAULT NULL,
  `jenis_dokumen` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `status_dokumen` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uploaded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_dokumen`),
  KEY `id_aset` (`id_aset`),
  KEY `id_proses` (`id_proses`),
  CONSTRAINT `dokumen_aset_id_aset_foreign` FOREIGN KEY (`id_aset`) REFERENCES `aset_tanah` (`id_aset`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `dokumen_aset_id_proses_foreign` FOREIGN KEY (`id_proses`) REFERENCES `proses_aset` (`id_proses`) ON DELETE CASCADE ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ebmd_vehicles`
--

DROP TABLE IF EXISTS `ebmd_vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ebmd_vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `no_polisi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor_register` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_type_id` bigint unsigned DEFAULT NULL,
  `tahun_pembuatan` int DEFAULT NULL,
  `tgl_perolehan` date DEFAULT NULL,
  `nilai_perolehan` decimal(15,2) DEFAULT NULL,
  `stnk_ada` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tidak',
  `bpkb_ada` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tidak',
  `no_rangka` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_mesin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warna` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_stnk` date DEFAULT NULL,
  `opd` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opd_id` bigint unsigned DEFAULT NULL,
  `pemegang` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `kondisi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'B',
  `foto_kendaraan` json DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned DEFAULT NULL,
  `is_synced` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ebmd_vehicles_vehicle_type_id_foreign` (`vehicle_type_id`),
  KEY `ebmd_vehicles_opd_id_foreign` (`opd_id`),
  KEY `ebmd_vehicles_user_id_foreign` (`user_id`),
  KEY `ebmd_vehicles_no_polisi_index` (`no_polisi`),
  KEY `ebmd_vehicles_nomor_register_index` (`nomor_register`),
  KEY `ebmd_vehicles_no_rangka_index` (`no_rangka`),
  KEY `ebmd_vehicles_no_mesin_index` (`no_mesin`),
  CONSTRAINT `ebmd_vehicles_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ebmd_vehicles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ebmd_vehicles_vehicle_type_id_foreign` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_activity_logs`
--

DROP TABLE IF EXISTS `elabel_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_activity_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(40) NOT NULL,
  `module` varchar(80) NOT NULL,
  `description` varchar(255) NOT NULL,
  `reference_type` varchar(80) DEFAULT NULL,
  `reference_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `module` (`module`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `elabel_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=658 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_box_years`
--

DROP TABLE IF EXISTS `elabel_box_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_box_years` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `box_id` int unsigned NOT NULL,
  `year` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `elabel_box_year_unique` (`box_id`,`year`),
  KEY `box_id` (`box_id`),
  KEY `year` (`year`),
  CONSTRAINT `elabel_box_years_box_id_foreign` FOREIGN KEY (`box_id`) REFERENCES `elabel_boxes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=155 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_boxes`
--

DROP TABLE IF EXISTS `elabel_boxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_boxes` (
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
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_bpkb`
--

DROP TABLE IF EXISTS `elabel_bpkb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_bpkb` (
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
  `sipat_opd_id` int unsigned DEFAULT NULL,
  `input_by` bigint unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `plate_number_key` varchar(20) GENERATED ALWAYS AS (upper(trim(`plate_number`))) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `elabel_bpkb_plate_year_unique` (`plate_number_key`,`year`),
  KEY `box_id` (`box_id`),
  KEY `input_by` (`input_by`),
  KEY `elabel_bpkb_sipat_opd_id_foreign` (`sipat_opd_id`),
  CONSTRAINT `elabel_bpkb_box_id_foreign` FOREIGN KEY (`box_id`) REFERENCES `elabel_boxes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `elabel_bpkb_input_by_foreign` FOREIGN KEY (`input_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `elabel_bpkb_sipat_opd_id_foreign` FOREIGN KEY (`sipat_opd_id`) REFERENCES `opd` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8131 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_bpkb_deletes`
--

DROP TABLE IF EXISTS `elabel_bpkb_deletes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_bpkb_deletes` (
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
  `sipat_opd_id` int unsigned DEFAULT NULL,
  `input_by` bigint unsigned DEFAULT NULL,
  `deleted_by` bigint unsigned NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `reason` varchar(50) NOT NULL,
  `reason_detail` varchar(255) DEFAULT NULL,
  `support_doc_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bpkb_id` (`bpkb_id`),
  KEY `deleted_by` (`deleted_by`),
  KEY `elabel_bpkb_deletes_sipat_opd_id_foreign` (`sipat_opd_id`),
  CONSTRAINT `elabel_bpkb_deletes_deleted_by_foreign` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `elabel_bpkb_deletes_sipat_opd_id_foreign` FOREIGN KEY (`sipat_opd_id`) REFERENCES `opd` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_loan_histories`
--

DROP TABLE IF EXISTS `elabel_loan_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_loan_histories` (
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_loans`
--

DROP TABLE IF EXISTS `elabel_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_loans` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `bpkb_id` int unsigned NOT NULL,
  `requester_id` bigint unsigned DEFAULT NULL,
  `requester_name` varchar(100) DEFAULT NULL,
  `requester_phone` varchar(30) DEFAULT NULL,
  `requester_email` varchar(150) DEFAULT NULL,
  `requester_org` varchar(150) DEFAULT NULL,
  `sipat_opd_id` int unsigned DEFAULT NULL,
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
  KEY `elabel_loans_sipat_opd_id_foreign` (`sipat_opd_id`),
  CONSTRAINT `elabel_loans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `elabel_loans_bpkb_id_foreign` FOREIGN KEY (`bpkb_id`) REFERENCES `elabel_bpkb` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `elabel_loans_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `elabel_loans_sipat_opd_id_foreign` FOREIGN KEY (`sipat_opd_id`) REFERENCES `opd` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_sertifikat_boxes`
--

DROP TABLE IF EXISTS `elabel_sertifikat_boxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_sertifikat_boxes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `box_code` varchar(30) NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `elabel_sertifikat_boxes_code_unique` (`box_code`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_sertifikat_tanah`
--

DROP TABLE IF EXISTS `elabel_sertifikat_tanah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_sertifikat_tanah` (
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
  `sipat_opd_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `no_sertipikat_key` varchar(100) GENERATED ALWAYS AS ((case when ((`no_sertipikat` is null) or (trim(`no_sertipikat`) = _utf8mb3'')) then NULL else upper(trim(`no_sertipikat`)) end)) STORED,
  `nibar_key` varchar(100) GENERATED ALWAYS AS ((case when ((`nibar` is null) or (trim(`nibar`) = _utf8mb3'')) then NULL else upper(trim(`nibar`)) end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `elabel_sertifikat_no_sertipikat_unique` (`no_sertipikat_key`),
  UNIQUE KEY `elabel_sertifikat_nibar_unique` (`nibar_key`),
  KEY `no_sertipikat` (`no_sertipikat`),
  KEY `elabel_sertifikat_tanah_sipat_opd_id_foreign` (`sipat_opd_id`),
  CONSTRAINT `elabel_sertifikat_tanah_sipat_opd_id_foreign` FOREIGN KEY (`sipat_opd_id`) REFERENCES `opd` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1839 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_surat_penyerahan`
--

DROP TABLE IF EXISTS `elabel_surat_penyerahan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_surat_penyerahan` (
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
  `sipat_opd_id` int unsigned DEFAULT NULL,
  `box_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `no_surat` (`no_surat`),
  KEY `nibar` (`nibar`),
  KEY `elabel_surat_penyerahan_sipat_opd_id_foreign` (`sipat_opd_id`),
  CONSTRAINT `elabel_surat_penyerahan_sipat_opd_id_foreign` FOREIGN KEY (`sipat_opd_id`) REFERENCES `opd` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `elabel_surat_penyerahan_boxes`
--

DROP TABLE IF EXISTS `elabel_surat_penyerahan_boxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elabel_surat_penyerahan_boxes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `box_code` varchar(30) NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `elabel_surat_penyerahan_boxes_code_unique` (`box_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `integration_audit_logs`
--

DROP TABLE IF EXISTS `integration_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `integration_audit_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `event_id` varchar(36) NOT NULL,
  `correlation_id` varchar(36) DEFAULT NULL,
  `nibar` varchar(100) NOT NULL,
  `event_name` varchar(60) NOT NULL,
  `source_system` varchar(20) NOT NULL,
  `direction` varchar(10) NOT NULL,
  `changes` text,
  `reason` varchar(500) DEFAULT NULL,
  `sync_status` enum('SUCCESS','FAILED','PENDING') NOT NULL DEFAULT 'PENDING',
  `error_message` text,
  `data_version` int unsigned NOT NULL DEFAULT '1',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_nibar` (`nibar`),
  KEY `idx_sync_status` (`sync_status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kecamatan`
--

DROP TABLE IF EXISTS `kecamatan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kecamatan` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kepala_desa`
--

DROP TABLE IF EXISTS `kepala_desa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kepala_desa` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `desa_id` int unsigned NOT NULL,
  `nama` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `desa_id` (`desa_id`),
  CONSTRAINT `fk_kepala_desa_desa` FOREIGN KEY (`desa_id`) REFERENCES `desa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `master_pengamanan_items`
--

DROP TABLE IF EXISTS `master_pengamanan_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_pengamanan_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `opd`
--

DROP TABLE IF EXISTS `opd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `opd` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `opd_nama_unique` (`nama`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `opd_mappings`
--

DROP TABLE IF EXISTS `opd_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `opd_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sipat_opd_id` int unsigned NOT NULL,
  `erandis_opd_id` bigint unsigned NOT NULL,
  `status_verifikasi` enum('matched','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'matched',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `opd_mappings_sipat_opd_id_erandis_opd_id_unique` (`sipat_opd_id`,`erandis_opd_id`),
  KEY `opd_mappings_erandis_opd_id_foreign` (`erandis_opd_id`),
  CONSTRAINT `opd_mappings_erandis_opd_id_foreign` FOREIGN KEY (`erandis_opd_id`) REFERENCES `opds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `opd_mappings_sipat_opd_id_foreign` FOREIGN KEY (`sipat_opd_id`) REFERENCES `opd` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `opds`
--

DROP TABLE IF EXISTS `opds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `opds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `singkatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `opds_nama_unique` (`nama`)
) ENGINE=InnoDB AUTO_INCREMENT=2054 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pemohon`
--

DROP TABLE IF EXISTS `pemohon`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pemohon` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nik` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ttl` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `umur` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_kelamin` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `warga_negara` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `agama` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pekerjaan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabatan` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pengamanan_fisik`
--

DROP TABLE IF EXISTS `pengamanan_fisik`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengamanan_fisik` (
  `id_pengamanan` int unsigned NOT NULL AUTO_INCREMENT,
  `id_aset` int unsigned NOT NULL,
  `sertifikat_ada` tinyint(1) NOT NULL DEFAULT '0',
  `pagar` tinyint(1) NOT NULL DEFAULT '0',
  `papan_nama` tinyint(1) NOT NULL DEFAULT '0',
  `dikuasai_pihak_lain` tinyint(1) NOT NULL DEFAULT '0',
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tgl_cek` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_pengamanan`),
  UNIQUE KEY `id_aset` (`id_aset`),
  CONSTRAINT `pengamanan_fisik_id_aset_foreign` FOREIGN KEY (`id_aset`) REFERENCES `aset_tanah` (`id_aset`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pengamanan_fisik_values`
--

DROP TABLE IF EXISTS `pengamanan_fisik_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengamanan_fisik_values` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `id_pengamanan` int unsigned NOT NULL,
  `id_item` int unsigned NOT NULL,
  `is_checked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pengamanan_fisik_values_id_pengamanan_foreign` (`id_pengamanan`),
  KEY `pengamanan_fisik_values_id_item_foreign` (`id_item`),
  CONSTRAINT `pengamanan_fisik_values_id_item_foreign` FOREIGN KEY (`id_item`) REFERENCES `master_pengamanan_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `pengamanan_fisik_values_id_pengamanan_foreign` FOREIGN KEY (`id_pengamanan`) REFERENCES `pengamanan_fisik` (`id_pengamanan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `proses_aset`
--

DROP TABLE IF EXISTS `proses_aset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proses_aset` (
  `id_proses` int unsigned NOT NULL AUTO_INCREMENT,
  `id_aset` int unsigned NOT NULL,
  `id_status` int unsigned NOT NULL,
  `tgl_mulai` date DEFAULT NULL,
  `tgl_selesai` date DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `durasi_hari` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_proses`),
  KEY `id_aset` (`id_aset`),
  KEY `id_status` (`id_status`),
  CONSTRAINT `proses_aset_id_aset_foreign` FOREIGN KEY (`id_aset`) REFERENCES `aset_tanah` (`id_aset`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `proses_aset_id_status_foreign` FOREIGN KEY (`id_status`) REFERENCES `status_proses` (`id_status`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=1262 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report_export_settings`
--

DROP TABLE IF EXISTS `report_export_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_export_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `letterhead_id` bigint unsigned DEFAULT NULL,
  `signatory_id` bigint unsigned DEFAULT NULL,
  `paper_size` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'A4',
  `orientation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'L',
  `show_summary` tinyint(1) NOT NULL DEFAULT '1',
  `show_signature` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_export_settings_report_type_unique` (`report_type`),
  KEY `report_export_settings_letterhead_id_foreign` (`letterhead_id`),
  KEY `report_export_settings_signatory_id_foreign` (`signatory_id`),
  CONSTRAINT `report_export_settings_letterhead_id_foreign` FOREIGN KEY (`letterhead_id`) REFERENCES `report_letterheads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `report_export_settings_signatory_id_foreign` FOREIGN KEY (`signatory_id`) REFERENCES `report_signatories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report_letterheads`
--

DROP TABLE IF EXISTS `report_letterheads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_letterheads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_pemerintah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_instansi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `telepon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report_signatories`
--

DROP TABLE IF EXISTS `report_signatories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_signatories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jabatan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pangkat_golongan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kota_ttd` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `report_titles`
--

DROP TABLE IF EXISTS `report_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_titles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `status_proses`
--

DROP TABLE IF EXISTS `status_proses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `status_proses` (
  `id_status` int unsigned NOT NULL AUTO_INCREMENT,
  `nama_status` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `urutan` int NOT NULL,
  `warna` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kategori` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'proses',
  PRIMARY KEY (`id_status`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `surat_skpt`
--

DROP TABLE IF EXISTS `surat_skpt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `surat_skpt` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nomor_surat` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat_kantor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `desa_id` int unsigned DEFAULT NULL,
  `kepala_desa_id` int unsigned DEFAULT NULL,
  `camat_id` int unsigned DEFAULT NULL,
  `pemohon_id` int unsigned DEFAULT NULL,
  `lokasi_tanah` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `jenis_tanah` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_tanah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `asal_tanah` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `pernyataan_tanah` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `luas_tanah` decimal(15,2) DEFAULT NULL,
  `dasar_perolehan` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `batas_utara` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `batas_timur` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `batas_selatan` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `batas_barat` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tanggal_surat` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_skpt_desa` (`desa_id`),
  KEY `fk_skpt_kepala_desa` (`kepala_desa_id`),
  KEY `fk_skpt_camat` (`camat_id`),
  KEY `fk_skpt_pemohon` (`pemohon_id`),
  CONSTRAINT `fk_skpt_camat` FOREIGN KEY (`camat_id`) REFERENCES `camat` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_skpt_desa` FOREIGN KEY (`desa_id`) REFERENCES `desa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_skpt_kepala_desa` FOREIGN KEY (`kepala_desa_id`) REFERENCES `kepala_desa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_skpt_pemohon` FOREIGN KEY (`pemohon_id`) REFERENCES `pemohon` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'opd',
  `opd_id` bigint unsigned DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_opd_id_foreign` (`opd_id`),
  CONSTRAINT `users_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2289 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vehicle_types`
--

DROP TABLE IF EXISTS `vehicle_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicle_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicle_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=289 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `no_polisi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nomor_register` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vehicle_type_id` bigint unsigned DEFAULT NULL,
  `tahun_pembuatan` int DEFAULT NULL,
  `tgl_perolehan` date DEFAULT NULL,
  `nilai_perolehan` decimal(15,2) DEFAULT NULL,
  `stnk_ada` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tidak',
  `bpkb_ada` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tidak',
  `no_rangka` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_mesin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warna` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tgl_stnk` date DEFAULT NULL,
  `opd` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `opd_id` bigint unsigned DEFAULT NULL,
  `pemegang` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kondisi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Baik',
  `foto_kendaraan` json DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vehicles_no_polisi_unique` (`no_polisi`),
  UNIQUE KEY `vehicles_nomor_register_unique` (`nomor_register`),
  KEY `vehicles_user_id_foreign` (`user_id`),
  KEY `vehicles_vehicle_type_id_foreign` (`vehicle_type_id`),
  KEY `vehicles_opd_id_foreign` (`opd_id`),
  KEY `vehicles_status_index` (`status`),
  KEY `vehicles_no_polisi_status_index` (`no_polisi`,`status`),
  KEY `vehicles_kondisi_index` (`kondisi`),
  CONSTRAINT `vehicles_opd_id_foreign` FOREIGN KEY (`opd_id`) REFERENCES `opds` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vehicles_vehicle_type_id_foreign` FOREIGN KEY (`vehicle_type_id`) REFERENCES `vehicle_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1041 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-24 13:20:15
