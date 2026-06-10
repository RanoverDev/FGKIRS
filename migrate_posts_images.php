<?php
/**
 * Migração única: reorganiza imagens de posts do diretório plano para subdiretórios Y/m/d
 *
 * O que faz:
 *  1. Lê todos os registros em post_images cujo filename NÃO contém '/' (formato antigo plano)
 *  2. Determina a data de cada registro (created_at da imagem)
 *  3. Move o arquivo para uploads/posts/YYYY/MM/DD/
 *  4. Atualiza o banco: post_images.filename e posts.featured_image quando aplicável
 *
 * Uso:
 *  - Dry-run (apenas lista o que seria feito, sem alterar nada):
 *      https://seusite.com.br/migrate_posts_images.php
 *
 *  - Executar de verdade:
 *      https://seusite.com.br/migrate_posts_images.php?execute=1&token=FGKIRS_MIGRATE_2026
 *
 * APAGUE este arquivo do servidor após executar com sucesso.
 */

define('MIGRATE_TOKEN', 'FGKIRS_MIGRATE_2026');

// ── bootstrap mínimo ──────────────────────────────────────────────────────────
$configPath = __DIR__ . '/config/config.php';
if (!file_exists($configPath)) {
    die('config/config.php não encontrado.');
}
require_once $configPath;

$dryRun  = !(isset($_GET['execute']) && $_GET['execute'] === '1' && ($_GET['token'] ?? '') === MIGRATE_TOKEN);
$baseDir = __DIR__ . '/public/uploads/posts';

// ── conexão ───────────────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die('Falha na conexão com o banco: ' . htmlspecialchars($e->getMessage()));
}

// ── busca imagens no formato antigo (sem '/') ─────────────────────────────────
$rows = $pdo->query(
    "SELECT pi.id, pi.post_id, pi.filename, pi.is_featured, pi.created_at,
            p.featured_image AS post_featured_image
     FROM post_images pi
     JOIN posts p ON pi.post_id = p.id
     WHERE pi.filename NOT LIKE '%/%'
     ORDER BY pi.created_at ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// ── output header ─────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Migração de Imagens de Posts</title>
<style>
  body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 2rem; }
  h1   { color: #fff; }
  .dry { color: #f0ad4e; font-weight: bold; }
  .ok  { color: #5cb85c; }
  .err { color: #d9534f; }
  .skip{ color: #999; }
  table{ border-collapse: collapse; width: 100%; margin-top: 1rem; font-size: .85rem; }
  th,td{ border: 1px solid #444; padding: .3rem .6rem; text-align: left; }
  th   { background: #333; color: #fff; }
</style>
</head>
<body>
<h1>Migração: imagens de posts → subdiretórios de data</h1>

<?php if ($dryRun): ?>
<p class="dry">MODO DRY-RUN — nenhuma alteração será feita. Para executar de verdade, adicione <code>?execute=1&amp;token=<?= MIGRATE_TOKEN ?></code> na URL.</p>
<?php else: ?>
<p class="ok">MODO EXECUÇÃO — movendo arquivos e atualizando o banco.</p>
<?php endif; ?>

<p>Imagens no formato antigo (diretório plano) encontradas: <strong><?= count($rows) ?></strong></p>

<?php if (empty($rows)): ?>
<p class="ok">Nenhuma imagem para migrar. Tudo já está no formato correto.</p>
</body></html>
<?php exit; endif; ?>

<table>
<thead>
  <tr>
    <th>post_images.id</th>
    <th>post_id</th>
    <th>Arquivo atual</th>
    <th>Novo caminho</th>
    <th>Status</th>
  </tr>
</thead>
<tbody>
<?php

$moved   = 0;
$skipped = 0;
$errors  = 0;

foreach ($rows as $row) {
    $oldFilename = $row['filename'];                         // ex: 1717000000_abc.jpg
    $oldPath     = $baseDir . '/' . $oldFilename;

    // Determina subdiretório pela data de criação da imagem
    $date       = new DateTime($row['created_at']);
    $dateFolder = $date->format('Y/m/d');
    $newDir     = $baseDir . '/' . $dateFolder;
    $newFilename = $dateFolder . '/' . $oldFilename;        // ex: 2026/06/01/1717000000_abc.jpg
    $newPath     = $baseDir . '/' . $newFilename;

    // ── arquivo existe? ───────────────────────────────────────────────────────
    if (!file_exists($oldPath)) {
        echo "<tr><td>{$row['id']}</td><td>{$row['post_id']}</td>
              <td>" . htmlspecialchars($oldFilename) . "</td>
              <td>" . htmlspecialchars($newFilename) . "</td>
              <td class='skip'>Arquivo não encontrado no servidor (ignorado)</td></tr>";
        $skipped++;
        continue;
    }

    if ($dryRun) {
        echo "<tr><td>{$row['id']}</td><td>{$row['post_id']}</td>
              <td>" . htmlspecialchars($oldFilename) . "</td>
              <td>" . htmlspecialchars($newFilename) . "</td>
              <td class='dry'>Seria movido</td></tr>";
        $moved++;
        continue;
    }

    // ── executa a migração ────────────────────────────────────────────────────
    if (!is_dir($newDir)) {
        mkdir($newDir, 0755, true);
    }

    if (!rename($oldPath, $newPath)) {
        echo "<tr><td>{$row['id']}</td><td>{$row['post_id']}</td>
              <td>" . htmlspecialchars($oldFilename) . "</td>
              <td>" . htmlspecialchars($newFilename) . "</td>
              <td class='err'>ERRO ao mover arquivo</td></tr>";
        $errors++;
        continue;
    }

    // Atualiza post_images.filename
    $pdo->prepare("UPDATE post_images SET filename = ? WHERE id = ?")
        ->execute([$newFilename, $row['id']]);

    // Atualiza posts.featured_image se este era o destaque
    if ($row['is_featured'] && $row['post_featured_image'] === $oldFilename) {
        $pdo->prepare("UPDATE posts SET featured_image = ? WHERE id = ?")
            ->execute([$newFilename, $row['post_id']]);
    }

    echo "<tr><td>{$row['id']}</td><td>{$row['post_id']}</td>
          <td>" . htmlspecialchars($oldFilename) . "</td>
          <td>" . htmlspecialchars($newFilename) . "</td>
          <td class='ok'>Movido e banco atualizado</td></tr>";
    $moved++;
}
?>
</tbody>
</table>

<p style="margin-top:1.5rem">
    <?php if ($dryRun): ?>
        <span class="dry">Seriam movidos: <?= $moved ?> | Ignorados (arquivo ausente): <?= $skipped ?></span>
    <?php else: ?>
        <span class="ok">Movidos: <?= $moved ?></span> &nbsp;
        <span class="skip">Ignorados: <?= $skipped ?></span> &nbsp;
        <span class="err">Erros: <?= $errors ?></span>
    <?php endif; ?>
</p>

<?php if (!$dryRun && $errors === 0 && $moved > 0): ?>
<p class="ok" style="font-size:1.1rem"><strong>Migração concluída com sucesso! Apague este arquivo do servidor agora.</strong></p>
<?php endif; ?>

</body>
</html>
