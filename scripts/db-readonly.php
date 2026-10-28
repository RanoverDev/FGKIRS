<?php

/**
 * Consulta somente-leitura ao banco, para inspeção via CLI.
 *
 * Uso: php scripts/db-readonly.php "SELECT COUNT(*) FROM competition_categories"
 *
 * Aceita uma única instrução SELECT, SHOW ou DESCRIBE. A sessão MySQL também
 * é aberta em READ ONLY, então mesmo que o filtro falhe o banco recusa escrita.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require __DIR__ . '/../config/config.php';

$sql = trim($argv[1] ?? '');
$sql = rtrim($sql, "; \t\n\r");

$allowed   = (bool) preg_match('/^(SELECT|SHOW|DESCRIBE|DESC)\b/i', $sql);
$multiple  = str_contains($sql, ';');
$forbidden = (bool) preg_match('/\b(INTO\s+(OUTFILE|DUMPFILE)|LOAD_FILE|SLEEP|BENCHMARK|FOR\s+UPDATE)\b/i', $sql);

if ($sql === '' || !$allowed || $multiple || $forbidden) {
    fwrite(STDERR, "Recusado: use uma única consulta SELECT, SHOW ou DESCRIBE.\n");
    exit(2);
}

// IPv4 explícito: a liberação de acesso remoto costuma ser por IPv4, e o PHP
// prefere IPv6 quando o host tem os dois registros
$host = gethostbyname(DB_HOST);

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]
    );
    $pdo->exec('SET SESSION TRANSACTION READ ONLY');

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
    exit(1);
}

foreach (array_slice($rows, 0, 200) as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
}

if (count($rows) > 200) {
    echo '… (' . count($rows) . " linhas, mostrando 200)\n";
}
