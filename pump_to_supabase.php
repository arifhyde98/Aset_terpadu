<?php

// Accurate Data Pump from backup.sql to Supabase PostgreSQL (Fixed Cascading Truncate)

$host = 'db.xxwcfbrywhqyifvzcudw.supabase.co';
$port = '5432';
$db = 'postgres';
$user = 'postgres';
$pass = 'Perubahan98';

echo "Connecting to Supabase PostgreSQL...\n";
$dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 60
]);

echo "Connected successfully.\n";

// Disable foreign key checks during bulk insert
$pdo->exec("SET session_replication_role = 'replica';");
echo "Foreign key checks disabled for session.\n";

// 1. Extract exact column order from CREATE TABLE in backup.sql
$sqlContent = file_get_contents('backup.sql');
preg_match_all('/CREATE TABLE `([^`]+)` \((.*?)\) ENGINE=/s', $sqlContent, $matches);

$tableSourceColumns = [];
for ($i = 0; $i < count($matches[1]); $i++) {
    $tName = $matches[1][$i];
    $body = $matches[2][$i];
    
    $cols = [];
    $lines = explode("\n", $body);
    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^`([^`]+)`\s+([a-zA-Z]+)/', $line, $m)) {
            $cols[] = $m[1];
        }
    }
    $tableSourceColumns[$tName] = $cols;
}

// 2. Fetch destination column types in Supabase
$colsMeta = $pdo->query("
    SELECT table_name, column_name, data_type 
    FROM information_schema.columns 
    WHERE table_schema = 'public'
")->fetchAll(PDO::FETCH_ASSOC);

$colTypeMap = [];
foreach ($colsMeta as $cm) {
    $colTypeMap[$cm['table_name']][$cm['column_name']] = $cm['data_type'];
}

function parseSqlValuesString($valuesStr) {
    $rows = [];
    $len = strlen($valuesStr);
    $inString = false;
    $stringChar = '';
    $inTuple = false;
    $currentVal = '';
    $currentRow = [];
    $escaped = false;

    for ($i = 0; $i < $len; $i++) {
        $ch = $valuesStr[$i];

        if ($inString) {
            if ($escaped) {
                $currentVal .= $ch;
                $escaped = false;
            } elseif ($ch === '\\') {
                $escaped = true;
            } elseif ($ch === $stringChar) {
                if ($i + 1 < $len && $valuesStr[$i + 1] === $stringChar) {
                    $currentVal .= $stringChar;
                    $i++;
                } else {
                    $inString = false;
                }
            } else {
                $currentVal .= $ch;
            }
            continue;
        }

        if ($ch === "'" || $ch === '"') {
            $inString = true;
            $stringChar = $ch;
            continue;
        }

        if ($ch === '(' && !$inTuple) {
            $inTuple = true;
            $currentRow = [];
            $currentVal = '';
            continue;
        }

        if ($ch === ')' && $inTuple) {
            $currentRow[] = trim($currentVal);
            $rows[] = $currentRow;
            $currentRow = [];
            $currentVal = '';
            $inTuple = false;
            continue;
        }

        if ($ch === ',' && $inTuple) {
            $currentRow[] = trim($currentVal);
            $currentVal = '';
            continue;
        }

        if ($inTuple) {
            $currentVal .= $ch;
        }
    }

    return $rows;
}

$skippedTables = ['migrations', 'cache', 'sessions'];

// 3. Truncate all non-skipped tables in Supabase ONCE at the start
echo "Truncating tables once at start...\n";
$tablesToTruncate = array_filter(array_keys($tableSourceColumns), function($t) use ($skippedTables) {
    return !in_array($t, $skippedTables);
});
$truncateSql = 'TRUNCATE TABLE "' . implode('", "', $tablesToTruncate) . '" CASCADE;';
$pdo->exec($truncateSql);
echo "All target tables truncated successfully.\n";

$file = fopen('backup.sql', 'r');
$importedCounts = [];

echo "\nStarting data pump from backup.sql...\n";

while (($line = fgets($file)) !== false) {
    $line = trim($line);
    if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
        continue;
    }

    if (preg_match('/^INSERT INTO `([^`]+)` VALUES (.*);$/i', $line, $matches)) {
        $tableName = $matches[1];
        $valuesPart = $matches[2];

        if (in_array($tableName, $skippedTables)) {
            echo "Skipping table: {$tableName}\n";
            continue;
        }

        if (!isset($tableSourceColumns[$tableName])) {
            echo "Warning: Table {$tableName} not found in schema. Skipping.\n";
            continue;
        }

        $parsedRows = parseSqlValuesString($valuesPart);
        $rowCount = count($parsedRows);
        if ($rowCount === 0) continue;

        // Check columns count in parsed row vs source schema
        $actualColsInDump = count($parsedRows[0]);
        $schemaCols = $tableSourceColumns[$tableName];
        $colsToUse = array_slice($schemaCols, 0, $actualColsInDump);

        echo "Importing table: {$tableName} ({$rowCount} rows)... ";

        $colNamesSql = array_map(function($c) { return '"' . $c . '"'; }, $colsToUse);

        $batchSize = 200;
        $totalInserted = 0;

        for ($b = 0; $b < $rowCount; $b += $batchSize) {
            $batchRows = array_slice($parsedRows, $b, $batchSize);
            $placeholders = [];
            $params = [];

            foreach ($batchRows as $rawRow) {
                $rowPlaceholders = [];
                foreach ($colsToUse as $idx => $colName) {
                    $rawVal = $rawRow[$idx] ?? 'NULL';
                    $targetType = $colTypeMap[$tableName][$colName] ?? 'text';

                    if ($rawVal === 'NULL' || $rawVal === 'null' || $rawVal === '') {
                        $rowPlaceholders[] = 'NULL';
                    } elseif ($targetType === 'boolean') {
                        if ($rawVal === '1' || strtolower($rawVal) === 'true') {
                            $rowPlaceholders[] = 'TRUE';
                        } else {
                            $rowPlaceholders[] = 'FALSE';
                        }
                    } else {
                        // Unescape string values
                        $cleanVal = stripcslashes($rawVal);
                        if ((strpos($cleanVal, "'") === 0 && substr($cleanVal, -1) === "'") ||
                            (strpos($cleanVal, '"') === 0 && substr($cleanVal, -1) === '"')) {
                            $cleanVal = substr($cleanVal, 1, -1);
                        }

                        // Fix invalid zero dates in MySQL (0000-00-00)
                        if ($targetType === 'date' || strpos($targetType, 'timestamp') !== false) {
                            if (strpos($cleanVal, '0000-00-00') !== false) {
                                $rowPlaceholders[] = 'NULL';
                                continue;
                            }
                        }

                        $rowPlaceholders[] = '?';
                        $params[] = $cleanVal;
                    }
                }
                $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
            }

            $sql = 'INSERT INTO "' . $tableName . '" (' . implode(', ', $colNamesSql) . ') VALUES ' . implode(', ', $placeholders);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $totalInserted += count($batchRows);
        }

        $importedCounts[$tableName] = $totalInserted;
        echo "DONE ({$totalInserted} rows)\n";

        // Update sequence for auto-increment ID
        $seqRows = $pdo->query("
            SELECT column_name, pg_get_serial_sequence('\"{$tableName}\"', column_name) as seq 
            FROM information_schema.columns 
            WHERE table_schema = 'public' AND table_name = '{$tableName}' AND column_default LIKE 'nextval%'
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($seqRows as $sr) {
            if (!empty($sr['seq'])) {
                $colName = $sr['column_name'];
                $seq = $sr['seq'];
                $pdo->exec("SELECT setval('{$seq}', COALESCE((SELECT MAX(\"{$colName}\") FROM \"{$tableName}\"), 1));");
            }
        }
    }
}

fclose($file);

// Re-enable foreign keys
$pdo->exec("SET session_replication_role = 'origin';");
echo "\nForeign key checks re-enabled.\n";

echo "\n🎉 SUMMARY IMPORT TO SUPABASE:\n";
$grandTotal = 0;
foreach ($importedCounts as $tbl => $cnt) {
    echo "  - {$tbl}: {$cnt} rows\n";
    $grandTotal += $cnt;
}
echo "Total records imported: {$grandTotal} rows across " . count($importedCounts) . " tables.\n";
