<?php
use Models\ChampionshipAthlete;
use Models\ChampionshipReferee;

$pageTitle = 'Inscritos – ' . $championship['title'];
require_once __DIR__ . '/../layout/header.php';

$championshipId = (int) $championship['id'];

$athletesByDojo = [];
foreach ($athletes as $athlete) {
    $athletesByDojo[(int) $athlete['dojo_id']][] = $athlete;
}

$teamsByDojo = [];
foreach ($teams as $team) {
    $teamsByDojo[(int) $team['dojo_id']][] = $team;
}

$refereesByDojo = [];
foreach ($referees as $referee) {
    $refereesByDojo[(int) $referee['dojo_id']][] = $referee;
}
?>

<div class="mb-6">
    <a href="/fgkirs-admin/championships" class="text-sm text-slate-500 hover:text-red-700 transition">
        ← Voltar para eventos
    </a>
</div>

<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900"><?= htmlspecialchars($championship['title']) ?></h1>
        <p class="text-sm text-slate-500 mt-1">
            Inscritos de todos os dojos · <?= date('d/m/Y', strtotime($championship['event_date'])) ?>
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="/fgkirs-admin/championships/<?= $championshipId ?>/registrations/print" target="_blank" rel="noopener"
            class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 px-6 rounded-lg transition">
            Versão para impressão
        </a>
        <a href="/fgkirs-admin/championships/edit/<?= $championshipId ?>"
            class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2.5 px-6 rounded-lg transition">
            Editar evento
        </a>
    </div>
</div>

<?php require __DIR__ . '/partials/flash.php'; ?>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-8">
    <?php
    $cards = [
        'Dojos'       => $totals['dojos'],
        'Atletas'     => $totals['athletes'],
        'Inscrições'  => $totals['entries'],
        'Equipes'     => $totals['teams'],
        'Árbitros'    => $totals['referees'],
    ];
    ?>
    <?php foreach ($cards as $label => $value): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-2xl font-bold text-slate-900"><?= (int) $value ?></p>
            <p class="text-xs uppercase tracking-wider text-slate-500 mt-1"><?= $label ?></p>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($dojoTotals)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
        <p class="text-slate-500">Nenhum dojo inscreveu atletas neste evento ainda.</p>
    </div>
<?php else: ?>
    <div class="space-y-4">
        <?php foreach ($dojoTotals as $dojoRow): ?>
            <?php $dojoRowId = (int) $dojoRow['id']; ?>
            <details class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" open>
                <summary class="px-5 py-4 cursor-pointer flex flex-wrap items-center justify-between gap-3 hover:bg-slate-50">
                    <div class="min-w-0">
                        <h2 class="font-bold text-slate-900"><?= htmlspecialchars($dojoRow['name']) ?></h2>
                        <?php if (!empty($dojoRow['city'])): ?>
                            <p class="text-xs text-slate-500"><?= htmlspecialchars($dojoRow['city']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-4 text-xs text-slate-600 shrink-0">
                        <span><strong class="text-slate-900"><?= (int) $dojoRow['athlete_count'] ?></strong> atletas</span>
                        <span><strong class="text-slate-900"><?= (int) $dojoRow['entry_count'] ?></strong> inscrições</span>
                        <span><strong class="text-slate-900"><?= (int) $dojoRow['team_count'] ?></strong> equipes</span>
                    </div>
                </summary>

                <div class="border-t border-slate-200 px-5 py-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500">
                                    <th class="py-2 pr-4">Atleta</th>
                                    <th class="py-2 pr-4">Idade</th>
                                    <th class="py-2 pr-4">Faixa</th>
                                    <th class="py-2 pr-4">Peso</th>
                                    <th class="py-2">Categorias</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($athletesByDojo[$dojoRowId] ?? [] as $athlete): ?>
                                    <tr class="align-top">
                                        <td class="py-2 pr-4">
                                            <span class="text-sm font-medium text-slate-900">
                                                <?= htmlspecialchars($athlete['name']) ?>
                                            </span>
                                            <?php if ($athlete['is_guest']): ?>
                                                <span class="ml-1 text-[10px] font-bold uppercase text-amber-700">avulso</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-2 pr-4 text-sm text-slate-700">
                                            <?= ChampionshipAthlete::ageOn($athlete['birth_date'], $championship['event_date']) ?>
                                        </td>
                                        <td class="py-2 pr-4 text-sm text-slate-700">
                                            <?= htmlspecialchars($athlete['belt_name'] ?? '—') ?>
                                        </td>
                                        <td class="py-2 pr-4 text-sm text-slate-700">
                                            <?= $athlete['weight'] !== null
                                                ? number_format((float) $athlete['weight'], 1, ',', '') . ' kg'
                                                : '—' ?>
                                        </td>
                                        <td class="py-2">
                                            <?php if (empty($athlete['entries'])): ?>
                                                <span class="text-xs text-amber-700">Sem categoria</span>
                                            <?php else: ?>
                                                <ul class="text-xs text-slate-700 space-y-0.5">
                                                    <?php foreach ($athlete['entries'] as $entry): ?>
                                                        <li><?= htmlspecialchars($entry['category_name']) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($teamsByDojo[$dojoRowId])): ?>
                        <h3 class="text-sm font-bold text-slate-900 mt-6 mb-2">Equipes</h3>
                        <ul class="space-y-1.5">
                            <?php foreach ($teamsByDojo[$dojoRowId] as $index => $team): ?>
                                <li class="text-sm text-slate-700">
                                    <span class="font-semibold">
                                        <?= htmlspecialchars($team['name'] ?: 'Equipe ' . chr(65 + $index)) ?>
                                    </span>
                                    — <?= htmlspecialchars($team['category_name']) ?>
                                    <span class="text-xs text-slate-500 block">
                                        <?= htmlspecialchars(implode(', ', $team['member_names'])) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($refereesByDojo[$dojoRowId])): ?>
                        <h3 class="text-sm font-bold text-slate-900 mt-6 mb-2">Árbitros</h3>
                        <ul class="space-y-1">
                            <?php foreach ($refereesByDojo[$dojoRowId] as $referee): ?>
                                <li class="text-sm text-slate-700">
                                    <?= htmlspecialchars($referee['name']) ?>
                                    <span class="text-xs text-slate-500">
                                        — <?= htmlspecialchars(ChampionshipReferee::ROLES[$referee['role']] ?? '') ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <a href="/fgkirs-admin/championships/<?= $championshipId ?>/athletes?dojo_id=<?= $dojoRowId ?>"
                        class="inline-block mt-5 text-sm font-semibold text-red-700 hover:text-red-800 transition">
                        Abrir inscrições deste dojo →
                    </a>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
