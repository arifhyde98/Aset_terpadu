<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateOpdSingkatanSeeder extends Seeder
{
    /**
     * Run the database seeds to populate OPD abbreviations (singkatan).
     */
    public function run(): void
    {
        $singkatanMap = [
            2    => 'SETDA',
            5    => 'DINKES',
            6    => 'DKP',
            7    => 'DISKAN',
            10   => 'SETWAN',
            12   => 'INSPEKTORAT',
            13   => 'DISDIKPORA',
            15   => 'DISHUB',
            17   => 'BAPPERIDA',
            18   => 'DINSOS-PMD',
            19   => 'DISPAR',
            21   => 'DISTAN',
            23   => 'DLH-P2KP',
            26   => 'DP2KB',
            27   => 'DISNAKERTRANS',
            31   => 'KEC. BANAWA',
            35   => 'KEC. PINEMBANI',
            207  => 'BAPENDA',
            209  => 'UPPB',
            211  => 'DISDUKCAPIL',
            213  => 'DLHD',
            214  => 'PUPR',
            215  => 'DPMD',
            216  => 'DISPORA',
            217  => 'DISDIKBUD',
            218  => 'DPPKB',
            219  => 'DISPERINDAG',
            220  => 'DISPERPUS',
            222  => 'DISNAKKESWAN',
            223  => 'DINSOS',
            225  => 'DISNAKERTRANS',
            243  => 'KEC. RIOPAKAVA',
            244  => 'KEC. SIRENJA',
            245  => 'KEC. SOJOL UTARA',
            246  => 'KEC. SOJOL',
            247  => 'KEC. TANANTOVEA',
            248  => 'SATPOL PP',
            514  => 'DISKOMINFO',
            516  => 'DP3A',
            517  => 'DPMPTSP',
            518  => 'KEC. SINDUE TOBATA',
            525  => 'BPBD',
            526  => 'KESBANGPOL',
            533  => 'KEC. SINDUE TOMBUSABORA',
            2062 => 'BAPPEDA',
            2063 => 'KEC. BALAESANG',
            2064 => 'KEC. DAMPELAS',
            2065 => 'KEC. BANAWA SELATAN',
            2066 => 'KEC. SINDUE',
            2316 => 'RSUD KABELOTA',
            2317 => 'RSUD PENDAU TAMBU',
            2320 => 'DISARSIP',
            2327 => 'BPKAD',
            2329 => 'BALITBANG',
            2339 => 'DISPERKIMTAN',
            2341 => 'DISKOP UMKM',
            2342 => 'DINAS TPHP',
            2343 => 'BKPSDM',
            2350 => 'KEC. BANAWA TENGAH',
            2355 => 'KEC. LABUAN',
            2357 => 'KEC. BALAESANG TANJUNG',
        ];

        foreach ($singkatanMap as $id => $singkatan) {
            DB::table('opds')
                ->where('id', $id)
                ->update(['singkatan' => $singkatan]);
        }
    }
}
