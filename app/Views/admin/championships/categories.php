<?php
use Helpers\Csrf;

$id        = (int) $championship['id'];
$pageTitle = 'Categorias – ' . $championship['title'];
$e         = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''));

$statusStyle = [
    'open'      => 'bg-green-100 text-green-800',
    'closed'    => 'bg-slate-200 text-slate-600',
    'merged'    => 'bg-blue-100 text-blue-800',
    'cancelled' => 'bg-red-100 text-red-700',
];
$statusLabel = ['open' => 'Aberta', 'closed' => 'Fechada', 'merged' => 'Juntada', 'cancelled' => 'Cancelada'];

$byDiscipline = [];
foreach ($rows as $row) {
    $byDiscipline[$row['discipline_name'] ?: 'Outras'][] = $row;
}

require_once __DIR__ . '/../layout/header.php';
?>

<div class="mb-6">
    <a href="/fgkirs-admin/championships/edit/<?= $id ?>" class="text-sm text-slate-500 hover:text-red-700 transition">← Voltar para o evento</a>
</div>

<h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= $e($championship['title']) ?></h1>
<p class="text-sm text-slate-500 mt-1 mb-6">
    Categorias do evento, geradas do regulamento <strong><?= $e($ruleset->name) ?></strong>.
    Mudar o regulamento depois não altera as categorias já geradas.
</p>

<?php require __DIR__ . '/partials/flash.php'; ?>

<form method="GET" action="/fgkirs-admin/championships/<?= $id ?>/categories/preview"
    class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8">
    <h2 class="text-lg font-bold text-slate-900 mb-1">Gerar categorias</h2>
    <p class="text-xs text-slate-500 mb-4">
        Escolha o que o evento terá e veja a prévia antes de gravar. Gerar de novo só acrescenta o que falta:
        nunca duplica nem apaga categoria com inscritos.
    </p>

    <div class="grid md:grid-cols-3 gap-6">
        <fieldset>
            <legend class="text-sm font-bold text-slate-800 mb-2">Disciplinas</legend>
            <?php foreach ($ruleset->disciplines as $d): ?>
                <label class="flex items-center gap-2 text-sm text-slate-700 mb-1.5">
                    <input type="checkbox" name="disciplines[]" value="<?= (int) $d->id ?>" checked class="rounded border-slate-300 text-red-700">
                    <?= $e($d->name) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <fieldset>
            <legend class="text-sm font-bold text-slate-800 mb-2">Divisões etárias</legend>
            <?php foreach ($ruleset->ageDivisions as $a): ?>
                <label class="flex items-center gap-2 text-sm text-slate-700 mb-1.5">
                    <input type="checkbox" name="ages[]" value="<?= (int) $a->id ?>" checked class="rounded border-slate-300 text-red-700">
                    <?= $e($a->label()) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <fieldset>
            <legend class="text-sm font-bold text-slate-800 mb-2">Divisões de faixa</legend>
            <?php foreach ($ruleset->beltDivisions as $b): ?>
                <label class="flex items-center gap-2 text-sm text-slate-700 mb-1.5">
                    <input type="checkbox" name="belts[]" value="<?= (int) $b->id ?>" checked class="rounded border-slate-300 text-red-700">
                    <?= $e($b->name) ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
    </div>

    <button type="submit" class="mt-5 bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">Gerar categorias</button>
</form>

<h2 class="text-lg font-bold text-slate-900 mb-3">Categorias do evento <span class="text-slate-500 font-normal">(<?= count($rows) ?>)</span></h2>

<?php if (empty($rows)): ?>
    <div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-500">
        Nenhuma categoria gerada ainda. Use o formulário acima.
    </div>
<?php endif; ?>

<?php foreach ($byDiscipline as $discipline => $items): ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-5 overflow-hidden">
        <div class="bg-slate-900 text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold uppercase tracking-wide text-sm"><?= $e($discipline) ?></h3>
            <span class="text-xs text-slate-300"><?= count($items) ?> categorias</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($items as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-2.5">
                                <p class="text-slate-900"><?= $e($row['name']) ?></p>
                                <p class="text-xs text-slate-500 font-mono"><?= $e($row['code']) ?></p>
                            </td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-slate-700">
                                <?php if ($row['format'] === 'team'): ?>
                                    <?= (int) $row['team_count'] ?> equipe(s)
                                <?php else: ?>
                                    <?= (int) $row['enrolled'] ?> inscrito(s)
                                <?php endif; ?>
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-bold <?= $statusStyle[$row['status']] ?? '' ?>">
                                    <?= $statusLabel[$row['status']] ?? $e($row['status']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-2.5 text-right whitespace-nowrap">
                                <?php if (in_array($row['status'], ['open', 'closed'], true)): ?>
                                    <form method="POST" action="/fgkirs-admin/championships/<?= $id ?>/categories/<?= (int) $row['id'] ?>/status" class="inline">
                                        <?= Csrf::field() ?>
                                        <input type="hidden" name="action" value="<?= $row['status'] === 'open' ? 'close' : 'open' ?>">
                                        <button type="submit" class="text-slate-700 hover:text-slate-900 font-semibold px-2">
                                            <?= $row['status'] === 'open' ? 'Fechar' : 'Reabrir' ?>
                                        </button>
                                    </form>

                                    <details class="inline-block relative align-middle">
                                        <summary class="cursor-pointer text-blue-600 hover:text-blue-700 font-semibold px-2 inline">Juntar</summary>
                                        <form method="POST" action="/fgkirs-admin/championships/<?= $id ?>/categories/merge"
                                            class="absolute right-0 z-10 mt-1 w-80 bg-white border border-slate-200 rounded-lg shadow-lg p-4 text-left whitespace-normal"
                                            onsubmit="return confirm('Mover os inscritos desta categoria para o destino? Eles passam a constar como exceção.');">
                                            <?= Csrf::field() ?>
                                            <input type="hidden" name="from_id" value="<?= (int) $row['id'] ?>">
                                            <label class="block text-xs text-slate-500 mb-1">Juntar em:</label>
                                            <select name="to_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm bg-white mb-3">
                                                <option value="">Selecione...</option>
                                                <?php foreach ($items as $target): ?>
                                                    <?php if ($target['id'] !== $row['id'] && $target['status'] === 'open'): ?>
                                                        <option value="<?= (int) $target['id'] ?>"><?= $e($target['name']) ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="bg-red-700 hover:bg-red-800 text-white text-sm font-semibold py-1.5 px-4 rounded-lg">Juntar</button>
                                        </form>
                                    </details>
                                <?php elseif ($row['status'] === 'merged'): ?>
                                    <span class="text-xs text-slate-500">juntada em #<?= (int) $row['merged_into_id'] ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
