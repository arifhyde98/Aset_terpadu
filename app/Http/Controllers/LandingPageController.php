<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\UnifiedAssetSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    protected UnifiedAssetSearchService $searchService;

    public function __construct(UnifiedAssetSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Menampilkan Landing Page SIPAT Terpadu (Akses Publik).
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // 1. Ambil Statistik Live
        $stats = $this->searchService->getPortalStats();

        // 2. Ambil Opsi Filter Dropdown
        $filterOptions = $this->searchService->getFilterOptions();

        // 3. Ambil Konfigurasi Web / Branding
        $settings = [
            'site_name' => Setting::get('site_name', 'SIPAT TERPADU'),
            'site_subtitle' => Setting::get('site_subtitle', 'Sistem Informasi Aset Pemerintah Daerah'),
            'site_logo' => Setting::get('site_logo'),
            'site_logo_right' => Setting::get('site_logo_right') ?: 'images/logo.png',
            'hero_title' => 'Cari Informasi Aset Daerah',
            'hero_subtitle' => 'Akses informasi kendaraan dinas, status sertifikasi tanah, dan ketersediaan arsip aset melalui satu portal terpadu.',
            'hero_image' => Setting::get('hero_image', 'images/hero-illustration.png'),
            'hero_bg_image' => Setting::get('hero_bg_image', 'images/hero-illustration.png'),
        ];

        return view('landing.index', compact('stats', 'filterOptions', 'settings'));
    }

    /**
     * Endpoint API Pencarian Kendaraan Dinas untuk AJAX.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchVehicles(Request $request): JsonResponse
    {
        $params = $request->only(['q', 'search_by', 'opd', 'jenis', 'status', 'limit']);
        $result = $this->searchService->searchVehicles($params);

        return response()->json($result);
    }

    /**
     * Endpoint API Pencarian Sertifikat Tanah untuk AJAX.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchLand(Request $request): JsonResponse
    {
        $params = $request->only(['q', 'search_by', 'opd', 'status_sertifikasi', 'kecamatan', 'desa', 'limit']);
        $result = $this->searchService->searchLand($params);

        return response()->json($result);
    }

    /**
     * Endpoint API Pencarian Ketersediaan Arsip untuk AJAX.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchArchives(Request $request): JsonResponse
    {
        $params = $request->only(['q', 'search_by', 'opd', 'doc_type', 'status_arsip', 'box_location', 'limit']);
        $result = $this->searchService->searchArchives($params);

        return response()->json($result);
    }

    /**
     * Endpoint API Pengambilan Statistik Portal.
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->searchService->getPortalStats();
        return response()->json($stats);
    }
}
