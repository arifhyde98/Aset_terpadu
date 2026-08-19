<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Models\Elabel\ElabelActivityLog;
use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelLoan;
use App\Models\Elabel\ElabelLoanHistory;
use App\Models\Elabel\ElabelSertifikat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ElabelLoanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request, string $documentType = 'bpkb'): View
    {
        $documentType = strtolower($documentType);
        if (!in_array($documentType, ['bpkb', 'sertifikat'], true)) {
            $documentType = 'bpkb';
        }

        $items = [];
        $availableBpkb = [];

        if ($documentType === 'bpkb') {
            $items = ElabelLoan::with(['bpkb.box', 'requester', 'approver'])
                ->orderBy('requested_at', 'desc')
                ->get();

            $availableBpkb = ElabelBpkb::with('box')
                ->where('status', 'Tersedia')
                ->orderBy('plate_number', 'asc')
                ->get();
        }

        return view('elabel.loans.index', [
            'items'         => $items,
            'availableBpkb' => $availableBpkb,
            'documentType'  => $documentType,
            'documentLabel' => $documentType === 'sertifikat' ? 'Sertipikat Tanah' : 'BPKB',
            'activeMenu'    => 'peminjaman',
        ]);
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $request->validate([
            'bpkb_id'         => ['required', 'integer', 'exists:elabel_bpkb,id'],
            'requester_name'  => ['required', 'string', 'min:3', 'max:100'],
            'requester_phone' => ['nullable', 'string', 'max:30'],
            'requester_email' => ['nullable', 'email', 'max:150'],
            'requester_org'   => ['nullable', 'string', 'max:150'],
            'requester_note'  => ['nullable', 'string', 'max:255'],
            'note'            => ['nullable', 'string', 'max:255'],
        ]);

        $bpkbId = (int) $request->get('bpkb_id');
        $item = ElabelBpkb::find($bpkbId);

        if (!$item || $item->status !== 'Tersedia') {
            return redirect()->back()->withInput()->with('error', 'BPKB tidak tersedia untuk diminta scan.');
        }

        DB::beginTransaction();
        try {
            $loan = ElabelLoan::create([
                'bpkb_id'          => $bpkbId,
                'requester_id'     => null,
                'requester_name'   => (string) $request->get('requester_name'),
                'requester_phone'  => (string) $request->get('requester_phone') ?: null,
                'requester_email'  => (string) $request->get('requester_email') ?: null,
                'requester_org'    => (string) $request->get('requester_org') ?: null,
                'requester_note'   => (string) $request->get('requester_note') ?: null,
                'requested_at'     => now(),
                'approved_by'      => Auth::id() ?: 1,
                'approved_at'      => now(),
                'status'           => 'Disetujui',
                'note'             => (string) $request->get('note') ?: 'Permintaan scan manual oleh admin.',
            ]);

            ElabelLoanHistory::create([
                'loan_id'    => $loan->id,
                'status'     => 'Disetujui',
                'changed_by' => Auth::id() ?: 1,
                'changed_at' => now(),
                'note'       => 'Permintaan scan manual oleh admin.',
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Permintaan scan manual gagal disimpan: ' . $e->getMessage());
        }

        $this->logActivity('create', 'Permintaan Scan', 'Menambahkan permintaan scan manual untuk BPKB ' . ($item->plate_number ?? '-') . '.', 'elabel_loans', $loan->id);

        return redirect()->route('elabel.peminjaman.index')->with('success', 'Permintaan scan manual berhasil ditambahkan.');
    }

    public function approve(int $id): RedirectResponse
    {
        $loan = ElabelLoan::find($id);
        if (!$loan) {
            return redirect()->route('elabel.peminjaman.index')->with('error', 'Data permintaan scan tidak ditemukan.');
        }

        if ($loan->status !== 'Menunggu') {
            return redirect()->route('elabel.peminjaman.index')->with('error', 'Status permintaan scan sudah diproses.');
        }

        $loan->update([
            'status'      => 'Disetujui',
            'approved_by' => Auth::id() ?: 1,
            'approved_at' => now(),
        ]);

        ElabelLoanHistory::create([
            'loan_id'    => $id,
            'status'     => 'Disetujui',
            'changed_by' => Auth::id() ?: 1,
            'changed_at' => now(),
        ]);

        $this->logActivity('approve', 'Permintaan Scan', 'Menyetujui permintaan scan BPKB ID ' . $loan->bpkb_id . '.', 'elabel_loans', $id);

        return redirect()->route('elabel.peminjaman.index')->with('success', 'Permintaan Scan disetujui.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $loan = ElabelLoan::find($id);
        if (!$loan) {
            return redirect()->route('elabel.peminjaman.index')->with('error', 'Data permintaan scan tidak ditemukan.');
        }

        if ($loan->status !== 'Menunggu') {
            return redirect()->route('elabel.peminjaman.index')->with('error', 'Status permintaan scan sudah diproses.');
        }

        $note = (string) $request->get('note');

        $loan->update([
            'status'      => 'Ditolak',
            'approved_by' => Auth::id() ?: 1,
            'approved_at' => now(),
            'note'        => $note ?: null,
        ]);

        ElabelLoanHistory::create([
            'loan_id'    => $id,
            'status'     => 'Ditolak',
            'changed_by' => Auth::id() ?: 1,
            'changed_at' => now(),
            'note'       => $note ?: null,
        ]);

        $this->logActivity('reject', 'Permintaan Scan', 'Menolak permintaan scan BPKB ID ' . $loan->bpkb_id . '.', 'elabel_loans', $id);

        return redirect()->route('elabel.peminjaman.index')->with('success', 'Permintaan Scan ditolak.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $loan = ElabelLoan::find($id);
        if (!$loan) {
            return redirect()->back()->with('error', 'Data permintaan scan tidak ditemukan.');
        }

        if ($loan->requested_at && $loan->requested_at->gt(now()->subDays(7))) {
            return redirect()->back()->with('error', 'Permintaan scan hanya bisa dihapus setelah lebih dari 7 hari.');
        }

        DB::beginTransaction();
        try {
            ElabelLoanHistory::where('loan_id', $id)->delete();
            $loan->delete();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Permintaan scan gagal dihapus.');
        }

        $this->logActivity('delete', 'Permintaan Scan', 'Menghapus permintaan scan ID ' . $id . '.', 'elabel_loans', $id);

        return redirect()->back()->with('success', 'Permintaan scan berhasil dihapus.');
    }

    public function download(int $id): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        $loan = ElabelLoan::with('bpkb')->find($id);
        if (!$loan) {
            return redirect()->back()->with('error', 'Data permintaan scan tidak ditemukan.');
        }

        if ($loan->status !== 'Disetujui') {
            return redirect()->back()->with('error', 'File scan hanya bisa didownload setelah permintaan disetujui.');
        }

        $bpkb = $loan->bpkb;
        if (!$bpkb || !$bpkb->pdf_path || !Storage::disk('public')->exists($bpkb->pdf_path)) {
            return redirect()->back()->with('error', 'File scan untuk data yang diminta belum tersedia atau tidak ditemukan.');
        }

        $sourcePath = storage_path('app/public/' . $bpkb->pdf_path);
        $watermarkedPath = $this->watermarkPdf($sourcePath, $loan);

        if ($watermarkedPath === null) {
            // Fallback: serve original if Ghostscript watermark unavailable
            return response()->file($sourcePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="scan-bpkb-' . $bpkb->plate_number . '.pdf"',
            ]);
        }

        $filename = 'scan-bpkb-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($bpkb->plate_number ?? 'dokumen')) . '.pdf';

        return response()->streamDownload(function () use ($watermarkedPath) {
            readfile($watermarkedPath);
            @unlink($watermarkedPath);
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function watermarkPdf(string $sourcePath, ElabelLoan $loan): ?string
    {
        $gsPath = trim((string) shell_exec('command -v gs 2>/dev/null'));
        if ($gsPath === '' || !is_executable($gsPath)) {
            return null;
        }

        $outputPath = tempnam(sys_get_temp_dir(), 'scan-watermark-');
        $scriptPath = tempnam(sys_get_temp_dir(), 'scan-watermark-ps-');
        if ($outputPath === false || $scriptPath === false) {
            return null;
        }

        $requesterName = strtoupper(trim((string) ($loan->requester_name ?? 'Pemohon')));
        $requestedDate = $loan->requested_at ? $loan->requested_at->format('d/m/Y H:i') : '-';

        $lineOne = 'DIMINTA OLEH: ' . $requesterName;
        $lineTwo = 'TANGGAL PENGAJUAN: ' . $requestedDate;

        $script = <<<PS
<<
  /EndPage {
    2 ne {
      pop
      gsave
        0 setgray
        /Helvetica-Bold findfont 9 scalefont setfont
        24 24 moveto ({$this->pdfString($lineOne)}) show
        /Helvetica findfont 8 scalefont setfont
        24 13 moveto ({$this->pdfString($lineTwo)}) show
      grestore
      true
    } {
      pop
      false
    } ifelse
  } bind
>> setpagedevice
PS;

        if (file_put_contents($scriptPath, $script) === false) {
            @unlink($outputPath);
            @unlink($scriptPath);
            return null;
        }

        $command = escapeshellarg($gsPath)
            . ' -q -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -dCompatibilityLevel=1.4'
            . ' -sOutputFile=' . escapeshellarg($outputPath)
            . ' ' . escapeshellarg($scriptPath)
            . ' ' . escapeshellarg($sourcePath)
            . ' 2>&1';

        exec($command, $output, $exitCode);
        @unlink($scriptPath);

        if ($exitCode !== 0 || !is_file($outputPath) || filesize($outputPath) === 0) {
            @unlink($outputPath);
            return null;
        }

        return $outputPath;
    }

    private function pdfString(string $value): string
    {
        $value = str_replace(["\r", "\n"], ' ', $value);
        $value = preg_replace('/[^\x20-\x7E]/', '?', $value) ?: '';
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }

    private function logActivity(string $action, string $module, string $description, ?string $refType = null, ?int $refId = null): void
    {
        ElabelActivityLog::create([
            'user_id'        => Auth::id() ?: 1,
            'action'         => $action,
            'module'         => $module,
            'description'    => $description,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'created_at'     => now(),
        ]);
    }
}
