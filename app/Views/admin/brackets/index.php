<?php
use Helpers\Csrf;

/**
 * @var array $championship
 * @var bool  $ready
 * @var array $overview
 * @var bool  $canEdit
 */
require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/../championships/partials/header.php';

$championshipId = (int) $championship['id'];
$base           = "/fgkirs-admin/championships/$championshipId/brackets";

$badge = [
    'draft'    => ['Sorteada', 'bg-blue-100 text-blue-800'],
    'running'  => ['Em andamento', 'bg-amber-100 text-amber-800'],
    'finished' => ['Encerrada', 'bg-green-100 text-green-800'],
];

$withBracket = count(array_filter($overview, static fn ($r) => $r['bracket'] !== null));
$pending     = count(array_filter($overview, static fn ($r) => $r['bracket'] === null && $r['participants'] > 0));
$finished    = count(array_filter($overview, static fn ($r) => ($r['bracket']['status'] ?? '') === 'finished'));
?>

<?php if (!$ready): ?>
    <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-5 py-4 text-sm">
        <p class="font-semibold mb-1">Falta preparar o banco para as chaves.</p>
        <p>Rode o arquivo <code class="bg-amber-100 px-1 rounded">database/manual/08_chaves.sql</code> no phpMyAdmin (aba SQL) e recarregue esta página.</p>
    </div>
<?php else: ?>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div class="flex flex-wrap gap-5 text-sm text-slate-600">
            <span><strong class="text-slate-900"><?= count($overview) ?></strong> categorias</span>
            <span><strong class="text-slate-900"><?= $withBracket ?></strong> com chave</span>
            <span><strong class="text-slate-900"><?= $finished ?></strong> encerradas</span>
            <span><strong class="text-slate-900"><?= $pending ?></strong> prontas para sortear</span>
        </div>
        <?php if ($canEdit && $pending > 0): ?>
            <button type="button" id="generate-all"
                class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-5 rounded-lg transition text-sm">
                Sortear as <?= $pending ?> chave(s) que faltam
            </button>
        <?php endif; ?>
    </div>

    <p class="text-xs text-slate-500 mb-4">
        Entram na chave os atletas com inscrição <strong>enviada</strong> ou <strong>confirmada</strong>. Rascunhos e cancelados ficam de fora.
    </p>

    <?php if (empty($overview)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center text-slate-500">
            Este evento ainda não tem categorias. Gere-as a partir do regulamento na tela do evento.
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-900 text-white">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Categoria</th>
                            <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wider">Participantes</th>
                            <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wider">Chave</th>
                            <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php $lastDiscipline = null; ?>
                        <?php foreach ($overview as $row): ?>
                            <?php if ($row['discipline_name'] !== $lastDiscipline): $lastDiscipline = $row['discipline_name']; ?>
                                <tr class="bg-slate-50">
                                    <td colspan="4" class="px-5 py-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                                        <?= htmlspecialchars($row['discipline_name'] ?: 'Categorias') ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-slate-900"><?= htmlspecialchars($row['short_name'] ?: $row['name']) ?></p>
                                    <?php if ($row['status'] === 'closed'): ?>
                                        <p class="text-xs text-slate-400">Categoria fechada para novas inscrições</p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-center text-slate-700"><?= (int) $row['participants'] ?></td>
                                <td class="px-5 py-3 text-center">
                                    <?php if ($row['bracket']): [$label, $cls] = $badge[$row['bracket']['status']]; ?>
                                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-bold <?= $cls ?>"><?= $label ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400"><?= $row['participants'] > 0 ? 'Não sorteada' : '—' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-3 text-right whitespace-nowrap">
                                    <?php if ($row['bracket'] || ($canEdit && $row['participants'] > 0)): ?>
                                        <a href="<?= $base ?>/<?= (int) $row['id'] ?>"
                                            class="text-blue-600 hover:text-blue-700 font-semibold">
                                            <?= $row['bracket'] ? 'Abrir chave' : 'Sortear' ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($canEdit && $pending > 0): ?>
        <script>
            document.getElementById('generate-all').addEventListener('click', async e => {
                if (!confirm('Sortear agora todas as categorias que ainda não têm chave?')) return;
                e.target.disabled = true;

                const body = new FormData();
                body.append('csrf_token', <?= json_encode(Csrf::token()) ?>);

                try {
                    const res  = await fetch('<?= $base ?>/generate-all', { method: 'POST', body, headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    if (!data.ok) throw new Error(data.message);
                    window.location.reload();
                } catch (ex) {
                    alert(ex.message || 'Não foi possível sortear.');
                    e.target.disabled = false;
                }
            });
        </script>
    <?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
