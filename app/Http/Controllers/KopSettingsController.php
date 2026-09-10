<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KopSettingsController extends Controller implements HasMiddleware
{
    private const TEXT_FIELDS = [
        'kop_nama_instansi',
        'kop_nama_unit',
        'kop_subunit',
        'kop_alamat',
        'kop_kontak',
        'kop_nama_laporan_aset',
        'kop_footer',
        'kop_kota_ttd',
        // Penanda Tangan 1 (Kiri - Pejabat Bidang / Teknis)
        'kop_pejabat1_jabatan',
        'kop_pejabat1_nama',
        'kop_pejabat1_nip',
        // Penanda Tangan 2 (Kanan - Kepala Badan / Pengesah)
        'kop_pejabat2_jabatan',
        'kop_pejabat2_nama',
        'kop_pejabat2_nip',
        // Legacy Fallback
        'kop_pejabat_jabatan',
        'kop_pejabat_nama',
        'kop_pejabat_nip',
    ];

    private const DEFAULTS = [
        'kop_nama_instansi' => 'PEMERINTAH KABUPATEN DONGGALA',
        'kop_nama_unit' => 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH',
        'kop_subunit' => 'Bidang Pengelolaan Aset Daerah',
        'kop_alamat' => 'Jl. Trans Sulawesi, Banawa, Kabupaten Donggala, Sulawesi Tengah',
        'kop_kontak' => 'Email: bpkad@donggalakab.go.id | Web: sipat.donggalakab.go.id',
        'kop_logo' => '',
        'kop_nama_laporan_aset' => 'LAPORAN REKAPITULASI ASET TANAH',
        'kop_footer' => 'Dokumen ini dihasilkan resmi oleh Aplikasi SIPAT Terpadu.',
        'kop_kota_ttd' => 'Banawa',
        // Penanda Tangan 1 (Kiri - Pejabat Bidang)
        'kop_pejabat1_jabatan' => 'KEPALA BIDANG ASET DAERAH',
        'kop_pejabat1_nama' => 'YENI SJ AMIR, SH.MSi',
        'kop_pejabat1_nip' => 'NIP.',
        // Penanda Tangan 2 (Kanan - Kepala Badan)
        'kop_pejabat2_jabatan' => 'KEPALA BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH',
        'kop_pejabat2_nama' => 'YENI SJ AMIR, SH.MSi',
        'kop_pejabat2_nip' => 'NIP.',
        // Legacy
        'kop_pejabat_jabatan' => 'KEPALA BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH',
        'kop_pejabat_nama' => 'YENI SJ AMIR, SH.MSi',
        'kop_pejabat_nip' => 'NIP.',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin'),
        ];
    }

    public function index()
    {
        $settings = $this->getSettingsMap();
        $defaults = self::DEFAULTS;

        return view('master.kop_settings.index', compact('settings', 'defaults'));
    }

    public function update(Request $request)
    {
        foreach (self::TEXT_FIELDS as $field) {
            if ($request->has($field)) {
                $val = $request->input($field, '');
                $this->saveSetting($field, $val);
            }
        }

        // Sinkronkan legacy kop_pejabat_* dengan penanda tangan 2 jika ada
        if ($request->has('kop_pejabat2_jabatan')) {
            $this->saveSetting('kop_pejabat_jabatan', (string)$request->input('kop_pejabat2_jabatan'));
        }
        if ($request->has('kop_pejabat2_nama')) {
            $this->saveSetting('kop_pejabat_nama', (string)$request->input('kop_pejabat2_nama'));
        }
        if ($request->has('kop_pejabat2_nip')) {
            $this->saveSetting('kop_pejabat_nip', (string)$request->input('kop_pejabat2_nip'));
        }

        if ($request->hasFile('kop_logo')) {
            $request->validate([
                'kop_logo' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            ]);

            $path = $request->file('kop_logo')->store('kop', 'public');
            $this->saveSetting('kop_logo', $path);
        }

        return redirect()->back()->with('success', 'Master KOP Surat Pemda berhasil diperbarui.');
    }

    private function getSettingsMap(): array
    {
        $map = self::DEFAULTS;

        if (Schema::hasTable('settings')) {
            $rows = DB::table('settings')->whereIn('key', array_keys(self::DEFAULTS))->get();
            foreach ($rows as $row) {
                $val = trim($row->value ?? '');
                if ($val !== '') {
                    $map[$row->key] = $val;
                }
            }
        }

        // Backward compatibility fallback
        if (empty($map['kop_pejabat2_jabatan']) && !empty($map['kop_pejabat_jabatan'])) {
            $map['kop_pejabat2_jabatan'] = $map['kop_pejabat_jabatan'];
        }
        if (empty($map['kop_pejabat2_nama']) && !empty($map['kop_pejabat_nama'])) {
            $map['kop_pejabat2_nama'] = $map['kop_pejabat_nama'];
        }
        if (empty($map['kop_pejabat2_nip']) && !empty($map['kop_pejabat_nip'])) {
            $map['kop_pejabat2_nip'] = $map['kop_pejabat_nip'];
        }

        return $map;
    }

    private function saveSetting(string $key, string $value): void
    {
        if (!Schema::hasTable('settings')) return;

        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => $value,
                'updated_at' => now(),
            ]
        );
    }
}
