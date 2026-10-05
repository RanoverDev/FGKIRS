<?php

/**
 * Migra os atletas existentes para a tabela athletes e preenche championship_athletes.athlete_id.
 *
 * Uso: php scripts/migrate_athletes.php --dry-run   (relatório, nada é gravado)
 *      php scripts/migrate_athletes.php             (grava)
 *
 * Roda tudo numa transação: no --dry-run ela é desfeita no fim, então o relatório é
 * exatamente o que a execução real faria. Idempotente: alunos são reconhecidos por
 * user_id e avulsos por dojo + nome normalizado + nascimento.
 *
 * Pré-requisito: php migrate.php (migration 002).
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../app/Models/Athlete.php';

use Models\Athlete;

$dryRun = in_array('--dry-run', $argv, true);

// IPv4 explícito: a liberação de acesso remoto costuma ser por IPv4
$pdo = new PDO(
    'mysql:host=' . gethostbyname(DB_HOST) . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 10,
    ]
);

$naturalKey = static fn (int $dojoId, string $name, string $birth): string
    => $dojoId . '|' . Athlete::normalizeName($name) . '|' . $birth;

$report = ['created_users' => 0, 'created_guests' => 0, 'reused' => 0, 'linked' => 0, 'ignored' => []];

$insert = $pdo->prepare(
    "INSERT INTO athletes
        (dojo_id, user_id, full_name, birth_date, gender, style_id, graduation_id, weight_kg, height_cm,
         is_para_karate, fgkirs_registration, cbki_registration, status, created_by)
     VALUES
        (:dojo_id, :user_id, :full_name, :birth_date, :gender, :style_id, :graduation_id, :weight_kg, :height_cm,
         :is_para_karate, :fgkirs_registration, :cbki_registration, :status, :created_by)"
);

$pdo->beginTransaction();

try {
    // Índices em memória do que já existe em athletes
    $byUser = [];
    $byKey  = [];
    foreach ($pdo->query('SELECT id, user_id, dojo_id, full_name, birth_date FROM athletes') as $a) {
        if ($a['user_id'] !== null) {
            $byUser[(int) $a['user_id']] = (int) $a['id'];
        }
        $byKey[$naturalKey((int) $a['dojo_id'], $a['full_name'], $a['birth_date'])] = (int) $a['id'];
    }

    // 1. Alunos com login e perfil de atleta
    $users = $pdo->query(
        "SELECT u.id AS user_id, u.name, u.dojo_id, ap.birth_date, ap.gender, ap.weight, ap.height,
                ap.style_id, ap.graduation_id, ap.is_para_karate, ap.fgkirs_registration, ap.cbki_registration
         FROM users u
         JOIN athlete_profiles ap ON ap.user_id = u.id
         WHERE u.dojo_id IS NOT NULL"
    )->fetchAll();

    foreach ($users as $u) {
        $userId = (int) $u['user_id'];

        if (isset($byUser[$userId])) {
            $report['reused']++;
            continue;
        }

        if (empty($u['birth_date']) || !in_array($u['gender'], ['M', 'F'], true)) {
            $report['ignored'][] = "usuário #$userId {$u['name']}: perfil sem data de nascimento ou sexo M/F";
            continue;
        }

        $key = $naturalKey((int) $u['dojo_id'], $u['name'], $u['birth_date']);

        // Mesma pessoa já cadastrada como avulsa em outra rodada: liga o login a ela em vez de duplicar
        if (isset($byKey[$key])) {
            $pdo->prepare('UPDATE athletes SET user_id = :u WHERE id = :id AND user_id IS NULL')
                ->execute(['u' => $userId, 'id' => $byKey[$key]]);
            $byUser[$userId] = $byKey[$key];
            $report['reused']++;
            continue;
        }

        $insert->execute([
            'dojo_id'             => $u['dojo_id'],
            'user_id'             => $userId,
            'full_name'           => $u['name'],
            'birth_date'          => $u['birth_date'],
            'gender'              => $u['gender'],
            'style_id'            => $u['style_id'],
            'graduation_id'       => $u['graduation_id'],
            'weight_kg'           => $u['weight'],
            'height_cm'           => $u['height'] > 0 ? $u['height'] : null,
            'is_para_karate'      => (int) $u['is_para_karate'],
            'fgkirs_registration' => $u['fgkirs_registration'] !== null ? (string) $u['fgkirs_registration'] : null,
            'cbki_registration'   => $u['cbki_registration'],
            'status'              => 'active',
            'created_by'          => $userId,
        ]);

        $id = (int) $pdo->lastInsertId();
        $byUser[$userId] = $id;
        $byKey[$key]     = $id;
        $report['created_users']++;
    }

    // 2 e 3. Inscrições do evento: liga cada linha ao atleta, criando os avulsos que faltam
    $rows = $pdo->query(
        "SELECT id, championship_id, dojo_id, user_id, name, gender, birth_date, style_id, graduation_id,
                weight, height, is_guest, is_para_karate, registered_by
         FROM championship_athletes
         WHERE athlete_id IS NULL
         ORDER BY id ASC"
    )->fetchAll();

    $link = $pdo->prepare('UPDATE championship_athletes SET athlete_id = :a WHERE id = :id');

    foreach ($rows as $r) {
        $label = "inscrição #{$r['id']} (evento #{$r['championship_id']}) {$r['name']}";

        if ((int) $r['is_guest'] !== 1) {
            $athleteId = $r['user_id'] !== null ? ($byUser[(int) $r['user_id']] ?? null) : null;

            if ($athleteId === null) {
                $report['ignored'][] = "$label: aluno sem cadastro em athletes (perfil incompleto ou usuário removido)";
                continue;
            }

            $link->execute(['a' => $athleteId, 'id' => $r['id']]);
            $report['linked']++;
            continue;
        }

        if (empty($r['birth_date']) || !in_array($r['gender'], ['M', 'F'], true)) {
            $report['ignored'][] = "$label: avulso sem data de nascimento ou sexo";
            continue;
        }

        $key = $naturalKey((int) $r['dojo_id'], $r['name'], $r['birth_date']);

        if (isset($byKey[$key])) {
            $athleteId = $byKey[$key];
            $report['reused']++;
        } else {
            $insert->execute([
                'dojo_id'             => $r['dojo_id'],
                'user_id'             => null,
                'full_name'           => $r['name'],
                'birth_date'          => $r['birth_date'],
                'gender'              => $r['gender'],
                'style_id'            => $r['style_id'],
                'graduation_id'       => $r['graduation_id'],
                'weight_kg'           => $r['weight'],
                'height_cm'           => $r['height'] > 0 ? $r['height'] : null,
                'is_para_karate'      => (int) $r['is_para_karate'],
                'fgkirs_registration' => null,
                'cbki_registration'   => null,
                'status'              => 'pending',
                'created_by'          => $r['registered_by'],
            ]);

            $athleteId = (int) $pdo->lastInsertId();
            $byKey[$key] = $athleteId;
            $report['created_guests']++;
        }

        $link->execute(['a' => $athleteId, 'id' => $r['id']]);
        $report['linked']++;
    }

    $dryRun ? $pdo->rollBack() : $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Erro, nada foi gravado: ' . $e->getMessage() . "\n");
    exit(1);
}

echo ($dryRun ? "[DRY-RUN] nada foi gravado\n" : "Migração concluída\n");
echo "Atletas criados a partir de alunos:   {$report['created_users']}\n";
echo "Atletas criados a partir de avulsos:  {$report['created_guests']} (status pending)\n";
echo "Reaproveitados (já existiam):         {$report['reused']}\n";
echo "Inscrições ligadas a um atleta:       {$report['linked']}\n";
echo 'Ignorados:                            ' . count($report['ignored']) . "\n";

foreach ($report['ignored'] as $line) {
    echo "  - $line\n";
}
