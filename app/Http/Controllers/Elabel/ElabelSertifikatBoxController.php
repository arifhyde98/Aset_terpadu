<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Models\Elabel\ElabelActivityLog;
use App\Models\Elabel\ElabelSertifikat;
use App\Models\Elabel\ElabelSertifikatBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ElabelSertifikatBoxController extends Controller implements HasMiddleware
{
    private const MAX_SERTIFIKAT_PER_BOX = 40;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request): View
    {
        $query = trim((string) $request->get('q'));
        $builder = ElabelSertifikatBox::withCount('sertifikats')->orderBy('id', 'desc');

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('box_code', 'LIKE', "%{$query}%")
                  ->orWhere('lokasi', 'LIKE', "%{$query}%");
            });
        }

        $boxes = $builder->get();

        return view('elabel.sertifikat_boxes.index', [
            'items'      => $boxes,
            'maxPerBox'  => self::MAX_SERTIFIKAT_PER_BOX,
            'activeMenu' => 'sertifikat_boxes',
        ]);
    }

    public function create(): View
    {
        return view('elabel.sertifikat_boxes.create', [
            'nextBoxCode' => $this->nextSequentialBoxCode(),
            'activeMenu'  => 'sertifikat_boxes',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'box_code' => 'required|string|max:30',
            'lokasi'   => 'required|string|max:255',
        ]);

        $lokasi = trim((string) $request->input('lokasi'));
        $existingByLocation = $this->findBoxesByLocation($lokasi);

        $boxCode = !empty($existingByLocation)
            ? $this->nextBoxCodeSuffix((string) ($existingByLocation[0]->box_code ?? ''))
            : $this->normalizeBoxCode((string) $request->input('box_code'));

        if (ElabelSertifikatBox::where('box_code', $boxCode)->exists()) {
            return redirect()->back()->withInput()->with('error', 'Kode box sertipikat ' . $boxCode . ' sudah digunakan.');
        }

        $box = ElabelSertifikatBox::create([
            'box_code'   => $boxCode,
            'lokasi'     => $lokasi,
            'created_by' => Auth::id() ?: 1,
        ]);

        $this->logActivity('create', 'Box Sertipikat', 'Menambahkan box sertipikat ' . $boxCode . '.', 'sertifikat_boxes', $box->id);

        return redirect()->route('elabel.sertifikat-boxes.index')->with('success', 'Box sertipikat ' . $boxCode . ' berhasil ditambahkan.');
    }

    public function show(int $id): View|RedirectResponse
    {
        $box = ElabelSertifikatBox::with(['sertifikats' => function ($q) {
            $q->orderBy('no_sertipikat', 'asc');
        }])->find($id);

        if (!$box) {
            return redirect()->route('elabel.sertifikat-boxes.index')->with('error', 'Box sertipikat tidak ditemukan.');
        }

        $items = $box->sertifikats;
        $mergeCandidateCount = $items->count();
        $mergeTargets = $mergeCandidateCount > 0
            ? $this->availableMergeTargets($id, $mergeCandidateCount)
            : [];
        $splitOptions = $this->availableSplitOptions($box, $items);

        return view('elabel.sertifikat_boxes.show', [
            'box'                 => $box,
            'items'               => $items,
            'maxPerBox'           => self::MAX_SERTIFIKAT_PER_BOX,
            'mergeCandidateCount' => $mergeCandidateCount,
            'mergeTargets'        => $mergeTargets,
            'splitOptions'        => $splitOptions,
            'activeMenu'          => 'sertifikat_boxes',
        ]);
    }

    public function merge(Request $request, int $id): RedirectResponse
    {
        $sourceBox = ElabelSertifikatBox::find($id);
        if (!$sourceBox) {
            return redirect()->route('elabel.sertifikat-boxes.index')->with('error', 'Box sertipikat sumber tidak ditemukan.');
        }

        $targetId = (int) $request->input('target_box_id');
        $targetBox = ElabelSertifikatBox::find($targetId);
        if (!$targetBox) {
            return redirect()->route('elabel.sertifikat-boxes.show', $id)->with('error', 'Box sertipikat tujuan tidak ditemukan.');
        }

        if ($targetId === $id) {
            return redirect()->route('elabel.sertifikat-boxes.show', $id)->with('error', 'Box tujuan tidak boleh sama dengan box sumber.');
        }

        $sourceCount = ElabelSertifikat::where('box_id', $id)->count();
        $targetCount = ElabelSertifikat::where('box_id', $targetId)->count();
        if ($sourceCount === 0) {
            return redirect()->route('elabel.sertifikat-boxes.show', $id)->with('error', 'Box sumber tidak memiliki data sertipikat untuk digabung.');
        }

        if (($sourceCount + $targetCount) > self::MAX_SERTIFIKAT_PER_BOX) {
            return redirect()->route('elabel.sertifikat-boxes.show', $id)->with('error', 'Penggabungan hanya bisa dilakukan jika total isi box sumber dan tujuan maksimal ' . self::MAX_SERTIFIKAT_PER_BOX . ' sertipikat.');
        }

        $mergedLocation = $this->mergeLocationLabels((string) $targetBox->lokasi, (string) $sourceBox->lokasi);

        DB::transaction(function () use ($id, $targetId, $targetBox, $sourceBox, $mergedLocation) {
            ElabelSertifikat::where('box_id', $id)->update(['box_id' => $targetId]);
            $targetBox->update(['lokasi' => $mergedLocation]);
            $sourceBox->delete();

            $this->logActivity('delete', 'Box Sertipikat', 'Menggabungkan box ' . $sourceBox->box_code . ' ke box ' . $targetBox->box_code . '.', 'sertifikat_boxes', $targetId);
        });

        return redirect()->route('elabel.sertifikat-boxes.show', $targetId)->with('success', 'Box ' . $sourceBox->box_code . ' berhasil digabung ke box ' . $targetBox->box_code . '.');
    }

    public function split(Request $request, int $id): RedirectResponse
    {
        $box = ElabelSertifikatBox::find($id);
        if (!$box) {
            return redirect()->route('elabel.sertifikat-boxes.index')->with('error', 'Box sertipikat tidak ditemukan.');
        }

        $selectedLocation = trim((string) $request->input('split_location'));
        if ($selectedLocation === '') {
            return redirect()->route('elabel.sertifikat-boxes.show', $id)->with('error', 'Pilih lokasi yang ingin dipisahkan.');
        }

        $allLocations = $this->explodeLocationLabels((string) $box->lokasi);
        if (count($allLocations) < 2) {
            return redirect()->route('elabel.sertifikat-boxes.show', $id)->with('error', 'Box ini belum merupakan box gabungan.');
        }

        $selectedKey = strtoupper($selectedLocation);
        if (!isset($allLocations[$selectedKey])) {
            return redirect()->route('elabel.sertifikat-boxes.show', $id)->with('error', 'Lokasi yang dipilih tidak ada pada box ini.');
        }

        $selectedLabel = $allLocations[$selectedKey];
        unset($allLocations[$selectedKey]);

        $itemsToMove = ElabelSertifikat::where('box_id', $id)
            ->where(DB::raw('UPPER(TRIM(lokasi))'), $selectedKey)
            ->get();

        if ($itemsToMove->isEmpty()) {
            return redirect()->route('elabel.sertifikat-boxes.show', $id)->with('error', 'Tidak ada data sertipikat untuk lokasi ' . $selectedLabel . '.');
        }

        $newBoxCode = $this->nextBoxCodeSuffix((string) $box->box_code);
        $remainingLocation = implode(', ', array_values($allLocations));

        DB::transaction(function () use ($id, $box, $newBoxCode, $selectedLabel, $remainingLocation, $itemsToMove) {
            $newBox = ElabelSertifikatBox::create([
                'box_code'   => $newBoxCode,
                'lokasi'     => $selectedLabel,
                'created_by' => Auth::id() ?: 1,
            ]);

            foreach ($itemsToMove as $item) {
                $item->update(['box_id' => $newBox->id]);
            }

            $box->update(['lokasi' => $remainingLocation]);

            $this->logActivity('create', 'Box Sertipikat', 'Memisahkan lokasi ' . $selectedLabel . ' dari box ' . $box->box_code . ' ke box baru ' . $newBoxCode . '.', 'sertifikat_boxes', $newBox->id);
        });

        return redirect()->route('elabel.sertifikat-boxes.index')->with('success', 'Lokasi ' . $selectedLabel . ' berhasil dipisahkan ke box ' . $newBoxCode . '.');
    }

    public function label(int $id): View|RedirectResponse
    {
        $box = ElabelSertifikatBox::with(['sertifikats' => function ($q) {
            $q->orderBy('no_sertipikat', 'asc');
        }])->find($id);

        if (!$box) {
            return redirect()->route('elabel.sertifikat-boxes.index')->with('error', 'Box sertipikat tidak ditemukan.');
        }

        return view('elabel.sertifikat_boxes.label', [
            'box'       => $box,
            'items'     => $box->sertifikats,
            'maxPerBox' => self::MAX_SERTIFIKAT_PER_BOX,
        ]);
    }

    public function destroy(int $id): RedirectResponse

    {
        $box = ElabelSertifikatBox::find($id);
        if (!$box) {
            return redirect()->route('elabel.sertifikat-boxes.index')->with('error', 'Box sertipikat tidak ditemukan.');
        }

        $count = ElabelSertifikat::where('box_id', $id)->count();
        if ($count > 0) {
            return redirect()->route('elabel.sertifikat-boxes.index')->with('error', 'Box sertipikat tidak bisa dihapus karena masih berisi ' . $count . ' data sertipikat.');
        }

        $boxCode = $box->box_code;
        $box->delete();

        $this->logActivity('delete', 'Box Sertipikat', 'Menghapus box sertipikat ' . $boxCode . '.', 'sertifikat_boxes', $id);

        return redirect()->route('elabel.sertifikat-boxes.index')->with('success', 'Box sertipikat ' . $boxCode . ' berhasil dihapus.');
    }

    private function normalizeBoxCode(string $code): string
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return $this->nextSequentialBoxCode();
        }

        if (strpos($code, 'ST-') === 0) {
            $suffix = substr($code, 3);
            if (ctype_digit($suffix)) {
                return 'ST-' . str_pad($suffix, 2, '0', STR_PAD_LEFT);
            }
            return $code;
        }

        if (ctype_digit($code)) {
            return 'ST-' . str_pad($code, 2, '0', STR_PAD_LEFT);
        }

        return 'ST-' . $code;
    }

    private function nextSequentialBoxCode(): string
    {
        $rows = ElabelSertifikatBox::where('box_code', 'LIKE', 'ST-%')->pluck('box_code');
        $maxNumber = 0;
        foreach ($rows as $boxCode) {
            if (preg_match('/^ST-(\d+)$/', $boxCode, $matches)) {
                $number = (int) $matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        return 'ST-' . str_pad((string) ($maxNumber + 1), 2, '0', STR_PAD_LEFT);
    }

    private function nextBoxCodeSuffix(string $baseCode): string
    {
        $baseCode = preg_replace('/ \(\d+\)$/', '', trim($baseCode)) ?: trim($baseCode);
        $existing = ElabelSertifikatBox::where('box_code', 'LIKE', $baseCode . '%')->pluck('box_code');

        $max = 1;
        foreach ($existing as $code) {
            if (preg_match('/^' . preg_quote($baseCode, '/') . ' \((\d+)\)$/', $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            } elseif ($code === $baseCode) {
                $max = max($max, 1);
            }
        }

        return $baseCode . ' (' . ($max + 1) . ')';
    }

    private function findBoxesByLocation(string $lokasi): array
    {
        $normalized = strtoupper(trim($lokasi));
        if ($normalized === '') return [];

        $allBoxes = ElabelSertifikatBox::orderBy('id', 'asc')->get();
        $result = [];
        foreach ($allBoxes as $box) {
            $boxLocations = array_map('strtoupper', array_map('trim', explode(',', $box->lokasi ?? '')));
            if (in_array($normalized, $boxLocations)) {
                $result[] = $box;
            }
        }
        return $result;
    }

    private function availableMergeTargets(int $sourceId, int $sourceCount): array
    {
        $targets = ElabelSertifikatBox::withCount('sertifikats')
            ->where('id', '!=', $sourceId)
            ->orderBy('box_code', 'asc')
            ->get();

        $result = [];
        foreach ($targets as $target) {
            $targetCount = $target->sertifikats_count;
            if (($sourceCount + $targetCount) <= self::MAX_SERTIFIKAT_PER_BOX) {
                $target->combined_count = $sourceCount + $targetCount;
                $result[] = $target;
            }
        }

        return $result;
    }

    private function mergeLocationLabels(string $targetLocation, string $sourceLocation): string
    {
        $parts = [];
        foreach ([$targetLocation, $sourceLocation] as $location) {
            $items = preg_split('/\s*,\s*/', trim($location)) ?: [];
            foreach ($items as $item) {
                $item = trim($item);
                if ($item !== '') {
                    $parts[strtoupper($item)] = $item;
                }
            }
        }
        return implode(', ', array_values($parts));
    }

    private function availableSplitOptions(ElabelSertifikatBox $box, $items): array
    {
        $locations = $this->explodeLocationLabels((string) $box->lokasi);
        if (count($locations) < 2) return [];

        $counts = [];
        foreach ($items as $item) {
            $label = trim((string) ($item->lokasi ?? ''));
            if ($label !== '') {
                $counts[strtoupper($label)] = ($counts[strtoupper($label)] ?? 0) + 1;
            }
        }

        $options = [];
        foreach ($locations as $key => $label) {
            $options[] = [
                'label' => $label,
                'count' => $counts[$key] ?? 0,
            ];
        }

        return $options;
    }

    private function explodeLocationLabels(string $location): array
    {
        $parts = [];
        foreach (preg_split('/\s*,\s*/', trim($location)) ?: [] as $item) {
            $item = trim($item);
            if ($item !== '') {
                $parts[strtoupper($item)] = $item;
            }
        }
        return $parts;
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
