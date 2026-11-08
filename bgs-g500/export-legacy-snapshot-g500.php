<?php
/** G5.00: read-only consistent extraction. PRIVATELY STORED DATA. Does NOT import anywhere. */
declare(strict_types=1);
ini_set('display_errors', 'stderr');
error_reporting(E_ALL);
if (PHP_SAPI !== 'cli' || getenv('HOME') !== '/home/smartlo1') {
    fwrite(STDERR, "BLOCKED: run as smartlo1 on legacy Jagoan host\n"); exit(1);
}
$base = '/home/smartlo1/bgs-legacy-audit-g480';
$envFile = '/home/smartlo1/web/erp-bgs.smartlog.id/.env';
if (!is_file($envFile) || is_link($envFile)) { fwrite(STDERR, "BLOCKED: legacy env missing\n"); exit(1); }
$env = parse_ini_file($envFile, false, INI_SCANNER_RAW);
if (!is_array($env) || ($env['DB_DATABASE'] ?? '') !== 'smartlo1_erp_app') {
    fwrite(STDERR, "BLOCKED: legacy database identity\n"); exit(1);
}
$port = (string)($env['DB_PORT'] ?? '3306');
if (!ctype_digit($port)) { fwrite(STDERR, "BLOCKED: database port\n"); exit(1); }
$groups = [
    'masters' => ['companies','customers','suppliers','products'],
    'orders' => ['quotations','quotation_details','po_customers','po_customer_details','po_suppliers','po_supplier_details'],
    'fulfilment' => ['goods_receipts','goods_receipt_details','invoice_customers','invoice_customer_details','delivery_orders','delivery_order_details','document_receipts'],
];
try {
    $pdo = new PDO('mysql:host='.($env['DB_HOST'] ?? '127.0.0.1').';port='.$port.';dbname=smartlo1_erp_app;charset=utf8mb4',
        (string)($env['DB_USERNAME'] ?? ''), (string)($env['DB_PASSWORD'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 20,
        ]);
    if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== 'smartlo1_erp_app') throw new RuntimeException('Database mismatch');
    $folder = $base.'/private-snapshots';
    if (!is_dir($folder) && !mkdir($folder, 0700)) throw new RuntimeException('Cannot create private directory');
    if (is_link($folder)) throw new RuntimeException('Symlink output directory rejected');
    chmod($folder, 0700);
    $run = $folder.'/g500-'.date('Ymd-His').'-'.bin2hex(random_bytes(4));
    if (!mkdir($run, 0700)) throw new RuntimeException('Cannot create run directory');
    $manifest = ['report'=>'BGS_G500_READ_ONLY_SOURCE_SNAPSHOT', 'source'=>'smartlo1_erp_app',
        'captured_at'=>date(DATE_ATOM), 'files'=>[], 'tables'=>[],
        'known_exceptions'=>[
            ['source_table'=>'invoice_customer_details','source_id'=>30,'reason'=>'ORPHAN_INVOICE_HEADER','decision'=>'HOLD_NO_AUTO_MAP'],
            ['source_table'=>'document_receipts','source_id'=>2,'reason'=>'UNMATCHED_INVOICE_NUMBER','decision'=>'HOLD_NO_AUTO_MAP'],
        ], 'scope_exclusions'=>['customers2','products6','products_6','invoice_customers_backup','dpp_headers','payments_and_other_tables'],
        'notes'=>['No records were excluded from these raw snapshots','Do not import without exception policy and reconciliation','Raw snapshot contains confidential business data; never upload to GitHub or chat'],
        'database_writes'=>0];
    $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT, READ ONLY');
    try {
        foreach ($groups as $group=>$tables) {
            $snapshot = ['database'=>'smartlo1_erp_app','tables'=>[]];
            foreach ($tables as $table) {
                // Only fixed, hardcoded names above can reach a SQL identifier.
                $q = $pdo->query('SELECT * FROM `'.$table.'` ORDER BY `id` ASC');
                $rows = $q->fetchAll();
                $snapshot['tables'][$table] = $rows;
                $manifest['tables'][$table] = ['count'=>count($rows), 'max_id'=>0];
                foreach ($rows as $row) $manifest['tables'][$table]['max_id'] = max($manifest['tables'][$table]['max_id'], (int)$row['id']);
            }
            $path = $run.'/'.$group.'.json';
            $payload = json_encode($snapshot, JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
            if (file_put_contents($path, $payload, LOCK_EX) !== strlen($payload)) throw new RuntimeException('Snapshot write failure');
            chmod($path,0600);
            $manifest['files'][$group.'.json'] = ['sha256'=>hash_file('sha256',$path),'bytes'=>filesize($path)];
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
    $manifestPath = $run.'/manifest.json';
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)."\n", LOCK_EX);
    chmod($manifestPath,0600);
    echo "G500_EXPORT=PASS\nDATABASE_WRITES=0\nPRIVATE_DIRECTORY=$run\n";
    foreach ($manifest['tables'] as $table=>$stats) echo 'COUNT_'.$table.'='.$stats['count']."\n";
    echo 'MANIFEST_SHA256='.hash_file('sha256',$manifestPath)."\n";
    echo "NEXT_GATE=REAL_DATA_RECONCILIATION_NOT_PRODUCTION_IMPORT\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'G500_EXPORT=BLOCKED ERROR_CLASS='.get_class($e).'\n');
    exit(1);
}
