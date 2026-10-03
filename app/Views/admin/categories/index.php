<?php
use Helpers\Csrf;
use Models\CompetitionCategory;

$pageTitle = 'Categorias de Disputa';
require_once __DIR__ . '/../layout/header.php';

$activeEntryType = $filters['entry_type'] ?? null;
$activeModality  = $filters['modality'] ?? null;

$tabs = [
    ''           => 'Todas',
    'individual' => 'Individual',
    'team'       => 'Equipe',
];
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900">Categorias de Disputa</h1>
        <p class="text-sm text-slate-500 mt-1">
            Catálogo usado por todos os eventos. O sistema cruza idade, sexo e graduação para sugerir
            as categorias de cada atleta.
        </p>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
        <form method="POST" action="/fgkirs-admin/categories/restore-defaults"
            onsubmit="return confirm('Adicionar ao catálogo as categorias padrão que ainda não existem? As categorias atuais não serão alteradas.');">
            <?= Csrf::field() ?>
            <button type="submit"
                class="border border-slate-300 hover:bg-slate-50 text-slate-700 font-semibold py-2 px-4 rounded-lg transition">
                Restaurar catálogo padrão
            </button>
        </form>
        <a href="/fgkirs-admin/categories/create"
            class="bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-6 rounded-lg transition">
            + Nova Categoria
        </a>
    </div>
</div>

<?php require __DIR__ . '/../championships/partials/flash.php'; ?>

<div class="flex flex-wrap gap-2 mb-5">
    <?php foreach ($tabs as $value => $label): ?>
        <a href="/fgkirs-admin/categories<?= $value ? '?entry_type=' . $value : '' ?>"
            class="px-4 py-2 rounded-lg text-sm font-semibold transition
                   <?= (string) $activeEntryType === $value
                       ? 'bg-slate-900 text-white'
                       : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>

    <?php foreach (CompetitionCategory::MODALITIES as $value => $label): ?>
        <a href="/fgkirs-admin/categories?modality=<?= $value ?>"
            class="px-4 py-2 rounded-lg text-sm font-semibold transition
                   <?= $activeModality === $value
                       ? 'bg-slate-900 text-white'
                       : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-900 text-white">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Categoria</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Tipo</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Idade</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wider">Peso</th>
                    <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wider">Ativa</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                            Nenhuma categoria encontrada.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="text-sm font-medium text-slate-900">
                                    <?= htmlspecialchars($category['name']) ?>
                                </p>
                                <p class="text-xs text-slate-500">
                                    <?= CompetitionCategory::GENDERS[$category['gender']] ?>
                                    · <?= CompetitionCategory::BELT_GROUPS[$category['belt_group']] ?>
                                    <?php if ($category['team_size'] !== null): ?>
                                        · <?= (int) $category['team_size'] ?> integrantes
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-700">
                                <?= CompetitionCategory::MODALITIES[$category['modality']] ?>
                                <span class="text-xs text-slate-500 block">
                                    <?= CompetitionCategory::ENTRY_TYPES[$category['entry_type']] ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-700 whitespace-nowrap">
                                <?php if ($category['age_min'] !== null && $category['age_max'] !== null): ?>
                                    <?= (int) $category['age_min'] ?> a <?= (int) $category['age_max'] ?>
                                <?php elseif ($category['age_min'] !== null): ?>
                                    <?= (int) $category['age_min'] ?>+
                                <?php elseif ($category['age_max'] !== null): ?>
                                    até <?= (int) $category['age_max'] ?>
                                <?php else: ?>
                                    livre
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-sm text-slate-700 whitespace-nowrap">
                                <?php if ($category['weight_max'] !== null): ?>
                                    até <?= number_format((float) $category['weight_max'], 0, ',', '') ?> kg
                                <?php elseif ($category['weight_min'] !== null): ?>
                                    acima de <?= number_format((float) $category['weight_min'], 0, ',', '') ?> kg
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <?php if ($category['is_active']): ?>
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full bg-green-100 text-green-800 text-[11px] font-bold">
                                        Sim
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full bg-slate-200 text-slate-600 text-[11px] font-bold">
                                        Não
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="/fgkirs-admin/categories/edit/<?= (int) $category['id'] ?>"
                                    class="text-blue-600 hover:text-blue-700 text-sm font-semibold px-2">Editar</a>
                                <a href="/fgkirs-admin/categories/delete/<?= (int) $category['id'] ?>?token=<?= urlencode(Csrf::token()) ?>"
                                    data-confirm-delete
                                    class="text-red-600 hover:text-red-700 text-sm font-semibold px-2">Excluir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
