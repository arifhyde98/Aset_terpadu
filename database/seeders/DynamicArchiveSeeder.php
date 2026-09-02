<?php

namespace Database\Seeders;

use App\Models\Elabel\Dynamic\ArchiveBox;
use App\Models\Elabel\Dynamic\ArchiveType;
use Illuminate\Database\Seeder;

class DynamicArchiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. IMB / PBG Bangunan
        $imb = ArchiveType::updateOrCreate(
            ['kode' => 'IMB'],
            [
                'nama'          => 'Dokumen IMB & PBG Bangunan',
                'deskripsi'     => 'Arsip Izin Mendirikan Bangunan (IMB), PBG, dan Dokumen Kelayakan Teknis Gedung Pemerintah Daerah.',
                'icon'          => 'bi-building',
                'warna_badge'   => 'primary',
                'is_active'     => true,
                'schema_fields' => [
                    [
                        'name'        => 'nama_pemohon',
                        'label'       => 'Nama Pemohon / Pemilik',
                        'type'        => 'text',
                        'required'    => true,
                        'placeholder' => 'Cth: Dinas Kesehatan Kab. Donggala',
                        'help_text'   => 'Nama OPD atau pihak pemohon izin bangunan',
                        'options'     => [],
                    ],
                    [
                        'name'        => 'luas_bangunan',
                        'label'       => 'Luas Bangunan (m²)',
                        'type'        => 'number',
                        'required'    => false,
                        'placeholder' => 'Cth: 450.50',
                        'help_text'   => 'Luas total lantai bangunan dalam meter persegi',
                        'options'     => [],
                    ],
                    [
                        'name'        => 'lokasi_bangunan',
                        'label'       => 'Lokasi / Alamat Bangunan',
                        'type'        => 'textarea',
                        'required'    => true,
                        'placeholder' => 'Alamat lengkap lokasi gedung...',
                        'help_text'   => '',
                        'options'     => [],
                    ],
                    [
                        'name'        => 'fungsi_bangunan',
                        'label'       => 'Fungsi Bangunan',
                        'type'        => 'select',
                        'required'    => true,
                        'placeholder' => '',
                        'help_text'   => 'Kategori pemanfaatan gedung',
                        'options'     => ['Gedung Kantor Pemerintahan', 'Puskesmas / Fasilitas Kesehatan', 'Sekolah / Pendidikan', 'Gedung Olahraga / Sosial', 'Pos / Posko / Lainnya'],
                    ],
                ],
            ]
        );

        ArchiveBox::firstOrCreate(
            ['nomor_box' => 'BOX-IMB-001'],
            [
                'archive_type_id'    => $imb->id,
                'barcode_code'       => 'BOX-IMB-001',
                'lokasi_rak'         => 'Rak A-01 Lantai 2',
                'tahun'              => date('Y'),
                'kapasitas_maksimal' => 100,
                'keterangan'         => 'Box penyimpanan berkas IMB & PBG Gedung Pemda',
            ]
        );

        // 2. Kontrak & SPK Pengadaan
        $kontrak = ArchiveType::updateOrCreate(
            ['kode' => 'KONTRAK'],
            [
                'nama'          => 'Kontrak & SPK Pengadaan',
                'deskripsi'     => 'Berkas Surat Perjanjian Kerja (SPK), Kontrak Konstruksi, dan Berita Acara Serah Terima Pengadaan BMD.',
                'icon'          => 'bi-briefcase',
                'warna_badge'   => 'warning',
                'is_active'     => true,
                'schema_fields' => [
                    [
                        'name'        => 'nama_pihak_ketiga',
                        'label'       => 'Penyedia / Pihak Ketiga (CV/PT)',
                        'type'        => 'text',
                        'required'    => true,
                        'placeholder' => 'Cth: PT. Sulawesi Karya Abadi',
                        'help_text'   => '',
                        'options'     => [],
                    ],
                    [
                        'name'        => 'nilai_kontrak',
                        'label'       => 'Nilai Kontrak (Rp)',
                        'type'        => 'number',
                        'required'    => true,
                        'placeholder' => 'Cth: 150000000',
                        'help_text'   => 'Nominal kontrak dalam Rupiah',
                        'options'     => [],
                    ],
                    [
                        'name'        => 'tanggal_kontrak',
                        'label'       => 'Tanggal Kontrak / SPK',
                        'type'        => 'date',
                        'required'    => true,
                        'placeholder' => '',
                        'help_text'   => '',
                        'options'     => [],
                    ],
                    [
                        'name'        => 'sumber_dana',
                        'label'       => 'Sumber Dana',
                        'type'        => 'select',
                        'required'    => true,
                        'placeholder' => '',
                        'help_text'   => '',
                        'options'     => ['APBD Murni (PAD)', 'DAK Fisik', 'DAU', 'Hibah', 'BTT / Darurat'],
                    ],
                ],
            ]
        );

        ArchiveBox::firstOrCreate(
            ['nomor_box' => 'BOX-KONTRAK-001'],
            [
                'archive_type_id'    => $kontrak->id,
                'barcode_code'       => 'BOX-KONTRAK-001',
                'lokasi_rak'         => 'Rak B-01 Lantai 2',
                'tahun'              => date('Y'),
                'kapasitas_maksimal' => 80,
                'keterangan'         => 'Box arsip kontrak pengadaan barang & jasa',
            ]
        );

        // 3. Kuitansi & SPJ Keuangan
        $spj = ArchiveType::updateOrCreate(
            ['kode' => 'SPJ'],
            [
                'nama'          => 'Kuitansi & SPJ Keuangan',
                'deskripsi'     => 'Dokumen Surat Pertanggungjawaban (SPJ), kuitansi belanja modal, dan bukti pembayaran aset.',
                'icon'          => 'bi-receipt',
                'warna_badge'   => 'success',
                'is_active'     => true,
                'schema_fields' => [
                    [
                        'name'        => 'nomor_sp2d',
                        'label'       => 'Nomor SP2D',
                        'type'        => 'text',
                        'required'    => false,
                        'placeholder' => 'Cth: 0012/SP2D-LS/2024',
                        'help_text'   => 'Nomor Surat Perintah Pencairan Dana',
                        'options'     => [],
                    ],
                    [
                        'name'        => 'nominal_belanja',
                        'label'       => 'Jumlah Realisasi (Rp)',
                        'type'        => 'number',
                        'required'    => true,
                        'placeholder' => 'Cth: 75000000',
                        'help_text'   => '',
                        'options'     => [],
                    ],
                    [
                        'name'        => 'kode_rekening',
                        'label'       => 'Kode Rekening Belanja',
                        'type'        => 'text',
                        'required'    => false,
                        'placeholder' => '5.2.02.xx.xx.xxxx',
                        'help_text'   => '',
                        'options'     => [],
                    ],
                ],
            ]
        );

        ArchiveBox::firstOrCreate(
            ['nomor_box' => 'BOX-SPJ-001'],
            [
                'archive_type_id'    => $spj->id,
                'barcode_code'       => 'BOX-SPJ-001',
                'lokasi_rak'         => 'Rak C-01 Lantai 2',
                'tahun'              => date('Y'),
                'kapasitas_maksimal' => 120,
                'keterangan'         => 'Box kuitansi & SPJ belanja modal',
            ]
        );
    }
}
