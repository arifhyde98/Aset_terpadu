<?php
// ==========================================================================
// PORTAL EKOSISTEM PROJEK BPKAD DONGGALA (DYNAMIC STANDALONE APPLICATION)
// ==========================================================================

function scanProjects() {
    $projekDir = '/home/arif/Projek';
    $nginxDir = '/etc/nginx/sites-available';
    $nginxEnabledDir = '/etc/nginx/sites-enabled';

    // 1. Scan Nginx Configs
    $nginxConfigs = [];
    if (is_dir($nginxDir)) {
        $files = glob($nginxDir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && !str_ends_with($file, '.save') && !str_contains($file, '.save.')) {
                $content = @file_get_contents($file);
                $basename = basename($file);
                $isLink = is_link($nginxEnabledDir . '/' . $basename) || file_exists($nginxEnabledDir . '/' . $basename);
                
                preg_match_all('/^\s*server_name\s+(.*?);/m', $content, $serverNameMatches);
                preg_match_all('/^\s*root\s+(.*?);/m', $content, $rootMatches);

                $serverNames = [];
                if (!empty($serverNameMatches[1])) {
                    foreach ($serverNameMatches[1] as $sn) {
                        $names = array_filter(explode(' ', trim($sn)));
                        $serverNames = array_merge($serverNames, $names);
                    }
                }

                $rootPath = !empty($rootMatches[1][0]) ? trim($rootMatches[1][0]) : '';

                $nginxConfigs[$basename] = [
                    'config' => $basename,
                    'enabled' => $isLink,
                    'server_names' => array_unique($serverNames),
                    'root' => $rootPath
                ];
            }
        }
    }

    // 2. Scan Folders di /home/arif/Projek
    $projects = [];
    $folders = glob($projekDir . '/*', GLOB_ONLYDIR);
    
    // Metadata deskripsi dan atribut projek
    $metaMap = [
        'SIPAT_Terpadu' => [
            'name' => 'SIPAT Terpadu',
            'category' => 'Single Platform',
            'icon' => 'bi-layers-half',
            'desc' => 'Platform terintegrasi tunggal pertanahan KIB A, gedung KIB C, kendaraan E-RANDIS, dan pengarsipan eLABEL.'
        ],
        'Aset_BPKAD' => [
            'name' => 'Aset BPKAD',
            'category' => 'Core Asset',
            'icon' => 'bi-building-gear',
            'desc' => 'Sistem utama pengelolaan inventarisasi, akuntabilitas, dan pelaporan data aset daerah BPKAD Donggala.'
        ],
        'E-RANDIS_PHP' => [
            'name' => 'E-RANDIS',
            'category' => 'Kendaraan Dinas',
            'icon' => 'bi-car-front-fill',
            'desc' => 'Manajemen kendaraan dinas roda 2/4/6, pelacakan pemegang fisik, pinjam pakai, dan mutasi aset.'
        ],
        'eLabel' => [
            'name' => 'eLABEL',
            'category' => 'Arsip Berkas',
            'icon' => 'bi-qr-code-scan',
            'desc' => 'Universal Dynamic Archive Engine untuk pelabelan barcode & manajemen fisik berkas sertifikat & BPKB.'
        ],
        'SIPAT' => [
            'name' => 'SIPAT System',
            'category' => 'Pertanahan Legacy',
            'icon' => 'bi-geo-alt-fill',
            'desc' => 'Sistem informasi pertanahan KIB A, histori pensertifikatan BPN, dan dokumen resmi SKPT.'
        ],
        'staging_SIPAT' => [
            'name' => 'Staging SIPAT',
            'category' => 'Testing Server',
            'icon' => 'bi-cpu-fill',
            'desc' => 'Environment uji coba real-time streaming SSE sinkronisasi basis data staging modul SIPAT.'
        ],
        'sigeo_donggala' => [
            'name' => 'SIGEO Donggala',
            'category' => 'Geospasial GIS',
            'icon' => 'bi-map-fill',
            'desc' => 'Sistem informasi geospasial pemetaan bidang tanah dan visualisasi layer peta GIS Donggala.'
        ],
        'MONITORING_SERVER' => [
            'name' => 'Server Monitor',
            'category' => 'Infrastructure',
            'icon' => 'bi-activity',
            'desc' => 'Dashboard pemantauan kesehatan server, penggunaan memori, CPU load, uptime, dan Nginx metrics.'
        ],
        'POS_universal' => [
            'name' => 'POS Universal',
            'category' => 'Transaction POS',
            'icon' => 'bi-cart-check-fill',
            'desc' => 'Aplikasi Point of Sales (POS) berbasis web untuk manajemen penjualan & persediaan barang.'
        ],
        'invite_kita' => [
            'name' => 'Invite Kita',
            'category' => 'Media & Events',
            'icon' => 'bi-envelope-paper-heart-fill',
            'desc' => 'Platform pembuat dan pengelola undangan digital interaktif.'
        ],
        'Atfalah_privat' => [
            'name' => 'Atfalah Privat',
            'category' => 'Private App',
            'icon' => 'bi-shield-lock-fill',
            'desc' => 'Layanan modul sistem internal privat Atfalah berbasis Nginx HTTP.'
        ],
        'SSO_Server' => [
            'name' => 'SSO Auth Server',
            'category' => 'Auth Gateway',
            'icon' => 'bi-key-fill',
            'desc' => 'Central Single Sign-On (SSO) gateway otentikasi terpusat untuk ekosistem aplikasi.'
        ],
        'Lapor_BPKAD' => [
            'name' => 'Lapor BPKAD',
            'category' => 'Development',
            'icon' => 'bi-chat-left-text-fill',
            'desc' => 'Aplikasi sistem pengaduan dan pengawasan layanan publik BPKAD (Pengembangan lokal).'
        ],
        'projek_test' => [
            'name' => 'projek_test',
            'category' => 'Sandbox',
            'icon' => 'bi-code-slash',
            'desc' => 'Direktori sandbox untuk pengujian script, pustaka, dan eksperimen fitur internal.'
        ]
    ];

    foreach ($folders as $folderPath) {
        $folderName = basename($folderPath);
        if (in_array($folderName, ['.git', '.agents', '.codex', '.skills', '.vscode', 'PORTAL_HOMEPAGE'])) {
            continue;
        }

        $matchedConf = null;
        $primaryDomain = null;
        $cleanFolder = strtolower(rtrim($folderPath, '/'));

        // Exact match algorithm to prevent substring false positives
        foreach ($nginxConfigs as $cKey => $conf) {
            if (!$conf['root']) continue;
            $cleanRoot = strtolower(rtrim(str_replace('/public', '', $conf['root']), '/'));

            if ($cleanRoot === $cleanFolder || str_starts_with($cleanRoot, $cleanFolder . '/') || str_starts_with($cleanFolder, $cleanRoot . '/')) {
                $matchedConf = $conf;
                break;
            }
        }

        if ($matchedConf) {
            // Prioritize domain ending in .sipat-donggala.my.id
            foreach ($matchedConf['server_names'] as $sn) {
                if (str_ends_with($sn, '.sipat-donggala.my.id') || $sn === 'sipat-donggala.my.id') {
                    $primaryDomain = $sn;
                    break;
                }
            }
            if (!$primaryDomain && !empty($matchedConf['server_names'])) {
                $primaryDomain = $matchedConf['server_names'][0];
            }
        }

        $meta = $metaMap[$folderName] ?? [
            'name' => $folderName,
            'category' => 'Module',
            'icon' => 'bi-folder2-open',
            'desc' => "Direktori modul aplikasi {$folderName} di lingkungan /home/arif/Projek."
        ];

        // Live Health Check via Fast cURL
        $url = null;
        $httpCode = 0;
        $statusKey = 'local'; // 'live', 'standby', 'local'

        if ($primaryDomain) {
            $scheme = (str_contains($primaryDomain, 'atfalah')) ? 'http' : 'https';
            $url = "{$scheme}://{$primaryDomain}";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 400) {
                $statusKey = 'live';
            } else if ($httpCode > 0) {
                $statusKey = 'standby';
            } else {
                $statusKey = 'standby';
            }
        }

        $projects[] = [
            'id' => strtolower($folderName),
            'folder' => $folderName,
            'path' => $folderPath,
            'name' => $meta['name'],
            'category' => $meta['category'],
            'icon' => $meta['icon'],
            'desc' => $meta['desc'],
            'domain' => $primaryDomain,
            'url' => $url,
            'http_code' => $httpCode,
            'status' => $statusKey,
            'nginx_config' => $matchedConf ? $matchedConf['config'] : null
        ];
    }

    $stats = [
        'total' => count($projects),
        'live' => count(array_filter($projects, fn($p) => $p['status'] === 'live')),
        'standby' => count(array_filter($projects, fn($p) => $p['status'] === 'standby')),
        'local' => count(array_filter($projects, fn($p) => $p['status'] === 'local')),
    ];

    return [
        'scanned_at' => date('Y-m-d H:i:s'),
        'stats' => $stats,
        'projects' => $projects
    ];
}

