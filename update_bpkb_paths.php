<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sourceRecords = DB::connection('mysql')->select("SELECT no_bpkb, pdf_path FROM elabel.bpkb WHERE pdf_path IS NOT NULL AND pdf_path != ''");
$updatedCount = 0;

foreach ($sourceRecords as $record) {
    if (!$record->no_bpkb) continue;
    $basename = basename($record->pdf_path);
    $newPath = 'elabel/bpkb/' . $basename;
    
    $affected = DB::table('elabel_bpkb')
        ->where('no_bpkb', $record->no_bpkb)
        ->update(['pdf_path' => $newPath]);
        
    if ($affected) {
        $updatedCount += $affected;
    }
}

echo "Berhasil update {$updatedCount} record BPKB!\n";
