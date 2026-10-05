<?php

/**
 * Runner de migrations: aplica database/migrations/NNN_*.sql (ou .php) em ordem, uma única vez cada.
 * Um .php deve retornar um callable que recebe o PDO, para migrations que precisam de lógica.
 *
 * Uso: php migrate.php [--dry-run]
 *
 * O MySQL faz commit implícito em DDL, então não há rollback: o script para no primeiro
 * erro e só registra em schema_migrations o arquivo que terminou por completo.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/config/config.php';

$dryRun = in_array('--dry-run', $argv, true);

// IPv4 explícito: a liberação de acesso remoto costuma ser por IPv4
$host = gethostbyname(DB_HOST);

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10]
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS schema_migrations (
            version    VARCHAR(100) PRIMARY KEY,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro de conexão: ' . $e->getMessage() . "\n");
    exit(1);
}

$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files   = glob(__DIR__ . '/database/migrations/[0-9][0-9][0-9]_*.{sql,php}', GLOB_BRACE) ?: [];
sort($files);

$pending = array_filter($files, fn (string $f) => !in_array(pathinfo($f, PATHINFO_FILENAME), $applied, true));

if ($pending === []) {
    echo "Nada a aplicar.\n";
    exit(0);
}

foreach ($pending as $file) {
    $version = pathinfo($file, PATHINFO_FILENAME);

    if ($dryRun) {
        echo "[dry-run] $version\n";
        continue;
    }

    echo "Aplicando $version... ";

    try {
        if (str_ends_with($file, '.php')) {
            (require $file)($pdo);
        } else {
            // Remove comentários de linha antes de separar as instruções por ';'
            $sql        = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents($file));
            $statements = array_filter(array_map('trim', explode(';', $sql)), fn (string $s) => $s !== '');

            foreach ($statements as $statement) {
                $pdo->exec($statement);
            }
        }

        $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (:v)')->execute(['v' => $version]);
        echo "ok\n";
    } catch (Throwable $e) {
        echo "FALHOU\n";
        fwrite(STDERR, "Arquivo: $file\nErro: " . $e->getMessage() . "\n");
        exit(1);
    }
}
