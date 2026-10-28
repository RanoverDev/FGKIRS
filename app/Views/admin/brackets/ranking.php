<?php
/**
 * @var bool  $ready
 * @var array $filters
 * @var int[] $years
 * @var array $athletes
 * @var array $dojos
 */
require_once __DIR__ . '/../layout/header.php';

$input = 'px-4 py-2 border border-slate-300 rounded-lg bg-white focus:ring-2 focus:ring-red-700 focus:border-transparent text-sm';
?>

<div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Ranking</h1>
    <p class="text-sm text-slate-500 mt-1">Pontos dos resultados de todos os campeonatos: 1º = 10, 2º = 7, 3º = 5, 5º = 3 e participação = 1.</p>
</div>

<?php if (!$ready): ?>
    <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-5 py-4 text-sm">
        <p class="font-semibold mb-1">Falta preparar o banco para o ranking.</p>
        <p>Rode o arquivo <code class="bg-amber-100 px-1 rounded">database/manual/08_chaves.sql</code> no phpMyAdmin e recarregue esta página.</p>
    </div>
<?php else: ?>

    <form method="GET" class="flex flex-wrap gap-3 mb-6">
        <select name="year" class="<?= $input ?>" onchange="this.form.submit()">
            <option value="">Todos os anos</option>
            <?php foreach ($years as $year): ?>
                <option value="<?= $year ?>" <?= (int) $filters['year'] === $year ? 'selected' : '' ?>><?= $year ?></option>
            <?php endforeach; ?>
        </select>
        <select name="modality" class="<?= $input ?>" onchange="this.form.submit()">
            <option value="">Kata e Kumite</option>
            <option value="kata" <?= $filters['modality'] === 'kata' ? 'selected' : '' ?>>Kata</option>
            <option value="kumite" <?= $filters['modality'] === 'kumite' ? 'selected' : '' ?>>Kumite</option>
        </select>
    </form>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <h2 class="px-5 py-4 text-base font-bold text-slate-900 border-b border-slate-100">Atletas</h2>
            <?php if (empty($athletes)): ?>
                <p class="p-8 text-center text-sm text-slate-500">Sem resultados para este filtro.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-4 py-2 text-left w-10">#</th>
                                <th class="px-2 py-2 text-left">Atleta</th>
                                <th class="px-2 py-2 text-center" title="Ouro">1º</th>
                                <th class="px-2 py-2 text-center" title="Prata">2º</th>
                                <th class="px-2 py-2 text-center" title="Bronze">3º</th>
                                <th class="px-4 py-2 text-right">Pontos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($athletes as $i => $a): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2 text-slate-400 font-semibold"><?= $i + 1 ?></td>
                                    <td class="px-2 py-2">
                                        <p class="font-medium text-slate-900"><?= htmlspecialchars($a['name']) ?></p>
                                        <p class="text-xs text-slate-400"><?= htmlspecialchars($a['dojo_name']) ?></p>
                                    </td>
                                    <td class="px-2 py-2 text-center"><?= (int) $a['golds'] ?></td>
                                    <td class="px-2 py-2 text-center"><?= (int) $a['silvers'] ?></td>
                                    <td class="px-2 py-2 text-center"><?= (int) $a['bronzes'] ?></td>
                                    <td class="px-4 py-2 text-right font-bold text-slate-900"><?= (int) $a['points'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden self-start">
            <h2 class="px-5 py-4 text-base font-bold text-slate-900 border-b border-slate-100">Dojos</h2>
            <?php if (empty($dojos)): ?>
                <p class="p-8 text-center text-sm text-slate-500">Sem resultados para este filtro.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-4 py-2 text-left w-10">#</th>
                                <th class="px-2 py-2 text-left">Dojo</th>
                                <th class="px-2 py-2 text-center">1º</th>
                                <th class="px-2 py-2 text-center">2º</th>
                                <th class="px-2 py-2 text-center">3º</th>
                                <th class="px-4 py-2 text-right">Pontos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($dojos as $i => $d): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2 text-slate-400 font-semibold"><?= $i + 1 ?></td>
                                    <td class="px-2 py-2">
                                        <p class="font-medium text-slate-900"><?= htmlspecialchars($d['name']) ?></p>
                                        <?php if (!empty($d['city'])): ?><p class="text-xs text-slate-400"><?= htmlspecialchars($d['city']) ?></p><?php endif; ?>
                                    </td>
                                    <td class="px-2 py-2 text-center"><?= (int) $d['golds'] ?></td>
                                    <td class="px-2 py-2 text-center"><?= (int) $d['silvers'] ?></td>
                                    <td class="px-2 py-2 text-center"><?= (int) $d['bronzes'] ?></td>
                                    <td class="px-4 py-2 text-right font-bold text-slate-900"><?= (int) $d['points'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
