<?php

namespace App\Http\Controllers;

use App\Models\ReportLetterhead;
use App\Models\ReportSignatory;
use App\Models\ReportExportSetting;
use App\Reports\ReportRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller Manajemen Pengaturan Cetak Laporan (ReportSettingController)
 * 
 * Mengelola Kop Surat, pejabat penanda tangan, dan orientasi kertas dinamis
 * khusus untuk diakses oleh Superadmin.
 */
class ReportSettingController extends Controller implements HasMiddleware
{
    /**
     * Mendapatkan middleware yang ditugaskan ke controller ini.
     * 
     * @return array
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('role:superadmin,admin'),
        ];
    }

    /**
     * Menampilkan halaman dashboard konfigurasi dokumen laporan.
     * 
     * @return View
     */
    public function index(): View
    {
        $letterhead = ReportLetterhead::where('is_active', true)->orderBy('is_default', 'desc')->first() 
            ?? new ReportLetterhead();
            
        $signatory = ReportSignatory::where('is_active', true)->orderBy('is_default', 'desc')->first() 
            ?? new ReportSignatory();

        $reportTypes = app(ReportRegistry::class)->getSupportedTypes();
        $exportSettings = ReportExportSetting::whereIn('report_type', array_keys($reportTypes))
            ->get()
            ->keyBy('report_type');

        return view('reports.settings', compact('letterhead', 'signatory', 'exportSettings', 'reportTypes'));
    }

    /**
     * Mengubah data Kop Surat instansi.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateLetterhead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_pemerintah' => 'required|string|max:255',
            'nama_instansi'   => 'required|string|max:255',
            'nama_unit'       => 'nullable|string|max:255',
            'alamat'          => 'required|string',
            'telepon'         => 'nullable|string|max:100',
            'email'           => 'nullable|email|max:100',
            'website'         => 'nullable|string|max:100',
            'logo'            => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $letterhead = ReportLetterhead::where('is_active', true)->orderBy('is_default', 'desc')->first();
        if (!$letterhead) {
            $letterhead = new ReportLetterhead();
            $letterhead->is_active = true;
            $letterhead->is_default = true;
        }

        if ($request->hasFile('logo')) {
            // Hapus berkas lama jika ada
            if ($letterhead->logo_path && File::exists(public_path($letterhead->logo_path)) && !Str::contains($letterhead->logo_path, 'logo-sulteng.png')) {
                File::delete(public_path($letterhead->logo_path));
            }

            $directory = public_path('uploads/report/logo');
            File::ensureDirectoryExists($directory);

            $file = $request->file('logo');
            $filename = 'logo_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $filename);

            $letterhead->logo_path = 'uploads/report/logo/' . $filename;
        }

        $letterhead->nama_pemerintah = $validated['nama_pemerintah'];
        $letterhead->nama_instansi = $validated['nama_instansi'];
        $letterhead->nama_unit = $validated['nama_unit'];
        $letterhead->alamat = $validated['alamat'];
        $letterhead->telepon = $validated['telepon'];
        $letterhead->email = $validated['email'];
        $letterhead->website = $validated['website'];
        $letterhead->save();

        // Selaraskan seluruh pengaturan ekspor laporan agar mengarah ke Kop Surat aktif
        ReportExportSetting::query()->update(['letterhead_id' => $letterhead->id]);

        return redirect()->back()->with('success', 'Kop Surat laporan berhasil diperbarui dan diselaraskan ke seluruh jenis laporan.');
    }

    /**
     * Mengubah data Pejabat Penanda Tangan laporan.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateSignatory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama'             => 'required|string|max:255',
            'jabatan'          => 'required|string|max:255',
            'nip'              => 'nullable|string|max:100',
            'pangkat_golongan' => 'nullable|string|max:100',
            'kota_ttd'         => 'required|string|max:100',
            'signature_image'  => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);

        $signatory = ReportSignatory::where('is_active', true)->orderBy('is_default', 'desc')->first();
        if (!$signatory) {
            $signatory = new ReportSignatory();
            $signatory->is_active = true;
            $signatory->is_default = true;
        }

        if ($request->hasFile('signature_image')) {
            // Hapus berkas tanda tangan lama
            if ($signatory->signature_image_path && File::exists(public_path($signatory->signature_image_path))) {
                File::delete(public_path($signatory->signature_image_path));
            }

            $directory = public_path('uploads/report/signature');
            File::ensureDirectoryExists($directory);

            $file = $request->file('signature_image');
            $filename = 'sig_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->move($directory, $filename);

            $signatory->signature_image_path = 'uploads/report/signature/' . $filename;
        }

        $signatory->nama = $validated['nama'];
        $signatory->jabatan = $validated['jabatan'];
        $signatory->nip = $validated['nip'];
        $signatory->pangkat_golongan = $validated['pangkat_golongan'];
        $signatory->kota_ttd = $validated['kota_ttd'];
        $signatory->save();

        // Selaraskan seluruh pengaturan ekspor laporan agar mengarah ke Pejabat aktif
        ReportExportSetting::query()->update(['signatory_id' => $signatory->id]);

        return redirect()->back()->with('success', 'Data pejabat penanda tangan berhasil diperbarui dan diselaraskan ke seluruh jenis laporan.');
    }

    /**
     * Memperbarui aturan ekspor per jenis laporan.
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateExportSetting(Request $request): RedirectResponse
    {
        $reportTypes = array_keys(app(ReportRegistry::class)->getSupportedTypes());

        $validated = $request->validate([
            'report_type'    => ['required', Rule::in($reportTypes)],
            'paper_size'     => 'required|in:A4,F4,Letter,Legal',
            'orientation'    => 'required|in:L,P',
            'show_summary'   => 'nullable|boolean',
            'show_signature' => 'nullable|boolean',
        ]);

        $letterhead = ReportLetterhead::where('is_active', true)->orderBy('is_default', 'desc')->first();
        $signatory = ReportSignatory::where('is_active', true)->orderBy('is_default', 'desc')->first();

        if (!$letterhead || !$signatory) {
            return redirect()->back()->with('error', 'Kop Surat dan Pejabat penanda tangan wajib dikonfigurasi terlebih dahulu sebelum mengatur jenis laporan.');
        }

        ReportExportSetting::updateOrCreate(
            ['report_type' => $validated['report_type']],
            [
                'letterhead_id'  => $letterhead->id,
                'signatory_id'   => $signatory->id,
                'paper_size'     => $validated['paper_size'],
                'orientation'    => $validated['orientation'],
                'show_summary'   => $request->has('show_summary') ? true : false,
                'show_signature' => $request->has('show_signature') ? true : false,
            ]
        );

        return redirect()->back()->with('success', 'Konfigurasi ekspor tipe laporan berhasil diperbarui.');
    }
}