// If API Request
if (isset($_GET['api']) || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
    header('Content-Type: application/json');
    echo json_encode(scanProjects());
    exit;
}

$data = scanProjects();
$stats = $data['stats'];
$projects = $data['projects'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Ekosistem Projek BPKAD Donggala</title>
    <!-- Google Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0B0F19;
            --surface-dark: #111827;
            --surface-card: #1E293B;
            --accent-blue: #38BDF8;
            --accent-indigo: #6366F1;
            --accent-emerald: #10B981;
            --accent-amber: #F59E0B;
            --accent-purple: #A855F7;
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --border-glass: rgba(255, 255, 255, 0.08);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(56, 189, 248, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            background-attachment: fixed;
            overflow-x: hidden;
        }

        .navbar-brand-badge {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-blue));
            color: white;
            font-weight: 800;
            padding: 0.4rem 0.9rem;
            border-radius: 0.75rem;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }

        .hero-banner {
            padding: 3rem 0 2rem;
            text-align: center;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFFFFF 30%, #94A3B8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }

        .hero-subtitle {
            color: var(--text-muted);
            font-size: 1.05rem;
            max-width: 680px;
            margin: 0 auto 1.75rem;
        }

        .stat-pill {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-glass);
            border-radius: 1rem;
            padding: 0.75rem 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .stat-item { text-align: center; }
        .stat-num { font-size: 1.5rem; font-weight: 800; line-height: 1; }
        .stat-lbl { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 0.25rem; }

        .search-container {
            max-width: 600px;
            margin: 1.75rem auto 1rem;
        }

        .search-input-group {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 1rem;
            padding: 0.35rem 0.5rem 0.35rem 1.25rem;
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
        }

        .search-input-group:focus-within {
            border-color: var(--accent-blue);
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.25);
        }

        .search-input-group input {
            background: transparent;
            border: none;
            color: white;
            outline: none;
            width: 100%;
            font-size: 0.95rem;
        }

        .search-input-group input::placeholder { color: #64748B; }

        .filter-btn {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--border-glass);
            color: var(--text-muted);
            border-radius: 0.75rem;
            padding: 0.5rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--accent-indigo);
            color: white;
            border-color: var(--accent-indigo);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35);
        }

        /* Project Cards */
        .project-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 1.25rem;
            padding: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .project-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: transparent;
            transition: all 0.3s ease;
        }

        .project-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.5);
        }

        .project-card.status-live:hover::before { background: linear-gradient(90deg, #10B981, #34D399); }
        .project-card.status-standby:hover::before { background: linear-gradient(90deg, #F59E0B, #FBBF24); }
        .project-card.status-local:hover::before { background: linear-gradient(90deg, #6366F1, #818CF8); }

        .card-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 1rem;
        }

        .icon-live { background: rgba(16, 185, 129, 0.15); color: #34D399; }
        .icon-standby { background: rgba(245, 158, 11, 0.15); color: #FBBF24; }
        .icon-local { background: rgba(99, 102, 241, 0.15); color: #818CF8; }

        .status-badge {
            font-size: 0.725rem;
            font-weight: 700;
            padding: 0.3rem 0.7rem;
            border-radius: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .badge-live { background: rgba(16, 185, 129, 0.15); color: #34D399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-standby { background: rgba(245, 158, 11, 0.15); color: #FBBF24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-local { background: rgba(99, 102, 241, 0.15); color: #818CF8; border: 1px solid rgba(99, 102, 241, 0.3); }

        .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot-live { background-color: #10B981; box-shadow: 0 0 8px #10B981; animation: pulse 2s infinite; }
        .dot-standby { background-color: #F59E0B; box-shadow: 0 0 8px #F59E0B; }
        .dot-local { background-color: #6366F1; }

        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .project-title { font-size: 1.15rem; font-weight: 700; color: white; margin-bottom: 0.25rem; }
        .project-folder { font-family: monospace; font-size: 0.775rem; color: #64748B; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.35rem; }
        .project-desc { color: var(--text-muted); font-size: 0.875rem; line-height: 1.5; margin-bottom: 1.25rem; }

        .btn-visit {
            background: linear-gradient(135deg, var(--accent-indigo), var(--accent-blue));
            color: white;
            border: none;
            border-radius: 0.75rem;
            padding: 0.55rem 1rem;
            font-size: 0.85rem;
            font-weight: 700;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-visit:hover {
            color: white; opacity: 0.92; transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(56, 189, 248, 0.35);
        }

        .btn-disabled {
            background: rgba(255, 255, 255, 0.05);
            color: #64748B;
            border: 1px solid var(--border-glass);
            border-radius: 0.75rem;
            padding: 0.55rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: not-allowed;
            text-decoration: none;
        }

        .cat-tag {
            background: rgba(255, 255, 255, 0.06);
            color: #CBD5E1;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 0.4rem;
            font-weight: 600;
        }

        .footer {
            border-top: 1px solid var(--border-glass);
            padding: 2rem 0;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 4rem;
        }

        .spin-icon { animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <!-- Header / Navbar -->
    <nav class="navbar navbar-dark py-3 border-bottom border-secondary border-opacity-25">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <span class="navbar-brand-badge">BPKAD</span>
                <span class="fw-bold text-white fs-5 ms-1">Portal Ekosistem Projek</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="refreshScanner()">
                    <i class="bi bi-arrow-clockwise me-1" id="refreshIcon"></i> Live Scan
                </button>
                <span class="badge bg-dark border border-secondary text-secondary font-monospace px-3 py-2 rounded-pill">
                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> Kab. Donggala
                </span>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <div class="container hero-banner">
        <h1 class="hero-title">Portal Projek Dinamis BPKAD</h1>
        <p class="hero-subtitle">Direktori terpadu berbasis pemindaian otomatis Nginx & direktori server secara real-time untuk seluruh aplikasi BPKAD Donggala.</p>

        <!-- Dynamic Statistics -->
        <div class="stat-pill" id="statPill">
            <div class="stat-item">
                <div class="stat-num text-white" id="statTotal"><?= $stats['total'] ?></div>
                <div class="stat-lbl">Total Projek</div>
            </div>
            <div class="vr bg-secondary opacity-50" style="height: 30px;"></div>
            <div class="stat-item">
                <div class="stat-num text-success" id="statLive"><?= $stats['live'] ?></div>
                <div class="stat-lbl">Live Online</div>
            </div>
            <div class="vr bg-secondary opacity-50" style="height: 30px;"></div>
            <div class="stat-item">
                <div class="stat-num text-warning" id="statStandby"><?= $stats['standby'] ?></div>
                <div class="stat-lbl">Standby</div>
            </div>
            <div class="vr bg-secondary opacity-50" style="height: 30px;"></div>
            <div class="stat-item">
                <div class="stat-num text-info" id="statLocal"><?= $stats['local'] ?></div>
                <div class="stat-lbl">Lokal Dev</div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-container">
            <div class="search-input-group d-flex align-items-center">
                <i class="bi bi-search text-secondary me-2 fs-5"></i>
                <input type="text" id="projectSearch" placeholder="Cari nama projek, domain, kategori, atau nama folder..." onkeyup="filterProjects()">
            </div>
        </div>

        <!-- Filter Category Buttons -->
        <div class="d-flex justify-content-center flex-wrap gap-2 mt-3">
            <button class="filter-btn active" onclick="setFilter('all', this)">Semua (<?= $stats['total'] ?>)</button>
            <button class="filter-btn" onclick="setFilter('live', this)"><i class="bi bi-check-circle-fill text-success me-1"></i>Live Online (<?= $stats['live'] ?>)</button>
            <button class="filter-btn" onclick="setFilter('standby', this)"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Standby (<?= $stats['standby'] ?>)</button>
            <button class="filter-btn" onclick="setFilter('local', this)"><i class="bi bi-laptop me-1"></i>Lokal / Dev (<?= $stats['local'] ?>)</button>
        </div>
    </div>

    <!-- Main Project Grid -->
    <div class="container pb-5">
        <div class="row g-4" id="projectGrid">
            <?php foreach ($projects as $p): ?>
                <div class="col-md-6 col-lg-4 project-item" data-status="<?= $p['status'] ?>" data-search="<?= strtolower($p['name'] . ' ' . $p['folder'] . ' ' . $p['category'] . ' ' . $p['domain'] . ' ' . $p['desc']) ?>">
                    <div class="project-card status-<?= $p['status'] ?>">
                        <div>
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="card-icon-wrapper icon-<?= $p['status'] ?>">
                                    <i class="bi <?= $p['icon'] ?>"></i>
                                </div>
                                <?php if ($p['status'] === 'live'): ?>
                                    <span class="status-badge badge-live">
                                        <span class="pulse-dot dot-live"></span> 200 OK (Live)
                                    </span>
                                <?php elseif ($p['status'] === 'standby'): ?>
                                    <span class="status-badge badge-standby">
                                        <span class="pulse-dot dot-standby"></span> Standby / <?= $p['http_code'] > 0 ? $p['http_code'] : '404' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge badge-local">
                                        <span class="pulse-dot dot-local"></span> Lokal Dev
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <h3 class="project-title mb-0"><?= htmlspecialchars($p['name']) ?></h3>
                                <span class="cat-tag ms-auto"><?= htmlspecialchars($p['category']) ?></span>
                            </div>
                            <div class="project-folder">
                                <i class="bi bi-folder-fill text-amber opacity-75"></i> /home/arif/Projek/<?= htmlspecialchars($p['folder']) ?>
                            </div>
                            <p class="project-desc"><?= htmlspecialchars($p['desc']) ?></p>
                        </div>
                        <div>
                            <?php if ($p['url']): ?>
                                <a href="<?= htmlspecialchars($p['url']) ?>" target="_blank" class="btn-visit" <?= $p['status'] === 'standby' ? 'style="background: linear-gradient(135deg, #F59E0B, #D97706);"' : '' ?>>
                                    <span>Buka Aplikasi</span> <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            <?php else: ?>
                                <span class="btn-disabled">
                                    <i class="bi bi-pc-display"></i> Lingkungan Lokal
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- No Results Found State -->
        <div id="noResults" class="text-center py-5 d-none">
            <i class="bi bi-search-heart fs-1 text-secondary opacity-50 mb-3 d-block"></i>
            <h5 class="fw-bold text-white mb-1">Projek Tidak Ditemukan</h5>
            <p class="text-muted small">Coba masukkan kata kunci pencarian yang berbeda.</p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p class="mb-1 fw-medium">Portal Ekosistem Projek Dinamis &copy; 2026 BPKAD Kabupaten Donggala</p>
            <p class="mb-0 text-muted small">Terakhir dipindai: <span id="scanTime"><?= $data['scanned_at'] ?></span> &bull; Dynamic PHP Engine &bull; Nginx Auto-Discovery</p>
        </div>
    </footer>

    <!-- JavaScript Filter & Dynamic Scanner Refresh -->
    <script>
        let currentFilter = 'all';

        function setFilter(filter, btn) {
            currentFilter = filter;
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            filterProjects();
        }

        function filterProjects() {
            const query = document.getElementById('projectSearch').value.toLowerCase();
            const items = document.querySelectorAll('.project-item');
            let visibleCount = 0;

            items.forEach(item => {
                const status = item.getAttribute('data-status');
                const searchData = item.getAttribute('data-search').toLowerCase();

                const matchesStatus = (currentFilter === 'all') || (status === currentFilter);
                const matchesSearch = searchData.includes(query);

                if (matchesStatus && matchesSearch) {
                    item.classList.remove('d-none');
                    visibleCount++;
                } else {
                    item.classList.add('d-none');
                }
            });

            const noResults = document.getElementById('noResults');
            if (visibleCount === 0) {
                noResults.classList.remove('d-none');
            } else {
                noResults.classList.add('d-none');
            }
        }

        function refreshScanner() {
            const icon = document.getElementById('refreshIcon');
            icon.classList.add('spin-icon');
            
            fetch('./index.php?api=1')
                .then(res => res.json())
                .then(data => {
                    document.getElementById('statTotal').innerText = data.stats.total;
                    document.getElementById('statLive').innerText = data.stats.live;
                    document.getElementById('statStandby').innerText = data.stats.standby;
                    document.getElementById('statLocal').innerText = data.stats.local;
                    document.getElementById('scanTime').innerText = data.scanned_at;
                    
                    icon.classList.remove('spin-icon');
                })
                .catch(err => {
                    console.error('Scan refresh error:', err);
                    icon.classList.remove('spin-icon');
                });
        }
    </script>
</body>
</html>
