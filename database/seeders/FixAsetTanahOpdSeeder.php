<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixAsetTanahOpdSeeder extends Seeder
{
    /**
     * Run the database seeds for fixing land asset (aset_tanah) OPD mapping & strings.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // 1. Perbaiki aset NULL opd_id berdasarkan peruntukan / nama_aset
            // Dinas Kesehatan (id: 5) - Koreksi 140 aset tanah yang tertukar di Dinas Sosial (id: 223)
            DB::table('aset_tanah')
                ->where('opd_id', 223)
                ->where(function ($q) {
                    $q->where('opd', 'LIKE', '%Kesehatan%')
                      ->orWhere('peruntukan', 'LIKE', '%Puskesmas%')
                      ->orWhere('peruntukan', 'LIKE', '%Pustu%')
                      ->orWhere('peruntukan', 'LIKE', '%Poskesdes%');
                })
                ->update([
                    'opd_id' => 5,
                    'opd' => 'DINAS KESEHATAN'
                ]);

            // Dinas Pendidikan dan Kebudayaan (id: 217)
            DB::table('aset_tanah')
                ->whereIn('id_aset', [10, 19, 307, 449, 1213, 1214])
                ->update([
                    'opd_id' => 217,
                    'opd' => 'DINAS PENDIDIKAN DAN KEBUDAYAAN'
                ]);

            // Dinas Perhubungan (id: 15)
            DB::table('aset_tanah')
                ->whereIn('id_aset', [898, 900])
                ->whereNull('opd_id')
                ->update([
                    'opd_id' => 15,
                    'opd' => 'DINAS PERHUBUNGAN'
                ]);

            // Dinas Perikanan (id: 7)
            DB::table('aset_tanah')
                ->whereIn('id_aset', [957])
                ->whereNull('opd_id')
                ->update([
                    'opd_id' => 7,
                    'opd' => 'DINAS PERIKANAN'
                ]);

            // Sekretariat Daerah (id: 2)
            DB::table('aset_tanah')
                ->whereIn('id_aset', [1064])
                ->whereNull('opd_id')
                ->update([
                    'opd_id' => 2,
                    'opd' => 'SEKRETARIAT DAERAH'
                ]);

            // BPKAD / Badan Pengelolaan Keuangan dan Aset Daerah (id: 2327) untuk aset umum / belum teralokasi khusus
            DB::table('aset_tanah')
                ->whereNull('opd_id')
                ->update([
                    'opd_id' => 2327,
                    'opd' => 'BADAN PENGELOLAAN KEUANGAN DAN ASET DAERAH'
                ]);

            // 2. Koreksi sub_opd_id Kelurahan Ganti (id_aset: 290)
            DB::table('aset_tanah')
                ->where('id_aset', 290)
                ->update([
                    'opd_id' => 31, // Kecamatan Banawa
                    'sub_opd_id' => 10, // KANTOR KELURAHAN GANTI
                    'opd' => 'Kecamatan Banawa'
                ]);

            // 3. Harmonisasi 100% kolom string `aset_tanah.opd` dengan nama resmi dari `opds.nama`
            $records = DB::table('aset_tanah as a')
                ->join('opds as o', 'a.opd_id', '=', 'o.id')
                ->select('a.id_aset', 'o.nama as opd_nama')
                ->get();

            foreach ($records as $r) {
                DB::table('aset_tanah')
                    ->where('id_aset', $r->id_aset)
                    ->update(['opd' => $r->opd_nama]);
            }
        });
    }
}
