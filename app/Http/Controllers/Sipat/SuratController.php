<?php

namespace App\Http\Controllers\Sipat;

use App\Http\Controllers\Controller;
use App\Models\Camat;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\KepalaDesa;
use App\Models\Pemohon;
use App\Models\SuratSkpt;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use App\Http\Requests\Sipat\StoreSuratSkptRequest;

class SuratController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function skpt(Request $request): View
    {
        $filterKecamatan = $request->get('kecamatan_id');
        
        $recentQuery = SuratSkpt::with(['desa.kecamatan', 'pemohon'])
            ->orderBy('id', 'DESC')
            ->limit(10);
            
        if ($filterKecamatan) {
            $recentQuery->whereHas('desa.kecamatan', function($q) use ($filterKecamatan) {
                $q->where('id', $filterKecamatan);
            });
        }
        
        $recent = $recentQuery->get();

        return view('sipat.surat.skpt', [
            'title' => 'Generate SKPT',
            'skpt' => null,
            'recent' => $recent,
            'filterKecamatan' => $filterKecamatan,
            'kecamatanList' => Kecamatan::orderBy('nama', 'ASC')->get(),
            'desaList' => Desa::orderBy('nama', 'ASC')->get(),
            'kepalaList' => KepalaDesa::where('aktif', 1)->orderBy('nama', 'ASC')->get(),
            'camatList' => Camat::where('aktif', 1)->orderBy('nama', 'ASC')->get(),
            'pemohonList' => Pemohon::orderBy('nama', 'ASC')->get(),
        ]);
    }

    public function showSkpt(Request $request, SuratSkpt $skpt): View
    {
        $skpt->load(['desa.kecamatan', 'kepalaDesa', 'camat', 'pemohon']);

        $filterKecamatan = $request->get('kecamatan_id');
        $recentQuery = SuratSkpt::with(['desa.kecamatan', 'pemohon'])
            ->orderBy('id', 'DESC')
            ->limit(10);
            
        if ($filterKecamatan) {
            $recentQuery->whereHas('desa.kecamatan', function($q) use ($filterKecamatan) {
                $q->where('id', $filterKecamatan);
            });
        }
        
        $recent = $recentQuery->get();

        return view('sipat.surat.skpt', [
            'title' => 'Generate SKPT',
            'skpt' => $skpt,
            'recent' => $recent,
            'filterKecamatan' => $filterKecamatan,
            'kecamatanList' => Kecamatan::orderBy('nama', 'ASC')->get(),
            'desaList' => Desa::orderBy('nama', 'ASC')->get(),
            'kepalaList' => KepalaDesa::where('aktif', 1)->orderBy('nama', 'ASC')->get(),
            'camatList' => Camat::where('aktif', 1)->orderBy('nama', 'ASC')->get(),
            'pemohonList' => Pemohon::orderBy('nama', 'ASC')->get(),
        ]);
    }

    public function deleteSkpt(SuratSkpt $skpt): RedirectResponse
    {
        $skpt->delete();
        return redirect()->route('sipat.surat.skpt')->with('success', 'Data SKPT berhasil dihapus.');
    }

    public function printSkpt(SuratSkpt $skpt): View|RedirectResponse
    {
        $skpt->load(['desa.kecamatan', 'kepalaDesa', 'camat', 'pemohon']);

        return view('sipat.surat.skpt_print', [
            'title' => 'Cetak SKPT',
            'skpt' => $skpt,
        ]);
    }

    public function pdfSkpt(SuratSkpt $skpt): Response|RedirectResponse
    {
        $skpt->load(['desa.kecamatan', 'kepalaDesa', 'camat', 'pemohon']);

        if (!class_exists(\Mpdf\Mpdf::class)) {
            return redirect()->route('sipat.surat.showSkpt', $skpt->id)->with('error', 'PDF belum tersedia (library mPDF belum terpasang).');
        }

        $html = view('sipat.surat.skpt_pdf', ['skpt' => $skpt])->render();
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
            'margin_right' => 12,
        ]);
        $mpdf->WriteHTML($html);

        $filename = 'SKPT_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $skpt->nomor_surat ?? $id) . '.pdf';
        
        return response($mpdf->Output($filename, 'S'))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportWordSkpt(SuratSkpt $skpt): Response|RedirectResponse
    {
        $skpt->load(['desa.kecamatan', 'kepalaDesa', 'camat', 'pemohon']);

        if (!class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
            $html = view('sipat.surat.skpt_word', ['skpt' => $skpt])->render();
            $filename = 'SKPT_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $skpt->nomor_surat ?? $skpt->id) . '.doc';
            return response($html)
                ->header('Content-Type', 'application/msword')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection([
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
        ]);

        $bold = ['bold' => true];
        $center = ['alignment' => 'center'];
        $alamatKantor = trim($skpt->alamat_kantor ?? '');
        $jenisTanah = trim($skpt->jenis_tanah ?? '');
        if ($jenisTanah === '') {
            $jenisTanah = 'Pekarangan dan Bangunan';
        }
        $statusTanah = trim($skpt->status_tanah ?? '');
        if ($statusTanah === '') {
            $statusTanah = 'tanah yang dikuasai oleh negara (bekas tanah Swapraja)';
        }
        $lokasiTanah = trim($skpt->lokasi_tanah ?? '');
        $lokasiText = $lokasiTanah !== '' ? $lokasiTanah . ' ' : '';
        $asalTanah = trim($skpt->asal_tanah ?? '');
        if ($asalTanah === '') {
            $asalTanah = 'Selanjutnya diterangkan bahwa bidang tanah tersebut berasal dari tanah negara yang dibuka langsung dan dikuasai oleh …………………………... pada tahun ………... kemudian tanah tersebut diserahkan/beralih kepada Pemerintah Kabupaten Donggala secara ' . ($skpt->dasar_perolehan ?? 'Jual Beli tanpa surat-surat') . ' pada tahun ………';
        }
        $pernyataanTanah = trim($skpt->pernyataan_tanah ?? '');
        if ($pernyataanTanah === '') {
            $pernyataanTanah = 'Bahwa tanah tersebut merupakan tanah Non Pertanian milik Pemerintah Kabupaten Donggala serta pihak lain tidak ada yang keberatan/tidak dalam sengketa.';
        }

        $section->addText('PEMERINTAH KABUPATEN DONGGALA', $bold, $center);
        $desaJenisRaw = strtolower(trim($skpt->desa->jenis ?? ''));
        $desaLabel = $desaJenisRaw === 'kelurahan' ? 'Kelurahan' : 'Desa';
        $desaLabelUpper = strtoupper($desaLabel);
        $pejabatLabel = $desaLabel === 'Kelurahan' ? 'Lurah' : 'Kepala Desa';

        $section->addText('KECAMATAN ' . strtoupper($skpt->desa->kecamatan->nama ?? '-'), $bold, $center);
        $section->addText($desaLabelUpper . ' ' . strtoupper($skpt->desa->nama ?? '-'), $bold, $center);
        if ($alamatKantor !== '') {
            $section->addText('Alamat : ' . $alamatKantor, null, $center);
        }
        $section->addText('SURAT KETERANGAN PENGUASAAN TANAH', $bold, $center);
        $section->addText('NOMOR : ' . ($skpt->nomor_surat ?? '-'), null, $center);

        $section->addTextBreak(1);
        $section->addText(
            'Yang bertanda tangan di Bawah ini ' . $pejabatLabel . ' ' .
            ($skpt->desa->nama ?? '-') .
            ' Kecamatan ' . ($skpt->desa->kecamatan->nama ?? '-') .
            ' Kabupaten Donggala Provinsi Sulawesi Tengah menerangkan dengan sebenarnya bahwa:'
        );

        $identity = $section->addTable();
        $rows = [
            ['Nama', $skpt->pemohon->nama ?? '-'],
            ['NIK', $skpt->pemohon->nik ?? '-'],
            ['TTL', $skpt->pemohon->ttl ?? '-'],
            ['Umur', $skpt->pemohon->umur ?? '-'],
            ['Warga Negara', $skpt->pemohon->warga_negara ?? '-'],
            ['Pekerjaan', $skpt->pemohon->pekerjaan ?? '-'],
            ['Jabatan', $skpt->pemohon->jabatan ?? '-'],
            ['Alamat', $skpt->pemohon->alamat ?? '-'],
        ];
        foreach ($rows as $row) {
            $identity->addRow();
            $identity->addCell(2500)->addText($row[0]);
            $identity->addCell(300)->addText(':');
            $identity->addCell(6000)->addText($row[1]);
        }

        $section->addTextBreak(1);
        $section->addText(
            'Benar mengusahakan / Menggarap / Menggunakan dan atau menguasai sebidang tanah ' .
            $jenisTanah .
            ' dengan status tanah ' . $statusTanah .
            ' seluas ' . ($skpt->luas_tanah ?? '-') . ' M2 yang terletak di ' . $lokasiText . $desaLabel . ' ' .
            ($skpt->desa->nama ?? '-') . ' Kecamatan ' . ($skpt->desa->kecamatan->nama ?? '-') .
            ' dengan batas-batas sebagai berikut :'
        );

        $section->addTextBreak(1);
        $borders = $section->addTable();
        $batasRows = [
            ['Sebelah Utara', $skpt->batas_utara ?? '-'],
            ['Sebelah Timur', $skpt->batas_timur ?? '-'],
            ['Sebelah Selatan', $skpt->batas_selatan ?? '-'],
            ['Sebelah Barat', $skpt->batas_barat ?? '-'],
        ];
        foreach ($batasRows as $row) {
            $borders->addRow();
            $borders->addCell(2500)->addText($row[0]);
            $borders->addCell(300)->addText(':');
            $borders->addCell(6000)->addText($row[1]);
        }

        $section->addTextBreak(1);
        $section->addText($asalTanah);
        $section->addTextBreak(1);
        $section->addText($pernyataanTanah);
        $section->addTextBreak(1);
        $section->addText('Demikian surat keterangan penguasaan tanah ini dibuat dengan sebenarnya untuk dipergunakan sebagaimana mestinya dan mengingat sumpah jabatan.');
        if (!empty($skpt->keterangan)) {
            $section->addText('Keterangan: ' . $skpt->keterangan);
        }
        $section->addTextBreak(1);
        $section->addText('Tanggal, ' . ($skpt->tanggal_surat ?? '-'));

        $section->addTextBreak(2);
        $table = $section->addTable();
        $table->addRow();
        $table->addCell(4500)->addText('Mengetahui,');
        $table->addCell(4500)->addText($pejabatLabel . ' ' . ($skpt->desa->nama ?? '-'));
        $table->addRow();
        $table->addCell(4500)->addText('Camat ' . ($skpt->desa->kecamatan->nama ?? '-'));
        $table->addCell(4500)->addText('');
        $table->addRow();
        $table->addCell(4500)->addText('');
        $table->addCell(4500)->addText('');
        $table->addRow();
        $camatNip = !empty($skpt->camat->nip) ? 'NIP. ' . $skpt->camat->nip : '';
        $kepalaNip = !empty($skpt->kepalaDesa->nip) ? 'NIP. ' . $skpt->kepalaDesa->nip : '';
        $table->addCell(4500)->addText(trim(($skpt->camat->nama ?? '-') . PHP_EOL . $camatNip));
        $table->addCell(4500)->addText(trim(($skpt->kepalaDesa->nama ?? '-') . PHP_EOL . $kepalaNip));

        $filename = 'SKPT_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $skpt->nomor_surat ?? $skpt->id) . '.docx';

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function storeSkpt(StoreSuratSkptRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['kecamatan_id']);

        $nomor = trim((string) ($data['nomor_surat'] ?? ''));
        if ($nomor === '') {
            $nomor = 'SKPT-' . date('Ymd') . '-' . random_int(1000, 9999);
        }

        $data['nomor_surat'] = $nomor;

        $skpt = SuratSkpt::create($data);

        return redirect()->route('sipat.surat.showSkpt', $skpt->id)->with('success', 'Data SKPT tersimpan.');
    }

    public function pernyataanBatas(): View
    {
        return view('sipat.surat.pernyataan_batas', [
            'title' => 'Generate Pernyataan Batas',
        ]);
    }
}
