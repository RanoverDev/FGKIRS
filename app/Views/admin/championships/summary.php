<?php
use Helpers\Auth;
use Models\ChampionshipAthlete;

$pageTitle = $championship['title'];
require_once __DIR__ . '/../layout/header.php';
require __DIR__ . '/partials/header.php';

$championshipId = (int) $championship['id'];
$dojoParam      = (Auth::isAdmin() && !empty($dojoId)) ? '?dojo_id=' . (int) $dojoId : '';
?>

<?php if (!empty($dojoId)): ?>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div class="grid grid-cols-3 gap-3 flex-1">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-2xl font-bold text-slate-900"><?= count($athletes) ?></p>
                <p class="text-xs uppercase tracking-wider text-slate-500 mt-1">Atletas</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-2xl font-bold text-slate-900"><?= (int) $entryCount ?></p>
                <p class="text-xs uppercase tracking-wider text-slate-500 mt-1">Inscrições</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                <p class="text-2xl font-bold text-slate-900"><?= count($teams) ?></p>
                <p class="text-xs uppercase tracking-wider text-slate-500 mt-1">Equipes</p>
            </div>
        </div>

        <a href="/fgkirs-admin/championships/<?= $championshipId ?>/summary/print<?= $dojoParam ?>"
            target="_blank" rel="noopener"
            class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 px-6 rounded-lg transition text-center shrink-0">
            Versão para impressão
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <h2 class="font-bold text-slate-900">Atletas individuais</h2>
        </div>

        <?php if (empty($athletes)): ?>
            <p class="px-5 py-10 text-center text-sm text-slate-500">Nenhum atleta inscrito ainda.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-white">
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-3">Atleta</th>
                            <th class="px-5 py-3">Idade</th>
                            <th class="px-5 py-3">Faixa</th>
                            <th class="px-5 py-3">Categorias</th>
                            <?php if ($canWrite): ?>
                                <th class="px-5 py-3 text-right">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($athletes as $athlete): ?>
                            <tr class="align-top">
                                <td class="px-5 py-3">
                                    <p class="text-sm font-semibold text-slate-900">
                                        <?= htmlspecialchars($athlete['name']) ?>
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        <?= htmlspecialchars(ChampionshipAthlete::GENDERS[$athlete['gender']]) ?>
                                        <?php if ($athlete['weight'] !== null): ?>
                                            · <?= number_format((float) $athlete['weight'], 1, ',', '') ?> kg
                                        <?php endif; ?>
                                        <?php if (!empty($athlete['height'])): ?>
                                            · <?= (int) $athlete['height'] ?> cm
                                        <?php endif; ?>
                                    </p>
                                </td>
                                <td class="px-5 py-3 text-sm text-slate-700"><?= (int) $athlete['age'] ?></td>
                                <td class="px-5 py-3 text-sm text-slate-700">
                                    <?= htmlspecialchars($athlete['belt_name'] ?? '—') ?>
                                </td>
                                <td class="px-5 py-3">
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
                                <?php if ($canWrite): ?>
                                    <td class="px-5 py-3">
                                        <?php $actionsBack = 'summary'; require __DIR__ . '/partials/athlete_row_actions.php'; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
            <h2 class="font-bold text-slate-900">Equipes</h2>
        </div>

        <?php if (empty($teams)): ?>
            <p class="px-5 py-10 text-center text-sm text-slate-500">Nenhuma equipe inscrita ainda.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($teams as $index => $team): ?>
                    <li class="px-5 py-4">
                        <p class="text-sm font-semibold text-slate-900">
                            <?= htmlspecialchars($team['name'] ?: 'Equipe ' . chr(65 + $index)) ?>
                            <span class="font-normal text-slate-500">— <?= htmlspecialchars($team['category_name']) ?></span>
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?= htmlspecialchars(implode(', ', array_column($team['members'], 'name'))) ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

<?php endif; ?>

<?php if (!empty($dojoId) && $canWrite): ?>
    <?php $editBack = 'summary'; require __DIR__ . '/partials/athlete_edit_modal.php'; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
