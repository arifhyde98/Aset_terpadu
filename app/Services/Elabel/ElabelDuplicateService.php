<?php

namespace App\Services\Elabel;

use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelSertifikat;
use App\Models\Elabel\ElabelLoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ElabelDuplicateService
{
    /**
     * Memindai duplikasi BPKB secara global.
     *
     * @return array
     */
    public function getDuplicateBpkbList(): array
    {
        $bpkbs = ElabelBpkb::with('opdSipat')->get();
        $duplicates = [];
        
        $groups = [];
        foreach ($bpkbs as $bpkb) {
            $noBpkb = trim($bpkb->no_bpkb);
            if ($noBpkb === '') continue;

            $clean = preg_replace('/[^a-zA-Z0-9]/', '', $noBpkb);
            if ($clean === '') continue;

            $groups[$clean][] = $bpkb;
        }

        foreach ($groups as $clean => $list) {
            if (count($list) > 1) {
                $original = $list[0];
                for ($i = 1; $i < count($list); $i++) {
                    $dup = $list[$i];
                    $duplicates[] = [
                        'duplicate_bpkb' => $dup,
                        'original_bpkb'  => $original,
                        'reason'         => "Nomor BPKB identik setelah dibersihkan: \"{$dup->no_bpkb}\""
                    ];
                }
            }
        }

        return $duplicates;
    }

    /**
     * Memindai duplikasi Sertifikat secara global.
     *
     * @return array
     */
    public function getDuplicateSertifikatList(): array
    {
        $sertifikats = ElabelSertifikat::with('opdSipat')->get();
        $duplicates = [];

        $groups = [];
        foreach ($sertifikats as $sert) {
            $noSert = trim($sert->no_sertipikat);
            if ($noSert === '') continue;

            $clean = preg_replace('/[^a-zA-Z0-9]/', '', $noSert);
            if ($clean === '') continue;

            $groups[$clean][] = $sert;
        }

        foreach ($groups as $clean => $list) {
            if (count($list) > 1) {
                $original = $list[0];
                for ($i = 1; $i < count($list); $i++) {
                    $dup = $list[$i];
                    $duplicates[] = [
                        'duplicate_sertifikat' => $dup,
                        'original_sertifikat'  => $original,
                        'reason'               => "Nomor Sertifikat identik setelah dibersihkan: \"{$dup->no_sertipikat}\""
                    ];
                }
            }
        }

        return $duplicates;
    }

    /**
     * Menggabungkan BPKB ganda secara aman.
     *
     * @param int $originalId
     * @param int $duplicateId
     * @return bool
     */
    public function mergeBpkb(int $originalId, int $duplicateId): bool
    {
        return DB::transaction(function () use ($originalId, $duplicateId) {
            $original = ElabelBpkb::find($originalId);
            $duplicate = ElabelBpkb::find($duplicateId);

            if (!$original || !$duplicate) return false;

            // 1. Pindahkan riwayat request scan / loan
            ElabelLoan::where('bpkb_id', $duplicateId)->update(['bpkb_id' => $originalId]);

            // 2. Lengkapi field yang kosong di original dari duplicate
            $fields = [
                'plate_number', 'no_bpkb', 'nibar', 'no_rangka', 'no_mesin', 
                'merek', 'tipe', 'isi_silinder', 'warna', 'pengguna', 'pdf_path', 'sipat_opd_id'
            ];

            $updated = false;
            foreach ($fields as $field) {
                if (empty($original->{$field}) && !empty($duplicate->{$field})) {
                    $original->{$field} = $duplicate->{$field};
                    $updated = true;
                }
            }

            if ($updated) {
                $original->save();
            }

            // 3. Hapus data ganda
            $duplicate->delete();

            return true;
        });
    }

    /**
     * Menggabungkan Sertifikat ganda secara aman.
     *
     * @param int $originalId
     * @param int $duplicateId
     * @return bool
     */
    public function mergeSertifikat(int $originalId, int $duplicateId): bool
    {
        return DB::transaction(function () use ($originalId, $duplicateId) {
            $original = ElabelSertifikat::find($originalId);
            $duplicate = ElabelSertifikat::find($duplicateId);

            if (!$original || !$duplicate) return false;

            // Lengkapi field yang kosong di original dari duplicate
            $fields = [
                'no_sertipikat', 'nibar', 'status_penggunaan', 'spesifikasi', 'luas', 
                'tanggal_perolehan', 'nilai_perolehan', 'nama_pemilik', 'cara_perolehan', 
                'alamat', 'lokasi', 'dinas', 'pdf_path', 'sipat_opd_id'
            ];

            $updated = false;
            foreach ($fields as $field) {
                if (empty($original->{$field}) && !empty($duplicate->{$field})) {
                    $original->{$field} = $duplicate->{$field};
                    $updated = true;
                }
            }

            if ($updated) {
                $original->save();
            }

            // Hapus data ganda
            $duplicate->delete();

            return true;
        });
    }
}
