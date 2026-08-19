<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Models\Elabel\ElabelActivityLog;
use App\Models\Elabel\ElabelBox;
use App\Models\Elabel\ElabelBoxYear;
use App\Models\Elabel\ElabelBpkb;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ElabelBoxController extends Controller implements HasMiddleware
{
    private const MAX_BPKB_MERGE_SOURCE = 25;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request, ?string $type = null): View
    {
        $vehicleType = $this->normalizeVehicleType($type ?: $request->get('type'));
        $vehicleLabel = $this->vehicleLabel($vehicleType);

        $query = ElabelBox::withCount(['bpkbs' => function ($q) {
            $q->whereNotIn('status', ['Dihapus', 'Dipinjam']);
        }])->with('years');

        if ($vehicleType !== null) {
            $query->where('vehicle_type', $vehicleType);
        }

        $boxes = $query->orderBy('id', 'desc')->get();

        return view('elabel.boxes.index', [
            'boxes'        => $boxes,
            'vehicleType'  => $vehicleType,
            'vehicleLabel' => $vehicleLabel,
            'activeMenu'   => $vehicleType === 'R2' ? 'boxes_motor' : ($vehicleType === 'R4' ? 'boxes_mobil' : 'boxes'),
        ]);
    }

    public function create(Request $request, ?string $type = null): View
    {
        $vehicleType = $this->normalizeVehicleType($type ?: $request->get('type'));
        $vehicleLabel = $this->vehicleLabel($vehicleType);
        $nextBoxCodes = [
            'R4' => $this->nextSequentialBoxCode('R4'),
            'R2' => $this->nextSequentialBoxCode('R2'),
        ];

        return view('elabel.boxes.create', [
            'vehicleType'  => $vehicleType,
            'vehicleLabel' => $vehicleLabel,
            'nextBoxCodes' => $nextBoxCodes,
            'activeMenu'   => $vehicleType === 'R2' ? 'boxes_motor' : ($vehicleType === 'R4' ? 'boxes_mobil' : 'boxes'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'box_code'     => 'required|string|max:30',
            'location'     => 'nullable|string|max:100',
            'vehicle_type' => 'required|in:R4,R2',
            'years'        => 'required|string',
        ]);

        $vehicleType = $request->input('vehicle_type');
        $boxCodeInput = (string) $request->input('box_code');
        $boxCode = $this->applyBoxPrefix($boxCodeInput, $vehicleType);

        if (ElabelBox::where('box_code', $boxCode)->exists()) {
            return redirect()->back()->withInput()->with('error', 'Kode box ' . $boxCode . ' sudah digunakan.');
        }

        DB::transaction(function () use ($request, $vehicleType, $boxCode) {
            $box = ElabelBox::create([
                'box_code'     => $boxCode,
                'location'     => $request->input('location'),
                'vehicle_type' => $vehicleType,
                'created_by'   => Auth::id() ?: 1,
            ]);

            $yearsInput = (string) $request->input('years');
            $parts = preg_split('/[\s\-\,]+/', trim($yearsInput)) ?: [];
            foreach ($parts as $part) {
                $year = (int) $part;
                if ($year > 1900 && $year < 2100) {
                    ElabelBoxYear::create([
                        'box_id' => $box->id,
                        'year'   => $year,
                    ]);
                }
            }

            $this->logActivity('create', 'Box BPKB', 'Menambahkan box BPKB ' . $boxCode . '.', 'boxes', $box->id);
        });

        return redirect()->route('elabel.boxes.index', ['type' => strtolower($vehicleType)])
            ->with('success', 'Box BPKB ' . $boxCode . ' berhasil ditambahkan.');
    }

    public function show(int $id): View|RedirectResponse
    {
        $box = ElabelBox::with(['years', 'bpkbs' => function ($q) {
            $q->whereNotIn('status', ['Dihapus', 'Dipinjam'])->orderBy('year', 'desc')->orderBy('plate_number', 'asc');
        }])->find($id);

        if (!$box) {
            return redirect()->route('elabel.boxes.index')->with('error', 'Box BPKB tidak ditemukan.');
        }

        $mergeCandidateCount = $box->bpkbs->count();
        $mergeTargets = [];
        if ($mergeCandidateCount > 0 && $mergeCandidateCount <= self::MAX_BPKB_MERGE_SOURCE) {
            $mergeTargets = $this->availableMergeTargets($box, $id);
        }

        return view('elabel.boxes.show', [
            'box'                 => $box,
            'items'               => $box->bpkbs,
            'years'               => $box->years,
            'mergeTargets'        => $mergeTargets,
            'mergeCandidateCount' => $mergeCandidateCount,
            'maxMergeSource'      => self::MAX_BPKB_MERGE_SOURCE,
            'activeMenu'          => $box->vehicle_type === 'R2' ? 'boxes_motor' : 'boxes_mobil',
        ]);
    }

    public function merge(Request $request, int $id): RedirectResponse
    {
        $sourceBox = ElabelBox::find($id);
        if (!$sourceBox) {
            return redirect()->route('elabel.boxes.index')->with('error', 'Box sumber tidak ditemukan.');
        }

        $targetId = (int) $request->input('target_box_id');
        $targetBox = ElabelBox::find($targetId);
        if (!$targetBox) {
            return redirect()->route('elabel.boxes.show', $id)->with('error', 'Box tujuan tidak ditemukan.');
        }

        if ($targetId === $id) {
            return redirect()->route('elabel.boxes.show', $id)->with('error', 'Box tujuan tidak boleh sama dengan box sumber.');
        }

        if ($sourceBox->vehicle_type !== $targetBox->vehicle_type) {
            return redirect()->route('elabel.boxes.show', $id)->with('error', 'Penggabungan hanya bisa dilakukan ke box dengan jenis kendaraan yang sama.');
        }

        if (!$this->canMergeIntoTarget($id, $targetId)) {
            return redirect()->route('elabel.boxes.show', $id)->with('error', 'Penggabungan hanya bisa dilakukan jika tahun box sumber sudah tercakup di box tujuan.');
        }

        $sourceCount = ElabelBpkb::where('box_id', $id)->whereNotIn('status', ['Dihapus', 'Dipinjam'])->count();
        if ($sourceCount === 0) {
            return redirect()->route('elabel.boxes.show', $id)->with('error', 'Box sumber tidak memiliki data BPKB aktif untuk digabung.');
        }

        if ($sourceCount > self::MAX_BPKB_MERGE_SOURCE) {
            return redirect()->route('elabel.boxes.show', $id)->with('error', 'Box hanya bisa digabung jika berisi maksimal ' . self::MAX_BPKB_MERGE_SOURCE . ' BPKB.');
        }

        DB::transaction(function () use ($id, $targetId, $sourceBox, $targetBox) {
            ElabelBpkb::where('box_id', $id)->update(['box_id' => $targetId]);

            $this->mergeBoxYears($id, $targetId);
            ElabelBoxYear::where('box_id', $id)->delete();
            $sourceBox->delete();

            $this->logActivity('delete', 'Box BPKB', 'Menggabungkan box ' . $sourceBox->box_code . ' ke box ' . $targetBox->box_code . '.', 'boxes', $targetId);
        });

        return redirect()->route('elabel.boxes.show', $targetId)->with('success', 'Box ' . $sourceBox->box_code . ' berhasil digabung ke box ' . $targetBox->box_code . '.');
    }

    public function label(int $id): View|RedirectResponse
    {
        $box = ElabelBox::with(['years', 'bpkbs' => function ($q) {
            $q->whereNotIn('status', ['Dihapus', 'Dipinjam'])->orderBy('year', 'desc')->orderBy('plate_number', 'asc');
        }])->find($id);

        if (!$box) {
            return redirect()->route('elabel.boxes.index')->with('error', 'Box BPKB tidak ditemukan.');
        }

        return view('elabel.boxes.label', [
            'box'   => $box,
            'items' => $box->bpkbs,
            'years' => $box->years,
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $box = ElabelBox::find($id);
        if (!$box) {
            return redirect()->route('elabel.boxes.index')->with('error', 'Box BPKB tidak ditemukan.');
        }

        $count = ElabelBpkb::where('box_id', $id)->count();
        if ($count > 0) {
            return redirect()->route('elabel.boxes.index')->with('error', 'Box BPKB tidak bisa dihapus karena masih berisi ' . $count . ' data BPKB.');
        }

        ElabelBoxYear::where('box_id', $id)->delete();
        $boxCode = $box->box_code;
        $box->delete();

        $this->logActivity('delete', 'Box BPKB', 'Menghapus box ' . $boxCode . '.', 'boxes', $id);

        return redirect()->route('elabel.boxes.index')->with('success', 'Box BPKB ' . $boxCode . ' berhasil dihapus.');
    }

    private function normalizeVehicleType(?string $type): ?string
    {
        if ($type === null) return null;
        $type = strtoupper(trim($type));
        if (in_array($type, ['MOTOR', 'R2'])) return 'R2';
        if (in_array($type, ['MOBIL', 'R4'])) return 'R4';
        return in_array($type, ['R4', 'R2']) ? $type : null;
    }

    private function vehicleLabel(?string $type): string
    {
        if ($type === 'R2') return 'R2 (Motor)';
        if ($type === 'R4') return 'R4 (Mobil)';
        return 'Semua Jenis';
    }

    private function applyBoxPrefix(string $code, string $vehicleType): string
    {
        $code = strtoupper(trim($code));
        $prefix = $vehicleType . '-';

        if ($code === '') {
            return $this->nextSequentialBoxCode($vehicleType);
        }

        if (strpos($code, $prefix) === 0) {
            $suffix = substr($code, strlen($prefix));
            if (ctype_digit($suffix)) {
                return $prefix . str_pad($suffix, 2, '0', STR_PAD_LEFT);
            }
            return $code;
        }

        if (ctype_digit($code)) {
            return $prefix . str_pad($code, 2, '0', STR_PAD_LEFT);
        }

        return $prefix . $code;
    }

    private function nextSequentialBoxCode(string $vehicleType): string
    {
        $boxes = ElabelBox::where('vehicle_type', $vehicleType)
            ->where('box_code', 'LIKE', $vehicleType . '-%')
            ->pluck('box_code');

        $maxNumber = 0;
        foreach ($boxes as $boxCode) {
            if (preg_match('/^' . preg_quote($vehicleType, '/') . '-(\d+)$/', $boxCode, $matches)) {
                $number = (int) $matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        return $vehicleType . '-' . str_pad((string) ($maxNumber + 1), 2, '0', STR_PAD_LEFT);
    }

    private function availableMergeTargets(ElabelBox $sourceBox, int $sourceId): array
    {
        $targets = ElabelBox::withCount(['bpkbs' => function ($q) {
            $q->whereNotIn('status', ['Dihapus', 'Dipinjam']);
        }])
            ->where('vehicle_type', $sourceBox->vehicle_type)
            ->where('id', '!=', $sourceId)
            ->orderBy('box_code', 'asc')
            ->get();

        $result = [];
        foreach ($targets as $target) {
            if ($this->canMergeIntoTarget($sourceId, $target->id)) {
                $result[] = $target;
            }
        }

        return $result;
    }

    private function canMergeIntoTarget(int $sourceId, int $targetId): bool
    {
        $sourceYears = ElabelBoxYear::where('box_id', $sourceId)->pluck('year')->toArray();
        $targetYears = ElabelBoxYear::where('box_id', $targetId)->pluck('year')->toArray();

        if (empty($sourceYears) || empty($targetYears)) {
            return false;
        }

        foreach ($sourceYears as $year) {
            if (!in_array($year, $targetYears)) {
                return false;
            }
        }

        return true;
    }

    private function mergeBoxYears(int $sourceId, int $targetId): void
    {
        $sourceYears = ElabelBoxYear::where('box_id', $sourceId)->pluck('year')->toArray();
        $targetYears = ElabelBoxYear::where('box_id', $targetId)->pluck('year')->toArray();

        foreach ($sourceYears as $year) {
            if (!in_array($year, $targetYears)) {
                ElabelBoxYear::create([
                    'box_id' => $targetId,
                    'year'   => $year,
                ]);
            }
        }
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
