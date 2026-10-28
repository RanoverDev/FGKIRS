<?php
/**
 * @var array $championship
 * @var bool  $ready
 * @var array $results
 */
require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/../championships/partials/header.php';

$championshipId = (int) $championship['id'];

$byCategory = [];
$byDojo     = [];

foreach ($results as $r) {
    $byCategory[(int) $r['event_category_id']]['title'] = $r['short_name'] ?: $r['category_name'];
    $byCategory[(int) $r['event_category_id']]['discipline'] = $r['discipline_name'];
    $byCategory[(int) $r['event_category_id']]['rows'][] = $r;

    $dojo = &$byDojo[(int) $r['dojo_id']];
    $dojo ??= ['name' => $r['dojo_name'], 'gold' => 0, 'silver' => 0, 'bronze' => 0, 'points' => 0];
    $dojo['gold']   += (int) $r['place'] === 1;
    $dojo['silver'] += (int) $r['place'] === 2;
    $dojo['bronze'] += (int) $r['place'] === 3;
    $dojo['points'] += (int) $r['points'];
    unset($dojo);
}

uasort($byDojo, static fn ($a, $b) => [$b['points'], $b['gold'], $b['silver'], $b['bronze'], $a['name']] <=> [$a['points'], $a['gold'], $a['silver'], $a['bronze'], $b['name']]);

$medal = [1 => 'bg-yellow-100 text-yellow-800', 2 => 'bg-slate-200 text-slate-700', 3 => 'bg-orange-100 text-orange-800'];
?>

<style>
    @media print { #sidebar, .no-print, nav { display: none !important; } main { padding: 0 !important; } }
</style>

<?php if (!$ready): ?>
    <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-5 py-4 text-sm">
        <p class="font-semibold mb-1">Falta preparar o banco para os resultados.</p>
        <p>Rode o arquivo <code class="bg-amber-100 px-1 rounded">database/manual/08_chaves.sql</code> no phpMyAdmin e recarregue esta página.</p>
    </div>
<?php elseif (empty($results)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center text-slate-500">
        Ainda não há resultados neste evento. Eles aparecem aqui conforme as lutas são lançadas nas
        <a href="/fgkirs-admin/championships/<?= $championshipId ?>/brackets" class="text-red-700 font-semibold underline">chaves</a>.
    </div>
<?php else: ?>

    <div class="no-print flex items-center justify-between mb-4">
        <p class="text-sm text-slate-500"><?= count($byCategory) ?> categoria(s) com resultado</p>
        <div class="flex gap-2">
            <a href="/fgkirs-admin/ranking" class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2 px-4 rounded-lg text-sm transition">Ranking geral</a>
            <button type="button" onclick="window.print()" class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2 px-4 rounded-lg text-sm transition">Imprimir</button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
        <h2 class="text-base font-bold text-slate-900 mb-3">Quadro de medalhas por dojo</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-slate-400 border-b border-slate-100">
                        <th class="text-left py-2 pr-4">Dojo</th>
                        <th class="text-center py-2 px-3">Ouro</th>
                        <th class="text-center py-2 px-3">Prata</th>
                        <th class="text-center py-2 px-3">Bronze</th>
                        <th class="text-right py-2 pl-3">Pontos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach ($byDojo as $dojo): ?>
                        <tr>
                            <td class="py-2 pr-4 font-medium text-slate-800"><?= htmlspecialchars($dojo['name']) ?></td>
                            <td class="text-center px-3"><?= $dojo['gold'] ?></td>
                            <td class="text-center px-3"><?= $dojo['silver'] ?></td>
                            <td class="text-center px-3"><?= $dojo['bronze'] ?></td>
                            <td class="text-right pl-3 font-bold text-slate-900"><?= $dojo['points'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <?php foreach ($byCategory as $category): ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 break-inside-avoid">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400"><?= htmlspecialchars($category['discipline']) ?></p>
                <h3 class="font-bold text-slate-900 mb-3"><?= htmlspecialchars($category['title']) ?></h3>
                <ul class="space-y-1.5">
                    <?php foreach ($category['rows'] as $r): ?>
                        <?php if ((int) $r['place'] > 3) { continue; } ?>
                        <li class="flex items-center gap-3">
                            <span class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center font-black text-xs <?= $medal[(int) $r['place']] ?>"><?= (int) $r['place'] ?>º</span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900 truncate"><?= htmlspecialchars($r['participant_name']) ?></p>
                                <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($r['dojo_name']) ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
