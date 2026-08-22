<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelBpkbDelete;
use App\Models\Elabel\ElabelSertifikat;
use App\Models\Elabel\ElabelSuratPenyerahan;
use App\Models\Elabel\ElabelLoan;
use App\Models\OpdSipat;

class MigrateElabelOpdCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elabel:migrate-opd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mencocokkan nama dinas/pengguna lama di eLABEL dengan tabel opd SIPAT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi OPD eLABEL ke tabel master SIPAT (opd)...');

        // Mengambil semua OPD SIPAT dan memetakan nama menjadi case-insensitive key
        $opds = OpdSipat::all();
        $opdMap = [];
        foreach ($opds as $opd) {
            $key = strtolower(trim($opd->nama));
            $opdMap[$key] = $opd->id;
        }

        // Kamus pemetaan alias untuk nama singkatan/pendek ke nama resmi SIPAT
        $aliases = [
            'dinas pendidikan' => 'Dinas Pendidikan dan Kebudayaan',
            'setda' => 'Sekretariat Daerah',
            'sek. dprd' => 'Sekretariat DPRD',
            'dukcapil' => 'Dinas Kependudukan Dan Catatan Sipil',
            'pol pp' => 'Kantor Satuan Polisi Pamong Praja',
            'dinas pu' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
            'bpkad' => 'Badan Pengelolaan Keuangan dan Aset Daerah',
            'dinas perumahan' => 'Dinas Perumahan, Kawasan Permukiman dan Pertanahan',
            'dlh' => 'Dinas Lingkungan Hidup Daerah',
            'kesbangpol' => 'Badan Kesatuan Bangsa dan Politik',
            'pmd' => 'Dinas Pemberdayaan Masyarakat dan Desa',
            'dp3a' => 'Dinas Pemberdayaan Perempuan dan Perlindungan Anak',
            'dpm-ptsp' => 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu',
            'naketrans' => 'Dinas Tenaga Kerja dan Transmigrasi',
            'dinas kb' => 'Dinas Pengendalian Penduduk dan Keluarga Berencana',
            'rsud kabelota' => 'RSUD Kabelota Donggala',
            'rsud pendau' => 'RSUD Pendau Tambu',
            'banawa' => 'Kecamatan Banawa',
            'banawa tengah' => 'Kecamatan Banawa Tengah',
            'dampelas' => 'Kecamatan Dampelas',
            'sindue' => 'Kecamatan Sindue',
            'sindue tombusabora' => 'Kecamatan Sindue Tombusabora',
            'sirenja' => 'Kecamatan Sirenja',
            'sojol' => 'Kecamatan Sojol',
            'balaesang' => 'Kecamatan Balaesang',
        ];

        // Menerapkan kamus alias ke opdMap
        foreach ($aliases as $short => $long) {
            $longKey = strtolower(trim($long));
            if (isset($opdMap[$longKey])) {
                $opdMap[strtolower(trim($short))] = $opdMap[$longKey];
            }
        }

        $this->info('Jumlah data OPD SIPAT terdeteksi: ' . count($opds));

        // 1. ElabelSertifikat
        $sertifikats = ElabelSertifikat::whereNull('sipat_opd_id')->whereNotNull('dinas')->get();
        $matchedSertifikat = 0;
        foreach ($sertifikats as $item) {
            $key = strtolower(trim($item->dinas));
            if (isset($opdMap[$key])) {
                $item->sipat_opd_id = $opdMap[$key];
                $item->save();
                $matchedSertifikat++;
            }
        }
        $this->info("Sertifikat Tanah: berhasil memetakan {$matchedSertifikat} dari " . $sertifikats->count() . " data.");

        // 2. ElabelSuratPenyerahan
        $surats = ElabelSuratPenyerahan::whereNull('sipat_opd_id')->whereNotNull('dinas')->get();
        $matchedSurat = 0;
        foreach ($surats as $item) {
            $key = strtolower(trim($item->dinas));
            if (isset($opdMap[$key])) {
                $item->sipat_opd_id = $opdMap[$key];
                $item->save();
                $matchedSurat++;
            }
        }
        $this->info("Surat Penyerahan: berhasil memetakan {$matchedSurat} dari " . $surats->count() . " data.");

        // 3. ElabelLoan
        $loans = ElabelLoan::whereNull('sipat_opd_id')->whereNotNull('requester_org')->get();
        $matchedLoan = 0;
        foreach ($loans as $item) {
            $key = strtolower(trim($item->requester_org));
            if (isset($opdMap[$key])) {
                $item->sipat_opd_id = $opdMap[$key];
                $item->save();
                $matchedLoan++;
            }
        }
        $this->info("Peminjaman BPKB: berhasil memetakan {$matchedLoan} dari " . $loans->count() . " data.");

        $this->info('Sinkronisasi selesai!');
    }
}
