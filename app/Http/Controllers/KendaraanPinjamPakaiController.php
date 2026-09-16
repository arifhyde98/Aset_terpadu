<?php

namespace App\Http\Controllers;

use App\Models\KendaraanPinjamPakai;
use App\Models\Vehicle;
use App\Models\Opd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class KendaraanPinjamPakaiController extends Controller
{
    /**
     * Tampilkan daftar kendaraan pinjam pakai
     */
    public function index(Request $request)
    {
        $query = KendaraanPinjamPakai::with(['vehicle', 'opd', 'creator']);

        // Search Filter
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('nama_instansi_peminjam', 'like', "%{$search}%")
                  ->orWhere('nama_penanggung_jawab', 'like', "%{$search}%")
                  ->orWhere('nomor_nppp_bast', 'like', "%{$search}%")
                  ->orWhereHas('vehicle', function ($qv) use ($search) {
                      $qv->where('no_polisi', 'like', "%{$search}%")
                         ->orWhere('merk', 'like', "%{$search}%")
                         ->orWhere('tipe', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Kategori
        if ($request->filled('kategori')) {
            $query->where('kategori_peminjam', $request->kategori);
        }

        // Filter Status
        if ($request->filled('status')) {
            if ($request->status === 'jatuh_tempo') {
                $query->where('status_perjanjian', '!=', 'Selesai / Dikembalikan')
                      ->where('tanggal_selesai', '<=', Carbon::now()->addDays(30));
            } else {
                $query->where('status_perjanjian', $request->status);
            }
        }

        $pinjamPakais = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Stats summary
        $totalPinjamPakai = KendaraanPinjamPakai::count();
        $totalAktif = KendaraanPinjamPakai::where('status_perjanjian', 'Aktif')->count();
        $totalJatuhTempo = KendaraanPinjamPakai::where('status_perjanjian', '!=', 'Selesai / Dikembalikan')
            ->where('tanggal_selesai', '<=', Carbon::now()->addDays(30))
            ->count();
        $totalSelesai = KendaraanPinjamPakai::whereIn('status_perjanjian', ['Selesai', 'Selesai / Dikembalikan'])->count();

        return view('vehicles.pinjam_pakai.index', compact(
            'pinjamPakais',
            'totalPinjamPakai',
            'totalAktif',
            'totalJatuhTempo',
            'totalSelesai'
        ));
    }

    /**
     * Form tambah kendaraan pinjam pakai baru
     */
    public function create()
    {
        $vehicles = Vehicle::orderBy('merk', 'asc')->orderBy('tipe', 'asc')->get();
        $opds = Opd::orderBy('nama', 'asc')->get();

        return view('vehicles.pinjam_pakai.create', compact('vehicles', 'opds'));
    }

    /**
     * Simpan data pinjam pakai baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'opd_id' => 'nullable|exists:opds,id',
            'kategori_peminjam' => 'required|string|in:Instansi Vertikal,Antar OPD,Lainnya',
            'nama_instansi_peminjam' => 'required|string|max:255',
            'nama_penanggung_jawab' => 'required|string|max:255',
            'nip_penanggung_jawab' => 'nullable|string|max:50',
            'jabatan_penanggung_jawab' => 'nullable|string|max:150',
            'kontak_penanggung_jawab' => 'nullable|string|max:50',
            'nomor_nppp_bast' => 'required|string|max:100',
            'tanggal_nppp_bast' => 'nullable|date',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'file_dokumen_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'keterangan' => 'nullable|string',
        ]);

        if ($request->hasFile('file_dokumen_pdf')) {
            $validated['file_dokumen_pdf'] = $request->file('file_dokumen_pdf')->store('dokumen_pinjam_pakai', 'public');
        }

        $validated['created_by'] = auth()->id();
        $validated['status_perjanjian'] = 'Aktif';

        // Auto opd_id from vehicle if null
        if (empty($validated['opd_id']) && !empty($validated['vehicle_id'])) {
            $vehicle = Vehicle::find($validated['vehicle_id']);
            if ($vehicle && isset($vehicle->opd_id)) {
                $validated['opd_id'] = $vehicle->opd_id;
            }
        }

        KendaraanPinjamPakai::create($validated);

        return redirect()->route('vehicles.pinjam-pakai.index')
            ->with('success', 'Data Kendaraan Pinjam Pakai berhasil ditambahkan.');
    }

    /**
     * Detail pinjam pakai
     */
    public function show($id)
    {
        $pinjamPakai = KendaraanPinjamPakai::with(['vehicle', 'opd', 'creator'])->findOrFail($id);
        return view('vehicles.pinjam_pakai.show', compact('pinjamPakai'));
    }

    /**
     * Form edit pinjam pakai
     */
    public function edit($id)
    {
        $pinjamPakai = KendaraanPinjamPakai::findOrFail($id);
        $vehicles = Vehicle::orderBy('merk', 'asc')->orderBy('tipe', 'asc')->get();
        $opds = Opd::orderBy('nama', 'asc')->get();

        return view('vehicles.pinjam_pakai.edit', compact('pinjamPakai', 'vehicles', 'opds'));
    }

    /**
     * Update data pinjam pakai
     */
    public function update(Request $request, $id)
    {
        $pinjamPakai = KendaraanPinjamPakai::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'opd_id' => 'nullable|exists:opds,id',
            'kategori_peminjam' => 'required|string|in:Instansi Vertikal,Antar OPD,Lainnya',
            'nama_instansi_peminjam' => 'required|string|max:255',
            'nama_penanggung_jawab' => 'required|string|max:255',
            'nip_penanggung_jawab' => 'nullable|string|max:50',
            'jabatan_penanggung_jawab' => 'nullable|string|max:150',
            'kontak_penanggung_jawab' => 'nullable|string|max:50',
            'nomor_nppp_bast' => 'required|string|max:100',
            'tanggal_nppp_bast' => 'nullable|date',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status_perjanjian' => 'required|string|in:Aktif,Akan Jatuh Tempo,Selesai / Dikembalikan,Diperpanjang',
            'file_dokumen_pdf' => 'nullable|file|mimes:pdf|max:10240',
            'keterangan' => 'nullable|string',
        ]);

        if ($request->hasFile('file_dokumen_pdf')) {
            if ($pinjamPakai->file_dokumen_pdf && Storage::disk('public')->exists($pinjamPakai->file_dokumen_pdf)) {
                Storage::disk('public')->delete($pinjamPakai->file_dokumen_pdf);
            }
            $validated['file_dokumen_pdf'] = $request->file('file_dokumen_pdf')->store('dokumen_pinjam_pakai', 'public');
        } else {
            unset($validated['file_dokumen_pdf']);
        }

        $pinjamPakai->update($validated);

        return redirect()->route('vehicles.pinjam-pakai.index')
            ->with('success', 'Data Kendaraan Pinjam Pakai berhasil diperbarui.');
    }

    /**
     * Pengembalian kendaraan / selesaikan pinjam pakai
     */
    public function kembalikan($id)
    {
        $pinjamPakai = KendaraanPinjamPakai::findOrFail($id);
        $pinjamPakai->update([
            'status_perjanjian' => 'Selesai / Dikembalikan'
        ]);

        return redirect()->route('vehicles.pinjam-pakai.index')
            ->with('success', 'Status Perjanjian Pinjam Pakai telah diselesaikan / dikembalikan.');
    }

    /**
     * Hapus data pinjam pakai
     */
    public function destroy($id)
    {
        $pinjamPakai = KendaraanPinjamPakai::findOrFail($id);
        $pinjamPakai->delete();

        return redirect()->route('vehicles.pinjam-pakai.index')
            ->with('success', 'Data Pinjam Pakai berhasil dihapus.');
    }

    /**
     * Memproses impor data pinjam pakai secara massal (AI Smart Semantic Import)
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new \App\Imports\KendaraanPinjamPakaiImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('import_file'));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();

            \App\Models\Activity::log("Mengimpor {$imported} data kendaraan pinjam pakai via AI Smart Semantic Import.", 'success');

            return redirect()->route('vehicles.pinjam-pakai.index')
                ->with('success', "Proses impor selesai! Berhasil diimpor: {$imported} data. Dilewati: {$skipped} data.");
        } catch (\Exception $e) {
            return redirect()->route('vehicles.pinjam-pakai.index')
                ->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }

    /**
     * Download format Excel sampel untuk impor data pinjam pakai
     */
    public function downloadImportTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_import_kendaraan_pinjam_pakai.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // Heading Row
            fputcsv($file, [
                'No. Polisi',
                'Kategori Peminjam',
                'Nama Instansi Peminjam',
                'Nama Penanggung Jawab',
                'NIP Penanggung Jawab',
                'Jabatan Penanggung Jawab',
                'No. HP / Kontak',
                'Nomor Dokumen NPPP / BAST',
                'Tanggal NPPP / BAST',
                'Tanggal Mulai',
                'Tanggal Selesai',
                'Keterangan'
            ]);

            // Sample Row 1
            fputcsv($file, [
                'BE 1024 CZ',
                'Instansi Vertikal',
                'Kejaksaan Negeri',
                'Drs. H. Ahmad Fauzi, M.Si',
                '19850101 201001 1 002',
                'Kasi Intelijen',
                '081234567890',
                '028/102/BPKAD/2026',
                '2026-01-15',
                '2026-01-15',
                '2027-01-15',
                'Pinjam pakai operasional dinas kejaksaan'
            ]);

            // Sample Row 2
            fputcsv($file, [
                'BE 2048 BD',
                'Instansi Vertikal',
                'Polres',
                'Kompol Hendra Wijaya, S.I.K.',
                '19820315 200501 1 001',
                'Kabag Ops',
                '081398765432',
                '028/105/BPKAD/2026',
                '2026-02-01',
                '2026-02-01',
                '2027-02-01',
                'Pinjam pakai pengamanan pemilu'
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
