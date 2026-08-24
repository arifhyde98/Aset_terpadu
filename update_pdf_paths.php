<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceRecords = DB::connection('mysql')->select("SELECT nibar, pdf_path FROM elabel.sertifikat_tanah WHERE pdf_path IS NOT NULL AND pdf_path != ''");
$updatedCount = 0;

foreach ($sourceRecords as $record) {
    if (!$record->nibar) continue;
    $basename = basename($record->pdf_path);
    $newPath = 'elabel/sertifikat/' . $basename;
    
    $affected = DB::table('elabel_sertifikat_tanah')
        ->where('nibar', $record->nibar)
        ->update(['pdf_path' => $newPath]);
        
    if ($affected) {
        $updatedCount += $affected;
    }
}

echo "Berhasil update {$updatedCount} record!\n";
