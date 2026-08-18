<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KopSettingsController extends Controller
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
        'kop_pejabat_jabatan' => 'Kepala Bidang Pengelolaan Aset Daerah',
        'kop_pejabat_nama' => 'H. MUHAMMAD NATSIR, S.E., M.Si.',
        'kop_pejabat_nip' => 'NIP. 19780512 200501 1 008',
    ];

    public function index()
    {
        $settings = $this->getSettingsMap();
        $defaults = self::DEFAULTS;

        return view('master.kop_settings.index', compact('settings', 'defaults'));
    }

    public function update(Request $request)
    {
        foreach (self::TEXT_FIELDS as $field) {
            $val = $request->input($field, '');
            $this->saveSetting($field, $val);
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
