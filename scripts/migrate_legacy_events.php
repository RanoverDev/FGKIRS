<?php

/**
 * Leva os eventos do modelo antigo (legacy) para o novo (v2), como histórico.
 *
 * Uso: php scripts/migrate_legacy_events.php --dry-run   (relatório, nada é gravado)
 *      php scripts/migrate_legacy_events.php             (grava)
 *      php scripts/migrate_legacy_events.php --event=12  (só um evento)
 *
 * Pré-requisitos: migrations até a 005, scripts/migrate_athletes.php já executado e backup
 * (mysqldump) guardado fora do servidor. Cada evento roda numa transação e só é gravado se as
 * contagens e as inscrições baterem antes e depois; as tabelas antigas não são alteradas.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require __DIR__ . '/../config/config.php';

use Services\LegacyEventMigrator;

$dryRun = in_array('--dry-run', $argv, true);
$only   = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--event=')) {
        $only = (int) substr($arg, 8);
    }
}

// IPv4 explícito: a liberação de acesso remoto costuma ser por IPv4
$pdo = new PDO(
    'mysql:host=' . gethostbyname(DB_HOST) . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_TIMEOUT => 10]
);

$migrator = new LegacyEventMigrator($pdo);
$events   = array_filter($migrator->pending(), static fn (array $e) => $only === null || (int) $e['id'] === $only);

if ($events === []) {
    echo "Nenhum evento no modelo antigo para migrar.\n";
    exit(0);
}

echo $dryRun ? "[DRY-RUN] nada será gravado\n\n" : "Migrando eventos antigos\n\n";

$failed = 0;
$labels = ['athletes' => 'atletas', 'entries' => 'entradas', 'teams' => 'equipes', 'members' => 'integrantes', 'dojos' => 'dojos'];

foreach ($events as $event) {
    $report = $migrator->migrate($event, $dryRun);

    echo sprintf("#%d %s — %s\n", $report['id'], $report['title'], strtoupper($report['status']));

    foreach ($report['messages'] as $message) {
        echo "    $message\n";
    }

    if ($report['after']) {
        foreach ($labels as $key => $label) {
            echo sprintf("    %-12s antes %-5d depois %d\n", $label, $report['before'][$key], $report['after'][$key]);
        }
    }

    echo "\n";
    $failed += $report['status'] === 'failed' ? 1 : 0;
}

echo $failed ? "$failed evento(s) com falha: nada foi gravado para eles.\n" : "Concluído sem falhas.\n";
exit($failed ? 1 : 0);
