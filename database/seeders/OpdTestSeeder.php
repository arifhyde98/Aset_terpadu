<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Opd;

class OpdTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Membuat 500 data OPD dummy untuk pengujian pengosongan
        for ($i = 1; $i <= 500; $i++) {
            Opd::firstOrCreate([
                'nama' => "OPD Uji Coba $i",
                'singkatan' => "OPD_TEST_$i",
                'alamat' => "Alamat Kantor Uji Coba Ke-$i"
            ]);
        }
    }
}
