<?php

namespace App\Services;

use App\Models\ReportLetterhead;
use App\Models\ReportSignatory;
use App\Models\ReportExportSetting;

/**
 * Service Konfigurasi Dokumen Laporan Terpusat (ReportDocumentSettingService)
 * 
 * Mengelola pembacaan konfigurasi Kop Surat (letterhead) dan Pejabat Penanda Tangan (signatory)
 * dari database secara dinamis per tipe laporan, serta menyediakan fallback yang aman dan kokoh.
 */
class ReportDocumentSettingService
{
    /**
     * Membaca konfigurasi aktif untuk tipe laporan tertentu.
     * 
     * @param string $reportType Tipe laporan (e.g., 'status', 'duplicate')
     * @return array Konfigurasi ter-compile (letterhead, signatory, settings)
     */
    public function getSettingsForReportType(string $reportType): array
    {
        try {
            // 1. Cari setting spesifik tipe laporan di database
            $setting = ReportExportSetting::with(['letterhead', 'signatory'])
                ->where('report_type', $reportType)
                ->first();

            // Ambil KOP Surat (baik dari setting spesifik, default, atau fallback)
            $letterhead = $setting?->letterhead ?? $this->getDefaultLetterhead();

            // Ambil Pejabat (baik dari setting spesifik, default, atau fallback)
            $signatory = $setting?->signatory ?? $this->getDefaultSignatory();

            return [
                'letterhead' => [
                    'nama_pemerintah' => $letterhead?->nama_pemerintah ?? 'PEMERINTAH KABUPATEN DONGGALA',
                    'nama_instansi'   => $letterhead?->nama_instansi ?? 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH',
                    'nama_unit'       => $letterhead?->nama_unit ?? 'Bidang Pengelolaan Aset Daerah',
                    'alamat'          => $letterhead?->alamat ?? 'Jl. Trans Sulawesi, Banawa, Kabupaten Donggala, Sulawesi Tengah',
                    'telepon'         => $letterhead?->telepon ?? '(0457) 71112',
                    'email'           => $letterhead?->email ?? 'bpkad@donggalakab.go.id',
                    'website'         => $letterhead?->website ?? 'bpkad.donggalakab.go.id',
                    'logo_path'       => $letterhead?->logo_path ?? 'images/logo.png',
                ],
                'signatory' => [
                    'nama'                 => $signatory?->nama ?? 'H. MUHAMMAD NATSIR, S.E., M.Si.',
                    'jabatan'              => $signatory?->jabatan ?? 'Kepala Bidang Pengelolaan Aset Daerah',
                    'nip'                  => $signatory?->nip ?? '19780512 200501 1 008',
                    'pangkat_golongan'     => $signatory?->pangkat_golongan ?? 'Pembina, IV/a',
                    'kota_ttd'             => $signatory?->kota_ttd ?? 'Banawa',
                    'signature_image_path' => $signatory?->signature_image_path,
                ],
                'settings' => [
                    'paper_size'     => $setting?->paper_size ?? 'A4',
                    'orientation'    => $setting?->orientation ?? 'L',
                    'show_summary'   => $setting?->show_summary ?? true,
                    'show_signature' => $setting?->show_signature ?? true,
                ]
            ];
        } catch (\Throwable $e) {
            // Jika terjadi kegagalan database, sediakan hardcoded fallback mutlak agar ekspor tidak pernah 500 error
            \Illuminate\Support\Facades\Log::error('Kegagalan database saat memuat ReportDocumentSettingService: ' . $e->getMessage());
            return $this->getHardcodedFallback();
        }
    }

    /**
     * Mengambil Kop Surat default dari database.
     *
     * @return ReportLetterhead|null
     */
    protected function getDefaultLetterhead(): ?ReportLetterhead
    {
        return ReportLetterhead::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->first();
    }

    /**
     * Mengambil Pejabat default dari database.
     *
     * @return ReportSignatory|null
     */
    protected function getDefaultSignatory(): ?ReportSignatory
    {
        return ReportSignatory::where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->first();
    }

    /**
     * Mengembalikan data fallback mutlak jika database kosong atau gagal terhubung.
     *
     * @return array
     */
    protected function getHardcodedFallback(): array
    {
        return [
            'letterhead' => [
                'nama_pemerintah' => 'PEMERINTAH KABUPATEN DONGGALA',
                'nama_instansi'   => 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH',
                'nama_unit'       => 'Bidang Pengelolaan Aset Daerah',
                'alamat'          => 'Jl. Trans Sulawesi, Banawa, Kabupaten Donggala, Sulawesi Tengah',
                'telepon'         => '(0457) 71112',
                'email'           => 'bpkad@donggalakab.go.id',
                'website'         => 'bpkad.donggalakab.go.id',
                'logo_path'       => 'images/logo.png',
            ],
            'signatory' => [
                'nama'                 => 'H. MUHAMMAD NATSIR, S.E., M.Si.',
                'jabatan'              => 'Kepala Bidang Pengelolaan Aset Daerah',
                'nip'                  => '19780512 200501 1 008',
                'pangkat_golongan'     => 'Pembina, IV/a',
                'kota_ttd'             => 'Banawa',
                'signature_image_path' => null,
            ],
            'settings' => [
                'paper_size'     => 'A4',
                'orientation'    => 'L',
                'show_summary'   => true,
                'show_signature' => true,
            ]
        ];
    }
}
